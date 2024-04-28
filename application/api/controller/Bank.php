<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use think\Request;

class Bank extends Api
{

    public function list(Request $request)
    {
        $list = Db::name('bank')
            ->fieldRaw('id,realname,phone,bank_name,bank_account')
            ->where('user_id', $this->auth->id)->order('id')
            ->select();
        $this->success('', $list);
    }

    public function bind()
    {
        $data = $this->check();
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['user_id'] = $this->auth->id;
        if (!Db::name('bank')->insertGetId($data)) {
            $this->error('绑定失败');
        }
        $this->success('绑定成功');
    }

    public function edit()
    {
        $data = $this->check();
        $uid = $this->auth->id;
        $id = request()->post('id/d', '');
        if (!Db::name('bank')->where('id', $id)->where(['user_id' => $uid])->update($data)) {
            $this->error('修改失败');
        }
        $this->success('修改成功');
    }

    public function unbind()
    {
        $uid = $this->auth->id;
        $id = request()->post('id/d', '');
        if (!Db::name('bank')->where('id', $id)->where(['user_id' => $uid])->delete()) {
            $this->error('解绑失败');
        }
        $this->success('解绑成功');
    }

    private function check()
    {
        $realname = request()->post('realname', '');
        $phone = request()->post('phone', '');
        $bank_name = request()->post('bank_name', '');
        $bank_account = request()->post('bank_account', '');

        if (!$realname) $this->error('请填写真实姓名');
        if (!$phone) $this->error('请填写手机号');
        if (!is_mobile($phone)) $this->error('手机号不正确');
        if (!$bank_name) $this->error('请填写银行名称');
        if (!$bank_account) $this->error('请填写银行账号');

        $data = [];
        $data['realname'] = $realname;
        $data['phone'] = $phone;
        $data['bank_name'] = htmlspecialchars($bank_name);
        $data['bank_account'] = htmlspecialchars($bank_account);
        $data['update_time'] = date('Y-m-d H:i:s');

        return $data;
    }
}