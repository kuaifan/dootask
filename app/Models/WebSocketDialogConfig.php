<?php

namespace App\Models;

/**
 * App\Models\WebSocketDialogConfig
 *
 * @property int $id
 * @property int $dialog_id 对话ID
 * @property int $userid 用户ID
 * @property string $type 配置类型
 * @property string|null $value 配置值
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\WebSocketDialog|null $dialog
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogConfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogConfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogConfig query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogConfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogConfig whereDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogConfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogConfig whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogConfig whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogConfig whereUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogConfig whereValue($value)
 * @mixin \Eloquent
 */
class WebSocketDialogConfig extends AbstractModel
{
    /**
     * 可以批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'dialog_id',
        'userid',
        'type',
        'value',
    ];

    /**
     * 获取关联的对话
     */
    public function dialog()
    {
        return $this->belongsTo(WebSocketDialog::class, 'dialog_id');
    }

    /**
     * 获取关联的用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userid');
    }
}
