<?php

namespace app\common\model;

use think\db\Where;
use think\Model;

class Bank extends Model
{

    protected $autoWriteTimestamp = 'datetime';
    protected $name = 'bank';

    public function user()
    {
        return $this->hasMany(User::class, 'id', 'user_id');
    }

}
