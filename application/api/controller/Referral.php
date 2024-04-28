<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\UserLevel;
use app\common\model\User;
use app\common\model\StakeEarnings;

class Referral extends Api
{

    /**
     * 获取团队信息
     */
    public function getShareTeam()
    {
        $uid = $this->auth->id;
        $User = User::with('level')->where(['id' => $uid])->findOrEmpty();
        if ($User->isEmpty()) {
            $this->error();
        }
        $Teams = User::where("pid like '" . $User['id'] . ",%'")
            ->field('id,pid,username,user_level,is_approve,status,create_time,update_time')
            ->select();
        $dou_num = substr_count($User['pid'],",");
        $UserIDs = $level1 = $level2 = $level3 = [];
        if(count($Teams) > 0){
            foreach ($Teams as $colt) {
                $colt_dou_num = substr_count($colt['pid'],",");
                if($colt_dou_num == $dou_num + 1){
                    $UserIDs[] = $colt['id'];
                    $level1[] = $colt;
                } elseif ($colt_dou_num == $dou_num + 2){
                    $UserIDs[] = $colt['id'];
                    $level2[] = $colt;
                } elseif ($colt_dou_num == $dou_num + 3){
                    $UserIDs[] = $colt['id'];
                    $level3[] = $colt;
                }
            }
        }
        $level1_count = count($level1);
        $level2_count = count($level2);
        $level3_count = count($level3);
        $data = [
            //团队人数
            'team_size'        => $level1_count + $level2_count + $level3_count,
            //每级人数
            'level1_count'     => $level1_count,
            'level2_count'     => $level2_count,
            'level3_count'     => $level3_count,
            //每级会员信息
            'level1'           => $level1,
            'level2'           => $level2,
            'level3'           => $level3,
            //团队总收益
            'total_revenue'    => StakeEarnings::getAllTeamEthEarnings($UserIDs),
            //团队日收益
            'today_revenue'    => StakeEarnings::getTodayTeamEthEarnings($UserIDs),
            //我的等级
            'mylevel'           => $User['user_level'],
            'max_income'           => sprintf('%s%s',15,'%'),
        ];
        $this->success("请求成功", $data);
    }

}