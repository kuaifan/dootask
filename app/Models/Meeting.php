<?php

namespace App\Models;

use Cache;
use App\Module\Base;
use Illuminate\Support\Carbon;

/**
 * App\Models\Meeting
 *
 * @property int $id
 * @property string|null $meetingid 会议ID，不是数字
 * @property string|null $name 会议主题
 * @property string|null $channel 频道
 * @property int|null $userid 创建人
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $end_at
 * @property Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting whereMeetingid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Meeting whereUserid($value)
 * @mixin \Eloquent
 */
class Meeting extends AbstractModel
{
    const CACHE_KEY = 'meeting_share_link_code';
    const CACHE_EXPIRED_TIME = 6; // 小时

    /**
     * 获取分享链接
     * @return mixed
     */
    public function getShareLink()
    {
        $code = base64_encode("{$this->meetingid}" . Base::generatePassword());
        Cache::put(self::CACHE_KEY . '_' . $code, [
            'id' => $this->id,
            'meetingid' => $this->meetingid,
            'channel' => $this->channel,
        ], Carbon::now()->addHours(self::CACHE_EXPIRED_TIME));
        return Base::fillUrl("meeting/{$this->meetingid}/" . $code);
    }

    /**
     * 获取分享信息
     * @return mixed
     */
    public static function getShareInfo($code)
    {
        if (Cache::has(self::CACHE_KEY . '_' . $code)) {
            return Cache::get(self::CACHE_KEY . '_' . $code);
        }
        return null;
    }

    /**
     * 保存访客信息
     * @return void
     */
    public static function setTouristInfo($data)
    {
        Cache::put(Meeting::CACHE_KEY . '_' . $data['uid'], [
            'uid' => $data['uid'],
            'userimg' => $data['userimg'],
            'nickname' => $data['nickname'],
        ], Carbon::now()->addHours(self::CACHE_EXPIRED_TIME));
    }

    /**
     * 获取访客信息
     * @return mixed
     */
    public static function getTouristInfo($touristId)
    {
        if (Cache::has(Meeting::CACHE_KEY . '_' . $touristId)) {
            return Cache::get(Meeting::CACHE_KEY . '_' . $touristId);
        }
        return null;
    }
}
