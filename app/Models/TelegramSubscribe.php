<?php

namespace App\Models;

use App\Module\Telegram;

/**
 * App\Models\TelegramSubscribe
 *
 * @property int $id
 * @property int|null $userid
 * @property string|null $chat_id
 * @property int|null $subscribe 是否订阅
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramSubscribe newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramSubscribe newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramSubscribe query()
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramSubscribe whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramSubscribe whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramSubscribe whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramSubscribe whereSubscribe($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramSubscribe whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramSubscribe whereUserid($value)
 * @mixin \Eloquent
 */
class TelegramSubscribe extends AbstractModel
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
     * @return AbstractModel|AbstractModel[]|TelegramSubscribe|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|object|null
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
