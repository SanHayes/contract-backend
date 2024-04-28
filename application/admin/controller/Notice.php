<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\db\Where;
use think\facade\Request;

class Notice extends Backend
{

    /**
     * 列表
     */
    public function lists()
    {
        $map = new Where();
        $title = Request::param('title');
        $language = Request::param('language');
        if ($title !== null && $title !== '') {
            $map['title'] = $title;
        }
        if ($language !== null && $language !== '') {
            $map['language'] = $language;
        }
        $data = \app\common\model\Notice::where($map)
            ->order(['id' => 'ASC'])
            ->paginate();
        $this->success('请求成功', $data);
    }

    /**
     * 删除
     */
    public function delete()
    {
        $id = Request::param('id');
        if (!\app\common\model\Notice::destroy($id)) {
            $this->error();
        }
        $this->success();
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
        $result = \app\common\model\Notice::create($data);
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
        $row = \app\common\model\Notice::where($map)
            ->findOrEmpty();
        $post = Request::except(['id']);
        if (!$row->save($post)) {
            $this->error();
        }
        $this->success();
    }

    /**
     * 状态值0、1值切换
     */
    public function switch()
    {
        $id = Request::param('id');
        $field = Request::param('field');
        $row = \app\common\model\Notice::where(['id' => $id])
            ->findOrEmpty();
        if ($row->isEmpty()) {
            $this->error();
        }
        $row[$field] ^= 1;
        if (!$row->save()) {
            $this->error();
        }
        $this->success();
    }
}