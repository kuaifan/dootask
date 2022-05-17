<?php

namespace App\Tasks;

use App\Models\ProjectTask;
use App\Models\ProjectTaskMailLog;
use App\Models\User;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgRead;
use App\Module\Base;
use Carbon\Carbon;
use Guanguans\Notify\Factory;
use Guanguans\Notify\Messages\EmailMessage;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

class EmailNoticeTask extends AbstractTask
{
    public function __construct()
    {
        //
    }

    public function start()
    {
        $setting = Base::setting('emailSetting');
        // 任务通知
        if ($setting['notice'] === 'open') {
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
                            $this->taskEmail($task, 0);
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
                            $this->taskEmail($task, 1);
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
                            $this->taskEmail($task, 2);
                        }
                    });
            }
        }
        // 消息通知
        if ($setting['notice_msg'] === 'open') {
            $userMinute = intval($setting['msg_unread_user_minute']);
            $groupMinute = intval($setting['msg_unread_group_minute']);
            \DB::statement("SET SQL_MODE=''");
            $builder = WebSocketDialogMsg::select(['web_socket_dialog_msgs.*', 'r.id as r_id', 'r.userid as r_userid'])
                ->join('web_socket_dialog_msg_reads as r', 'web_socket_dialog_msgs.id', '=', 'r.msg_id')
                ->whereNull("r.read_at")
                ->where("r.email", 0);
            if ($userMinute > -1) {
                $builder->clone()
                    ->where("web_socket_dialog_msgs.dialog_type", "user")
                    ->whereBetween("web_socket_dialog_msgs.created_at", [
                        Carbon::now()->subMinutes($userMinute + 10),
                        Carbon::now()->subMinutes($userMinute)
                    ])
                    ->groupBy('r_userid')
                    ->chunkById(100, function ($rows) {
                        $this->unreadMsgEmail($rows, "user");
                    });
            }
            if ($groupMinute > -1) {
                $builder->clone()
                    ->where("web_socket_dialog_msgs.dialog_type", "group")
                    ->whereBetween("web_socket_dialog_msgs.created_at", [
                        Carbon::now()->subMinutes($groupMinute + 10),
                        Carbon::now()->subMinutes($groupMinute)
                    ])
                    ->groupBy('r_userid')
                    ->chunkById(100, function ($rows) {
                        $this->unreadMsgEmail($rows, "group");
                    });
            }
        }
    }

    /**
     * 任务过期前、超期后提醒
     * @param ProjectTask $task
     * @param int $type
     * @return void
     */
    private function taskEmail(ProjectTask $task, int $type)
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

        /** @var User $user */
        foreach ($users as $user) {
            $data = [
                'type' => $type,
                'userid' => $user->userid,
                'task_id' => $task->id,
            ];
            $emailLog = ProjectTaskMailLog::where($data)->exists();
            if ($emailLog) {
                continue;
            }
            try {
                if (!Base::isEmail($user->email)) {
                    throw new \Exception("User email '{$user->email}' address error");
                }
                $subject = match ($type) {
                    1 => env('APP_NAME') . " 任务提醒",
                    2 => env('APP_NAME') . " 任务过期提醒",
                    default => env('APP_NAME') . " 任务开始提醒",
                };
                $content = view('email.task', [
                    'type' => str_replace([0, 1, 2], ['start', 'before', 'after'], $type),
                    'user' => $user,
                    'task' => $task,
                    'setting' => $setting,
                ])->render();
                Factory::mailer()
                    ->setDsn("smtp://{$setting['account']}:{$setting['password']}@{$setting['smtp_server']}:{$setting['port']}?verify_peer=0")
                    ->setMessage(EmailMessage::create()
                        ->from(env('APP_NAME', 'Task') . " <{$setting['account']}>")
                        ->to($user->email)
                        ->subject($subject)
                        ->html($content))
                    ->send();
                $data['is_send'] = 1;
            } catch (\Throwable $e) {
                $data['send_error'] = $e->getMessage();
            }
            $data['email'] = $user->email;
            ProjectTaskMailLog::createInstance($data)->save();
        }
    }

    /**
     * 未读消息通知
     * @param $rows
     * @param $dialogType
     * @return void
     */
    private function unreadMsgEmail($rows, $dialogType)
    {
        $array = $rows->groupBy('r_userid');
        foreach ($array as $userid => $data) {
            $data = WebSocketDialogMsg::select(['web_socket_dialog_msgs.*', 'r.id as r_id', 'r.userid as r_userid'])
                ->join('web_socket_dialog_msg_reads as r', 'web_socket_dialog_msgs.id', '=', 'r.msg_id')
                ->whereNull("r.read_at")
                ->where("r.email", 0)
                ->where("web_socket_dialog_msgs.dialog_type", $dialogType)
                ->take(100)
                ->get();
            if (empty($data)) {
                continue;
            }
            $user = User::find($userid);
            if (empty($user)) {
                continue;
            }
            if (!Base::isEmail($user->email)) {
                continue;
            }
            $setting = Base::setting('emailSetting');
            $msgType = $dialogType === "group" ? "群聊" : "个人";
            $subject = env('APP_NAME') . " 未读{$msgType}消息提醒";
            $content = view('email.unread', [
                'type' => 'head',
                'nickname' => $user->nickname,
                'msgType' => $msgType,
                'count' => count($data),
            ])->render();
            $lists = $data->groupBy('dialog_id');
            /** @var WebSocketDialogMsg[] $items */
            foreach ($lists as $items) {
                $dialogId = 0;
                $dialogName = null;
                foreach ($items as $item) {
                    $item->cancelAppend();
                    $item->userInfo = User::userid2basic($item->userid);
                    $item->preview = $item->previewMsg(true);
                    if (empty($dialogId)) {
                        $dialogId = $item->dialog_id;
                    }
                    if ($dialogName === null) {
                        if ($dialogType === "user" && $item->userInfo) {
                            if ($item->userInfo->profession) {
                                $dialogName = $item->userInfo->nickname . " ({$item->userInfo->profession})";
                            } else {
                                $dialogName = $item->userInfo->nickname;
                            }
                        } else {
                            $dialogName = $item->webSocketDialog?->getGroupName();
                        }
                    }
                }
                $content .= view('email.unread', [
                    'type' => 'content',
                    'dialogUrl' => config("app.url") . "/manage/messenger/{$dialogId}",
                    'dialogName' => $dialogName,
                    'unread' => count($items),
                    'items' => $items,
                ])->render();
                $content = str_replace("{{RemoteURL}}", config("app.url") . "/", $content);
            }
            try {
                Factory::mailer()
                    ->setDsn("smtp://{$setting['account']}:{$setting['password']}@{$setting['smtp_server']}:{$setting['port']}?verify_peer=0")
                    ->setMessage(EmailMessage::create()
                        ->from(env('APP_NAME', 'Task') . " <{$setting['account']}>")
                        ->to($user->email)
                        ->subject($subject)
                        ->html($content))
                    ->send();
            } catch (\Throwable $e) {
                info("unreadMsgEmail: " . $e->getMessage());
            }
            WebSocketDialogMsgRead::whereIn('id', $data->pluck('r_id'))->update([
                'email' => 1
            ]);
        }
    }
}
