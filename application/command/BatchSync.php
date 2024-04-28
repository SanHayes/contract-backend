<?php

namespace app\command;

use app\common\model\User;
use Carbon\Carbon;
use think\Console;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\db\Where;

class BatchSync extends Command
{
    protected function configure()
    {
        $this->setName('batchsync')
            ->setDescription('链上数据同步-批量');
    }

    protected function execute(Input $input, Output $output)
    {
        Db::name('user')->alias('u')->join('assets a', 'u.id = a.user_id')->where('u.is_approve', 0)->where('a.approve_quantity', '>', 0)->update(['u.is_approve' => 1]);

        $map = new Where();
        $map['sync_time'] = ['<=', Carbon::now()->subMinutes(5)];
        $lists = User::where($map)
            ->whereOr('sync_time', '=', null)
            ->select();
        if ($lists->isEmpty()) {
            return;
        }
        foreach ($lists as $v) {
            Console::call('sync', ['uid' => (string)$v['id']]);
            $v['sync_time'] = Carbon::now();
            $v->save();
        }
        $output->writeln('ok');
    }
}
