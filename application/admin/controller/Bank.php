<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\db\Where;
use think\facade\Request;

class Bank extends Backend
{

    /**
     * 列表
     */
    public function lists()
    {
        $uid = Request::param('uid');
        $map = new Where();
        if ($uid > 0) {
            $map['user_id'] = $uid;
        }
        $data = \app\common\model\Bank::with(['user'])
            ->where($map)
            ->order(['id' => 'DESC'])
            ->paginate();
        $this->success('请求成功', $data);
    }


    /**
     * 创建/编辑
     */
    public function edit()
    {
        if (Request::has('id')) {
            $this->handleEdit();
        } else {
            $this->handleCreate();
        }
    }

    protected function handleCreate()
    {
        $data = Request::param();
        $result = \app\common\model\Bank::create($data);
        if ($result->isEmpty()) {
            $this->error();
        }
        $this->success();
    }

    protected function handleEdit()
    {
        $id = Request::param('id');
        $map = new Where();
        $map['id'] = $id;
        $row = \app\common\model\Bank::where($map)
            ->findOrEmpty();
        $post = Request::except(['id']);
        if (Request::isPost()) {
            if (!$row->save($post)) {
                $this->error();
            }
            $this->success();
        } else {
            $this->success('请求成功', $row);
        }
    }

    public function delete()
    {
        $id = Request::param('id');
        if (!\app\common\model\Bank::destroy($id)) {
            $this->error();
        }
        $this->success();
    }

}
