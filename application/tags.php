<?php

use app\common\hook\AdminLoginSuccessed;
use app\common\hook\UserLoginSuccessed;

return [
    'user_login_successed'  => [
        UserLoginSuccessed::class,
    ],
    'admin_login_successed' => [
        AdminLoginSuccessed::class,
    ],
];