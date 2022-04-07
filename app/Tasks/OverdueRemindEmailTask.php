<?php

namespace App\Tasks;

use App\Models\ProjectTask;
use App\Models\ProjectTaskMailLog;
use App\Models\User;
use App\Module\Base;
use Carbon\Carbon;
use Guanguans\Notify\Factory;
use Guanguans\Notify\Messages\EmailMessage;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

class OverdueRemindEmailTask extends AbstractTask
{
    public function __construct()
    {
        //
    }

    public function start()
    {
        $setting = Base::setting('emailSetting');
        if ($setting['notice'] === 'open') {
            $hours = floatval($setting['task_remind_hours']);
            $hours2 = floatval($setting['task_remind_hours2']);
            if ($hours > 0) {
                ProjectTask::whereNull('complete_at')
                    ->whereNull('archived_at')
                    ->whereBetween("end_at", [
                        Carbon::now()->addMinutes($hours * 60),
                        Carbon::now()->addMinutes($hours * 60 + 10)
                    ])->chunkById(100, function ($tasks) {
                        /** @var ProjectTask $task */
                        foreach ($tasks as $task) {
                            $this->overdueBeforeAfterEmail($task, true);
                        }
                    });
            }
            if ($hours2 > 0) {
                ProjectTask::whereNull('complete_at')
                    ->whereNull('archived_at')
                    ->whereBetween("end_at", [
                        Carbon::now()->subMinutes($hours2 * 60 + 10),
                        Carbon::now()->subMinutes($hours2 * 60)
                    ])->chunkById(100, function ($tasks) {
                        /** @var ProjectTask $task */
                        foreach ($tasks as $task) {
                            $this->overdueBeforeAfterEmail($task, false);
                        }
                    });
            }
        }
    }

    /**
     * 过期前、超期后提醒
     * @param ProjectTask $task
     * @param $isBefore
     * @return void
     */
    private function overdueBeforeAfterEmail(ProjectTask $task, $isBefore)
    {
        $userids = $task->taskUser->where('owner', 1)->pluck('userid')->toArray();
        if (empty($userids)) {
            return;
        }
        $users = User::whereIn('userid', $userids)->get();
        if (empty($users)) {
            return;
        }

        $setting = Base::setting('emailSetting');
        $hours = floatval($setting['task_remind_hours']);
        $hours2 = floatval($setting['task_remind_hours2']);

        /** @var User $user */
        foreach ($users as $user) {
            $data = [
                'type' => $isBefore ? 1 : 2,
                'userid' => $user->userid,
                'task_id' => $task->id,
            ];
            $emailLog = ProjectTaskMailLog::where($data)->first();
            if ($emailLog) {
                continue;
            }
            try {
                if (!Base::isEmail($user->email)) {
                    throw new \Exception("User email '{$user->email}' address error");
                }
                if ($isBefore) {
                    $subject = env('APP_NAME') . " 任务提醒";
                    $content = "<p>{$user->nickname} 您好：</p><p>您有一个任务【{$task->name}】还有{$hours}小时即将超时，请及时处理。</p>";
                } else {
                    $subject = env('APP_NAME') . " 任务过期提醒";
                    $content = "<p>{$user->nickname} 您好：</p><p>您的任务【{$task->name}】已经超时{$hours2}小时，请及时处理。</p>";
                }
                Factory::mailer()
                    ->setDsn("smtp://{$setting['account']}:{$setting['password']}@{$setting['smtp_server']}:{$setting['port']}?verify_peer=0")
                    ->setMessage(EmailMessage::create()
                        ->from(env('APP_NAME', 'Task') . " <{$setting['account']}>")
                        ->to($user->email)
                        ->subject($subject)
                        ->html($content))
                    ->send();
                $data['is_send'] = 1;
            } catch (\Exception $e) {
                $data['send_error'] = $e->getMessage();
            }
            $data['email'] = $user->email;
            ProjectTaskMailLog::createInstance($data)->save();
        }
    }
}
