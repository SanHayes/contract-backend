<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\SmartContract;
use app\common\model\Token;
use think\db\Where;
use think\facade\Request;

class Contract extends Backend
{

    /**
     * 列表
     */
    public function lists()
    {
        $map = new Where();
        $contractAddress = Request::param('contract_address');
        $symbol = Request::param('symbol');
        $status = Request::param('status');
        $coinId = Request::param('coin_id');
        if ($contractAddress !== null && $contractAddress !== '') {
            $map['contract_address'] = $contractAddress;
        }
        if ($symbol !== null && $symbol !== '') {
            $map['symbol'] = $symbol;
        }
        if ($status !== null && $status !== '') {
            $map['status'] = $status;
        }
        if ($coinId !== null && $coinId !== '') {
            $map['token_id'] = $coinId;
        }

        $data = SmartContract::with(['coin'])
            ->where($map)
            ->order(['id' => 'DESC'])
            ->paginate();
        $this->success('请求成功', $data);
    }

    public function symbols()
    {
        $data = Token::getSymbols();
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
        $result = SmartContract::create($data);
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
        $row = SmartContract::where($map)
            ->findOrEmpty();
        $post = Request::except(['id']);
        if (!$row->save($post)) {
            $this->error();
        }
        $this->success();
    }

    public function delete()
    {
        $id = Request::param('id');
        if(!SmartContract::destroy($id)){
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
        $row = SmartContract::where(['id' => $id])
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
