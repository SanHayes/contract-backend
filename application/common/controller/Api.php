<?php declare(strict_types=1);

namespace app\common\controller;

use app\common\auth\AuthGuard;
use app\http\middleware\UserLogMiddleware;
use app\http\middleware\ValidateMiddleware;
use think\App;
use think\Container;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\facade\Cookie;
use think\facade\Request;
use think\facade\Response;

/**
 * API控制器基类.
 */
abstract class Api
{

    protected $middleware = [
        UserLogMiddleware::class,
        ValidateMiddleware::class,
    ];

    /**
     * @var bool 验证失败是否抛出异常
     */
    protected $failException = false;

    /**
     * @var bool 是否批量验证
     */
    protected $batchValidate = false;

    /**
     * 无需登录的方法,同时也就不需要鉴权了.
     * @var array
     */
    protected $noNeedLogin = [];

    /**
     * 无需鉴权的方法,但需要登录.
     * @var array
     */
    protected $noNeedRight = [];

    /**
     * 权限Auth.
     * @var AuthGuard
     */
    protected $auth;

    /**
     * 默认响应输出类型,支持json/xml
     * @var string
     */
    protected $responseType = 'json';

    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * @var object|App
     */
    protected $app;

    public function __construct(App $app = null)
    {
        $this->app = $app ?: Container::get('app');
        $this->request = $this->app['request'];

        // 控制器初始化
        $this->initialize();

        $this->registerMiddleware();
    }

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

    /**
     * 注册控制器中间件
     */
    protected function registerMiddleware()
    {
        if (!is_iterable($this->middleware)) {
            return;
        }

        foreach ($this->middleware as $key => $val) {
            if (!is_int($key)) {
                $only = $except = null;

                if (isset($val['only'])) {
                    $only = array_map(function ($item) {
                        return strtolower($item);
                    }, $val['only']);
                } elseif (isset($val['except'])) {
                    $except = array_map(function ($item) {
                        return strtolower($item);
                    }, $val['except']);
                }

                if (isset($only) && !in_array(Request::action(), $only)) {
                    continue;
                } elseif (isset($except) && in_array(Request::action(), $except)) {
                    continue;
                } else {
                    $val = $key;
                }
            }

            $this->app['middleware']->controller($val);
        }
    }


    /**
     * 操作成功返回的数据.
     * @param  string  $msg  提示信息
     * @param  mixed  $data  要返回的数据
     * @param  int  $code  错误码，默认为1
     * @param  string|null  $type  输出类型
     * @param  array  $header  发送的 Header 信息
     */
    final protected function success(
        $msg = '请求成功',
        $data = null,
        int $code = 200,
        ?string $type = null,
        array $header = []
    ) {
        $this->result($msg, $data, $code, $type, $header);
    }

    /**
     * 操作失败返回的数据.
     * @param  string  $msg  提示信息
     * @param  mixed  $data  要返回的数据
     * @param  int  $code  错误码，默认为0
     * @param  string  $type  输出类型
     * @param  array  $header  发送的 Header 信息
     */
    final protected function error($msg = '请求失败', $data = null, $code = 500, $type = null, array $header = [])
    {
        $this->result($msg, $data, $code, $type, $header);
    }

    /**
     * 返回封装后的 API 数据到客户端.
     * @param  mixed  $msg  提示信息
     * @param  mixed  $data  要返回的数据
     * @param  int  $code  错误码，默认为0
     * @param  string  $type  输出类型，支持json/xml/jsonp
     * @param  array  $header  发送的 Header 信息
     * @throws HttpResponseException
     */
    protected function result($msg, $data = null, $code = 0, $type = '', array $header = [])
    {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'time' => Request::server('REQUEST_TIME'),
            'data' => $data,
        ];
        // 如果未设置类型则自动判断
        $type = $type ?: (Request::param(config('var_jsonp_handler')) ? 'jsonp' : $this->responseType);

        if (isset($header['statuscode'])) {
            $code = $header['statuscode'];
            unset($header['statuscode']);
        } else {
            //未设置状态码,根据code值判断
            $code = $code >= 1000 || $code < 200 ? 200 : $code;
        }
        $response = Response::create($result, $type, $code)
            ->header($header);
        throw new HttpResponseException($response);
    }

    /**
     * 设置验证失败后是否抛出异常
     * @param  bool  $fail  是否抛出异常
     * @return $this
     */
    protected function validateFailException($fail = true)
    {
        $this->failException = $fail;

        return $this;
    }

    /**
     * 验证数据
     * @param  array  $data  数据
     * @param  string|array  $validate  验证器名或者验证规则数组
     * @param  array  $message  提示信息
     * @param  bool  $batch  是否批量验证
     * @param  mixed  $callback  回调方法（闭包）
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, $message = [], $batch = false, $callback = null)
    {
        if (is_array($validate)) {
            $v = $this->app->validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $v = $this->app->validate($validate);
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch();
        }

        if (is_array($message)) {
            $v->message($message);
        }

        if ($callback && is_callable($callback)) {
            call_user_func_array($callback, [$v, &$data]);
        }

        if (!$v->check($data)) {
            if ($this->failException) {
                throw new ValidateException($v->getError());
            }
            return $v->getError();
        }

        return true;
    }

}
