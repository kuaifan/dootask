<?php

namespace App\Tasks;

use App\Models\ProjectTask;
use App\Models\ProjectTaskPushLog;
use App\Models\User;
use App\Module\Base;
use Carbon\Carbon;
use Hhxsv5\LaravelS\Swoole\Task\Task;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

class AppPushTask extends AbstractTask
{
    protected $endArray = [];

    public function __construct()
    {
        //
    }

    public function start()
    {
        $setting = Base::setting('appPushSetting');
        $pushTask = $setting['push'] === 'open' && $setting['push_task'] !== 'close';
        if (!$pushTask) {
            return;
        }
        $start = intval($setting['task_start_minute']);
        $hours = floatval($setting['task_remind_hours']);
        $hours2 = floatval($setting['task_remind_hours2']);
        if ($start > -1) {
            ProjectTask::whereNull("complete_at")
                ->whereNull("archived_at")
                ->whereBetween("start_at", [
                    Carbon::now()->subMinutes($start + 10),
                    Carbon::now()->subMinutes($start)
                ])->chunkById(100, function ($tasks) {
                    /** @var ProjectTask $task */
                    foreach ($tasks as $task) {
                        $this->taskPush($task, 0);
                    }
                });
        }
        if ($hours > -1) {
            ProjectTask::whereNull("complete_at")
                ->whereNull("archived_at")
                ->whereBetween("end_at", [
                    Carbon::now()->addMinutes($hours * 60),
                    Carbon::now()->addMinutes($hours * 60 + 10)
                ])->chunkById(100, function ($tasks) {
                    /** @var ProjectTask $task */
                    foreach ($tasks as $task) {
                        $this->taskPush($task, 1);
                    }
                });
        }
        if ($hours2 > -1) {
            ProjectTask::whereNull("complete_at")
                ->whereNull("archived_at")
                ->whereBetween("end_at", [
                    Carbon::now()->subMinutes($hours2 * 60 + 10),
                    Carbon::now()->subMinutes($hours2 * 60)
                ])->chunkById(100, function ($tasks) {
                    /** @var ProjectTask $task */
                    foreach ($tasks as $task) {
                        $this->taskPush($task, 2);
                    }
                });
        }
    }

    public function end()
    {
        foreach ($this->endArray as $task) {
            Task::deliver($task);
        }
    }

    /**
     * 任务过期前、超期后提醒
     * @param ProjectTask $task
     * @param int $type
     * @return void
     */
    private function taskPush(ProjectTask $task, int $type)
    {
        $userids = $task->taskUser->where('owner', 1)->pluck('userid')->toArray();
        if (empty($userids)) {
            return;
        }
        $users = User::whereIn('userid', $userids)->whereNull('disable_at')->get();
        if (empty($users)) {
            return;
        }

        $setting = Base::setting('appPushSetting');

        /** @var User $user */
        foreach ($users as $user) {
            $data = [
                'type' => $type,
                'userid' => $user->userid,
                'task_id' => $task->id,
            ];
            $pushLog = ProjectTaskPushLog::where($data)->exists();
            if ($pushLog) {
                continue;
            }
            $title = match ($type) {
                1 => "任务提醒",
                2 => "任务过期提醒",
                default => "任务开始提醒",
            };
            $body = view('push.task', [
                'type' => str_replace([0, 1, 2], ['start', 'before', 'after'], $type),
                'user' => $user,
                'task' => $task,
                'setting' => $setting,
            ])->render();
            $this->endArray[] = new PushUmengMsg($data['userid'], [
                'title' => $title,
                'body' => $body,
                'description' => "TID:{$data['task_id']}",
                'seconds' => 3600,
                'badge' => 1,
            ]);
            ProjectTaskPushLog::createInstance($data)->save();
        }
    }
}
