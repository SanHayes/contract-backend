<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\facade\Env;
use think\facade\Request;

class Upload extends Backend
{

    public function index()
    {
        /**
         * @var $file \think\File
         */
        $file = Request::file('file');
        $info = $file->move(Env::get('root_path').'/public/uploads');
        if (!$info) {
            $this->error();
        }
        //@todo 入库
        $saveName = str_replace('\\', '/', $info->getSaveName());
        $data['path'] = Request::domain().'/uploads/'.$saveName;
        $this->success('请求成功', $data);
    }

}