<?php

namespace App\Tasks;

use App\Models\Setting;
use App\Models\User;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgRead;
use App\Module\Base;
use App\Module\Doo;
use App\Module\Timer;
use Carbon\Carbon;
use Guanguans\Notify\Factory;
use Guanguans\Notify\Messages\EmailMessage;

/**
 * 未读消息邮件通知任务
 * 根据设置的时间范围，将未读消息通过邮件发送给用户
 */
class EmailNoticeTask extends AbstractTask
{
    /** @var array 允许发送通知的消息类型 */
    private const ALLOWED_MSG_TYPES = ["text", "file", "record", "meeting"];

    /** @var int 每批处理的数据量 */
    private const CHUNK_SIZE = 100;

    /** @var array 邮件相关设置 */
    private array $emailSetting;

    public function __construct()
    {
        parent::__construct();
        $this->emailSetting = Base::setting('emailSetting');
    }

    public function start()
    {
        // 检查是否可以发送邮件
        if (!$this->canSendEmails()) {
            return;
        }

        \DB::statement("SET SQL_MODE=''");

        // 分别处理用户消息和群组消息
        $this->processMessages('user');
        $this->processMessages('group');
    }

    /**
     * 检查是否可以发送邮件通知
     * 需要开启通知功能且在指定的时间范围内
     */
    private function canSendEmails(): bool
    {
        if ($this->emailSetting['notice_msg'] !== 'open') {
            return false;
        }

        $timeRanges = is_array($this->emailSetting['msg_unread_time_ranges'])
            ? $this->emailSetting['msg_unread_time_ranges']
            : [];

        return Timer::isTimeInRanges($timeRanges);
    }

    /**
     * 处理指定类型的未读消息
     * @param string $dialogType 对话类型：user|group
     */
    private function processMessages(string $dialogType): void
    {
        // 获取未读时间限制（分钟）
        $minute = $dialogType === 'user'
            ? intval($this->emailSetting['msg_unread_user_minute'])
            : intval($this->emailSetting['msg_unread_group_minute']);

        if ($minute <= -1) {
            return;
        }

        // 获取上次处理时间
        $lastProcessKey = 'time' . ucfirst($dialogType);
        $startTime = Base::settingFind('emailLastNotice', $lastProcessKey);
        $startTime = $startTime ? Carbon::parse($startTime) : Carbon::today();

        // 计算本次处理的结束时间（当前时间减去未读时间限制）
        $endTime = Carbon::now()->subMinutes($minute);

        // 如果开始时间晚于结束时间，则不处理
        if ($startTime->isAfter($endTime)) {
            return;
        }

        // 获取需要处理的用户列表
        $query = WebSocketDialogMsgRead::select('web_socket_dialog_msg_reads.userid')
            ->join('web_socket_dialog_msgs as m', 'm.id', '=', 'web_socket_dialog_msg_reads.msg_id')
            ->whereNull('web_socket_dialog_msg_reads.read_at')
            ->where('web_socket_dialog_msg_reads.silence', 0)
            ->where('web_socket_dialog_msg_reads.email', 0)
            ->where('m.dialog_type', $dialogType)
            ->whereBetween('m.created_at', [$startTime, $endTime])
            ->whereIn('m.type', self::ALLOWED_MSG_TYPES)
            ->orderBy('web_socket_dialog_msg_reads.userid')
            ->groupBy('web_socket_dialog_msg_reads.userid');

        // 分批处理用户的未读消息
        $query->chunk(self::CHUNK_SIZE, function($users) use ($dialogType, $startTime, $endTime) {
            foreach ($users as $userData) {
                $this->sendUserEmail($userData->userid, $dialogType, $startTime, $endTime);
            }
        });

        // 更新处理时间
        Base::setting('emailLastNotice', [
            $lastProcessKey => $endTime->toDateTimeString()
        ]);
    }

    /**
     * 发送用户的未读消息邮件
     */
    private function sendUserEmail(int $userId, string $dialogType, Carbon $startTime, Carbon $endTime): void
    {
        // 验证用户
        $user = User::whereDisableAt(null)->find($userId);
        if (!$user || $user->bot || !is_null($user->disable_at) || !Base::isEmail($user->email)) {
            return;
        }

        // 获取未读消息
        $messages = $this->getUnreadMessages($userId, $dialogType, $startTime, $endTime);
        if ($messages->isEmpty()) {
            return;
        }

        // 设置用户语言
        Doo::setLanguage($user->lang);

        // 按对话分组并生成邮件内容
        $messagesByDialog = $messages->groupBy('dialog_id');
        $emailContent = $this->generateEmailContent($user, $messagesByDialog, $dialogType);

        try {
            // 发送邮件
            $this->sendEmail($user, $emailContent);
            // 标记消息已发送邮件
            WebSocketDialogMsgRead::whereIn('id', $messages->pluck('r_id'))
                ->update(['email' => 1]);
        } catch (\Throwable $e) {
            info("Email send failed for user {$userId}: " . $e->getMessage());
        }
    }

