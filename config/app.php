<?php declare(strict_types=1);

use app\common\exception\ExceptionHandle;
use think\facade\Env;

return [
    // 应用调试模式
    'app_debug'              => Env::get('app.app_debug', false),
    'url_html_suffix'        => '',
    // 自动搜索控制器
    'controller_auto_search' => true,
    // 是否自动转换URL中的控制器和操作名
    'url_convert'            => false,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'       => ExceptionHandle::class,
    //登录失败超过10次则1天后重试
    'login_failure_retry'    => Env::get('app.login_failure_retry', true),
    'encrypt_key'            => Env::get('app.encrypt_key', ''),
    //归集间隔，单位：秒，默认5分钟
    'collect_interval'        => Env::get('app.collect_interval', 300),
];