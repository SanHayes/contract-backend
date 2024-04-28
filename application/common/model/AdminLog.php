<?php

namespace app\common\model;

use app\admin\auth\AuthGuard;
use think\facade\Request;
use think\Model;

class AdminLog extends Model
{

    protected $name = 'admin_log';
    protected $autoWriteTimestamp = 'datetime';
    protected $json = [
        'param',
    ];

    protected $auto = [
        'admin_id',
        'domain',
        'url',
        'param',
        'ip',
        'user_agent',
    ];

    public function setAdminIdAttr($value, $data)
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


    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

}
