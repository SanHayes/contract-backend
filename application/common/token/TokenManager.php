<?php declare(strict_types = 1);


namespace app\common\token;


use think\facade\App;
use think\facade\Config;

class TokenManager
{
    /**
     * @var array Token的实例
     */
    protected static $instance = [];

    /**
     * @var Storage 操作句柄
     */
    protected static $handler;

    /**
     * 连接Token驱动.
     * @param  array  $options  配置数组
     * @param  bool  $reconnect  Token连接标识 true 强制重新连接
     * @return Storage
     */
    public static function connect(array $options = [], bool $reconnect = false): Storage
    {
        $identify = md5(serialize($options));

        if ($reconnect === true
            || !isset(static::$instance[$identify])
        ) {
            $class = $options['storage'];

            // 记录初始化信息
            App::isDebug() && trace('[ TOKEN ] INIT '.$class, 'info');

            if ($reconnect === true) {
                return new $class($options);
            }

            static::$instance[$identify] = new $class($options);
        }

        return static::$instance[$identify];
    }

    /**
     * 自动初始化Token.
     * @param  array  $options  配置数组
     * @return Storage
     */
    public static function init(array $options = []): Storage
    {
        if (is_null(static::$handler)) {
            if (empty($options) && Config::get('token.type') == 'complex') {
                $default = Config::get('token.default');
                // 获取默认Token配置，并连接
                $options = Config::get('token.'.$default['type']) ?: $default;
            } elseif (empty($options)) {
                $options = Config::get('token');
            }

            static::$handler = static::connect($options);
        }

        return static::$handler;
    }

    /**
     * 判断Token是否可用.
     * @param  string  $token  Token标识
     * @param  mixed  $user_id
     * @return bool
     */
    public static function check($token, $user_id): bool
    {
        return static::init()->check($token, $user_id);
    }

    /**
     * 读取Token.
     * @param  string  $token  Token标识
     * @return mixed
     */
    public static function get($token)
    {
        return static::init()->get($token);
    }

    /**
     * 写入Token.
     * @param  string  $token  Token标识
     * @param  mixed  $user_id  存储数据
     * @param  int  $expire  有效时间 0为永久
     * @return bool
     */
    public static function set($token, $user_id, $expire = 0): bool
    {
        return static::init()->set($token, $user_id, $expire);
    }

    /**
     * 删除Token.
     * @param  string  $token  标签名
     * @return bool
     */
    public static function delete($token): bool
    {
        return static::init()->delete($token);
    }

    /**
     * 清除Token.
     * @param  mixed  $user_id
     * @return bool
     */
    public static function clear($user_id): bool
    {
        return static::init()->clear($user_id);
    }

}
