<?php declare(strict_types=1);

namespace app\common\auth;

use app\common\model\Assets;
use app\common\model\User;
use app\common\model\UserGroup;
use app\common\model\UserRelation;
use app\common\model\UserRule;
use app\common\token\TokenManager;
use Carbon\Carbon;
use thans\jwt\facade\JWTAuth;
use think\Db;
use think\db\Where;
use think\Exception;
use think\facade\Config;
use think\facade\Hook;
use think\facade\Request;
use think\helper\Hash;

/**
 * @property-read int id
 * @property-read string username
 * @property-read int user_level
 * @property-read int is_approve
 * @property-read int group_id
 * @property-read string wallet_address
 * @property-read string invite_code
 */
class AuthGuard
{

    /**
     * @var static
     */
    protected static $instance = null;

    /**
     * @var string
     */
    protected $_error = '';

    /**
     * @var bool
     */
    protected $_logined = false;

    /**
     * @var \app\common\model\User
     */
    protected $_user;

    /**
     * @var string
     */
    protected $_token = '';

    /**
     * @var string
     */
    protected $requestUri = '';

    /**
     * @var array
     */
    protected $rules = [];

    /**
     * @var string[]
     */
    protected $allowFields = [
        'id',
        'username',
        'is_approve',
        'user_level',
        'group_id',
        'wallet_address',
        'invite_code',
    ];

    private const STATUS_NORMAL = 1;

    /**
     * 兼容调用user模型的属性.
     *
     * @param  string  $name
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->_user ? $this->_user->{$name} : null;
    }

    /**
     * @return static
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * 获取User模型.
     * @return \think\Model
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * 根据Token初始化.
     *
     * @param  string  $token  Token
     * @return bool
     */
    public function init(string $token = ''): bool
    {
        if ($this->_logined) {
            return true;
        }
        if ($this->_error) {
            return false;
        }
        if (!$token) {
            return false;
        }
        $data = TokenManager::get($token);
        if (!$data) {
            return false;
        }
        $user_id = intval($data['user_id']);
        if ($user_id > 0) {
            $user = User::get($user_id);
            if (!$user) {
                $this->setError('Account not exist');
                return false;
            }
            if ($user['status'] != static::STATUS_NORMAL) {
                $this->setError('Account is locked');
                return false;
            }
            $this->_user = $user;
            $this->_logined = true;
            $this->_token = $token;

            return true;
        }
        $this->setError('You are not logged in');
        return false;
    }

    /**
     * 注册用户.
     *
     * @param  string  $username  用户名
     * @param  string  $password  密码
     * @param  string  $mobile  手机号
     * @param  array  $extend  扩展参数
     * @return bool
     */
    public function register(
        string $username,
        string $password,
        string $mobile,
        array $extend = [],
        bool $init = true
    ): bool {
        // 检测用户名或邮箱、手机号是否存在
        if ($username && User::get(['username' => $username])) {
            $this->setError('Username already exist');
            return false;
        }
        if ($mobile && User::get(['mobile' => $mobile])) {
            $this->setError('Mobile already exist');
            return false;
        }

        $data = [
            'username' => $username,
            'password' => $password,
            'mobile'   => $mobile,
        ];

        $ip = Request::ip();
        $time = Carbon::now();
        $params = array_merge($data, [
            'join_time'  => $time,
            'join_ip'    => $ip,
            'login_time' => $time,
            'login_ip'   => $ip,
            'prev_time'  => $time,
            'status'     => static::STATUS_NORMAL,
            'group_id'   => UserGroup::where(['name' => 'user'])->value('id'),
        ]);
        $params['password'] = Hash::make($password);
        $params['mobile'] = $mobile;
        $params = array_merge($params, $extend);

        //账号注册时需要开启事务,避免出现垃圾数据
        Db::startTrans();
        try {
            $user = User::create($params, true);

            //关系链数据
            // $pid = $params['pid'] ?? 0;
            // if (!UserRelation::createNewRecord($user['id'], $pid, $pid)) {
            //     throw new Exception('建立关系链失败');
            // }

            //创建用户资产
            Assets::createNewAccount($user);

            $this->_user = User::get($user->id);

            if ($init) {
                //设置Token
                $payload = [
                    'uid'    => $user->id,
                    'scopes' => ['user'],
                ];
                $this->_token = JWTAuth::builder($payload);
                TokenManager::set($this->_token, $user->id, Config::get('jwt.ttl'));
            }

            //注册成功的事件
            Hook::listen('user_register_successed', [$this->_user, $data]);
            Db::commit();
            return true;
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            Db::rollback();
            return false;
        }
    }

    /**
     * 用户登录.
     *
     * @param  string  $account  账号,用户名、邮箱、手机号
     * @param  string  $password  密码
     * @return bool
     */
    public function login(string $account, string $password): bool
    {
        $map = new Where();
        $map['username|mobile'] = $account;
        $user = User::where($map)
            ->findOrEmpty();
        if ($user->isEmpty()) {
            $this->setError('Account is incorrect');
            return false;
        }

        if ($user->status != static::STATUS_NORMAL) {
            $this->setError('Account is locked');
            return false;
        }
        if (!Hash::check($password, $user->password)) {
            $this->setError('Password is incorrect');
            return false;
        }

        //直接登录会员
        $this->direct($user->id);

        return true;
    }

    /**
     * 注销
     *
     * @return bool
     */
    public function logout(): bool
    {
        if (!$this->_logined) {
            $this->setError('You are not logged in');
            return false;
        }
        //设置登录标识
        $this->_logined = false;
        //删除Token
        TokenManager::delete($this->_token);
        return true;
    }

