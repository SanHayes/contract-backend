<?php declare(strict_types=1);

namespace app\common\token\storage;

use app\common\token\Storage;
use think\Db;

class MysqlStorage extends Storage
{
    /**
     * 默认配置.
     * @var array
     */
    protected $options = [
        'table'      => 'user_token',
        'expire'     => 2592000,
        'connection' => [],
    ];

    /**
     * 构造函数.
     * @param  array  $options  参数
     */
    public function __construct(array $options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        if ($this->options['connection']) {
            $this->handler = Db::connect($this->options['connection'])->name($this->options['table']);
        } else {
            $this->handler = Db::name($this->options['table']);
        }
    }

    public function __toString()
    {
        return 'MysqlStorage';
    }

    /**
     * 存储Token.
     * @param  string  $token  Token
     * @param  int  $user_id  会员ID
     * @param  int  $expire  过期时长,0表示无限,单位秒
     * @return bool
     */
    public function set($token, $user_id, $expire = null): bool
    {
        $expiretime = !is_null($expire) && $expire !== 0 ? time() + $expire : 0;
        $token = $this->getEncryptedToken($token);
        $this->handler->insert([
            'token'      => $token,
            'user_id'    => $user_id,
            'createtime' => time(),
            'expiretime' => $expiretime,
        ]);
        return true;
    }

    /**
     * 获取Token内的信息.
     * @param  string  $token
     * @return array
     */
    public function get($token): array
    {
        if (!$token) {
            return [];
        }
        $data = $this->handler->where('token', $this->getEncryptedToken($token))
            ->find();
        if ($data) {
            if (!$data['expiretime'] || $data['expiretime'] > time()) {
                //返回未加密的token给客户端使用
                $data['token'] = $token;
                //返回剩余有效时间
                $data['expires_in'] = $this->getExpiredIn($data['expiretime']);
                return $data;
            }
            self::delete($token);
        }
        return [];
    }

    /**
     * 判断Token是否可用.
     * @param  string  $token  Token
     * @param  int  $user_id  会员ID
     * @return bool
     */
    public function check($token, $user_id): bool
    {
        $data = $this->get($token);
        return $data && $data['user_id'] == $user_id;
    }

    /**
     * 删除Token.
     * @param  string  $token
     * @return bool
     */
    public function delete($token): bool
    {
        $this->handler->where('token', $this->getEncryptedToken($token))
            ->delete();
        return true;
    }

    /**
     * 删除指定用户的所有Token.
     * @param  int  $user_id
     * @return bool
     */
    public function clear($user_id): bool
    {
        $this->handler->where('user_id', $user_id)
            ->delete();
        return true;
    }

}
