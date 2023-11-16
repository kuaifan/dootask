<?php

namespace App\Tasks;

use App\Models\User;
use App\Module\Base;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectUser;
use Carbon\Carbon;
use App\Models\WebSocketDialogMsg;
use Illuminate\Support\Facades\Cache;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

class UnclaimedTaskRemindTask extends AbstractTask
{
    public function __construct()
    {
        parent::__construct();
    }

    public function start()
    {
        //
        $setting = Base::setting('system');
        if ($setting['unclaimed_task_reminder'] !== 'open') {
            return;
        }
        if (!$setting['unclaimed_task_reminder_time']) {
            return;
        }
        //
        $times = explode(':',date('H:i'));
        $reminderTimes = explode(':',$setting['unclaimed_task_reminder_time']);
        if( !isset($times[1]) || !isset($reminderTimes[1]) || $times[0] != $reminderTimes[0]){
            return;
        }
        // 执行一次
        if (Cache::get("UnclaimedTaskRemindTask:His",0)) {
            return;
        }
        if( $times[1] >= $reminderTimes[1] - 1 && $times[1] <= $reminderTimes[1] + 1){
            //
            Cache::put("UnclaimedTaskRemindTask:His", date('H:i:s'), Carbon::now()->addMinutes(5));
            //
            Project::whereNull('deleted_at')->whereNull('archived_at')->chunk(100,function($projects) {
                foreach ($projects as $project) {
                    //
                    $count = ProjectTask::query()
                        ->leftJoin('project_task_users', function ($query) {
                            $query->on('project_tasks.id', '=', 'project_task_users.task_id');
                        })
                        ->where('project_tasks.project_id',$project->id)
                        ->whereNull('project_tasks.deleted_at')
                        ->whereNull('project_tasks.archived_at')
                        ->whereNull('project_task_users.id')
                        ->count();
                    if($count > 0){
                        $botUser = User::botGetOrCreate('task-alert');
                        if (empty($botUser)) {
                            return;
                        }
                        if (!ProjectUser::whereUserid($botUser->userid)->whereProjectId($project->id)->exists()) {
                            $project->joinProject($botUser->userid);
                            $project->syncDialogUser();
                        }
                        WebSocketDialogMsg::sendMsg(null, $project->dialog_id , 'text', [
                            'text' => "当前存在{$count}个未领取任务"
                        ], $botUser->userid);
                    }
                }
            });
        }
    }

    public function end()
    {

    }

}
