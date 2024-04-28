<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\Admin;
use app\common\model\AdminLog;
use app\common\model\AdminLoginLog;
use app\common\model\ApproveHistory;
use app\common\model\UserLoginLog;
use think\db\Where;
use think\facade\Request;

class Log extends Backend
{

    /**
     * 管理员登录日志
     */
    public function getAdminLoginList()
    {
        //@todo 入参
        $map = new Where();
        $username = Request::param('admin_id');
        $ip = Request::param('ip');
        $domain = Request::param('domain');

        if ($username !== null && $username !== '') {
            $adminId = Admin::where(['username' => $username])
                ->column('id');
            if ($adminId) {
                $map['admin_id'] = $adminId;
            }
        }
        if ($ip !== null && $ip !== '') {
            $map['ip'] = ['like', '%' . $ip . '%'];
        }
        if ($domain !== null && $domain !== '') {
            $map['domain'] = ['like', '%' . $domain . '%'];
        }

        $data = AdminLoginLog::with(['admin'])
            ->where($map)
            ->order(['id' => 'DESC'])
            ->paginate();
        $this->success('请求成功', $data);
    }

    /**
     * 前台登录日志
     */
    public function getLoginOperationList()
    {
        //@todo 入参
        $map = new Where();
        $data = UserLoginLog::with(['user'])
            ->where($map)
            ->order(['id' => 'DESC'])
            ->paginate();
        $this->success('请求成功', $data);
    }

    /**
     * 操作日志
     */
    public function getOperationList()
    {
        $map = new Where();
        $ip = Request::param('ip');
        $username = Request::param('admin_id');
        $url = Request::param('url');



        $domain = Request::param('domain');
        if ($ip !== null && $ip !== '') {
            $map['ip'] = ['like', '%' . $ip . '%'];
        }
        if ($domain !== null && $domain !== '') {
            $map['domain'] = ['like', '%' . $domain . '%'];
        }
        if ($username !== null && $username !== '') {
            $adminId = Admin::where(['username' => $username])
                ->column('id');
            if ($adminId) {
                $map['admin_id'] = ['in',$adminId];
            }
        }
        if ($url !== null && $url !== '') {
            $map['url'] = ['like', '%' . $url . '%'];
        }


        $data = AdminLog::with(['admin'])
            ->where($map)
            ->order(['id' => 'DESC'])
            ->paginate();
        $this->success('请求成功', $data);
    }

    /**
     * 用户授权记录
     */
    public function getUserAuthRecord()
    {
        //@todo 入参
        $map = new Where();
        $data = ApproveHistory::with(['user'])
            ->where($map)
            ->order(['id' => 'DESC'])
            ->paginate();
        $this->success('请求成功', $data);
    }

}