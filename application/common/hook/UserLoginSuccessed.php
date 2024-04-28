<?php

namespace app\common\hook;

use app\common\model\UserLoginLog;
use think\Console;
use think\facade\Request;

class UserLoginSuccessed
{

    public function run($params)
    {
        $uid = $params['id'];
        $data['user_id'] = $uid;
        $data['content'] = sprintf('%s登录，ip地址为：%s', $params['wallet_address'], Request::ip());
        UserLoginLog::create($data);
//        Console::call('sync', ['uid' => (string) $uid]);
    }

}