<?php declare(strict_types=1);

namespace app\common\token\storage;

use app\common\token\Storage;
use BadFunctionCallException;
use DateTime;
use Redis;

class RedisStorage extends Storage
{

    const userPre = 'up:';

    const tokenPre = 'tp:';

    protected $options = [
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'password'   => '',
        'select'     => 0,
        'timeout'    => 1,
        'expire'     => 0,
        'persistent' => false,
    ];

    /**
     * 构造函数.
     * @param  array  $options  缓存参数
     * @throws BadFunctionCallException
     */
    public function __construct($options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        if (extension_loaded('redis')) {
            $this->handler = new \Redis;

            if ($this->options['persistent']) {
                $this->handler->pconnect($this->options['host'], (int)$this->options['port'], $this->options['timeout'], 'persistent_id_' . $this->options['select']);
            } else {
                $this->handler->connect($this->options['host'], (int)$this->options['port'], $this->options['timeout']);
            }

            if ('' != $this->options['password']) {
                $this->handler->auth($this->options['password']);
            }

            if (0 != $this->options['select']) {
                $this->handler->select($this->options['select']);
            }
        } elseif (class_exists('\Predis\Client')) {
            $params = [];
            foreach ($this->options as $key => $val) {
                if (in_array($key, ['aggregate', 'cluster', 'connections', 'exceptions', 'prefix', 'profile', 'replication', 'parameters'])) {
                    $params[$key] = $val;
                    unset($this->options[$key]);
                }
            }

            if ('' == $this->options['password']) {
                unset($this->options['password']);
            }

            $this->handler = new \Predis\Client($this->options, $params);

            $this->options['prefix'] = '';
        } else {
            throw new \BadFunctionCallException('not support: redis');
        }
    }

    public function __toString()
    {
        return 'RedisStorage';
    }

    /**
     * 存储Token.
     * @param  string  $token  Token
     * @param  int  $user_id  会员ID
     * @param  int  $expire  过期时长,0表示无限,单位秒
     * @return bool
     */
    public function set($token, $user_id, $expire = 0)
    {
        if ($expire instanceof DateTime) {
            $expire = $expire->getTimestamp() - time();
        }
        $key = $this->getEncryptedToken(static::tokenPre.$token);
        if ($expire) {
            $result = $this->handler->setex($key, $expire, $user_id);
        } else {
            $result = $this->handler->set($key, $user_id);
        }
        //写入会员关联的token
        $this->handler->sAdd($this->getUserKey($user_id), $key);
        return (bool) $result;
    }

    /**
     * 获取Token内的信息.
     * @param  string  $token
     * @return array
     */
    public function get(string $token): array
    {
        $key = $this->getEncryptedToken(static::tokenPre.$token);
        $value = $this->handler->get($key);
        if (is_null($value) || $value === false) {
            return [];
        }
        //获取有效期
        $expire = $this->handler->ttl($key);
        $expire = $expire < 0 ? 365 * 86400 : $expire;
        $expiretime = time() + $expire;
        //解决使用redis方式储存token时api接口Token刷新与检测因expires_in拼写错误报错的BUG
        return ['token' => $token, 'user_id' => $value, 'expiretime' => $expiretime, 'expires_in' => $expire];
    }

    /**
     * 判断Token是否可用.
     * @param  string  $token  Token
     * @param  int  $user_id  会员ID
     * @return bool
     */
    public function check($token, $user_id): bool
    {
        $data = self::get($token);
        return $data && $data['user_id'] == $user_id;
    }

    /**
     * 删除Token.
     * @param  string  $token
     * @return bool
     */
    public function delete($token): bool
    {
        $data = $this->get($token);
        if ($data) {
            $key = $this->getEncryptedToken(static::tokenPre.$token);
            $user_id = $data['user_id'];
            $this->handler->del($key);
            $this->handler->sRem($this->getUserKey($user_id), $key);
        }
        return true;
    }

    /**
     * 删除指定用户的所有Token.
     * @param  int  $user_id
     * @return bool
     */
    public function clear($user_id): bool
    {
        $keys = $this->handler->sMembers($this->getUserKey($user_id));
        $this->handler->del($this->getUserKey($user_id));
        $this->handler->del($keys);
        return true;
    }

    /**
     * 获取会员的key.
     * @param $user_id
     * @return string
     */
    protected function getUserKey($user_id): string
    {
        return static::userPre.$user_id;
    }

}
