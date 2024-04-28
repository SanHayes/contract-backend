<?php declare(strict_types=1);

namespace app\admin\auth;

use app\common\auth\GoogleAuth;
use app\common\model\Admin;
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
 * @property-read string nickname
 * @property-read string username
 * @property-read int status
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
     * @var \app\common\model\Admin
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
    protected $allowFields = ['id', 'nickname', 'username', 'status'];

    private const STATUS_NORMAL = 1;

    /**
     * 兼容调用user模型的属性.
     *
     * @param string $name
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
     * @param string $token Token
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
            $user = Admin::get($user_id);
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
     * 注册超管.
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $mobile 手机号
     * @return bool
     */
    public function register(
        string $username,
        string $password,
        string $mobile = '',
        bool   $init = false
    ): bool
    {
        // 检测用户名或邮箱、手机号是否存在
        if ($username && Admin::get(['username' => $username])) {
            $this->setError('Username already exist');
            return false;
        }
        if ($mobile && Admin::get(['mobile' => $mobile])) {
            $this->setError('Mobile already exist');
            return false;
        }

        $data = [
            'username' => $username,
            'password' => $password,
            'mobile' => $mobile,
        ];

        $ip = Request::ip();
        $time = Carbon::now();
        $params = array_merge($data, [
            'join_time' => $time,
            'join_ip' => $ip,
            'login_time' => $time,
            'login_ip' => $ip,
            'prev_time' => $time,
            'status' => static::STATUS_NORMAL,
        ]);
        $params['password'] = Hash::make($password);
        $params['mobile'] = $mobile;

        //账号注册时需要开启事务,避免出现垃圾数据
        Db::startTrans();
        try {
            $user = Admin::create($params, true);

            $this->_user = Admin::get($user['id']);

            if ($init) {
                //设置Token
                $payload = [
                    'uid' => $user['id'],
                    'scopes' => ['user'],
                ];
                $this->_token = JWTAuth::builder($payload);
                TokenManager::set($this->_token, $user['id'], Config::get('jwt.ttl'));
            }

            Db::commit();
            return true;
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            Db::rollback();
            return false;
        }
    }

    /**
     * 管理员登录.
     *
     * @param string $account 账号,用户名、邮箱、手机号
     * @param string $password 密码
     * @param string $google_code google码
     * @return bool
     */
    public function login(string $account, string $password, string $google_code): bool
    {
        $map = new Where();
        $map['username|mobile'] = $account;
        $user = Admin::where($map)
            ->findOrEmpty();
        if ($user->isEmpty()) {
            $this->setError('Account is incorrect');
            return false;
        }

        if ($user['google_key']) {
            $google_auth = (new GoogleAuth())->verifyCode($user['google_key'], $google_code, 2);
            if (!$google_auth || !$google_code) {
                $this->setError('Google code is incorrect');
                return false;
            }
        }

        if ($user->status != static::STATUS_NORMAL) {
            $this->setError('Account is locked');
            return false;
        }
        //登录失败超过10次则1天后重试，1天内失败次数>=10的进行错误提示
        if (Config::get('login_failure_retry')
            && $user->login_failure >= 10
            && Carbon::parse($user['update_time'])->diffInDays() === 0
        ) {
            $this->setError('Please try again after 1 day');
            return false;
        }
        if (!Hash::check($password, $user->password)) {
            $user->login_failure++;
            $user->save();
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
     * @param string $newpassword 新密码
     * @param string $oldpassword 旧密码
     * @param bool $ignoreoldpassword 忽略旧密码
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
     * 使用google验证码修改登录密码
     * @param $newpassword
     * @param $google_code
     * @return bool
     */
    public function changepwd_google_auth($newpassword, $google_code): bool
    {
        if (!$this->_logined) {
            $this->setError('You are not logged in');
            return false;
        }

        $google_auth = (new GoogleAuth())->verifyCode($this->_user->google_key, $google_code, 2);

        if (!$google_auth) {
            $this->setError('Google code is incorrect');
            return false;
        }

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

    public function change_google_key($google_code, $new_key): bool
    {
        if (!$this->_logined) {
            $this->setError('You are not logged in');
            return false;
        }

        if (!$new_key) {
            $this->setError('Google code is empty');
            return false;
        }

        if (mb_strlen($new_key) < 16 || mb_strlen($new_key) > 128) {
            $this->setError('Google code error');
            return false;
        }

        if ($this->_user->google_key) {

            $google_auth = (new GoogleAuth())->verifyCode($this->_user->google_key, $google_code, 2);

            if (!$google_auth) {
                $this->setError('Google code is incorrect');
                return false;
            }
        }

        Db::startTrans();
        try {
            $this->_user->where('id',$this->_user['id'])->update(['google_key' => $new_key]);
            TokenManager::delete($this->_token);
            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * 修改手机号
     * @param string $mobile 手机号
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
     * @param int $userId
     * @return bool
     */
    public function direct(int $userId): bool
    {
        $user = Admin::get($userId);
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
                'uid' => $user->id,
                'scopes' => ['user'],
            ];
            $this->_token = JWTAuth::builder($payload);

            TokenManager::set($this->_token, $user->id, Config::get('jwt.ttl'));

            $this->_logined = true;

            //登录成功的事件
            Hook::listen('admin_login_successed', $this->_user);
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
     * @param string $path 控制器/方法
     * @param string $module 模块 默认为当前模块
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
        $url = ($module ?: Request::module()) . '/' . (is_null($path) ? $this->getRequestUri() : $path);
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
        $group = $this->_user->group;
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
     * @param string $uri
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
     * @param array $fields
     */
    public function setAllowFields(array $fields)
    {
        $this->allowFields = $fields;
    }

    /**
     * 删除一个指定会员.
     * @param int $user_id 会员ID
     * @return bool
     */
    public function delete($user_id): bool
    {
        $user = Admin::get($user_id);
        if (!$user) {
            return false;
        }
        Db::startTrans();
        try {
            // 删除会员
            Admin::destroy($user_id);
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
     * @param array $arr 需要验证权限的数组
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
     * @param string $error 错误信息
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