<?php

namespace App\Models;

/**
 * App\Models\AppPushDevice
 *
 * @property int $id
 * @property int $userid 用户ID
 * @property string $channel 推送通道
 * @property string $device_id 推送服务设备ID
 * @property string $vendor 厂商通道
 * @property string $platform 平台类型
 * @property string $device_hash 登录TOKEN MD5，关联UserDevice
 * @property string $device 设备型号
 * @property string $version 应用版本号
 * @property string|null $ua userAgent
 * @property int $is_notified 通知权限
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|AppPushDevice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AppPushDevice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AppPushDevice query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|AppPushDevice whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppPushDevice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppPushDevice whereDevice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppPushDevice whereDeviceHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppPushDevice whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppPushDevice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppPushDevice whereIsNotified($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppPushDevice wherePlatform($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppPushDevice whereUa($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppPushDevice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppPushDevice whereUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppPushDevice whereVendor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppPushDevice whereVersion($value)
 * @mixin \Eloquent
 */
class AppPushDevice extends AbstractModel
{
    protected $table = 'app_push_devices';
}
