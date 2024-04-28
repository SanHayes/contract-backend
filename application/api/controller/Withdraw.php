<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Assets;
use app\common\model\Token;
use app\common\model\TransactionFlow;
use app\common\model\WithdrawTransaction;
use Exception;
use think\Db;
use think\db\Where;
use think\facade\Config;
use think\facade\Request;

class Withdraw extends Api
{

    /**
     * 获取用户Balance数据
     */
    public function getUserBalance()
    {
        $uid = $this->auth->id;
        $user = $this->auth->getUser();
        $chain = $user['token']['chain'];
        $network = Config::get('blockchain.network');
        $ethTokenId = Token::where(['symbol' => 'ETH', 'network' => $network, 'chain' => $chain])
            ->value('id');
        $usdtTokenId = Token::where(['symbol' => 'USDT', 'network' => $network, 'chain' => $chain])
            ->value('id');
        $map = new Where();
        $map['user_id'] = $uid;
        $map['token_id'] = $ethTokenId;
        $ethAssets = Assets::where($map)->findOrEmpty();
        $map2 = new Where();
        $map2['user_id'] = $uid;
        $map2['token_id'] = $usdtTokenId;
        $usdtAssets = Assets::where($map2)->findOrEmpty();


        $precision = $ethAssets['token']['show_decimals'];
        $data = [
            //质押收益数量(ETH)
            'earnings' => format_number($ethAssets['available_balance'], $precision),
            //提现部分(USDT)
            'withdraw' => format_number($usdtAssets['available_balance'], $precision),
            //汇率
            'rate'     => Token::ethUsdtRate(),
        ];

        $this->success("请求成功", $data);
    }

    /**
     * 获取用户的交易Record
     */
    public function getUserLog()
    {
        $data = [
            //条数？
            "count" => 0,
            //每条记录的数组
            "data"  => [
            ],
            //币种汇率
            "rate"  => "1268.48",
        ];

        $this->success("请求成功", $data);
    }

    /**
     * 兑换
     */
    public function exchange()
    {
        $amount = Request::param('amount');
        if ($amount <= 0) {
            $this->error();
        }
        //来源币种
        $fromType = Request::param('fromType');
        //目标币种
        $toType = Request::param('toType');

        $network = Config::get('blockchain.network');
        $fromTokenId = Token::where(['symbol' => $fromType, 'network' => $network])
            ->value('id');
        $toTokenId = Token::where(['symbol' => $toType, 'network' => $network])
            ->value('id');

        $uid = $this->auth->id;
        $fromAssets = Assets::where(['user_id' => $uid, 'token_id' => $fromTokenId])
            ->findOrEmpty();
        if ($fromAssets->isEmpty()) {
            $this->error();
        }
        $toAssets = Assets::where(['user_id' => $uid, 'token_id' => $toTokenId])
            ->findOrEmpty();
        if ($toAssets->isEmpty()) {
            $this->error();
        }
        if ($amount > $fromAssets['available_balance']) {
            $this->error('not enough available');
        }
        Db::startTrans();
        try {
            $rate = Token::ethUsdtRate();
            //来源available_balance扣除相应数量
            $fromOldAvailableBalance = $fromAssets['available_balance'];
            $fromAssets['available_balance'] -= $amount;
            $fromAssets->save();
            TransactionFlow::create([
                'user_id'       => $uid,
                'token_id'      => $fromTokenId,
                'assets_type'   => 'available',
                'old_balance'   => $fromOldAvailableBalance,
                'amount'        => $amount,
                'new_balance'   => $fromAssets['available_balance'],
                'transfer_type' => 'exchange',
                'in_out'        => 'out',
                'remark'        => sprintf('ETH兑换USDT，当前汇率:%s', $rate),
            ]);
            //目标available_balance增加数量x汇率
            $toOldAvailableBalance = $toAssets['available_balance'];
            $toAmount = $amount * $rate;
            $toAssets['available_balance'] += $toAmount;
            $toAssets->save();
            TransactionFlow::create([
                'user_id'       => $uid,
                'token_id'      => $toTokenId,
                'assets_type'   => 'available',
                'old_balance'   => $toOldAvailableBalance,
                'amount'        => $toAmount,
                'new_balance'   => $toAssets['available_balance'],
                'transfer_type' => 'exchange',
                'in_out'        => 'in',
                'remark'        => sprintf('ETH兑换USDT，当前汇率:%s', $rate),
            ]);
            Db::commit();
        } catch (Exception $exception) {
            Db::rollback();
            $this->error($exception->getMessage());
        }

        $this->success();
    }

    /**
     * 提现
     */
    public function withdraw()
    {
        //数量
        $amount = Request::param('amount');
        if ($amount <= 0) {
            $this->error();
        }
        //币种
        $type = Request::param('type');
        $user = $this->auth->getUser();
        $network = Config::get('blockchain.network');
        $tokenId = Token::where(['symbol' => $type, 'id' => $user['token_id'], 'network' => $network])
            ->value('id');

        $uid = $this->auth->id;
        $assets = Assets::where(['user_id' => $uid, 'token_id' => $tokenId])
            ->findOrEmpty();
        if ($assets->isEmpty()) {
            $this->error();
        }
        if ($amount > $assets['available_balance']) {
            $this->error('err amount');
        }
        trace('$amount');
        trace($amount);
        //从available_balance转到frozen_balance
        Db::startTrans();
        try {
            $oldStakeBalance = $assets['available_balance'];
            $oldFrozenBalance = $assets['frozen_balance'];
            $assets['available_balance'] -= $amount;
            $assets['frozen_balance'] += $amount;
            $assets->save();
            //质押流出记录
            TransactionFlow::create([
                'user_id'       => $uid,
                'token_id'      => $tokenId,
                'assets_type'   => 'available',
                'old_balance'   => $oldStakeBalance,
                'amount'        => $amount,
                'new_balance'   => $assets['available_balance'],
                'transfer_type' => 'withdraw',
                'in_out'        => 'out',
                'remark'        => '提现冻结',
            ]);
            //冻结流入记录
            TransactionFlow::create([
                'user_id'       => $uid,
                'token_id'      => $tokenId,
                'assets_type'   => 'frozen',
                'old_balance'   => $oldFrozenBalance,
                'amount'        => $amount,
                'new_balance'   => $assets['frozen_balance'],
                'transfer_type' => 'withdraw',
                'in_out'        => 'in',
                'remark'        => '提现冻结',
            ]);
            //提现记录
            WithdrawTransaction::create([
                'user_id'    => $uid,
                'token_id'   => $tokenId,
                'amount'     => $amount,
                'address_to' => $user['wallet_address'],
            ]);
            Db::commit();
        } catch (Exception $exception) {
            Db::rollback();
            trace($exception->getTraceAsString());
            $this->error($exception->getMessage());
        }

        $this->success();
    }
}