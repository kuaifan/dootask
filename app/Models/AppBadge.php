<?php

namespace App\Models;

/**
 * App\Models\AppBadge
 *
 * 插件/微应用菜单角标（每个 (app_id, menu_key, userid) 一行，仅存非清除态）
 *
 * @property int $id
 * @property string $app_id 应用ID
 * @property string $menu_key 菜单稳定标识（空串=第一个菜单）
 * @property int $userid 用户ID
 * @property int $count 角标数字
 * @property bool $dot 是否显示红点
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AppBadge newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AppBadge newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AppBadge query()
 * @method static \Illuminate\Database\Eloquent\Builder|AppBadge whereAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppBadge whereCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppBadge whereDot($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppBadge whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppBadge whereMenuKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppBadge whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppBadge whereUserid($value)
 * @mixin \Eloquent
 */
class AppBadge extends AbstractModel
{
    protected $table = 'app_badges';

    const CREATED_AT = null;

    protected $fillable = [
        'app_id',
        'menu_key',
        'userid',
        'count',
        'dot',
    ];

    protected $casts = [
        'userid' => 'integer',
        'count' => 'integer',
        'dot' => 'boolean',
    ];
}
