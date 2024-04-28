<?php declare(strict_types=1);

use app\common\token\storage\MysqlStorage;
use app\common\token\storage\RedisStorage;
use think\facade\Env;

return [
    // 驱动方式
    'type'     => 'complex',
    // 缓存前缀
    'key'      => 'i3d6o32wo8fvs1fvdpwens',
    // 加密方式
    'hashalgo' => 'sha256',
    // 缓存有效期 0表示永久缓存
    'expire'   => 0,
    'default'  => [
        'type' => 'redis',
    ],

    'redis' => [
        'storage' => RedisStorage::class,
        'host'    => Env::get('redis.host', '127.0.0.1'),
        'port'    => Env::get('redis.port', '6379'),
    ],

    'mysql' => [
        'storage' => MysqlStorage::class,
    ],
];
