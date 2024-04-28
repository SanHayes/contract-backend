<?php

namespace app\admin\controller;

use app\common\auth\GoogleAuth;
use app\common\controller\Backend;
use think\facade\Request;

class Auth extends Backend
{

    protected $noNeedLogin = ['login'];

    /**
     * 登录
     */
    public function login()
    {
        $username = Request::param('username');
        $password = Request::param('password');
        $google_code = Request::param('google_code', '0');
        $result = $this->auth->login($username, $password, $google_code);
        if (!$result) {
            $this->error($this->auth->getError());
        }
        $data = $this->auth->getUserinfo();
        $this->success('请求成功', $data);
    }

    /**
     * 退出
     */
    public function logout()
    {
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }

    /**
     * 用户信息
     */
    public function info()
    {
        $data = $this->auth->getUserinfo();
        $this->success('请求成功', $data);
    }

    public function updatepwd()
    {
        $password = Request::param('newpwd');
        $goode_code = Request::param('goode_code');
        $result = $this->auth->changepwd_google_auth($password, $goode_code);
        if (!$result) {
            $this->error($this->auth->getError());
        }
        $this->success();
    }

    public function update_google_key()
    {
        $google_code = Request::param('google_code', '');
        $new_google_key = Request::param('new_google_key', '');
        $result = $this->auth->change_google_key($google_code, $new_google_key);
        if (!$result) {
            $this->error($this->auth->getError());
        }
        $this->success();
    }

    public function get_google_key()
    {
        $google_auth = new GoogleAuth;
        $data = [];
        $data['secret'] = $google_auth->createSecret();
        $data['url'] = $google_auth->getQRCodeGoogleUrl('aozao', $data['secret'], '熬早');
        $this->success('请求成功', $data);

    }

}
