<?php declare(strict_types=1);

namespace app\http\middleware;

use Closure;
use think\Request;

/**
 * CORS跨域中间件
 */
class CorsMiddleware
{

    public function handle(Request $request, Closure $next)
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Authorization, Sec-Fetch-Mode, DNT, X-Mx-ReqToken, Keep-Alive, User-Agent, If-Match, If-None-Match, If-Unmodified-Since, X-Requested-With, If-Modified-Since, Cache-Control, Content-Type, Accept-Language, Origin, Accept-Encoding");
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE');
        header('Access-Control-Max-Age: 1728000');
        if (strtoupper($request->method()) == 'OPTIONS') {
            return response();
        }

        return $next($request);
    }

}
