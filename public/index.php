<?php

use think\Container;

// 加载基础文件
require __DIR__ . '/../thinkphp/base.php';

Container::get('app')->run()->send();
