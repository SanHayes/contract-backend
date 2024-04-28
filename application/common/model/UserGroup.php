<?php

namespace app\common\model;

use think\facade\Cache;
use think\Model;
use think\model\concern\SoftDelete;

class UserGroup extends Model
{

    use SoftDelete;
    protected $name = 'user_group';
    protected $autoWriteTimestamp = 'datetime';

    protected static function init()
    {
        static::event('after_insert', function () {
            Cache::clear('UserGroup');
        });
        static::event('after_write', function () {
            Cache::clear('UserGroup');
        });
        static::event('after_delete', function () {
            Cache::clear('UserGroup');
        });
    }

}
