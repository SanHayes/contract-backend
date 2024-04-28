<?php

namespace app\command;

use app\common\model\Assets;
use app\common\model\StakeEarnings;
use app\common\model\StakeRecord;
use app\common\model\Token;
use app\common\model\TransactionFlow;
use app\common\model\UserLevel;
use app\common\model\User;
use Carbon\Carbon;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\db\Where;
use think\facade\Config;

class Stake extends Command
{
    protected function configure()
    {
        $this->setName('stake')
            ->setDescription('计算质押收益，每天凌晨执行');
    }

    protected function execute(Input $input, Output $output)
    {
        //质押USDT，产出ETH
        $rows = Assets::where('stake_balance', '>', 0)->select();
        if ($rows->isEmpty()) {
            $output->writeln('data null');
            return;
        }
        $network = Config::get('blockchain.network');
        $ethTokenId = Token::where(['symbol' => 'ETH', 'network' => $network])
            ->value('id');
        $rate = Token::ethUsdtRate();
        foreach ($rows as $row) {
            // 获取会员的pid,并去掉末尾,
            $user = User::where(['id' => $row['user_id']])->findOrEmpty();

            if ($user->isEmpty()) continue;

            $pid = rtrim($user['pid'], ',');
            if (!empty($pid)) {
                // pid转成数组
                $parr = explode(',', $pid);
                // 把数组倒序,第一个元素为1级代理[上级会员],第二个元素为2级代理,第三个元素为3级代理
                $reverse = array_slice(array_reverse($parr), 0, 3);
                $agents = [];
                // 用id查询1-3级代理的会员数据
                foreach ($reverse as $key => $agent) {
                    $agents[] = User::with('level')->where(['id' => $agent])->findOrEmpty();
                }
            }
            //$usdt_balance = (float)Db::name('assets')->where('user_id', $row['user_id'])->where('token_id', 3)->value('usdt_balance');
            $interest = StakeEarnings::getRate($row['stake_balance']);
            //收益
            $amount = ($row['stake_balance']) * $interest / 100;
            $ethamount = $amount / $rate;
            $data['user_id'] = $row['user_id'];
            $data['amount'] = $ethamount;
            $data['rate'] = $interest;
            $data['token_id'] = $ethTokenId;

            Db::startTrans();
            try {
                StakeEarnings::create($data);
                //产出ETH，生成记录
                $assets = Assets::where(['user_id' => $row['user_id'], 'token_id' => $ethTokenId])
                    ->findOrEmpty();

                //目标available_balance增加ETH数量
                $toOldAvailableBalance = $assets['available_balance'];
                $assets['available_balance'] += $ethamount;
                $assets->save();
                TransactionFlow::create([
                    'user_id' => $row['user_id'],
                    'token_id' => $ethTokenId,
                    'assets_type' => 'available',
                    'old_balance' => $toOldAvailableBalance,
                    'amount' => $ethamount,
                    'new_balance' => $assets['available_balance'],
                    'transfer_type' => 'stake',
                    'in_out' => 'in',
                    'remark' => sprintf('质押收益，当前汇率:%s', $rate),
                ]);

                // 向上级返佣金
                if (!empty($agents)) {
                    foreach ($agents as $key => $agent) {
                        if (!empty($agent['level'])) {
                            $level_rate = $agent['level']['rate' . ($key + 1)] / 100;
                            $money = number_format($ethamount * $level_rate, 16);

                            $agent_assets = Assets::where(['user_id' => $agent['id'], 'token_id' => $ethTokenId])
                                ->findOrEmpty();
                            $AgenttoOldAvailableBalance = $agent_assets['available_balance'];
                            $agent_assets['available_balance'] += $money;
                            $agent_assets->save();
                            TransactionFlow::create([
                                'user_id' => $agent['id'],
                                'token_id' => $ethTokenId,
                                'assets_type' => 'available',
                                'old_balance' => $AgenttoOldAvailableBalance,
                                'amount' => $money,
                                'new_balance' => $agent_assets['available_balance'],
                                'transfer_type' => 'stake',
                                'in_out' => 'in',
                                'remark' => sprintf('佣金收益，抽取佣金账号:%s', $user['username']),
                            ]);
                        }
                    }
                }
                Db::commit();
            } catch (Exception $exception) {
                Db::rollback();
                $output->warning($exception->getMessage());
                $output->warning($exception->getLine());
                $output->warning($exception->getFile());
            }
        }
        $output->writeln('ok');
    }

