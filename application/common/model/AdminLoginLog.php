<?php

namespace app\common\model;

use think\facade\Request;
use think\Model;

class AdminLoginLog extends Model
{

    protected $autoWriteTimestamp = 'datetime';
    protected $name = 'admin_login_log';
    protected $auto = [
        'ip',
        'domain',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
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
