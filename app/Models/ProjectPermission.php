<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Module\Base;
use App\Module\Doo;

/**
 * App\Models\ProjectPermission
 *
 * @property int $id
 * @property int|null $project_id 项目ID
 * @property array $permissions 权限
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPermission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPermission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPermission query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPermission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPermission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPermission wherePermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPermission whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectPermission whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectPermission extends AbstractModel
{

    const TASK_LIST_ADD = 'task_list_add';             // 添加列
    const TASK_LIST_UPDATE = 'task_list_update';       // 修改列
    const TASK_LIST_REMOVE = 'task_list_remove';       // 删除列
    const TASK_LIST_SORT = 'task_list_sort';           // 列表排序
    const TASK_ADD = 'task_add';                       // 任务添加
    const TASK_UPDATE = 'task_update';                 // 任务更新
    const TASK_TIME = 'task_time';                     // 任务时间
    const TASK_STATUS = 'task_status';                 // 任务状态
    const TASK_REMOVE = 'task_remove';                 // 任务删除
    const TASK_ARCHIVED = 'task_archived';             // 任务归档
    const TASK_MOVE = 'task_move';                     // 任务移动

    // 权限列表
    const PERMISSIONS = [
        'project_leader' => 1,  // 项目负责人
        'project_member' => 2,  // 项目成员
        'task_leader' => 3,     // 任务负责人
        'task_assist' => 4,     // 任务协助人
    ];

    // 权限描述
    const PERMISSIONS_DESC = [
        1 => "项目负责人",
        2 => "项目成员",
        3 => "任务负责人",
        4 => "任务协助人",
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['project_id', 'permissions'];

    /**
     * 权限
     * @param $value
     * @return array
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
        if ($key) {
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
            self::TASK_LIST_ADD => $projectTaskList = [self::PERMISSIONS['project_leader'], self::PERMISSIONS['project_member']],
            self::TASK_LIST_UPDATE => $projectTaskList,
            self::TASK_LIST_REMOVE => [self::PERMISSIONS['project_leader']],
            self::TASK_LIST_SORT => $projectTaskList,
            self::TASK_ADD => $projectTaskList,
            self::TASK_UPDATE => $taskUpdate = [self::PERMISSIONS['project_leader'], self::PERMISSIONS['task_leader'], self::PERMISSIONS['task_assist']],
            self::TASK_TIME => $taskUpdate,
            self::TASK_STATUS => $taskStatus = [self::PERMISSIONS['project_leader'], self::PERMISSIONS['task_leader']],
            self::TASK_REMOVE => $taskStatus,
            self::TASK_ARCHIVED => $taskStatus,
            self::TASK_MOVE => $taskStatus
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

    /**
     * 检查用户是否有执行特定动作的权限
     * @param string $action 动作名称
     * @param Project $project 项目实例
     * @param ProjectTask $task 任务实例
     * @return bool
     */
    public static function userTaskPermission(Project $project, $action, ProjectTask $task = null)
    {
        $userid = User::userid();
        $permissions = self::getPermission($project->id, $action);
        switch ($action) {
            // 任务添加，任务更新, 任务状态, 任务删除, 任务完成, 任务归档, 任务移动
            case self::TASK_LIST_ADD:
            case self::TASK_LIST_UPDATE:
            case self::TASK_LIST_REMOVE:
            case self::TASK_LIST_SORT:
            case self::TASK_ADD:
            case self::TASK_UPDATE:
            case self::TASK_TIME:
            case self::TASK_STATUS:
            case self::TASK_REMOVE:
            case self::TASK_ARCHIVED:
            case self::TASK_MOVE:
                $verify = false;
                // 项目负责人
                if (in_array(self::PERMISSIONS['project_leader'], $permissions)) {
                    if ($project->owner) {
                        $verify = true;
                    }
                }
                // 项目成员
                if (!$verify && in_array(self::PERMISSIONS['project_member'], $permissions)) {
                    $user = ProjectUser::whereProjectId($project->id)->whereUserid(intval($userid))->first();
                    if (!empty($user)) {
                        $verify = true;
                    }
                }
                // 任务负责人
                if (!$verify && $task && in_array(self::PERMISSIONS['task_leader'], $permissions)) {
                    if ($task->isOwner()) {
                        $verify = true;
                    }
                }
                // 任务协助人
                if (!$verify && $task && in_array(self::PERMISSIONS['task_assist'], $permissions)) {
                    if ($task->isAssister()) {
                        $verify = true;
                    }
                }
                //
                if (!$verify) {
                    $desc = [];
                    rsort($permissions);
                    foreach ($permissions as $permission) {
                        $desc[] = Doo::translate(self::PERMISSIONS_DESC[$permission]);
                    }
                    $desc = array_reverse($desc);
                    throw new ApiException(sprintf("仅限%s操作", implode('、', $desc)));
                }
                break;
        }
        return true;
    }
}
