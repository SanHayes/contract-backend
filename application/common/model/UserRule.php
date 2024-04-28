<?php

namespace app\common\model;

use think\facade\Cache;
use think\Model;
use think\model\concern\SoftDelete;

class UserRule extends Model
{

    use SoftDelete;
    protected $name = 'user_rule';
    protected $autoWriteTimestamp = 'datetime';

    protected static function init()
    {
        static::event('after_insert', function () {
            Cache::clear('UserRule');
        });
        static::event('after_write', function () {
            Cache::clear('UserRule');
        });
        static::event('after_delete', function () {
            Cache::clear('UserRule');
        });

        self::afterInsert(function (self $row) {
            $pk = $row->getPk();
            $row->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }

}
