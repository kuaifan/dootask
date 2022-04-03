<?php

namespace App\Tasks;

use App\Models\NotifyLog;
use App\Models\NotifyRule;
use App\Models\NotifyTaskLog;
use App\Models\ProjectTask;
use App\Models\NotifyTelegramSubscribe;
use App\Models\User;
use App\Module\Base;
use Carbon\Carbon;
use Guanguans\Notify\Factory;
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
        $rule_id = intval($this->data['rule_id']);
        $userid = intval($this->data['userid']);
        $vars = is_array($this->data['vars']) ? $this->data['vars'] : [];

        $rule = NotifyRule::whereId($rule_id)->first();
        if (empty($rule)) {
            return;
        }

        $notifyLog = NotifyLog::createInstance([
            'rule_id' => $rule->id,
            'userid' => $userid,
            'vars' => $vars,
            'content' => $rule->content,
        ]);
        $notifyLog->save();

        $content = $rule->content;
        foreach ($vars as $key => $val) {
            $content = str_replace('{' . $key . '}', $val, $content);
        }

        $setting = Base::setting('notifyConfig');
        try {
            switch ($rule->mode) {
                case "mail":
                    $email = User::whereUserid($userid)->value('email');
                    if (!Base::isEmail($email)) {
                        throw new \Exception("User email '{$email}' address error");
                    }
                    Factory::mailer()
                        ->setDsn("smtp://{$setting['mail_account']}:{$setting['mail_password']}@{$setting['mail_server']}:{$setting['mail_port']}?verify_peer=0")
                        ->setMessage(\Guanguans\Notify\Messages\EmailMessage::create()
                            ->from(env('APP_NAME', 'Task') . " <{$setting['mail_account']}>")
                            ->to($email)
                            ->subject($rule->name)
                            ->html($content))
                        ->send();
                    break;

                case "dingding":
                    Factory::dingTalk()
                        ->setToken($setting['dingding_token'])
                        ->setSecret($setting['dingding_secret'])
                        ->setMessage((new \Guanguans\Notify\Messages\DingTalk\MarkdownMessage([
                            'title' => $rule->name,
                            'text'  => $content,
                        ])))
                        ->send();
                    break;

                case "feishu":
                    Factory::feiShu()
                        ->setToken($setting['feishu_token'])
                        ->setSecret($setting['feishu_secret'])
                        ->setMessage(new \Guanguans\Notify\Messages\FeiShu\PostMessage([
                            'zh_cn' => [
                                'title' => $rule->name,
                                'content' => [
                                    [
                                        [
                                            "tag" => "text",
                                            "text" => $content
                                        ]
                                    ]
                                ]
                            ]
                        ]))
                        ->send();
                    break;

                case "wework":
                    Factory::weWork()
                        ->setToken($setting['wework_token'])
                        ->setMessage(new \Guanguans\Notify\Messages\WeWork\MarkdownMessage("# {$rule->name}\n{$content}"))
                        ->send();
                    break;

                case "xizhi":
                    Factory::xiZhi()
                        ->setToken($setting['xizhi_token'])
                        ->setMessage(new \Guanguans\Notify\Messages\XiZhiMessage($rule->name, $content))
                        ->send();
                    break;

                case "telegram":
                    $chat_ids = NotifyTelegramSubscribe::whereSubscribe(1)
                        ->whereUserid($userid)
                        ->orderByDesc('id')
                        ->take(5)
                        ->pluck('chat_id')
                        ->toArray();
                    if (empty($chat_ids)) {
                        throw new \Exception("User telegram chat_id is error");
                    }
                    foreach ($chat_ids as $chat_id) {
                        Factory::telegram()
                            ->setToken($setting['telegram_token'])
                            ->setMessage(\Guanguans\Notify\Messages\Telegram\TextMessage::create([
                                'chat_id' => $chat_id,
                                'text' => "# {$rule->name}\n{$content}",
                                'parse_mode' => 'MarkdownV2',
                            ]))
                            ->send();
                    }
                    break;

                case "gitter":
                    Factory::gitter()
                        ->setToken($setting['gitter_token'])
                        ->setRoomId($setting['gitter_roomid'])
                        ->setMessage(new \Guanguans\Notify\Messages\GitterMessage($content))
                        ->send();
                    break;

                case "googlechat":
                    Factory::googleChat()
                        ->setToken($setting['googlechat_token'])
                        ->setKey($setting['googlechat_token'])
                        ->setSpace($setting['googlechat_space'])
                        ->setMessage(new \Guanguans\Notify\Messages\GoogleChatMessage([
                            'name' => $rule->name,
                            'text' => $content,
                        ]))
                        ->send();
                    break;

                default:
                    throw new \Exception("Mode error");
            }
            $notifyLog->success = 1;
        } catch (\Exception $e) {
            $notifyLog->error = $e->getMessage();
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
