<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\WhitePaper;
use think\db\Where;
use think\facade\Request;

class Paper extends Backend
{

    /**
     * 白皮书列表
     */
    public function getWhitePaperList()
    {
        $data = WhitePaper::order(['id' => 'DESC'])
            ->paginate();
        $this->success('请求成功', $data);
    }

    /**
     * 删除
     */
    public function delete()
    {
        $id = Request::param('id');
        if(!WhitePaper::destroy($id)){
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
        $result = WhitePaper::create($data);
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
        $row = WhitePaper::where($map)
            ->findOrEmpty();
        $post = Request::except(['id']);
        if (!$row->save($post)) {
            $this->error();
        }
        $this->success();
    }

}