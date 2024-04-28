<?php

namespace app\command\flex;

use think\Console;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;


class Pledge extends Command
{
    protected function configure()
    {
        $this->setName('gboy:pledge')
            ->setDescription('同步质押订单的余额');
    }

    protected function execute(Input $input, Output $output)
    {
        $list = Db::name('stake_record')->fieldRaw('user_id,sum(amount) count_amount,token_id')->group('user_id')->select();

        foreach ($list as $v) {
            Db::name('assets')->where(['user_id' => $v['user_id'], 'token_id' => $v['token_id']])->update(['stake_balance' => $v['count_amount']]);

            //手动同步等级也修改下
            Console::call('gboy:user:level', ['uid' => (string)$v['user_id']]);
        }
        $output->writeln('ok');
    }

}
