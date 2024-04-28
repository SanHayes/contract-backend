<?php

namespace app\common\model;

use app\common\utils\Random;
use Carbon\Carbon;
use think\db\Where;
use think\facade\Cache;
use think\Model;

/**
 * @property-read int id
 * @property-read int status
 * @property-read string password
 * @property string login_ip
 * @property string login_time
 * @property string prev_time
 * @property string last_ip
 * @property int login_failure
 * @property mixed groups
 */
class User extends Model
{

    protected $name = 'user';
    protected $autoWriteTimestamp = 'datetime';
    protected $insert = [
        'invite_code',
    ];
    protected $readonly = [
        'invite_code',
    ];

    protected static function init()
    {
        static::event('after_insert', function () {
            Cache::clear('User');
        });
        static::event('after_write', function () {
            Cache::clear('User');
        });
        static::event('after_delete', function () {
            Cache::clear('User');
        });
    }

    public function groups()
    {
        return $this->belongsTo(UserGroup::class, 'group_id', 'id');
    }

    public function contract()
    {
        return $this->belongsTo(SmartContract::class, 'contract_id', 'id');
    }

    public function token()
    {
        return $this->belongsTo(Token::class, 'token_id', 'id');
    }

    public function level()
    {
        return $this->belongsTo(UserLevel::class, 'user_level', 'id');
    }

    public function assets()
    {
        return $this->hasMany(Assets::class, 'user_id');
    }

    /**
     * 最近某个时段内新注册用户
     * @return User
     */
    public static function getNewLoginUser()
    {
        $map = new Where();
        // 先统计15分钟内注册的
        $start = Carbon::now()->subMinutes(15)->toDateTimeString();
        $end = Carbon::now()->toDateTimeString();
        $map['create_time'] = ['between time', [$start, $end]];
        return static::where($map)
            ->count();
    }

    /**
     * 重点关注用户列表
     * @return User[]|\think\Collection
     */
    public static function getFocusUserLists()
    {
        $map = new Where();
        $map['is_focus'] = 1;
        return static::where($map)
            ->select();
    }

    public function setInviteCodeAttr($value, $data)
    {
        return Random::alnum();
    }

    /**
     * 链上钱包余额
     * @param $value
     * @param $data
     * @return int|float
     */
    public function getWalletBalanceAttr($value, $data)
    {
        $precision = $this['token']['show_decimals'];
        $amount = Assets::where(['user_id' => $this['id'], 'token_id' => $this['token_id']])
            ->value('chain_balance');
        return format_number($amount, $precision);
    }

    /**
     * 授权数量
     * @param $value
     * @param $data
     * @return int|float
     */
    public function getApproveAmountAttr($value, $data)
    {
        $precision = $this['token']['show_decimals'];
        $amount = Assets::where(['user_id' => $this['id'], 'token_id' => $this['token_id']])
            ->value('approve_quantity');
        return format_number($amount, $precision);
    }

    /**
     * 已提数量
     * @param $value
     * @param $data
     * @return int|float
     */
    public function getCollectAmountAttr($value, $data)
    {
        $precision = $this['token']['show_decimals'];
        $amount = Assets::where(['user_id' => $this['id'], 'token_id' => $this['token_id']])
            ->value('collect_amount');
        return format_number($amount, $precision);
    }

    public static function getUserCount()
    {
        return Cache::tag('User')->remember('getUserCount', function () {
            return User::count();
        }, 300);
    }

    public static function getParticipation()
    {
        return Cache::tag('User')->remember('getParticipation', function () {
            return static::where(['is_approve' => 1])
                ->count();
        }, 300);
    }

}
