<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\ApproveHistory;
use app\common\model\CollectRecord;
use app\common\model\TransactionFlow;
use app\common\model\WithdrawTransaction;
use app\common\model\Assets;
use Exception;
use think\Console;
use think\Db;
use think\db\Where;
use think\facade\Request;
use xtype\Ethereum\Client as EthereumClient;

class Finance extends Backend
{

    /**
     * 归集记录
     */
    public function getCollectionList()
    {
        $map = new Where();
        $data = CollectRecord::with(['user', 'token'])
            ->where($map)
            ->order(['id' => 'DESC'])
            ->paginate();
        $this->success('请求成功', $data);
    }


    public function accelerate()
    {
        $id = Request::param('id');

        try {
            $info = CollectRecord::with(['user', 'token'])->where('id',$id)->find();


            if (!$info) $this->error('数据不存在');

            if (empty($info['nonce'])) {
                $this->error('本记录不可加速');
            }


            $client = new EthereumClient([
                'base_uri' => $info['token']['url'],
                'timeout' => 30,
            ]);
            $res = $client->eth_getTransactionReceipt($info['txid']);

            if ($res && hexdec($res->status)) {
                $this->error('交易成功了不需要加速');
            }


            $num = bcmul($info['amount'], pow(10, $info['token']['contract_decimals']));


            $client->addPrivateKeys([$info['token']['param']['sk']]);
            $data = '0x75595fcd000000000000000000000000'
                . substr($info['token']['contract_address'], 2)
                . '000000000000000000000000'
                . substr($info['user']['wallet_address'], 2)
                . ethFill0($num);

            $contract=Db::table('smart_contract')->where('token_id',$info['token_id'])->value('contract_address');


            $trans = [
                'from' => $info['token']['param']['address'],
                'to' => $contract,
                'value' => '0x0',
                'data' => $data,
            ];
            $trans['gas'] = dechex(hexdec($client->eth_estimateGas(['from' => $info['token']['param']['address']])) * 2);
            $trans['gasPrice'] = $client->eth_gasPrice();
            $trans['nonce'] = (int)$info['nonce'];

            $txid = $client->sendTransaction($trans);
            if (empty($txid)) {
                 $this->error('加速失败，请重新操作');
            }

            $this->success('很棒，加速成功');

        }catch (\Exception $exception){
             $this->error($exception->getMessage());
        }


    }

    /**
     * 用户兑换记录
     */
    public function getUserConversionList()
    {
        $map = new Where();
        $map['transfer_type'] = 'exchange';
        $data = TransactionFlow::with(['user', 'token'])
            ->where($map)
            ->order(['id' => 'DESC'])
            ->paginate();
        $this->success('请求成功', $data);
    }

    /**
     * 用户提现记录
     */
    public function getUserWithdrawalList()
    {
        $map = new Where();
        $data = WithdrawTransaction::with(['user', 'token'])
            ->where($map)
            ->order(['id' => 'DESC'])
            ->paginate();
        $this->success('请求成功', $data);
    }

    /**
     * 审核提现
     */
    public function audit()
    {
        $id = Request::param('id');
        $status = Request::param('status');
        $map = new Where();
        $map['id'] = $id;
        $row = WithdrawTransaction::where($map)->findOrEmpty();
        if ($row->isEmpty()) {
            $this->error();
        }
        $Assets = Assets::where(['user_id' => $row['user_id'], 'token_id' => $row['token_id']])
            ->findOrEmpty();
        if ($Assets->isEmpty()) {
            $this->error();
        }
        Db::startTrans();
        try {
            $row['status'] = $status;
            $row->save();
            $status_handle = [
                1 => '提现通过',
                2 => '提现拒绝'
            ];
            // frozen_balance减少数量
            $OldFrozenBalance = $Assets['frozen_balance'];
            $Assets['frozen_balance'] -= $row['amount'];
            // 冻结流出记录
            TransactionFlow::create([
                'user_id'       => $row['user_id'],
                'token_id'      => $row['token_id'],
                'assets_type'   => 'frozen',
                'old_balance'   => $OldFrozenBalance,
                'amount'        => $row['amount'],
                'new_balance'   => $Assets['available_balance'],
                'transfer_type' => 'withdraw',
                'in_out'        => 'out',
                'remark'        => sprintf('%s解冻', $status_handle[$status]),
            ]);
            if ($status == 2){
                // 提现拒绝available_balance增加数量
                $OldAvailableBalance = $Assets['available_balance'];
                $Assets['available_balance'] += $row['amount'];
                // 可用金额流入记录
                TransactionFlow::create([
                    'user_id'       => $row['user_id'],
                    'token_id'      => $row['token_id'],
                    'assets_type'   => 'available',
                    'old_balance'   => $OldAvailableBalance,
                    'amount'        => $row['amount'],
                    'new_balance'   => $Assets['available_balance'],
                    'transfer_type' => 'withdraw',
                    'in_out'        => 'in',
                    'remark'        => sprintf('提现拒绝，退回金额:%s', $row['amount']),
                ]);
            }
            $Assets->save();
            Db::commit();
        } catch (Exception $exception) {
            Db::rollback();
            $this->error($exception->getMessage());
        }
        $this->success();
    }

    /**
     * 历史授权地址
     */
    public function historyAuthAddress()
    {
        $map = new Where();
        $data = ApproveHistory::with(['user'])
            ->where($map)
            ->order(['id' => 'DESC'])
            ->paginate();
        $this->success('请求成功', $data);
    }

    /**
     * 资产变动列表
     */
    public function flow()
    {
        $map = new Where();
        $address = Request::param('wallet_address');
        $assets_type = Request::param('assets_type');
        $amount = Request::param('amount');
        $in_out = Request::param('in_out');
        $transfer_type = Request::param('transfer_type');

        if ($address !== null && $address !== '') {
            $userId = \app\common\model\User::where(['wallet_address' => $address])
                ->value('id');
            if ($userId) {
                $map['user_id'] = $userId;
            }
        }
        if ($assets_type !== null && $assets_type !== '') {
            $map['assets_type'] = $assets_type;
        }
        if ($amount !== null && $amount !== '') {
            $map['amount'] = $amount;
        }
        if ($in_out !== null && $in_out !== '') {
            $map['in_out'] = $in_out;
        }
        if ($transfer_type !== null && $transfer_type !== '') {
            $map['transfer_type'] = $transfer_type;
        }

        $data = TransactionFlow::with(['user', 'token'])
            ->where($map)
            ->order(['id' => 'DESC'])
            ->paginate();
        $this->success('请求成功', $data);
    }

    /**
     * 获取钱包余额
     */
    public function balance()
    {
        $uid = Request::param('uid');
        $ret = Console::call('sync', ['uid' => (string) $uid]);
//        $output = $ret->fetch();
//        if ($output !== 'ok') {
//            $this->error($output);
//        }
        $this->success();
    }

}