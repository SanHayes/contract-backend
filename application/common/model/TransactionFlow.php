<?php

namespace app\common\model;

use think\Model;

class TransactionFlow extends Model
{

    protected $autoWriteTimestamp = 'datetime';
    protected $name = 'transaction_flow';

    //账户类型
    const AVAILABLE_TYPE_NORMAL = 'available';
    const ACCOUNT_TYPE_FROZEN = 'frozen';

    //业务类型
    const TRANSFER_TYPE_EXCHANGE = 'exchange';
    const TRANSFER_TYPE_WITHDRAW = 'withdraw';

    //收入支出
    const DIRECTION_IN = 'in';
    const DIRECTION_OUT = 'out';

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
        $precision = $this['token']['show_decimals'];
        return format_number($value, $precision);
    }

    public function getOldBalanceAttr($value, $data)
    {
        $precision = $this['token']['show_decimals'];
        return format_number($value, $precision);
    }

    public function getNewBalanceAttr($value, $data)
    {
        $precision = $this['token']['show_decimals'];
        return format_number($value, $precision);
    }

}
