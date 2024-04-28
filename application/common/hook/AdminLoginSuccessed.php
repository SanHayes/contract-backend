<?php

namespace app\common\hook;

use app\common\model\AdminLoginLog;
use Carbon\Carbon;

class AdminLoginSuccessed
{

    public function run($params)
    {
        $data['admin_id'] = $params['id'];
        $data['content'] = sprintf('%s于%s登录后台', $params['username'], Carbon::now());
        AdminLoginLog::create($data);
    }

}