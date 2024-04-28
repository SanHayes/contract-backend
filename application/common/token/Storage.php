<?php declare(strict_types = 1);


namespace app\common\token;


use think\facade\Config;

abstract class Storage
{

    /**
     * token前缀1
     */
    const userPre = '';

    /**
     * token前缀2
     */
    const tokenPre = '';

    protected $handler;

    protected $options = [];

    /**
     * 存储Token.
     * @param  string  $token  Token
     * @param  int  $user_id  会员ID
     * @param  int  $expire  过期时长,0表示无限,单位秒
     * @return bool
     */
    abstract public function set(string $token, int $user_id, int $expire = 0);

    /**
     * 获取Token内的信息.
     * @param  string  $token
     * @return array
     */
    abstract public function get(string $token);

    /**
     * 判断Token是否可用.
     * @param  string  $token  Token
     * @param  int  $user_id  会员ID
     * @return bool
     */
    abstract public function check(string $token, int $user_id);

    /**
     * 删除Token.
     * @param  string  $token
     * @return bool
     */
    abstract public function delete(string $token);

    /**
     * 删除指定用户的所有Token.
     * @param  int  $user_id
     * @return bool
     */
    abstract public function clear(int $user_id);

    /**
     * 获取加密后的Token.
     * @param  string  $token  Token标识
     * @return string
     */
    protected function getEncryptedToken(string $token): string
    {
        $config = Config::pull('token');
        return static::tokenPre . hash_hmac($config['hashalgo'], $token, $config['key']);
    }

    /**
     * 获取过期剩余时长
     * @param  int  $expire_time
     * @return float|int|mixed
     */
    protected function getExpiredIn(int $expire_time)
    {
        return $expire_time ? max(0, $expire_time - time()) : 365 * 86400;
    }

}
