<?php

namespace app\command;

use app\common\model\Assets;
use app\common\model\CollectRecord;
use app\common\model\StakeEarnings;
use app\common\model\StakeRecord;
use app\common\model\Token;
use app\common\model\User;
use Exception;
use IEXBase\TronAPI\Provider\HttpProvider;
use IEXBase\TronAPI\Tron;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Db;
use think\db\Where;
use Throwable;
use xtype\Ethereum\Client as EthereumClient;

class UserLevel extends Command
{
    protected function configure()
    {
        $this->setName('gboy:user:level')
            ->addArgument('uid', 2, '用户id')
            ->setDescription('用户等级升级');
    }

    protected function execute(Input $input, Output $output)
    {
        $uid = $input->getArgument('uid');

        $user_list = Db::name('user')->where(function ($query) use ($uid) {
            if ($uid) $query->where('id', $uid);
        })->order('id')->select();

        foreach ($user_list as $user) {
            // 账号等级计算
            $UserLevel = Db::name('user_level')->order('balance', 'asc')->select();
            $Level = 0;
            $assets = Assets::where(['user_id' => $user['id'], 'token_id' => 3])->find();

            if($assets){
                foreach ($UserLevel as $key => $val) {
                    // 取总归集的金额 提升会员等级
                    if ($assets['stake_balance'] >= $val['balance']) $Level = $val['id'];
                }
                if ($Level > $user['user_level']) {
                    Db::name('user')->where('id', $user['id'])->update(['user_level' => $Level]);
                }
            }

        }
        $output->writeln('ok');
    }

}
