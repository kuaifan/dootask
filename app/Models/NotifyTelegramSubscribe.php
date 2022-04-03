<?php

namespace App\Models;

use App\Module\Telegram;

/**
 * App\Models\NotifyTelegramSubscribe
 *
 * @property int $id
 * @property int|null $userid
 * @property string|null $chat_id
 * @property int|null $subscribe 是否订阅
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyTelegramSubscribe newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyTelegramSubscribe newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyTelegramSubscribe query()
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyTelegramSubscribe whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyTelegramSubscribe whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyTelegramSubscribe whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyTelegramSubscribe whereSubscribe($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyTelegramSubscribe whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotifyTelegramSubscribe whereUserid($value)
 * @mixin \Eloquent
 */
class NotifyTelegramSubscribe extends AbstractModel
{

    /**
     * 异步发送文本消息
     * @param $text
     */
    public function sendTextAsync($text)
    {
        Telegram::create()
            ->setText($text)
            ->setTo($this->chat_id)
            ->sendAsync();
    }

    /**
     * 获取订阅
     * @param $chat_id
     * @return AbstractModel|AbstractModel[]|NotifyTelegramSubscribe|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|object|null
     */
    public static function subscribe($chat_id)
    {
        if (empty($chat_id)) {
            return null;
        }
        $row = self::whereChatId($chat_id)->first();
        if (empty($row)) {
            $row = self::createInstance([
                'chat_id' => $chat_id,
            ]);
            $row->save();
        }
        return $row;
    }
}
