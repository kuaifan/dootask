<?php

namespace App\Models;

use Cache;
use Carbon\Carbon;
use DB;

class ApproveProcInstHistory extends AbstractModel
{
    protected $table = 'approve_proc_inst_history';

    /**
     * 获取用户审批状态（请假、外出）
     * @param $userid
     * @return mixed|null
     */
    public static function getUserApprovalStatus($userid)
    {
        if (empty($userid)) {
            return null;
        }
        return Cache::remember('user_is_leave_' . $userid, Carbon::now()->addMinute(), function () use ($userid) {
            return self::where([
                ['start_user_id', '=', $userid],
                [DB::raw("JSON_UNQUOTE(JSON_EXTRACT(var, '$.startTime'))"), '<=', Carbon::now()->toDateTimeString()],
                [DB::raw("JSON_UNQUOTE(JSON_EXTRACT(var, '$.endTime'))"), '>=', Carbon::now()->toDateTimeString()],
                ['state', '=', 2]
            ])->where(function ($query) {
                $query->where('proc_def_name', 'like', '%请假%')
                    ->orWhere('proc_def_name', 'like', '%外出%');
            })->orderByDesc('id')->value('proc_def_name');
        });
    }

    /**
     * 判断用户是否请假（包含：请假、外出）
     * @param $userid
     * @return bool
     */
    public static function userIsLeave($userid)
    {
        return (bool)self::getUserApprovalStatus($userid);
    }
}