    /**
     * 修改登陆密码
     * @param  string  $newpassword  新密码
     * @param  string  $oldpassword  旧密码
     * @param  bool  $ignoreoldpassword  忽略旧密码
     * @return bool
     */
    public function changepwd($newpassword, $oldpassword = '', $ignoreoldpassword = false): bool
    {
        if (!$this->_logined) {
            $this->setError('You are not logged in');
            return false;
        }
        //判断旧密码是否正确
        if (Hash::check($oldpassword, $this->_user->password)
            || $ignoreoldpassword
        ) {
            Db::startTrans();
            try {
                $newpassword = Hash::make($newpassword);
                $this->_user->save(['password' => $newpassword, 'login_failure' => 0]);

                TokenManager::delete($this->_token);
                Db::commit();
                return true;
            } catch (Exception $e) {
                Db::rollback();
                $this->setError($e->getMessage());
                return false;
            }
        }
        $this->setError('Password is incorrect');
        return false;
    }

    /**
     * 修改手机号
     * @param  string  $mobile  手机号
     * @return bool
     */
    public function changeMobile(string $mobile): bool
    {
        if (!$this->_logined) {
            $this->setError('You are not logged in');
            return false;
        }
        Db::startTrans();
        try {
            $this->_user->save(['mobile' => $mobile]);
            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            $this->setError($e->getMessage());
            return false;
        }
    }


    /**
     * 直接登录账号.
     * @param  int  $userId
     * @return bool
     */
    public function direct(int $userId): bool
    {
        $user = User::get($userId);
        if (!$user) {
            return false;
        }
        Db::startTrans();
        try {
            //记录上一次登录时间
            $user->prev_time = $user->login_time;
            $user->last_ip = $user->login_ip;
            //记录本次登录的IP和时间
            $user->login_ip = Request::ip();
            $user->login_time = Carbon::now();
            $user->login_failure = 0;

            $user->save();

            $this->_user = $user;

            $payload = [
                'uid'    => $user->id,
                'scopes' => ['user'],
            ];
            $this->_token = JWTAuth::builder($payload);

            TokenManager::set($this->_token, $user->id, Config::get('jwt.ttl'));

            $this->_logined = true;
            //登录成功的事件
            Hook::listen('user_login_successed', $this->_user);
            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * 检测是否是否有对应权限.
     * @param  string  $path  控制器/方法
     * @param  string  $module  模块 默认为当前模块
     * @return bool
     */
    public function check(string $path = '', string $module = ''): bool
    {
        //@todo 未实现，先写死
        return true;
        if (!$this->_logined) {
            return false;
        }

        $ruleList = $this->getRuleList();
        $rules = [];
        foreach ($ruleList as $v) {
            $rules[] = $v['name'];
        }
        $url = ($module ?: Request::module()).'/'.(is_null($path) ? $this->getRequestUri() : $path);
        $url = strtolower(str_replace('.', '/', $url));
        return in_array($url, $rules);
    }

    /**
     * 判断是否登录.
     * @return bool
     */
    public function isLogin(): bool
    {
        if ($this->_logined) {
            return true;
        }
        return false;
    }

    /**
     * 获取当前Token.
     */
    public function getToken(): string
    {
        return $this->_token;
    }

    /**
     * 获取会员基本信息.
     * @return array
     */
    public function getUserinfo(): array
    {
        $data = $this->_user->toArray();
        $allowFields = $this->getAllowFields();
        $userinfo = array_intersect_key($data, array_flip($allowFields));
        return array_merge($userinfo, TokenManager::get($this->_token));
    }

    /**
     * 获取会员组别规则列表.
     * @return array
     */
    public function getRuleList(): array
    {
        if ($this->rules) {
            return $this->rules;
        }
        $group = $this->_user->groups;
        if (!$group) {
            return [];
        }
        $rules = explode(',', $group->rules);
        $this->rules = UserRule::where('status', '1')
            ->where('id', 'in', $rules)
            ->field('id,pid,name,title,is_menu')
            ->select();
        return $this->rules;
    }

    /**
     * 获取当前请求的URI.
     * @return string
     */
    public function getRequestUri(): string
    {
        return $this->requestUri;
    }

    /**
     * 设置当前请求的URI.
     * @param  string  $uri
     */
    public function setRequestUri(string $uri)
    {
        $this->requestUri = $uri;
    }

    /**
     * 获取允许输出的字段.
     * @return array
     */
    public function getAllowFields(): array
    {
        return $this->allowFields;
    }

    /**
     * 设置允许输出的字段.
     * @param  array  $fields
     */
    public function setAllowFields(array $fields)
    {
        $this->allowFields = $fields;
    }

    /**
     * 删除一个指定会员.
     * @param  int  $user_id  会员ID
     * @return bool
     */
    public function delete($user_id): bool
    {
        $user = User::get($user_id);
        if (!$user) {
            return false;
        }
        Db::startTrans();
        try {
            // 删除会员
            User::destroy($user_id);
            // 删除会员指定的所有Token
            TokenManager::clear($user_id);

            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * 检测当前控制器和方法是否匹配传递的数组.
     *
     * @param  array  $arr  需要验证权限的数组
     * @return bool
     */
    public function match(array $arr = []): bool
    {
        if (!is_array($arr)) {
            return false;
        }
        $action = Request::action(true);
        // 是否存在
        if (in_array($action, $arr)
            || in_array('*', $arr)
        ) {
            return true;
        }

        // 没找到匹配
        return false;
    }

    /**
     * 设置错误信息.
     *
     * @param  string  $error  错误信息
     * @return $this
     */
    public function setError($error)
    {
        $this->_error = $error;
        return $this;
    }

    /**
     * 获取错误信息.
     * @return string
     */
    public function getError(): string
    {
        return $this->_error ? __($this->_error) : '';
    }

}