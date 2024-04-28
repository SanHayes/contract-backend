<?php

namespace app\common\model;

use think\Model;

class CollectRecord extends Model
{

    protected $autoWriteTimestamp = 'datetime';
    protected $name = 'collect_record';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function token()
    {
        return $this->belongsTo(Token::class);
    }

}
