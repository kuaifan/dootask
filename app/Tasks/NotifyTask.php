<?php

namespace App\Tasks;

use App\Models\NotifyLog;
use App\Models\NotifyRule;
use App\Models\NotifyTaskLog;
use App\Models\ProjectTask;
use App\Models\User;
use App\Module\Base;
use Carbon\Carbon;
use Guanguans\Notify\Factory;
use Guanguans\Notify\Messages\EmailMessage;
use Hhxsv5\LaravelS\Swoole\Task\Task;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

class NotifyTask extends AbstractTask
{
    protected $event;
    protected $data;

    public function __construct(string $event, array $data = [])
    {
        $this->event = $event;
        $this->data = $data;
    }

    public function start()
    {
        $event = $this->event;
        if (in_array($event, ['taskExpireBefore', 'taskExpireAfter'])) {
            $event = "taskExpireBeforeOrAfter";
        }
        if (method_exists($this, $event)) {
            $this->$event();
        }
    }

    /**
     * 通知
     * @return void
     */
    private function notify()
    {
        $rule_id = $this->data['rule_id'];
        $userid = $this->data['userid'];
        $vars = $this->data['vars'];

        $rule = NotifyRule::whereId($rule_id)->first();
        if (empty($rule)) {
            return;
        }
        $user = User::whereUserid($userid)->first();
        if (empty($user)) {
            return;
        }

        $notifyLog = NotifyLog::createInstance([
            'rule_id' => $rule->id,
            'userid' => $user->userid,
            'vars' => is_array($vars) ? $vars : [],
            'content' => $rule->content,
        ]);
        $notifyLog->save();

        $content = $rule->content;
        if (is_array($vars)) {
            foreach ($vars as $key => $val) {
                $content = str_replace('{' . $key . '}', $val, $content);
            }
        }

        $setting = Base::setting('notifyConfig');
        switch ($rule->mode) {
            case "mail":
                if (Base::isEmail($user->email)) {
                    $email = EmailMessage::create()
                        ->from(env('APP_NAME', 'Task') . " <{$setting['mail_account']}>")
                        ->to($user->email)
                        ->subject($rule->name)
                        ->html($content);
                    try {
                        Factory::mailer()
                            ->setDsn("smtp://{$setting['mail_account']}:{$setting['mail_password']}@{$setting['mail_server']}:{$setting['mail_port']}?verify_peer=0")
                            ->setMessage($email)
                            ->send();
                        $notifyLog->success = 1;
                    } catch (\Exception $e) {
                        $notifyLog->error = $e->getMessage();
                    }
                } else {
                    $notifyLog->error = "User email '{$user->email}' address error";
                }
                break;

            default:
                $notifyLog->error = "mode error";
                break;

        }

        $notifyLog->save();
        $rule->increment('total');
        $rule->last_at = Carbon::now();
        $rule->save();
    }

    /**
     * 任务到期前、任务超期后
     * @return void
     */
    private function taskExpireBeforeOrAfter()
    {
        NotifyRule::whereStatus(1)
            ->whereEvent($this->event)
            ->chunkById(10, function ($rules) {

                /** @var NotifyRule $rule */
                foreach ($rules as $rule) {
                    if ($rule->expire_hours <= 0) {
                        continue;   // 没有设置时间参数
                    }

                    if ($this->event === 'taskExpireBefore') {
                        $between = [
                            Carbon::now()->addMinutes($rule->expire_hours * 60 - 3),
                            Carbon::now()->addMinutes($rule->expire_hours * 60 + 3)
                        ];
                    } else {
                        $between = [
                            Carbon::now()->addMinutes($rule->expire_hours * 60 + 3),
                            Carbon::now()->addMinutes($rule->expire_hours * 60 - 3)
                        ];
                    }
                    ProjectTask::whereNull("complete_at")
                        ->whereNull("archived_at")
                        ->whereBetween("end_at", $between)->chunkById(100, function ($tasks) use ($rule) {

                            /** @var ProjectTask $task */
                            foreach ($tasks as $task) {
                                if (NotifyTaskLog::whereRuleId($rule->id)->whereTaskId($task->id)->exists()) {
                                    continue;  // 此规则已经给此任务发过就不发了
                                }
                                NotifyTaskLog::createInstance([
                                    'rule_id' => $rule->id,
                                    'task_id' => $task->id,
                                ])->save();

                                $userids = $task->taskUser->where('owner', 1)->pluck('userid')->toArray();
                                foreach ($userids as $userid) {
                                    Task::deliver(new self("notify", [
                                        'rule_id' => $rule->id,
                                        'userid' => $userid,
                                        'vars' => [
                                            'id' => $task->id,
                                            'name' => $task->name,
                                            'hour' => $rule->expire_hours,
                                        ]
                                    ]));
                                }
                            }
                        });
                }
            });
    }
}
