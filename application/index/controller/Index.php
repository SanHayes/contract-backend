<?php

namespace app\index\controller;

use app\common\controller\Api;

class Index extends Api
{

    protected $noNeedLogin = ['index'];

    public function index()
    {
        $this->success();
    }

}
