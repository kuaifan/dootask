<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Module\Base;
use Carbon\Carbon;
use Carbon\Traits\Creator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ReportReceive> $Receives
 * @property-read int|null $receives_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ReportAnalysis> $aiAnalyses
 * @property-read int|null $ai_analyses_count
 * @property-read \App\Models\ReportAnalysis|null $aiAnalysis
 * @property-read mixed $receives
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $receivesUser
 * @property-read int|null $receives_user_count
 * @property-read \App\Models\User|null $sendUser
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static Builder|Report newModelQuery()
 * @method static Builder|Report newQuery()
 * @method static Builder|Report query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
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
    public const LIST_FIELDS = [
        'id',
        'title',
        'type',
        'userid',
        'sign',
        'created_at',
        'updated_at',
    ];

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
            ->withPivot("receive_at", "read");
    }

    public function aiAnalyses(): HasMany
    {
        return $this->hasMany(ReportAnalysis::class, 'rid');
    }

    public function aiAnalysis(): HasOne
    {
        return $this->hasOne(ReportAnalysis::class, 'rid');
    }

    public function sendUser()
    {
        return $this->hasOne(User::class, "userid", "userid");
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
     * 获取汇报内容
     * @param $id
     * @return self|null
     */
    public static function idOrCodeToContent($id)
    {
        if (Base::isNumber($id)) {
            return self::find($id);
        } elseif ($id) {
            $reportLink = ReportLink::whereCode($id)->first();
            if ($reportLink) {
                return self::find($reportLink->rid);
            }
        }
        return null;
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
                return now()->year . $now_dt->weekOfYear;
            },
            Report::DAILY => function() use ($now_dt, $offset) {
                // 如果设置了周期偏移量
                empty( $offset ) || $now_dt->subDays( abs( $offset ) );
                return now()->format("Ymd");
            },
            default => "",
        };
        return $user->userid . ( is_callable($time_s) ? $time_s() : "" );
    }
}
