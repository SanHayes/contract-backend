<?php

use think\facade\Env;

return [
    //超时时间，秒
    'timeout'       => 30,
    // 网络类型
    'network'       => Env::get('blockchain.network', 'main'),
];