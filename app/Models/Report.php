<?php

namespace App\Models;

use App\Exceptions\ApiException;
use Carbon\Carbon;
use Carbon\Traits\Creator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use JetBrains\PhpStorm\Pure;

/**
 * App\Models\Report
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $title 标题
 * @property string $type 汇报类型
 * @property int $userid
 * @property string $content
 * @property string $sign 汇报唯一标识
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ReportReceive[] $Receives
 * @property-read int|null $receives_count
 * @property-read mixed $receives
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $receivesUser
 * @property-read int|null $receives_user_count
 * @property-read \App\Models\User|null $sendUser
 * @method static Builder|Report newModelQuery()
 * @method static Builder|Report newQuery()
 * @method static Builder|Report query()
 * @method static Builder|Report whereContent($value)
 * @method static Builder|Report whereCreatedAt($value)
 * @method static Builder|Report whereId($value)
 * @method static Builder|Report whereSign($value)
 * @method static Builder|Report whereTitle($value)
 * @method static Builder|Report whereType($value)
 * @method static Builder|Report whereUpdatedAt($value)
 * @method static Builder|Report whereUserid($value)
 * @mixin \Eloquent
 */
class Report extends AbstractModel
{
    use HasFactory;

    const WEEKLY = "weekly";
    const DAILY = "daily";

    protected $fillable = [
        "title",
        "type",
        "userid",
        "content",
    ];

    protected $appends = [
        'receives',
    ];

    public function Receives(): HasMany
    {
        return $this->hasMany(ReportReceive::class, "rid");
    }

    public function receivesUser(): BelongsToMany
    {
        return $this->belongsToMany(User::class, ReportReceive::class, "rid", "userid")
            ->withPivot("receive_time", "read");
    }

    public function sendUser()
    {
        return $this->hasOne(User::class, "userid", "userid");
    }

    public function getTypeAttribute($value): string
    {
        return match ($value) {
            Report::WEEKLY => "周报",
            Report::DAILY => "日报",
            default => "",
        };
    }

    public function getContentAttribute($value): string
    {
        return htmlspecialchars_decode($value);
    }

    public function getReceivesAttribute()
    {
        if (!isset($this->appendattrs['receives'])) {
            $this->appendattrs['receives'] = empty( $this->receivesUser ) ? [] : array_column($this->receivesUser->toArray(), "userid");
        }
        return $this->appendattrs['receives'];
    }

    /**
     * 获取单条记录
     * @param $id
     * @return Report|Builder|Model|object|null
     * @throw ApiException
     */
    public static function getOne($id)
    {
        $one = self::whereId($id)->first();
        if (empty($one))
            throw new ApiException("记录不存在");
        return $one;
    }

    /**
     * 获取最后一条提交记录
     * @param User|null $user
     * @return Builder|Model|\Illuminate\Database\Query\Builder|object
     */
    public static function getLastOne(User $user = null)
    {
        $user === null && $user = User::auth();
        $one = self::whereUserid($user->userid)->orderByDesc("created_at")->first();
        if ( empty($one) )
            throw new ApiException("记录不存在");
        return $one;
    }

    /**
     * 生成唯一标识
     * @param $type
     * @param $offset
     * @param Carbon|null $time
     * @return string
     */
    public static function generateSign($type, $offset, Carbon $time = null): string
    {
        $user = User::auth();
        $now_dt = $time === null ? Carbon::now() : $time;
        $time_s = match ($type) {
            Report::WEEKLY => function() use ($now_dt, $offset) {
                // 如果设置了周期偏移量
                empty( $offset ) || $now_dt->subWeeks( abs( $offset ) );
                $now_dt->startOfWeek(); // 设置为当周第一天
                return $now_dt->year . $now_dt->weekOfYear;
            },
            Report::DAILY => function() use ($now_dt, $offset) {
                // 如果设置了周期偏移量
                empty( $offset ) || $now_dt->subDays( abs( $offset ) );
                return $now_dt->format("Ymd");
            },
            default => "",
        };
        return $user->userid . ( is_callable($time_s) ? $time_s() : "" );
    }
}
