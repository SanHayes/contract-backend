<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\UserLevel;
use think\db\Where;
use think\facade\Request;

class Level extends Backend
{

    /**
     * 推荐奖励设置
     */
    public function getLevelDividedList()
    {
        $map = new Where();
        $name = Request::param('name');
        $language = Request::param('language');
        if ($name !== null && $name !== '') {
            $map['name'] = $name;
        }
        if ($language !== null && $language !== '') {
            $map['language'] = $language;
        }
        $data = UserLevel::where($map)
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
        if (!UserLevel::destroy($id)) {
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
        $result = UserLevel::create($data);
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
        $row = UserLevel::where($map)
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
        $row = UserLevel::where(['id' => $id])
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