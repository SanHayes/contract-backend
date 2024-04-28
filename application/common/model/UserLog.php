<?php

namespace app\common\model;

use app\common\auth\AuthGuard;
use think\facade\Request;
use think\Model;

class UserLog extends Model
{

    protected $name = 'user_log';
    protected $autoWriteTimestamp = 'datetime';
    protected $json = [
        'param',
    ];

    protected $auto = [
        'user_id',
        'domain',
        'url',
        'param',
        'ip',
        'user_agent',
    ];

    public function setUserIdAttr($value, $data)
    {
        $auth = AuthGuard::instance();
        $user = $auth->getUser();
        return $user['id'];
    }

    public function setDomainAttr($value, $data)
    {
        return Request::domain();
    }

    public function setUrlAttr($value, $data)
    {
        return Request::url();
    }

    public function setParamAttr($value, $data)
    {
        return Request::param();
    }

    public function setIpAttr($value, $data)
    {
        return Request::ip();
    }


    public function setUserAgentAttr($value, $data)
    {
        return Request::server('HTTP_USER_AGENT');
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
