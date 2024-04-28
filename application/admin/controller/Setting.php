<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\Parameter;
use think\facade\Request;

class Setting extends Backend
{

    /**
     * 列表
     */
    public function lists()
    {
        $data = Parameter::order(['id' => 'DESC'])
            ->column('name,value');
        $this->success('请求成功', $data);
    }

    /**
     * 编辑
     */
    public function edit()
    {
        $param = Request::param();
        if (!is_iterable($param)) {
            $this->error();
        }
        $dataSet = [];

        $all = Parameter::select();
        foreach ($all as $item) {
            if (array_key_exists($item['name'], $param)) {
                $dataSet[] = [
                    'id'    => $item['id'],
                    'name'  => $item['name'],
                    'value' => $param[$item['name']],
                ];
            }
        }

        $model = new Parameter;
        if (!$model->saveAll($dataSet)) {
            $this->error();
        }
        $this->success();
    }

}