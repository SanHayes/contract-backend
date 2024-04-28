<?php declare(strict_types=1);

use think\facade\Env;

return [
    // 数据库类型
    'type'           => 'mysql',
    // 服务器地址
    'hostname'       => Env::get('database.hostname', '127.0.0.1'),
    // 数据库名
    'database'       => Env::get('database.database'),
    // 用户名
    'username'       => Env::get('database.username'),
    // 密码
    'password'       => Env::get('database.password'),
    // 端口
    'hostport'       => Env::get('database.hostport', 3306),
    // 数据库编码默认采用utf8mb4
    'charset'        => Env::get('database.charset', 'utf8mb4'),
    // 数据库表前缀
    'prefix'         => Env::get('database.prefix', ''),
    'debug'          => Env::get('database.debug', false),
    // 数据集返回类型
    'resultset_type' => 'collection',
];