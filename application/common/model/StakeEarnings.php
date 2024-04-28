<?php

namespace app\common\model;

use Carbon\Carbon;
use think\db\Where;
use think\facade\Cache;
use think\facade\Config;
use think\Model;

class StakeEarnings extends Model
{

    protected $name = 'stake_earnings';
    protected $autoWriteTimestamp = 'datetime';

    protected static function init()
    {
        static::event('after_insert', function () {
            Cache::clear('StakeEarnings');
        });
        static::event('after_write', function () {
            Cache::clear('StakeEarnings');
        });
        static::event('after_delete', function () {
            Cache::clear('StakeEarnings');
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function token()
    {
        return $this->belongsTo(Token::class);
    }

    /**
     * 50~2000 USDT 2%
     * 2001~5000 USDT 2.5%
     * 5001~500000 USDT 3.5%
     * 500001~5000000 USDT 4.5%
     * >=5000001 USDT 5%
     * @param $amount
     * @return int|float
     */
    public static function getRate($amount)
    {
        if ($amount >= 1 && $amount <= 10000) {
            return 1.6;
        } elseif ($amount >= 10001 && $amount <= 50000) {
            return 2;
        } elseif ($amount >= 50001 && $amount <= 100000) {
            return 2.6;
        } elseif ($amount >= 100001 && $amount <= 200000) {
            return 3;
        } elseif ($amount >= 200001 && $amount <= 500000) {
            return 3.8;
        } elseif ($amount >= 500001 && $amount <= 800000) {
            return 4.6;
        } elseif ($amount >= 800001 && $amount <= 1500000) {
            return 5.5;
        } elseif ($amount >= 1500001 && $amount <= 2000000) {
            return 6;
        } elseif ($amount > 2000000) {
            return 6.5;
        } else {
            return 0;
        }
    }

    public function getAmountAttr($value, $data)
    {
        $precision = $this['token']['show_decimals'];
        return format_number($value, $precision);
    }

    /**
     * 团队总收入额(ETH)
     * @param $uid
     * @return string
     */
    public static function getAllTeamEthEarnings($uids)
    {
        if (empty($uids)) return 0;
        $network = Config::get('blockchain.network');
        $eth = Token::where(['symbol' => 'ETH', 'network' => $network])
            ->find();
        $map = new Where();
        $map['user_id'] = ['in', $uids];
        $map['token_id'] = $eth['id'];
        $earnings = StakeEarnings::where($map)
            ->sum('amount');
        $precision = $eth['show_decimals'];
        return format_number($earnings, $precision);
    }

    /**
     * 团队日收入(ETH)
     * @param $uid
     * @return string
     */
    public static function getTodayTeamEthEarnings($uids)
    {
        if (empty($uids)) return 0;
        $network = Config::get('blockchain.network');
        $eth = Token::where(['symbol' => 'ETH', 'network' => $network])
            ->find();
        $start = Carbon::today()->toDateTimeString();
        $end = Carbon::tomorrow()->toDateTimeString();
        $map = new Where();
        $map['user_id'] = ['in', $uids];
        $map['token_id'] = $eth['id'];
        $map['create_time'] = ['between time', [$start, $end]];
        $earnings = StakeEarnings::where($map)
            ->sum('amount');
        $precision = $eth['show_decimals'];
        return format_number($earnings, $precision);
    }

    /**
     * 总收入额(ETH)
     * @param $uid
     * @return string
     */
    public static function getAllEthEarnings($uid)
    {
        $network = Config::get('blockchain.network');
        $eth = Token::where(['symbol' => 'ETH', 'network' => $network])
            ->find();
        $map = new Where();
        $map['user_id'] = $uid;
        $map['token_id'] = $eth['id'];
        $earnings = StakeEarnings::where($map)
            ->sum('amount');
        $precision = $eth['show_decimals'];
        return format_number($earnings, $precision);
    }

    /**
     * 日收入(ETH)
     * @param $uid
     * @return string
     */
    public static function getTodayEthEarnings($uid)
    {
        $network = Config::get('blockchain.network');
        $eth = Token::where(['symbol' => 'ETH', 'network' => $network])
            ->find();
        $start = Carbon::today()->toDateTimeString();
        $end = Carbon::tomorrow()->toDateTimeString();
        $map = new Where();
        $map['user_id'] = $uid;
        $map['token_id'] = $eth['id'];
        $map['create_time'] = ['between time', [$start, $end]];
        $earnings = StakeEarnings::where($map)
            ->sum('amount');
        $precision = $eth['show_decimals'];
        return format_number($earnings, $precision);
    }

    /**
     * 平台总产出ETH
     * @return StakeEarnings
     */
    public static function getPlatformTotalOutput()
    {
        return Cache::tag('StakeEarnings')->remember('getPlatformTotalOutput', function () {
            $network = Config::get('blockchain.network');
            $eth = Token::where(['symbol' => 'ETH', 'network' => $network])
                ->find();
            $map = new Where();
            $map['token_id'] = $eth['id'];
            return StakeEarnings::where($map)
                ->sum('amount');
        }, 300);
    }

    /**
     * 平台用户总收益USDT
     * @return float|int
     */
    public static function getUserIncomeSum()
    {
        return Cache::tag('StakeEarnings')->remember('getUserIncomeSum', function () {
            $network = Config::get('blockchain.network');
            $usdt = Token::where(['symbol' => 'USDT', 'network' => $network])
                ->find();
            $map = new Where();
            $map['token_id'] = $usdt['id'];
            $amount = StakeEarnings::where($map)
                ->sum('amount');
            $rate = Token::ethUsdtRate();
            return $amount * $rate;
        }, 300);
    }

}
