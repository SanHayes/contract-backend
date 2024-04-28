<?php declare(strict_types=1);

use think\facade\Env;

return [
    // 日志记录方式，内置 file socket 支持扩展
    'type'             => Env::get('log.type', 'file'),
    'force_client_ids' => ['contract_backend'],
    'allow_client_ids' => ['contract_backend'],
    'level'            => ['log', 'error'],
    'host'             => Env::get('log.host'),
];