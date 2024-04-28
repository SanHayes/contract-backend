<?php

namespace app\common\model;

use think\db\Where;
use think\Model;

class Help extends Model
{

    protected $autoWriteTimestamp = 'datetime';
    protected $name = 'help';

    public static function getLastNotice($lang)
    {
        $map = new Where();
        $map['language'] = $lang;
        $map['status'] = 1;
        return static::where($map)
            ->fieldRaw('title,content')
            ->order(['id' => 'asc'])
            ->select();
    }

}
