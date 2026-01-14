<?php

namespace App\Models;

/**
 * App\Models\UserAppSort
 *
 * @property int $id
 * @property int $userid 用户ID
 * @property array|null $sorts 排序配置
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|UserAppSort newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserAppSort newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserAppSort query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|UserAppSort whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserAppSort whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserAppSort whereSorts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserAppSort whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserAppSort whereUserid($value)
 * @mixin \Eloquent
 */
class UserAppSort extends AbstractModel
{
    protected $fillable = [
        'userid',
        'sorts',
    ];

    protected $casts = [
        'sorts' => 'array',
    ];

    /**
     * 获取用户排序配置
     * @param int $userid
     * @return array
     */
    public static function getSorts(int $userid): array
    {
        $record = static::whereUserid($userid)->first();
        if (!$record) {
            return self::normalizeSorts([]);
        }
        return self::normalizeSorts($record->sorts);
    }

    /**
     * 保存排序配置
     * @param int $userid
     * @param array $sorts
     * @return static
     */
    public static function saveSorts(int $userid, array $sorts): self
    {
        return static::updateOrCreate(
            ['userid' => $userid],
            ['sorts' => self::normalizeSorts($sorts)]
        );
    }

    /**
     * 规范化排序数据
     * @param mixed $sorts
     * @return array
     */
    public static function normalizeSorts($sorts): array
    {
        $result = [
            'base' => [],
            'admin' => [],
        ];
        if (!is_array($sorts)) {
            return $result;
        }
        foreach (['base', 'admin'] as $group) {
            $list = $sorts[$group] ?? [];
            if (!is_array($list)) {
                $list = [];
            }
            $normalized = [];
            foreach ($list as $value) {
                if (!is_string($value)) {
                    continue;
                }
                $value = trim($value);
                if ($value === '') {
                    continue;
                }
                $normalized[] = $value;
            }
            $result[$group] = array_values(array_unique($normalized));
        }
        return $result;
    }
}
