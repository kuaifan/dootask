<?php
namespace App\Tasks;

use App\Models\UmengAlias;
use App\Models\WebSocketDialogMsgRead;
use App\Module\Base;
use Cache;
use Hhxsv5\LaravelS\Swoole\Task\Task;

/**
 * 推送友盟消息
 */
class PushUmengMsg extends AbstractTask
{
    protected $userid = 0;
    protected $array = [];
    protected $endPush = [];     // 需要在 end() 方法中处理的延迟推送列表

    /**
     * @param array|int $userid
     * @param array $array
     */
    public function __construct($userid, $array = [])
    {
        parent::__construct(...func_get_args());
        $this->userid = $userid;
        $this->array = is_array($array) ? $array : [];
    }

    public function start()
    {
        if (empty($this->userid) || empty($this->array)) {
            return;
        }
        $setting = Base::setting('appPushSetting');
        if ($setting['push'] !== 'open') {
            return;
        }

        // 消息ID
        $msgId = isset($this->array['id']) ? intval($this->array['id']) : 0;

        // 处理用户列表
        $userids = is_array($this->userid) ? $this->userid : [$this->userid];
        $directPushUsers = [];   // 直接推送的用户
        $delayedPushUsers = [];  // 需要延迟推送的用户

        foreach ($userids as $uid) {
            if ($this->getDelay() > 0) {
                // 已经延迟过，检查消息是否已读
                if ($msgId > 0) {
                    $isRead = WebSocketDialogMsgRead::whereMsgId($msgId)
                        ->whereUserid($uid)
                        ->whereNotNull('read_at')
                        ->exists();
                    if ($isRead) {
                        // 已读，跳过推送
                        continue;
                    }
                }
                // 未读或无法判断，执行推送
                $directPushUsers[] = $uid;
            } else {
                // 首次推送，检查 PC 端是否活跃
                $lastActive = Cache::get("user_pc_active:{$uid}");
                $isPcActive = $lastActive && (time() - $lastActive) < 60;

                if ($isPcActive) {
                    // PC 端活跃，需要延迟推送
                    $delayedPushUsers[] = $uid;
                } else {
                    // PC 端不活跃，直接推送
                    $directPushUsers[] = $uid;
                }
            }
        }

        // 直接推送
        if ($directPushUsers) {
            UmengAlias::pushMsgToUserid($directPushUsers, $this->array);
        }

        // 创建延迟推送任务
        if ($delayedPushUsers) {
            $this->endPush[] = [
                'userid' => $delayedPushUsers,
                'array' => $this->array,
            ];
        }
    }

    public function end()
    {
        if (empty($this->endPush)) {
            return;
        }
        foreach ($this->endPush as $item) {
            $task = new PushUmengMsg($item['userid'], $item['array']);
            $task->delay(10);
            Task::deliver($task);
        }
    }
}
