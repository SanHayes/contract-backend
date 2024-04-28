<?php

namespace app\common\model;

use think\db\Where;
use think\Model;

class NoticeCategory extends Model
{

    protected $autoWriteTimestamp = 'datetime';
    protected $name = 'notice_category';

    public static function getLastNotice($lang)
    {
        $map = new Where();
        $map['language'] = $lang;
        return static::where($map)
            ->order(['create_time' => 'DESC'])
            ->findOrEmpty();
    }

}
