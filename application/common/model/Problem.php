<?php

namespace app\common\model;

use think\db\Where;
use think\Model;

class Problem extends Model
{
    protected $autoWriteTimestamp = 'datetime';
    protected $name = 'problem';

    public static function getFaqs($lang)
    {
        $map = new Where();
        $map['language'] = $lang;
        return static::where($map)
            ->select();
    }

}