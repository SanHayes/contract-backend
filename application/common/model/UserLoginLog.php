<?php

namespace app\common\model;

use think\facade\Request;
use think\Model;

class UserLoginLog extends Model
{

    protected $autoWriteTimestamp = 'datetime';
    protected $name = 'user_login_log';
    protected $auto = [
        'ip',
        'domain',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setIpAttr($value, $data)
    {
        return Request::ip();
    }

    public function setDomainAttr($value, $data)
    {
        return Request::domain();
    }

}
