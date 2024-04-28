<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\db\Where;
use think\facade\Request;

class Message extends Backend
{

    /**
     * 站内信列表
     */
    public function lists()
    {
        $map = new Where();
        $type = Request::param('type');
        $status = Request::param('status');
        if ($type !== null && $type !== '') {
            $map['type'] = $type;
        }
        if ($status !== null && $status !== '') {
            $map['status'] = $status;
        }

        $data = \app\common\model\Message::where($map)
            ->order(['id' => 'DESC'])
            ->paginate();
        $this->success('请求成功', $data);
    }

    public function delete()
    {
        $id = Request::param('id');
        if (!\app\common\model\Message::destroy($id)) {
            $this->error();
        }
        $this->success();
    }

}