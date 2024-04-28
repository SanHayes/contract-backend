<?php

namespace app\common\model;

use think\Model;

class StakeRecord extends Model
{

    protected $name = 'stake_record';
    protected $autoWriteTimestamp = 'datetime';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function token()
    {
        return $this->belongsTo(Token::class);
    }

}
