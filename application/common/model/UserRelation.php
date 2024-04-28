<?php

namespace app\common\model;

use think\db\Where;
use think\facade\Cache;
use think\Model;

class UserRelation extends Model
{

    protected $autoWriteTimestamp = 'datetime';
    protected $name = 'user_relation';

    //最大层级数
    const MAX_LAYER = 3;

    protected static function init()
    {
        static::event('after_insert', function () {
            Cache::clear('UserRelation');
        });
        static::event('after_write', function () {
            Cache::clear('UserRelation');
        });
        static::event('after_delete', function () {
            Cache::clear('UserRelation');
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 推荐关系集合，不含自身
     * @param $value
     * @param $data
     * @return array|string[]
     */
    public function getRelationAttr($value, $data)
    {
        $rel = $data['rel'];
        if (null === $rel) {
            return [];
        }
        return [...explode(',', $rel)];
    }

    /**
     * 推荐关系集合，包含自身
     * @param $value
     * @param $data
     * @return array|string[]
     */
    public function getFullRelationAttr($value, $data)
    {
        $rel = $data['rel'];
        if (null === $rel) {
            return [];
        }
        return [...explode(',', $rel), $data['user_id']];
    }

    /**
     * 创建关系链
     * @param  int  $uid  当前用户id
     * @param  int  $direct  直推人id
     * @param  int  $pid  推荐人id
     * @return bool
     */
    public static function createNewRecord($uid, $direct = 0, $pid = 0): bool
    {
        $data['user_id'] = $uid;
        //直推人id
        $data['direct_id'] = $direct;
        if ($pid == 0) {
            $data['pid1'] = $pid;
        }
        if ($pid !== 0) {
            $mapParent = new Where();
            $mapParent['user_id'] = $pid;
            $parentInfo = static::where($mapParent)
                ->findOrEmpty();
            //检查pid子代个数
            if (!$parentInfo->isEmpty()) {
                //取出pid关系数据
                $rel = array_merge($parentInfo['relation'], [(string) $parentInfo['user_id']]);
                $data = array_merge($data, static::generatePidData($uid, $rel));
                $relation = implode(',', $rel);
                //增加子代人数
                $parentInfo->setInc('child_num');
            }
        }
        if (isset($relation)) {
            //增加团队总人数
            static::where('user_id', 'in', $relation)
                ->setInc('team_total');
        }
        $result = static::create($data);
        return !$result->isEmpty();
    }

    /**
     * 取出pid关系数据
     * @param  int $uid 当前用户id
     * @param  array  $rel
     * @return array
     */
    protected static function generatePidData($uid, array $rel): array
    {
        //唯一处理
        $rel = array_unique($rel);
        $rel = array_filter($rel, function ($v) use ($uid) {
            return $v != $uid;
        });
        $relation = implode(',', $rel);
        $data['rel'] = $relation;
        $reverse = array_reverse($rel);
        foreach (array_slice($reverse, 0, static::MAX_LAYER) as $k => $v) {
            $data['pid'.($k + 1)] = $v;
        }
        return $data;
    }

}
