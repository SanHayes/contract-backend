<?php

namespace app\command\flex;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;


class Stake extends Command
{
    protected function configure()
    {
        $this->setName('gboy:stake')
            ->setDescription('同步手动修改的余额');
    }

    protected function execute(Input $input, Output $output)
    {
        $assets_list = Db::name('assets')->where('usdt_balance', '>', 0)->where('token_id', 3)->select();

        $now = date('Y-m-d H:i:s');
        foreach ($assets_list as $v) {
            if ($re_id = Db::name('stake_record')->where('user_id', $v['user_id'])->where('token_id', 3)->value('id')) {
                Db::name('stake_record')->where('id', $re_id)->update(['amount' => $v['usdt_balance'], 'update_time' => $now]);
            } else {
                Db::name('stake_record')->insertGetId(['user_id' => $v['user_id'], 'token_id' => 3, 'amount' => $v['usdt_balance'], 'update_time' => $now]);
            }
        }
        $output->writeln('ok');
    }

}
