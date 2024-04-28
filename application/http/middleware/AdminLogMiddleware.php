<?php

namespace app\http\middleware;

use app\admin\auth\AuthGuard;
use app\common\model\AdminLog;
use Closure;
use think\Request;

class AdminLogMiddleware
{

    public function handle(Request $request, Closure $next)
    {
        $auth = AuthGuard::instance();
        if($auth->isLogin()){
            AdminLog::create();
        }
        return $next($request);
    }

}
