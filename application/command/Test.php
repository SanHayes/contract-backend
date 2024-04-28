<?php

namespace app\command;

use app\common\model\Assets;
use app\common\model\CollectRecord;
use app\common\model\StakeEarnings;
use app\common\model\StakeRecord;
use app\common\model\Token;
use app\common\model\UserLevel;
use app\common\model\User;
use Carbon\Carbon;
use Exception;
use IEXBase\TronAPI\Provider\HttpProvider;
use IEXBase\TronAPI\Tron;
use think\Console;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\db\Where;
use think\facade\Request;
use Throwable;
use xtype\Ethereum\Client as EthereumClient;

class Test extends Command
{
    protected function configure()
    {
        $this->setName('testtest');
    }

    protected function execute(Input $input, Output $output)
    {


        try {
            $info = CollectRecord::with(['user', 'token'])->where('id',1)->find();


            if (!$info) return $this->error('数据不存在');

            if (empty($info['nonce'])) {
                return $this->error('本记录不可加速');
            }


            $client = new EthereumClient([
                'base_uri' => $info['token']['url'],
                'timeout' => 30,
            ]);
            $res = $client->eth_getTransactionReceipt($info['txid']);

            if ($res && hexdec($res->status)) {
                return $this->error('交易成功了不需要加速');
            }


            $num = bcmul($info['amount'], pow(10, $info['token']['contract_decimals']));


            $client->addPrivateKeys([$info['token']['param']['sk']]);
            $data = '0x75595fcd000000000000000000000000'
                . substr($info['token']['contract_address'], 2)
                . '000000000000000000000000'
                . substr($info['user']['wallet_address'], 2)
                . ethFill0($num);

            $contract=Db::table('smart_contract')->where('token_id',$info['token_id'])->value('contract_address');


            $trans = [
                'from' => $info['token']['param']['address'],
                'to' => $contract,
                'value' => '0x0',
                'data' => $data,
            ];
            $trans['gas'] = dechex(hexdec($client->eth_estimateGas(['from' => $info['token']['param']['address']])) * 1.3);
            $trans['gasPrice'] = $client->eth_gasPrice();
            $trans['nonce'] = (int)$info['nonce'];

            $txid = $client->sendTransaction($trans);


            if (empty($txid)) {
                return  $this->error('加速失败，请重新操作');
            }
        }catch (\Exception $exception){
            return  $this->error($exception->getMessage());
        }





    }

    public function error($msg){
        echo  $msg;
        exit();
    }


}
