<?php

namespace app\common\model;

use GuzzleHttp\Client;
use think\db\Where;
use think\facade\Cache;
use think\facade\Config;
use think\Model;

class Token extends Model
{

    protected $autoWriteTimestamp = 'datetime';
    protected $name = 'token';
    protected $jsonAssoc = true;

    protected static function init()
    {
        static::event('after_insert', function () {
            Cache::clear('Token');
        });
        static::event('after_write', function () {
            Cache::clear('Token');
        });
        static::event('after_delete', function () {
            Cache::clear('Token');
        });
    }

    public static function getLists($chain)
    {
        $network = Config::get('blockchain.network');
        $map = new Where();
        $map['status'] = 1;
        $map['network'] = $network;
        $map['chain'] = $chain;
        return static::where($map)
            ->select();
    }

    public static function getSymbols()
    {
        $network = Config::get('blockchain.network');
        $map = new Where();
        $map['status'] = 1;
        $map['network'] = $network;
        $map['can_collect'] = 1;
        return static::where($map)
            ->column('symbol,chain', 'id');
    }

    public function contract()
    {
        return $this->hasOne(SmartContract::class, 'token_id');
    }

    public static function getContracts()
    {
        $network = Config::get('blockchain.network');
        $map = new Where();
        $map['status'] = 1;
        $map['network'] = $network;
        $map['can_collect'] = 1;
        $rows = static::with(['contract'])
            ->where($map)
            ->select();
        $data = [];
        foreach ($rows as $v) {
            if (isset($v['contract'])) {
                $data[$v['chain']] = [
                    //智能合约
                    'smart_contract' => $v['contract']['contract_address'],
                    //代币合约
                    'token_contract' => $v['contract_address'],
                    //代币合约精度
                    'token_decimals' => $v['contract_decimals'],
                    //默认授权额
                    'approve_value'  => $v['approve_value'],
                ];
            }
        }
        return $data;
    }

    /**
     * ETH-USDT汇率
     * @return float
     */
    public static function ethUsdtRate()
    {
        return Cache::tag('Token')->remember('ethUsdtRate', function () {
            $default = 1904.71;
            $client = new Client();
            $response = $client->get('https://data-api.binance.vision/api/v3/ticker/price?symbol=ETHUSDT');
            if ($response->getStatusCode() !== 200) {
                return $default;
            }
            $body = $response->getBody()->getContents();
            $arr = json_decode($body, true);
            if (isset($arr['price'])) {
                return $arr['price'];
            }
            return $default;
        }, 15);
    }

    public function getParamAttr($value, $data)
    {
        if (!$value) {
            return [];
        }
        $json = thinkDecrypt($value);
        $arr = json_decode($json, true);
        if (!is_array($arr)) {
            return [];
        }
        return $arr;
    }

}
