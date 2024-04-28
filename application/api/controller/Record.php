<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\TransactionFlow;
use think\db\Where;

class Record extends Api
{

    public function exchange()
    {
        $uid = $this->auth->id;
        $map = new Where();
        $map['transfer_type'] = 'exchange';
        $map['user_id'] = $uid;
        $visible = [
            'assets_type',
            'amount',
            'in_out',
            'create_time',
            'token' => [
                'symbol',
            ],
        ];
        $data = TransactionFlow::with(['token'])
            ->where($map)
            ->order(['id' => 'DESC'])
            ->visible($visible)
            ->paginate();

        foreach ($data as &$v) {
            $v['create_time'] = date('Y-m-d H:i:s', strtotime($v['create_time']) - 43200);
        }

        $this->success('请求成功', $data);
    }

    public function withdraw()
    {
        $uid = $this->auth->id;
        $map = new Where();
        $map['transfer_type'] = 'withdraw';
        $map['user_id'] = $uid;
        $visible = [
            'assets_type',
            'amount',
            'in_out',
            'create_time',
            'token' => [
                'symbol',
            ],
        ];
        $data = TransactionFlow::with(['token'])
            ->where($map)
            ->order(['id' => 'DESC'])
            ->visible($visible)
            ->paginate();
        foreach ($data as &$v) {
            $v['create_time'] = date('Y-m-d H:i:s', strtotime($v['create_time']) - 43200);
        }
        $this->success('请求成功', $data);
    }

    public function income()
    {
        $uid = $this->auth->id;
        $map = new Where();
        $map['transfer_type'] = 'stake';
        $map['user_id'] = $uid;
        $visible = [
            'assets_type',
            'amount',
            'in_out',
            'create_time',
            'token' => [
                'symbol',
            ],
        ];
        $data = TransactionFlow::with(['token'])
            ->where($map)
            ->order(['id' => 'DESC'])
            ->visible($visible)
            ->paginate();
        foreach ($data as &$v) {
            $v['create_time'] = date('Y-m-d H:i:s', strtotime($v['create_time']) - 43200);
        }
        $this->success('请求成功', $data);
    }

}