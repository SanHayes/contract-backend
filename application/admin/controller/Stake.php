<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\StakeEarnings;

class Stake extends Backend
{

    /**
     * 质押收益
     */
    public function lists()
    {
        $data = StakeEarnings::with(['user', 'token'])
            ->order(['id' => 'DESC'])
            ->paginate();
        $this->success('请求成功', $data);
    }

}