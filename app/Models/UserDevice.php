<?php

namespace App\Models;

use App\Module\Base;
use App\Module\Doo;
use App\Module\Lock;
use Cache;
use Carbon\Carbon;
use DeviceDetector\DeviceDetector;
use Illuminate\Database\Eloquent\SoftDeletes;
use Request;

/**
 * App\Models\UserDevice
 *
 * @property int $id
 * @property int|null $userid 会员ID
 * @property string|null $hash TOKEN MD5
 * @property string|null $detail 详细信息
 * @property string|null $expired_at 过期时间
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int $is_current
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDevice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDevice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDevice onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDevice query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDevice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDevice whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDevice whereDetail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDevice whereExpiredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDevice whereHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDevice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDevice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDevice whereUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDevice withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDevice withoutTrashed()
 * @mixin \Eloquent
 */
class UserDevice extends AbstractModel
{
    use SoftDeletes;

    protected $table = 'user_devices';

    public static int $deviceLimit = 200; // 每个用户设备限制数量

    protected $appends = [
        'is_current',
    ];

    public function getDetailAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        return Base::json2array($value);
    }

    public function getIsCurrentAttribute(): int
    {
        return $this->hash === md5(Doo::userToken()) ? 1 : 0;
    }

    /** ****************************************************************************** */
    /** ****************************************************************************** */
    /** ****************************************************************************** */

    /**
     * 缓存key
     * @param string $hash
     * @return string
     */
    public static function ck(string $hash): string
    {
        return "user_devices:{$hash}";
    }

    /**
     * 解析 UA 获取设备信息
     * @param string $ua
     * @return array
     */
    private static function getDeviceInfo(string $ua): array
    {
        $result = [
            'ip' => Base::getIp(),
            'type' => '电脑',
            'os' => 'Unknown',
            'browser' => 'Unknown',
            'version' => '',

            'app_name' => '',       // 客户端名称
            'app_type' => '',       // 客户端类型
            'app_version' => '',    // 客户端版本
        ];

        if (empty($ua)) {
            return $result;
        }

        // 使用 Device-Detector 解析 UA
        $dd = new DeviceDetector($ua);

        // 解析 UA 字符串
        $dd->parse();

        // 获取客户端信息（浏览器）
        $clientInfo = $dd->getClient();
        if (!empty($clientInfo)) {
            $result['browser'] = $clientInfo['name'] ?? 'Unknown';
            $result['version'] = $clientInfo['version'] ?? '';
        }

        // 获取操作系统信息
        $osInfo = $dd->getOs();
        if (!empty($osInfo)) {
            $result['os'] = trim(($osInfo['name'] ?? '') . ' ' . ($osInfo['version'] ?? ''));
            if (empty($result['os'])) {
                $result['os'] = 'Unknown';
            }
        }

        if (preg_match("/android_kuaifan_eeui/i", $ua)) {
            // Android 客户端
            $result['app_type'] = 'Android';
            if ($dd->getBrandName() && $dd->getModel()) {
                // 厂商+型号
                $result['app_name'] = $dd->getBrandName() . ' ' . $dd->getModel();
            } elseif ($dd->getBrandName()) {
                // 仅厂商
                $result['app_name'] = $dd->getBrandName();
            } elseif ($dd->isTablet()) {
                // 平板
                $result['app_name'] = 'Tablet';
            } elseif ($dd->isPhablet()) {
                // 平板
                $result['app_name'] = 'Phablet';
            }
            $result['app_version'] = self::getAfterVersion($ua, 'kuaifan_eeui/');
        } elseif (preg_match("/ios_kuaifan_eeui/i", $ua)) {
            // iOS 客户端
            $result['app_type'] = 'iOS';
            if (preg_match("/(macintosh|ipad)/i", $ua)) {
                // iPad
                $result['app_name'] = 'iPad';
            } elseif (preg_match("/iphone/i", $ua)) {
                // iPhone
                $result['app_name'] = 'iPhone';
            }
            $result['app_version'] = self::getAfterVersion($ua, 'kuaifan_eeui/');
        } elseif (preg_match("/dootask/i", $ua)) {
            // DooTask 客户端
            $result['app_type'] = $osInfo['name'];
            $result['app_version'] = self::getAfterVersion($ua, 'dootask/');
        } else {
            // 其他客户端
            $result['app_type'] = 'Web';
            $result['app_version'] = Base::getClientVersion();
        }

        return $result;
    }

    /**
     * 从 ua 的 find 之后的内容获取版本号
     * @param string $ua
     * @param string $find
     * @return string
     */
    private static function getAfterVersion(string $ua, string $find): string
    {
        $findPattern = preg_quote($find, '/');
        if (preg_match("/{$findPattern}(.*?)(?:\s|$)/i", $ua, $matches)) {
            $appInfo = $matches[1];

            // 从内容中提取版本号（寻找符合x.x.x格式的部分）
            if (preg_match("/(\d+\.\d+(?:\.\d+)*)/", $appInfo, $versionMatches)) {
                return $versionMatches[1];
            }
        }
        return '';
    }

    /** ****************************************************************************** */
    /** ****************************************************************************** */
    /** ****************************************************************************** */

    /**
     * 检查用户是否存在
     * @return string|null
     */
    public static function check(): ?string
    {
        $token = Doo::userToken();
        $userid = Doo::userId();

        $hash = md5($token);
        if (Cache::has(self::ck($hash))) {
            return $hash;
        }

        $row = self::whereHash($hash)->first();
        if ($row) {
            // 判断是否过期
            if ($row->expired_at && Carbon::parse($row->expired_at)->isPast()) {
                self::forget($row);
                return null;
            }
            // 更新缓存
            self::record();
            return $hash;
        }
        // 没有记录
        return null;
    }

    /**
     * 记录设备（添加、更新）
     * @param string|null $token
     * @return self|null
     */
    public static function record(string $token = null): ?self
    {
        if (empty($token)) {
            $token = Doo::userToken();
            $userid = Doo::userId();
            $expiredAt = Doo::userExpiredAt();
        } else {
            $info = Doo::tokenDecode($token);
            $userid = $info['userid'] ?? 0;
            $expiredAt = $info['expired_at'];
        }
        $hash = md5($token);
        //
        return Lock::withLock("userDeviceRecord:{$hash}", function () use ($expiredAt, $userid, $hash, $token) {
            return AbstractModel::transaction(function () use ($expiredAt, $userid, $hash, $token) {
                $row = self::whereHash($hash)->first();
                if (empty($row)) {
                    // 生成一个新的设备记录
                    $row = self::createInstance([
                        'userid' => $userid,
                        'hash' => $hash,
                    ]);
                    if (!$row->save()) {
                        return null;
                    }
                    // 删除多余的设备记录
                    $currentDeviceCount = self::whereUserid($userid)->count();
                    if ($currentDeviceCount > self::$deviceLimit) {
                        $rows = self::whereUserid($userid)->orderBy('id')->take($currentDeviceCount - self::$deviceLimit)->get();
                        foreach ($rows as $row) {
                            UserDevice::forget($row);
                        }
                    }
                }
                $row->expired_at = $expiredAt;
                if (Request::hasHeader('version')) {
                    $deviceInfo = array_merge(Base::json2array($row->detail), self::getDeviceInfo($_SERVER['HTTP_USER_AGENT'] ?? ''));
                    $row->detail = Base::array2json($deviceInfo);
                }
                $row->save();

                Cache::put(self::ck($hash), $row->userid, now()->addHour());
                return $row;
            });
        });
    }

    /**
     * 忘记设备（删除）
     * @param UserDevice|string|int|null $token
     * - UserDevice 表示指定的设备对象
     * - string 表示指定的 token
     * - int 表示指定的数据ID
     * - null 表示当前登录的设备
     * @return void
     */
    public static function forget(UserDevice|string|int $token = null): void
    {
        if ($token instanceof UserDevice) {
            $hash = $token->hash;
            $token->delete();
        } elseif (Base::isNumber($token)) {
            $row = self::find(intval($token));
            if ($row) {
                $hash = $row->hash;
                $row->delete();
            }
        } else {
            if ($token === null) {
                $token = Doo::userToken();
            }
            if ($token) {
                $hash = md5($token);
                self::whereHash($hash)->delete();
            }
        }
        if (isset($hash)) {
            Cache::forget(self::ck($hash));
            UmengAlias::whereDeviceHash($hash)->delete();
        }
    }
}
