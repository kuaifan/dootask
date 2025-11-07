<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * App\Models\WebSocketDialogMsgRead
 *
 * @property int $id
 * @property int|null $dialog_id 对话ID
 * @property int|null $msg_id 消息ID
 * @property int|null $userid 接收会员ID
 * @property int|null $mention 是否提及（被@）
 * @property int|null $silence 是否免打扰：0否，1是
 * @property int|null $email 是否发了邮件
 * @property int|null $after 在阅读之后才添加的记录
 * @property int|null $dot 红点标记
 * @property \Illuminate\Support\Carbon|null $read_at 阅读时间
 * @property-read \App\Models\WebSocketDialogMsg|null $webSocketDialogMsg
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead whereAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead whereDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead whereDot($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead whereMention($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead whereMsgId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead whereSilence($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsgRead whereUserid($value)
 * @mixin \Eloquent
 */
class WebSocketDialogMsgRead extends AbstractModel
{
    function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->timestamps = false;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function webSocketDialogMsg(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(WebSocketDialogMsg::class, 'id', 'msg_id');
    }

    /**
     * 强制标记成阅读
     * @param $dialogId
     * @param $userId
     * @return void
     */
    public static function forceRead($dialogId, $userId)
    {
        self::whereDialogId($dialogId)
            ->whereUserid($userId)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);
    }

    /**
     * 仅标记成阅读
     * @param $list
     * @return void
     */
    public static function onlyMarkRead($list)
    {
        if (empty($list)) {
            return;
        }

        $collection = collect($list);
        if ($collection->isEmpty()) {
            return;
        }

        $now = Carbon::now();
        $ids = [];
        $msgCounts = [];

        /** @var WebSocketDialogMsgRead $item */
        foreach ($collection as $item) {
            $ids[] = $item->id;
            if ($item->msg_id) {
                $msgCounts[$item->msg_id] = ($msgCounts[$item->msg_id] ?? 0) + 1;
            }
        }

        if (!empty($ids)) {
            DB::table((new self())->getTable())
                ->whereIn('id', $ids)
                ->whereNull('read_at')
                ->update(['read_at' => $now]);
        }

        if (!empty($msgCounts)) {
            $cases = [];
            $bindings = [];
            foreach ($msgCounts as $msgId => $num) {
                $cases[] = 'WHEN ? THEN ?';
                $bindings[] = $msgId;
                $bindings[] = $num;
            }
            $msgIds = array_keys($msgCounts);
            $bindings = array_merge($bindings, $msgIds);
            $placeholders = implode(',', array_fill(0, count($msgIds), '?'));
            $table = DB::getTablePrefix() . (new WebSocketDialogMsg())->getTable();
            $sql = "UPDATE {$table} SET `read` = `read` + CASE `id` " . implode(' ', $cases) . " END WHERE `deleted_at` IS NULL AND `id` IN ({$placeholders})";
            DB::update($sql, $bindings);
        }
    }

    /**
     * 标记指定会话的历史消息为已读
     * @param int $dialogId
     * @param int $sessionId
     * @param int $chunkSize
     * @return void
     */
    public static function markSessionMessagesAsRead(int $dialogId, int $sessionId, int $chunkSize = 100): void
    {
        if ($dialogId <= 0 || $sessionId <= 0) {
            return;
        }

        self::whereDialogId($dialogId)
            ->whereNull('read_at')
            ->whereIn('msg_id', function ($query) use ($dialogId, $sessionId) {
                $query->select('id')
                    ->from((new WebSocketDialogMsg())->getTable())
                    ->where('dialog_id', $dialogId)
                    ->where('session_id', $sessionId);
            })
            ->chunkById($chunkSize, function ($list) {
                self::onlyMarkRead($list);
            });
    }
}
