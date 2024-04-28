<?php

namespace app\common\model;

use think\Model;

class WithdrawTransaction extends Model
{

    protected $autoWriteTimestamp = 'datetime';
    protected $name = 'withdraw_transaction';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function token()
    {
        return $this->belongsTo(Token::class);
    }

    public function getAmountAttr($value, $data)
    {
        $precision = $this['token']['show_decimals'] ?? 2;
        return format_number($value, $precision);
    }

}
