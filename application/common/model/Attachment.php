<?php

namespace app\common\model;

use think\facade\Cache;
use think\Model;
use think\model\concern\SoftDelete;

class Attachment extends Model
{

    use SoftDelete;
    protected $name = 'attachment';
    protected $autoWriteTimestamp = 'datetime';

    protected static function init()
    {
        static::event('after_insert', function () {
            Cache::clear('Attachment');
        });
        static::event('after_write', function () {
            Cache::clear('Attachment');
        });
        static::event('after_delete', function () {
            Cache::clear('Attachment');
        });
    }

}
