<?php

namespace app\common\model;

use app\admin\auth\AuthGuard;
use think\facade\Cache;
use think\Model;

class SmartContract extends Model
{

    protected $name = 'smart_contract';
    protected $autoWriteTimestamp = 'datetime';
    protected $readonly = [
        'contract_address',
    ];

    protected static function init()
    {
        static::event('after_insert', function () {
            Cache::clear('SmartContract');
        });
        static::event('after_write', function () {
            Cache::clear('SmartContract');
        });
        static::event('after_delete', function () {
            Cache::clear('SmartContract');
        });
    }

    public function setAdminIdAttr($value, $data)
    {
        $auth = AuthGuard::instance();
        $user = $auth->getUser();
        return $user['id'];
    }

    public function coin()
    {
        return $this->belongsTo(Token::class);
    }

}
