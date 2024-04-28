<?php

namespace app\common\model;

use think\facade\Cache;
use think\Model;

class Parameter extends Model
{

    protected $name = 'parameter';
    protected $autoWriteTimestamp = 'datetime';

    protected static function init()
    {
        static::event('after_insert', function () {
            Cache::clear('Parameter');
        });
        static::event('after_write', function () {
            Cache::clear('Parameter');
        });
        static::event('after_delete', function () {
            Cache::clear('Parameter');
        });

        self::afterInsert(function (self $row) {
            $pk = $row->getPk();
            $row->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }

    /**
     * 获取配置字典
     * @return array
     */
    public static function getDict()
    {
        return static::column('name,value');
    }

}
