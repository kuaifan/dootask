<?php

namespace App\Models;

use App\Module\Base;
use Carbon\Carbon;
use Hedeqiang\UMeng\Android;
use Hedeqiang\UMeng\IOS;

/**
 * App\Models\UmengAlias
 *
 * @property int $id
 * @property int|null $userid 会员ID
 * @property string|null $alias 别名
 * @property string|null $platform 平台类型
 * @property string|null $device 设备类型
 * @property string|null $ua userAgent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|UmengAlias newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UmengAlias newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UmengAlias query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|UmengAlias whereAlias($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UmengAlias whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UmengAlias whereDevice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UmengAlias whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UmengAlias wherePlatform($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UmengAlias whereUa($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UmengAlias whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UmengAlias whereUserid($value)
 * @mixin \Eloquent
 */
class UmengAlias extends AbstractModel
{
    protected $table = 'umeng_alias';

    /**
     * 推送内容处理
     * @param $string
     * @return string
     */
    private static function specialCharacters($string)
    {
        return str_replace(["\r\n", "\r", "\n"], '', $string);
    }

    /**
     * 获取推送配置
     * @return array|false
     */
    public static function getPushConfig()
    {
        $setting = Base::setting('appPushSetting');
        if ($setting['push'] !== 'open') {
            return false;
        }
        $config = [];
        if ($setting['ios_key']) {
            $config['iOS'] = [
                'appKey' => $setting['ios_key'],
                'appMasterSecret' => $setting['ios_secret'],
                'production_mode' => true,
            ];
        }
        if ($setting['android_key']) {
            $config['Android'] = [
                'appKey' => $setting['android_key'],
                'appMasterSecret' => $setting['android_secret'],
                'production_mode' => true,
            ];
        }
        return $config;
    }

    /**
     * 推送消息
     * @param string $alias
     * @param string $platform
     * @param array $array [title, subtitle, body, description, extra, seconds, badge]
     * @return array|false
     */
    public static function pushMsgToAlias($alias, $platform, $array)
    {
        $config = self::getPushConfig();
        if ($config === false) {
            return false;
        }
        //
        $title = self::specialCharacters($array['title'] ?: '');        // 标题
        $subtitle = self::specialCharacters($array['subtitle'] ?: '');  // 副标题（iOS）
        $body = self::specialCharacters($array['body'] ?: '');          // 通知内容
        $description = $array['description'] ?: 'no description';               // 描述
        $extra = is_array($array['extra']) ? $array['extra'] : [];              // 额外参数
        $seconds = intval($array['seconds']) ?: 86400;                          // 有效时间（单位：秒）
        $badge = intval($array['badge']) ?: 0;                                  // 角标数（iOS）
        //
        switch ($platform) {
            case 'ios':
                if (!isset($config['iOS'])) {
                    return false;
                }
                $ios = new IOS($config);
                return $ios->send([
                    'description' => $description,
                    'payload' => array_merge([
                        'aps' => [
                            'alert' => [
                                'title' => $title,
                                'subtitle' => $subtitle,
                                'body' => $body,
                            ],
                            'sound' => 'default',
                            'badge' => $badge,
                        ],
                    ], $extra),
                    'type' => 'customizedcast',
                    'alias_type' => 'userid',
                    'alias' => $alias,
                    'policy' => [
                        'expire_time' => Carbon::now()->addSeconds($seconds)->toDateTimeString(),
                    ],
                ]);

            case 'android':
                if (!isset($config['Android'])) {
                    return false;
                }
                $android = new Android($config);
                return $android->send([
                    'description' => $description,
                    'payload' => array_merge([
                        'display_type' => 'notification',
                        'body' => [
                            'ticker' => $title,
                            'text' => $body,
                            'title' => $title,
                            'after_open' => 'go_app',
                            'play_sound' => true,
                        ],
                    ], $extra),
                    'type' => 'customizedcast',
                    'alias_type' => 'userid',
                    'alias' => $alias,
                    'mipush' => true,
                    'mi_activity' => 'app.eeui.umeng.activity.MfrMessageActivity',
                    'policy' => [
                        'expire_time' => Carbon::now()->addSeconds($seconds)->toDateTimeString(),
                    ],
                    'channel_properties' => [
                        'vivo_category' => 'IM',
                        'huawei_channel_importance' => 'NORMAL',
                        'huawei_channel_category' => 'IM',
                        'channel_fcm' => 1,
                    ],
                ]);

            default:
                return false;
        }
    }

    /**
     * 推送给指定会员
     * @param array|int $userid
     * @param array $array
     * @return void
     */
    public static function pushMsgToUserid($userid, $array)
    {
        $builder = self::select(['id', 'platform', 'alias', 'userid'])->where('updated_at', '>', Carbon::now()->subMonth());
        if (is_array($userid)) {
            $builder->whereIn('userid', $userid);
        } elseif (Base::isNumber($userid)) {
            $builder->whereUserid($userid);
        }
        $builder
            ->orderByDesc('updated_at')
            ->chunkById(100, function ($datas) use ($array) {
                $uids = $datas->groupBy('userid');
                foreach ($uids as $uid => $rows) {
                    $array['badge'] = WebSocketDialogMsgRead::whereUserid($uid)->whereSilence(0)->whereReadAt(null)->count();
                    $lists = $rows->take(5)->groupBy('platform');   // 每个会员最多推送5个别名
                    foreach ($lists as $platform => $list) {
                        $alias = $list->pluck('alias')->implode(',');
                        self::pushMsgToAlias($alias, $platform, $array);
                    }
                }
            });
    }
}
