<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Assets;
use app\common\model\StakeEarnings;

class Account extends Api
{

    /**
     * account界面的index请求
     */
    public function getAccount()
    {
        $uid = $this->auth->id;
        $assets = Assets::where(['user_id' => $uid])
            ->select();
        if ($assets->isEmpty()) {
            return;
        }
        $coin = [];
        //资产
        foreach ($assets as $v) {
            $symbol = strtolower($v['token']['symbol']);
            $precision = $v['token']['show_decimals'];
            $coin[$symbol] = [
                'total' => format_number($v['available_balance'] + $v['frozen_balance'], $precision),
                'available' => format_number($v['available_balance'], $precision),
                'freeze' => format_number($v['frozen_balance'], $precision),
            ];
        }

        $assets = Assets::where(['user_id' => $uid])->where('token_id', 3)->find();

        $data = [
            //总收入额(ETH)
            'earnings' => format_number(StakeEarnings::getAllEthEarnings($uid) + $assets['count_earnings'] ?? 0, 2),
            //日收入(ETH)
            'today' => format_number(StakeEarnings::getTodayEthEarnings($uid) + $assets['today_earnings'] ?? 0, 2),
            //收益率
            'yield' => StakeEarnings::getRate($assets['usdt_balance'] ?? 0) . '%',
            //钱包矿池(USDT)
            'pool' => format_number($assets['pool_earnings'] ?? 0, 2) + $coin['usdt']['total'] ?? 0,
            //钱包余额(USDT)
            'balance' => format_number($assets['usdt_balance'] ?? 0, 2),
        ];

        if(isset($coin['usdt']['total'])) $coin['usdt']['total'] = $data['pool'];


        $this->success("请求成功", array_merge($data, $coin));
    }

}