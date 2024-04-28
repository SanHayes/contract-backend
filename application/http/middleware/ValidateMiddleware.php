<?php declare(strict_types=1);

namespace app\http\middleware;

use Closure;
use think\Console;
use think\Container;
use think\exception\ValidateException;
use think\facade\App;
use think\Request;

class ValidateMiddleware
{

    public function handle(Request $request, Closure $next)
    {
        //获取当前参数
        $params = $request->param();
        //获取访问模块
        $module = $request->module();
        //获取访问控制器
        $controller = ucfirst($request->controller());
        //多级控制器的情况
        $controller = str_replace('.', '\\', $controller);
        //获取操作名,用于验证场景scene
        $scene = $request->action();
        $validateClass = sprintf('\app\%s\validate\%s', $module, $controller);
        $validate = sprintf('\app\%s\validate\%s.%s', $module, $controller, $scene);
        App::isDebug() && trace('validate scene:'.$validate);

        if (App::isDebug() && !class_exists($validateClass) && config('auto_create_validate')) {
            trace('自动创建验证器');
            $name = sprintf('%s/%s', $module, $controller);
            Console::call('make:validate', ['--name' => $name]);
        }

        //仅当验证器存在时 进行校验
        if (class_exists($validateClass)) {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            /**
             * @var $app \think\App
             */
            $app = Container::get('app');
            $v = $app->validate($validate);
            if (!empty($scene)) {
                $v->scene($scene);
            }

            if (!$v->check($params)) {
                //校验不通过则直接返回错误信息
                throw new ValidateException($v->getError(), 500);
            }

        }
        return $next($request);
    }

}
