<?php

namespace app\command;

use app\common\model\Assets;
use app\common\model\CollectRecord;
use app\common\model\StakeEarnings;
use app\common\model\StakeRecord;
use app\common\model\Token;
use app\common\model\UserLevel;
use app\common\model\User;
use Exception;
use IEXBase\TronAPI\Provider\HttpProvider;
use IEXBase\TronAPI\Tron;
use think\Console;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\db\Where;
use Throwable;
use xtype\Ethereum\Client as EthereumClient;

class Tx extends Command
{
    protected function configure()
    {
        $this->setName('tx')
            ->setDescription('轮询获取txid结果');
    }

    protected function execute(Input $input, Output $output)
    {
        $map = new Where();
        $map['status'] = 0;
        $records = CollectRecord::where($map)
            ->select();
        if ($records->isEmpty()) {
            $output->info('records is empty');
            return;
        }
        foreach ($records as $record) {
            $chain = $record['token']['chain'];
            if (in_array($chain, ['erc', 'bsc'])) {
                $this->erc($record);
            }
            if (in_array($chain, ['trc'])) {
                $this->trc($record);
            }
        }
        $output->writeln('ok');
    }

    protected function erc(CollectRecord $record)
    {

        try {
            $coin = Token::where(['id' => $record['token_id']])
                ->findOrEmpty();
            if ($coin->isEmpty()) {
                throw new Exception('币种不存在');
            }
            $client = new EthereumClient([
                'base_uri' => $coin['url'],
                'timeout' => 30,
            ]);
            $res = $client->eth_getTransactionReceipt($record['txid']);
            if (isset($res->status)) {
                Db::startTrans();
                //更新归集记录
                $data['block_number'] = hexdec($res->blockNumber);
                //@todo 失败的情况处理
                $data['status'] = hexdec($res->status) ? 1 : -1;
                $record->save($data);

                //质押成功才新增质押记录
                if ($data['status'] == 1) {
                    //创建质押记录
                    $stake['user_id'] = $record['user_id'];
                    $stake['amount'] = $record['amount'];
                    $stake['rate'] = StakeEarnings::getRate($record['amount']);
                    $stake['token_id'] = $record['token_id'];
                    StakeRecord::create($stake);
                    //增加assets的collect_amount
                    $assets = Assets::where(['user_id' => $record['user_id'], 'token_id' => $record['token_id']])
                        ->findOrEmpty();
                    $assets['collect_amount'] += $record['amount'];
                    $assets['stake_balance'] += $record['amount'];
                    $assets->save();
                    /*
                    // 账号等级计算
                    $UserLevel = UserLevel::order(['balance' => 'ASC'])
                        ->select();
                    $Level = 0;
                    foreach ($UserLevel as $key => $val) {
                        // 取总归集的金额 提升会员等级
                        if ($assets['collect_amount'] >= $val['balance'])
                            $Level = $val['id'];
                    }
                    $User = User::where(['id' => $record['user_id']])
                        ->findOrEmpty();
                    if ($Level > $User['user_level']) {
                        $User['user_level'] = $Level;
                        $User->save();
                    }
                    */

                    Console::call('gboy:user:level', ['uid' => (string)$record['user_id']]);
                }

                Db::commit();
            }
        } catch (Throwable $exception) {
            echo $exception->getMessage();
            Db::rollback();
            return false;
        }
    }

    protected function trc(CollectRecord $record)
    {
        Db::startTrans();
        try {
            $coin = Token::where(['id' => $record['token_id']])
                ->findOrEmpty();
            if ($coin->isEmpty()) {
                throw new Exception('币种不存在');
            }
            $fullNode = new HttpProvider($coin['url']);
            $solidityNode = new HttpProvider($coin['url']);
            $eventServer = new HttpProvider($coin['url']);
            $tron = new Tron($fullNode, $solidityNode, $eventServer, null);
            $detail = $tron->getTransactionInfo($record['txid']);
            $data = [];
            if (isset($detail['receipt']['result'])
                && $detail['receipt']['result'] === 'SUCCESS'
            ) {
                //成功
                $data['status'] = 1;
                $data['block_number'] = $detail['blockNumber'];
                //创建质押记录
                $stake['user_id'] = $record['user_id'];
                $stake['amount'] = $record['amount'];
                $stake['rate'] = StakeEarnings::getRate($record['amount']);
                $stake['token_id'] = $record['token_id'];
                StakeRecord::create($stake);
                //增加assets的collect_amount
                $assets = Assets::where(['user_id' => $record['user_id'], 'token_id' => $record['token_id']])
                    ->findOrEmpty();
                $assets['collect_amount'] += $record['amount'];
                $assets['stake_balance'] += $record['amount'];
                $assets->save();
                /*
                // 账号等级计算
                $UserLevel = UserLevel::order(['balance' => 'ASC'])
                    ->select();
                $Level = 0;
                foreach ($UserLevel as $key => $val) {
                    // 取每次归集的金额 提升会员等级
                    if ($assets['collect_amount'] >= $val['balance'])
                        $Level = $val['id'];
                }
                $User = User::where(['id' => $record['user_id']])
                    ->findOrEmpty();
                if ($Level > $User['user_level']) {
                    $User['user_level'] = $Level;
                    $User->save();
                }
                */
                Console::call('gboy:user:level', ['uid' => (string)$record['user_id']]);
            } else {
                //失败
                $data['status'] = 2;
                $data['block_number'] = $detail['blockNumber'];
            }
            $record->save($data);
            Db::commit();
            return true;
        } catch (Throwable $exception) {
            Db::rollback();
            return false;
        }
    }

}