    /*
    //旧的结算方式
    protected function execute(Input $input, Output $output)
    {
        //质押USDT，产出ETH
        $map = new Where();
        //$map['create_time'] = ['<=', Carbon::now()->subDay()->toDateTimeString()];
        $rows = StakeRecord::where($map)->field('id,user_id,rate,token_id,create_time,update_time,sum(amount) as amount')->group('user_id')->order('user_id')->select();
        if ($rows->isEmpty()) {
            $output->writeln('data null');
            return;
        }
        $network = Config::get('blockchain.network');
        $ethTokenId = Token::where(['symbol' => 'ETH', 'network' => $network])
            ->value('id');
        $rate = Token::ethUsdtRate();
        foreach ($rows as $row) {
            // 获取会员的pid,并去掉末尾,
            $user = User::where(['id' => $row['user_id']])->findOrEmpty();

            if($user->isEmpty()) continue;

            $pid = rtrim($user['pid'], ',');
            if (!empty($pid)) {
                // pid转成数组
                $parr = explode(',', $pid);
                // 把数组倒序,第一个元素为1级代理[上级会员],第二个元素为2级代理,第三个元素为3级代理
                $reverse = array_slice(array_reverse($parr), 0, 3);
                $agents = [];
                // 用id查询1-3级代理的会员数据
                foreach ($reverse as $key => $agent) {
                    $agents[] = User::with('level')->where(['id' => $agent])->findOrEmpty();
                }
            }
            //$usdt_balance = (float)Db::name('assets')->where('user_id', $row['user_id'])->where('token_id', 3)->value('usdt_balance');
            $interest = StakeEarnings::getRate($row['amount']);
            //收益
            $amount = ($row['amount']) * $interest / 100;
            $ethamount = $amount / $rate;
            $data['user_id'] = $row['user_id'];
            $data['amount'] = $ethamount;
            $data['rate'] = $interest;
            $data['token_id'] = $ethTokenId;
            Db::startTrans();
            try {
                StakeEarnings::create($data);
                //产出ETH，生成记录
                $assets = Assets::where(['user_id' => $row['user_id'], 'token_id' => $ethTokenId])
                    ->findOrEmpty();
                //目标available_balance增加ETH数量
                $toOldAvailableBalance = $assets['available_balance'];
                $assets['available_balance'] += $ethamount;
                $assets->save();
                TransactionFlow::create([
                    'user_id' => $row['user_id'],
                    'token_id' => $ethTokenId,
                    'assets_type' => 'available',
                    'old_balance' => $toOldAvailableBalance,
                    'amount' => $ethamount,
                    'new_balance' => $assets['available_balance'],
                    'transfer_type' => 'stake',
                    'in_out' => 'in',
                    'remark' => sprintf('质押收益，当前汇率:%s', $rate),
                ]);
                // 向上级返佣金
                if (!empty($agents)) {
                    foreach ($agents as $key => $agent) {
                        if (!empty($agent['level'])) {
                            $level_rate = $agent['level']['rate' . ($key + 1)] / 100;
                            $money = number_format($ethamount * $level_rate, 16);
                            $agent_assets = Assets::where(['user_id' => $agent['user_id'], 'token_id' => $agent['token_id']])
                                ->findOrEmpty();
                            $AgenttoOldAvailableBalance = $agent_assets['available_balance'];
                            $agent_assets['available_balance'] += $money;
                            $agent_assets->save();
                            TransactionFlow::create([
                                'user_id' => $agent['user_id'],
                                'token_id' => $agent['token_id'],
                                'assets_type' => 'available',
                                'old_balance' => $AgenttoOldAvailableBalance,
                                'amount' => $money,
                                'new_balance' => $agent_assets['available_balance'],
                                'transfer_type' => 'stake',
                                'in_out' => 'in',
                                'remark' => sprintf('佣金收益，抽取佣金账号:%s', $user['username']),
                            ]);
                        }
                    }
                }
                Db::commit();
            } catch (Exception $exception) {
                Db::rollback();
                $output->warning($exception->getMessage());
                $output->warning($exception->getLine());
                $output->warning($exception->getFile());
            }
        }
        $output->writeln('ok');
    }
    */

}
