<?php

namespace app\common\model;

use think\facade\Cache;
use think\Model;

/**
 * @property-read int id
 * @property-read int status
 * @property-read string password
 * @property string login_ip
 * @property string login_time
 * @property string prev_time
 * @property string last_ip
 * @property int login_failure
 */
class Admin extends Model
{

    protected $name = 'admin';
    protected $autoWriteTimestamp = 'datetime';

    protected static function init()
    {
        static::event('after_insert', function () {
            Cache::clear('Admin');
        });
        static::event('after_write', function () {
            Cache::clear('Admin');
        });
        static::event('after_delete', function () {
            Cache::clear('Admin');
        });
    }

}
