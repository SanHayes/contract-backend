<?php

namespace app\common\controller;

use app\admin\auth\AuthGuard;
use app\http\middleware\AdminLogMiddleware;
use app\http\middleware\ValidateMiddleware;
use think\facade\Cookie;
use think\facade\Request;

/**
 * @property-read AuthGuard auth
 */
abstract class Backend extends Api
{

    protected $middleware = [
        AdminLogMiddleware::class,
        ValidateMiddleware::class,
    ];

    /**
     * 初始化操作.
     */
    protected function initialize()
    {
        $this->auth = AuthGuard::instance();

        $controller = Request::controller(true);
        $action = Request::action(true);

        // token
        if (Request::header('authorization')) {
            preg_match('/bearer\s*(\S+)\b/i', Request::header('authorization'), $matches);
            $token = $matches[1];
        } else {
            $token = Request::server(
                'HTTP_TOKEN',
                Request::request('token', Cookie::get('token') ?? '')
            );
        }


        $path = str_replace('.', '/', $controller).'/'.$action;

        // 设置当前请求的URI
        $this->auth->setRequestUri($path);
        if ($token) {
            $this->auth->init($token);
        }
        // 检测是否需要验证登录
        if (!$this->auth->match($this->noNeedLogin)) {
            //检测是否登录
            if (!$this->auth->isLogin()) {
                $this->error(__('Please login first'), null, 403);
            }
            // 判断是否需要验证权限
            if (!$this->auth->match($this->noNeedRight)) {
                // 判断控制器和方法判断是否有对应权限
                if (!$this->auth->check($path)) {
                    $this->error(__('You have no permission'), null, 403);
                }
            }
        }
    }

}