<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Module\Base;

/**
 * App\Models\ProjectPermission
 *
 * @property int $id
 * @property int|null $project_id 项目ID
 * @property string|null $permissions 权限
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPermission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPermission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPermission query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPermission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPermission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPermission wherePermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPermission whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPermission whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectPermission extends AbstractModel
{

    const TASK_ADD = 'task_add'; // 任务添加
    const TASK_UPDATE = 'task_update'; // 任务更新
    const TASK_REMOVE = 'task_remove'; // 任务删除
    const TASK_UPDATE_COMPLETE = 'task_update_complete'; // 任务完成
    const TASK_ARCHIVED = 'task_archived'; // 任务归档
    const TASK_MOVE = 'task_move'; // 任务移动
    const PANEL_SHOW_TASK_COMPLETE = 'panel_show_task_complete'; // 显示已完成任务

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['project_id', 'permissions'];

    /**
     * 权限
     * @param $value
     * @return string
     */
    public function getPermissionsAttribute($value)
    {
        return Base::json2array($value);
    }

    /**
     * 获取权限值
     *
     * @param int $projectId
     * @param string $key
     * @return object|array
     */
    public static function getPermission($projectId, $key = '')
    {
        $projectPermission = self::initPermissions($projectId);
        $currentPermissions = $projectPermission->permissions;
        if ($key){
            if (!isset($currentPermissions[$key])) {
                throw new ApiException('项目权限设置不存在');
            }
            return $currentPermissions[$key];
        }
        return $projectPermission;
    }

    /**
     * 初始化项目权限
     *
     * @param int $projectId
     * @return ProjectPermission
     */
    public static function initPermissions($projectId)
    {
        $permissions = [
            self::TASK_ADD => [1,3],
            self::TASK_UPDATE => [1,2],
            self::TASK_REMOVE => [1,2],
            self::TASK_UPDATE_COMPLETE => [1,2],
            self::TASK_ARCHIVED => [1,2],
            self::TASK_MOVE => [1,2],
            self::PANEL_SHOW_TASK_COMPLETE => 1,
        ];
        return self::firstOrCreate(
            ['project_id' => $projectId],
            ['permissions' => Base::array2json($permissions)]
        );
    }

    /**
     * 更新项目权限
     *
     * @param  int  $projectId
     * @param  array  $permissions
     * @return ProjectPermission
     */
    public static function updatePermissions($projectId, $newPermissions)
    {
        $projectPermission = self::initPermissions($projectId);
        $currentPermissions = $projectPermission->permissions;
        $mergedPermissions = empty($newPermissions) ? $currentPermissions : array_merge($currentPermissions, $newPermissions);

        $projectPermission->permissions = Base::array2json($mergedPermissions);
        $projectPermission->save();

        return $projectPermission;
    }
}
