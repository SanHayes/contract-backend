<?php

namespace app\common\model;

use Carbon\Carbon;
use think\db\Where;
use think\Model;

class ApproveHistory extends Model
{

    protected $autoWriteTimestamp = 'datetime';
    protected $name = 'approve_history';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 最近某个时段内新授权用户
     * @return ApproveHistory
     */
    public static function authUser()
    {
        $map = new Where();
        // 先统计15分钟内注册的
        $start = Carbon::now()->subMinutes(15)->toDateTimeString();
        $end = Carbon::now()->toDateTimeString();
        $map['create_time'] = ['between time', [$start, $end]];
        return static::where($map)
            ->count();
    }

}
