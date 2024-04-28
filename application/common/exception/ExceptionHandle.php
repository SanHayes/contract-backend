<?php

namespace app\common\exception;

use Exception;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ValidateException;
use think\facade\Config;

class ExceptionHandle extends Handle
{

    public function render(Exception $e)
    {
        if (Config::get('app_debug')) {
            // 调试模式，获取详细的错误信息
            $data = [
                'name'   => get_class($e),
                'file'   => $e->getFile(),
                'line'   => $e->getLine(),
                'msg'    => $this->getMessage($e),
                'trace'  => $e->getTrace(),
                'code'   => $this->getCode($e),
                'datas'  => $this->getExtendData($e),
                'tables' => [
                    'GET Data'              => $_GET,
                    'POST Data'             => $_POST,
                    'Files'                 => $_FILES,
                    'Cookies'               => $_COOKIE,
                    'Session'               => $_SESSION ?? [],
                    'Server/Request Data'   => $_SERVER,
                    'Environment Variables' => $_ENV,
                ],
            ];
            return json($data);
        } else {
            $statusCode = $code = 500;
            $msg = '发生错误!请稍后再试～';
            // 验证异常
            if ($e instanceof ValidateException) {
                $code = 0;
                $statusCode = 200;
                $msg = $e->getError();
            }
            // Http异常
            if ($e instanceof HttpException) {
                $statusCode = $code = $e->getStatusCode();
            }
            $data = [
                'code' => $code,
                'msg'  => $msg,
                'time' => time(),
            ];
            return json($data, $statusCode);
        }
    }

}
