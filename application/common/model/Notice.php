<?php

namespace app\common\model;

use think\db\Where;
use think\Model;

class Notice extends Model
{

    protected $autoWriteTimestamp = 'datetime';
    protected $name = 'notice';

    public static function getLastNotice($lang)
    {
        $map = new Where();
        $map['language'] = $lang;
        return static::where($map)
            ->order(['create_time' => 'DESC'])
            ->findOrEmpty();
    }

}
