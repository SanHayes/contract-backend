<?php

namespace app\http\middleware;

use app\common\auth\AuthGuard;
use app\common\model\UserLog;
use Closure;
use think\Request;

class UserLogMiddleware
{

    public function handle(Request $request, Closure $next)
    {
        $auth = AuthGuard::instance();
        if($auth->isLogin()){
            UserLog::create();
        }
        return $next($request);
    }

}