    /**
     * 获取用户的未读消息
     */
    private function getUnreadMessages($userId, $dialogType, Carbon $startTime, Carbon $endTime)
    {
        return WebSocketDialogMsg::select([
            'web_socket_dialog_msgs.*',
            'r.id as r_id',
            'r.userid as r_userid'
        ])
            ->join('web_socket_dialog_msg_reads as r', 'web_socket_dialog_msgs.id', '=', 'r.msg_id')
            ->where([
                'r.userid' => $userId,
                'r.silence' => 0,
                'r.email' => 0,
                'web_socket_dialog_msgs.dialog_type' => $dialogType
            ])
            ->whereNull('r.read_at')
            ->whereBetween('web_socket_dialog_msgs.created_at', [$startTime, $endTime])
            ->whereIn('web_socket_dialog_msgs.type', self::ALLOWED_MSG_TYPES)
            ->orderBy('web_socket_dialog_msgs.created_at')
            ->limit(self::CHUNK_SIZE)
            ->get();
    }

    /**
     * 生成邮件内容
     */
    private function generateEmailContent($user, $messagesByDialog, $dialogType)
    {
        $msgType = $dialogType === "group" ? "群聊" : "单聊";

        // 生成邮件头部
        $content = view('email.unread', [
            'type' => 'head',
            'title' => Doo::translate(sprintf('%s，您好。', $user->nickname)),
            'desc' => Doo::translate(sprintf('您有%d条未读%s消息，请及时处理。', count($messagesByDialog), $msgType)),
        ])->render();

        $subject = null;
        // 处理每个对话的消息
        foreach ($messagesByDialog as $items) {
            $dialogId = 0;
            $dialogName = null;

            foreach ($items as $item) {
                $item->cancelAppend();
                $item->userInfo = User::userid2basic($item->userid, ['lang']);
                Doo::setLanguage($item->userInfo->lang);
                $item->preview = WebSocketDialogMsg::previewMsg($item, true);
                $item->preview = str_replace('<p>', '<p style="margin:0;padding:0">', $item->preview);

                if (empty($dialogId)) {
                    $dialogId = $item->dialog_id;
                }
                if ($dialogName === null) {
                    $dialogName = $this->getDialogName($item, $dialogType);
                }
            }

            // 生成邮件主题
            if ($subject === null) {
                $subject = count($messagesByDialog) > 1
                    ? sprintf('来自%d个%s未读消息提醒', count($messagesByDialog), $msgType)
                    : sprintf('来自%s未读消息提醒', $dialogName);
            }

            // 添加对话内容
            $content .= view('email.unread', [
                'type' => 'content',
                'dialogUrl' => '',  // 不显示回复消息按钮
                // 'dialogUrl' => config("app.url") . "/manage/messenger?dialog_id={$dialogId}",
                'dialogName' => trim($dialogName),
                'title' => Doo::translate(sprintf('%d条未读信息', count($items))),
                'button' => Doo::translate('回复消息'),
                'unread' => count($items),
                'items' => $items,
            ])->render();
        }

        $content = str_replace("{{RemoteURL}}", config("app.url") . "/", $content);

        return [
            'subject' => Doo::translate($subject),
            'content' => $content
        ];
    }

    /**
     * 获取对话名称
     */
    private function getDialogName($message, $dialogType)
    {
        if ($dialogType === "user" && $message->userInfo) {
            return $message->userInfo->profession
                ? sprintf('%s (%s) ', $message->userInfo->nickname, $message->userInfo->profession)
                : $message->userInfo->nickname;
        }
        return $message->webSocketDialog?->getGroupName();
    }

    /**
     * 发送邮件
     */
    private function sendEmail($user, $emailData): void
    {
        Setting::validateAddr($user->email, function($to) use ($emailData) {
            Factory::mailer()
                ->setDsn(sprintf(
                    'smtp://%s:%s@%s:%s?verify_peer=0',
                    $this->emailSetting['account'],
                    $this->emailSetting['password'],
                    $this->emailSetting['smtp_server'],
                    $this->emailSetting['port']
                ))
                ->setMessage(EmailMessage::create()
                    ->from(sprintf('%s <%s>', Base::settingFind('system', 'system_alias', 'Task'), $this->emailSetting['account']))
                    ->to($to)
                    ->subject($emailData['subject'])
                    ->html($emailData['content']))
                ->send();
        });
    }

    public function end()
    {
        // 任务结束处理
    }
}
