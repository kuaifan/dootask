<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Module\Base;
use App\Tasks\PushTask;
use Arr;
use Carbon\Carbon;
use DB;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Database\Eloquent\SoftDeletes;
use Request;

/**
 * App\Models\Project
 *
 * @property int $id
 * @property string|null $name 名称
 * @property string|null $desc 描述、备注
 * @property int|null $userid 创建人
 * @property int|null $personal 是否个人项目
 * @property string|null $archive_method 自动归档方式
 * @property int|null $archive_days 自动归档天数
 * @property string|null $ai_auto_analyze AI自动分析
 * @property string|null $task_template_share 共享模板开关
 * @property string|null $department_owner_view 部门负责人视角可见开关
 * @property string|null $user_simple 成员总数|1,2,3
 * @property int|null $dialog_id 聊天会话ID
 * @property \Illuminate\Support\Carbon|null $archived_at 归档时间
 * @property int|null $archived_userid 归档会员
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int $owner_userid
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectColumn> $projectColumn
 * @property-read int|null $project_column_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectLog> $projectLog
 * @property-read int|null $project_log_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectUser> $projectUser
 * @property-read int|null $project_user_count
 * @method static \Illuminate\Database\Eloquent\Builder|Project allData($userid = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Project authData($userid = null, $owner = null)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Project onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|Project searchByKeyword(string $keyword)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereArchiveDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereArchiveMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereArchivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereArchivedUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project wherePersonal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereUserSimple($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Project withoutTrashed()
 * @property-read array $deputy_userids
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereAiAutoAnalyze($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDepartmentOwnerView($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereTaskTemplateShare($value)
 * @mixin \Eloquent
 */
class Project extends AbstractModel
{
    use SoftDeletes;

    protected $hidden = [
        'deleted_at',
    ];

    protected $appends = [
        'owner_userid',
        'deputy_userids',
    ];

    /**
     * 负责人会员ID
     * @return int
     */
    public function getOwnerUseridAttribute()
    {
        if (!isset($this->appendattrs['owner_userid'])) {
            $ownerUser = ProjectUser::whereProjectId($this->id)->whereOwner(1)->first();
            $this->appendattrs['owner_userid'] = $ownerUser ? $ownerUser->userid : 0;
        }
        return $this->appendattrs['owner_userid'];
    }

    /**
     * 项目管理员 userid 列表
     * @return array
     */
    public function getDeputyUseridsAttribute(): array
    {
        if (empty($this->id)) {
            return [];
        }
        return ProjectUser::whereProjectId($this->id)
            ->whereOwner(ProjectUser::OWNER_DEPUTY)
            ->pluck('userid')
            ->map(fn($v) => (int)$v)
            ->toArray();
    }

    /**
     * 是否项目负责人（与 project_users.owner=1 一致）
     */
    public function isPrimaryOwner($userid): bool
    {
        if (empty($this->id) || $userid <= 0) {
            return false;
        }
        return ProjectUser::whereProjectId($this->id)
            ->whereUserid($userid)
            ->whereOwner(ProjectUser::OWNER_PRIMARY)
            ->exists();
    }

    /**
     * 是否项目管理员（与 project_users.owner=2 一致）
     */
    public function isDeputyOwner($userid): bool
    {
        if (empty($this->id) || $userid <= 0) {
            return false;
        }
        return ProjectUser::whereProjectId($this->id)
            ->whereUserid($userid)
            ->whereOwner(ProjectUser::OWNER_DEPUTY)
            ->exists();
    }

    /**
     * 是否负责人（含项目管理员）
     */
    public function isOwner($userid): bool
    {
        return $this->isPrimaryOwner($userid) || $this->isDeputyOwner($userid);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function projectColumn(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProjectColumn::class, 'project_id', 'id')->orderBy('sort')->orderBy('id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function projectLog(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProjectLog::class, 'project_id', 'id')->orderByDesc('id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function projectUser(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProjectUser::class, 'project_id', 'id')->orderBy('id');
    }

    /**
     * 查询所有项目（与正常查询多返回owner字段）
     * @param self $query
     * @param null $userid
     * @return self
     */
    public function scopeAllData($query, $userid = null)
    {
        $userid = $userid ?: User::userid();
        $query
            ->select([
                'projects.*',
                'project_users.owner',
                'project_users.top_at',
                'project_users.sort',
            ])
            ->leftJoin('project_users', function ($leftJoin) use ($userid) {
                $leftJoin
                    ->on('project_users.userid', '=', DB::raw($userid))
                    ->on('projects.id', '=', 'project_users.project_id');
            });
        return $query;
    }

    /**
     * 查询自己负责或参与的项目
     * @param self $query
     * @param null $userid
     * @param null $owner
     * @return self
     */
    public function scopeAuthData($query, $userid = null, $owner = null)
    {
        $userid = $userid ?: User::userid();
        $query
            ->select([
                'projects.*',
                'project_users.owner',
                'project_users.top_at',
                'project_users.sort',
            ])
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('project_users.userid', $userid);
        if ($owner !== null) {
            $query->where('project_users.owner', $owner);
        }
        return $query;
    }

    /**
     * 按关键词搜索项目（Scope）
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $keyword 搜索关键词
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchByKeyword($query, string $keyword)
    {
        return $query->where("projects.name", "like", "%{$keyword}%");
    }

    /**
     * 获取任务统计数据
     * @param $userid
     * @return array
     */
    public function getTaskStatistics($userid)
    {
        $array = [];
        $builder = ProjectTask::whereProjectId($this->id)->whereNull('archived_at');
        $array['task_num'] = $builder->count();
        $array['task_complete'] = $builder->whereNotNull('complete_at')->count();
        $array['task_percent'] = $array['task_num'] ? intval($array['task_complete'] / $array['task_num'] * 100) : 0;
        //
        $builder = ProjectTask::authData($userid, 1)->where('project_tasks.project_id', $this->id)->whereNull('project_tasks.archived_at');
        $array['task_my_num'] = $builder->count();
        $array['task_my_complete'] = $builder->whereNotNull('project_tasks.complete_at')->count();
        $array['task_my_percent'] = $array['task_my_num'] ? intval($array['task_my_complete'] / $array['task_my_num'] * 100) : 0;
        //
        return $array;
    }

    /**
     * 加入项目
     * @param int $userid   加入的会员ID
     * @return bool
     */
    public function joinProject($userid)
    {
        if (empty($userid)) {
            return false;
        }
        if (!User::whereUserid($userid)->exists()) {
            return false;
        }
        ProjectUser::updateInsert([
            'project_id' => $this->id,
            'userid' => $userid,
        ]);
        return true;
    }

    /**
     * 同步项目成员至聊天室
     */
    public function syncDialogUser()
    {
        if (empty($this->dialog_id)) {
            return;
        }
        AbstractModel::transaction(function() {
            // 拉所有项目成员 + 各自 owner 值
            $userOwnerMap = ProjectUser::whereProjectId($this->id)
                ->pluck('owner', 'userid');
            $userids = $userOwnerMap->keys()->map(fn($v) => (int)$v)->toArray();
            foreach ($userids as $userid) {
                $owner = (int)$userOwnerMap[$userid];
                // 巧合：编码完全一致 owner 0/1/2 → role 0/1/2
                $role = $owner;
                WebSocketDialogUser::updateInsert([
                    'dialog_id' => $this->dialog_id,
                    'userid' => $userid,
                ], [
                    'important' => 1,
                    'role' => $role,
                ], function () use ($userid, $role) {
                    return [
                        'important' => 1,
                        'role' => $role,
                        'bot' => User::isBot($userid) ? 1 : 0,
                    ];
                });
            }
            WebSocketDialogUser::whereDialogId($this->dialog_id)
                ->whereNotIn('userid', $userids)
                ->whereImportant(1)
                ->remove();
            // 同步 dialog.owner_id 到主负责人（owner=1）：前端「群主」标签依赖此字段，
            // 必须随项目主负责人变更（含用户离职转移）一起刷新，否则会显示已离职用户
            $primaryUserid = $userOwnerMap->search(ProjectUser::OWNER_PRIMARY);
            if ($primaryUserid !== false && (int)$primaryUserid > 0) {
                WebSocketDialog::whereId($this->dialog_id)
                    ->where('owner_id', '!=', (int)$primaryUserid)
                    ->update(['owner_id' => (int)$primaryUserid]);
            }
        });
    }

    /**
     * 获取相关所有人员（项目负责人、项目成员）
     * @return array
     */
    public function relationUserids()
    {
        return ProjectUser::whereProjectId($this->id)->orderBy('id')->pluck('userid')->toArray();
    }

    /**
     * 会员id是否在项目里
     * @param int $userid
     * @return int 0:不存在、1存在、2存在且是管理员
     */
    public function useridInTheProject($userid)
    {
        $user = ProjectUser::whereProjectId($this->id)->whereUserid(intval($userid))->first();
        if (empty($user)) {
            return 0;
        }
        return $user->owner ? 2 : 1;
    }

    /**
     * 归档项目、取消归档
     * @param Carbon|null $archived_at 归档时间
     * @return bool
     */
    public function archivedProject($archived_at)
    {
        AbstractModel::transaction(function () use ($archived_at) {
            if ($archived_at === null) {
                // 取消归档
                $this->archived_at = null;
                $this->archived_userid = User::userid();
                $this->addLog("项目取消归档");
                $this->pushMsg('recovery', $this);
                ProjectTask::whereProjectId($this->id)->whereArchivedFollow(1)->change([
                    'archived_at' => null,
                    'archived_follow' => 0
                ]);
            } else {
                // 归档项目
                $this->archived_at = $archived_at;
                $this->archived_userid = User::userid();
                $this->addLog("项目归档");
                $this->pushMsg('archived');
                ProjectTask::whereProjectId($this->id)->whereArchivedAt(null)->change([
                    'archived_at' => $archived_at,
                    'archived_follow' => 1
                ]);
            }
            $this->save();
        });
        return true;
    }

    /**
     * 删除项目
     * @return bool
     */
    public function deleteProject()
    {
        AbstractModel::transaction(function () {
            $dialog = WebSocketDialog::find($this->dialog_id);
            $dialog?->deleteDialog();
            $columns = ProjectColumn::whereProjectId($this->id)->get();
            foreach ($columns as $column) {
                $column->deleteColumn(false);
            }
            $this->delete();
            $this->addLog("删除项目");
        });
        $this->pushMsg('delete');
        return true;
    }

    /**
     * 添加项目日志
     * @param string $detail
     * @param array $record
     * @param int $userid
     * @return ProjectLog
     */
    public function addLog($detail, $record = [], $userid = 0)
    {
        $array = [
            'project_id' => $this->id,
            'column_id' => 0,
            'task_id' => 0,
            'userid' => $userid ?: User::userid(),
            'detail' => $detail,
        ];
        if ($record) {
            $array['record'] = $record;
        }
        $log = ProjectLog::createInstance($array);
        $log->save();
        return $log;
    }

    /**
     * 推送消息
     * @param string $action
     * @param array|self $data      推送内容
     * @param array $userid         指定会员，默认为项目所有成员
     */
    public function pushMsg($action, $data = null, $userid = null)
    {
        // 处理数据
        if ($data instanceof self) {
            $data = $data->toArray();
        }

        $data = is_array($data) ? $data : [];
        $data['id'] = $this->id;
        $data['name'] = $this->name;
        $data['desc'] = $this->desc;

        // 处理接收用户
        $recipients = [$userid, []];
        if ($userid === null) {
            $recipients[0] = $this->relationUserids();
        } elseif (!is_array($userid)) {
            $recipients[0] = [$userid];
        }

        // 移除不需要的字段
        unset($data['top_at']);

        // 处理所有者权限
        if (isset($data['owner'])) {
            $owners = ProjectUser::whereProjectId($data['id'])
                ->whereIn('owner', [ProjectUser::OWNER_PRIMARY, ProjectUser::OWNER_DEPUTY])
                ->pluck('userid')
                ->toArray();
            $recipients = [
                array_intersect($recipients[0], $owners),
                array_diff($recipients[0], $owners)
            ];
        }

        // 发送推送
        foreach ($recipients as $index => $userids) {
            if (empty($userids)) {
                continue;
            }

            if ($index > 0) {
                $data['owner'] = 0;
            }

            $params = [
                'ignoreFd' => Request::header('fd'),
                'userid' => array_values($userids),
                'msg' => [
                    'type' => 'project',
                    'action' => $action,
                    'data' => $data,
                ]
            ];

            Task::deliver(new PushTask($params, false));
        }
    }

    /**
     * 添加工作流
     * @param $flows
     * @return mixed
     */
    public function addFlow($flows)
    {
        return AbstractModel::transaction(function() use ($flows) {
            $projectFlow = ProjectFlow::whereProjectId($this->id)->first();
            if (empty($projectFlow)) {
                $projectFlow = ProjectFlow::createInstance([
                    'project_id' => $this->id,
                    'name' => 'Default'
                ]);
                if (!$projectFlow->save()) {
                    throw new ApiException('工作流创建失败');
                }
            }
            //
            $ids = [];
            $idc = [];
            $hasStart = false;
            $hasEnd = false;
            $upTaskList = [];
            $projectUserids = $this->relationUserids();
            foreach ($flows as $item) {
                $id = intval($item['id']);
                $name = trim(str_replace('|', '·', $item['name']));
                $turns = Base::arrayRetainInt($item['turns'] ?: [], true);
                $userids = Base::arrayRetainInt($item['userids'] ?: [], true);
                $usertype = trim($item['usertype']);
                $userlimit = intval($item['userlimit']);
                $columnid = intval($item['columnid']);
                if ($usertype == 'replace' && empty($userids)) {
                    throw new ApiException("状态[{$name}]设置错误，设置流转模式时必须填写状态负责人");
                }
                if ($usertype == 'merge' && empty($userids)) {
                    throw new ApiException("状态[{$name}]设置错误，设置剔除模式时必须填写状态负责人");
                }
                if ($userlimit && empty($userids)) {
                    throw new ApiException("状态[{$name}]设置错误，设置限制负责人时必须填写状态负责人");
                }
                foreach ($userids as $userid) {
                    if (!in_array($userid, $projectUserids)) {
                        $nickname = User::userid2nickname($userid);
                        throw new ApiException("状态[{$name}]设置错误，状态负责人[{$nickname}]不在项目成员内");
                    }
                }
                $flow = ProjectFlowItem::updateInsert([
                    'id' => $id,
                    'project_id' => $this->id,
                    'flow_id' => $projectFlow->id,
                ], [
                    'name' => $name,
                    'status' => trim($item['status']),
                    'color' => trim($item['color']),
                    'sort' => intval($item['sort']),
                    'turns' => $turns,
                    'userids' => $userids,
                    'usertype' => trim($item['usertype']),
                    'userlimit' => $userlimit,
                    'columnid' => $columnid,
                ], [], $isInsert);
                if ($flow) {
                    $ids[] = $flow->id;
                    if ($flow->id != $id) {
                        $idc[$id] = $flow->id;
                    }
                    if ($flow->status == 'start') {
                        $hasStart = true;
                    }
                    if ($flow->status == 'end') {
                        $hasEnd = true;
                    }
                    if (!$isInsert) {
                        $upTaskList[$flow->id] = $flow->status . "|" . $flow->name . "|" . $flow->color;
                    }
                }
            }
            if (!$hasStart) {
                throw new ApiException('至少需要1个开始状态');
            }
            if (!$hasEnd) {
                throw new ApiException('至少需要1个结束状态');
            }
            ProjectFlowItem::whereFlowId($projectFlow->id)->whereNotIn('id', $ids)->chunk(100, function($list) {
                foreach ($list as $item) {
                    $item->deleteFlowItem();
                }
            });
            //
            foreach ($upTaskList as $id => $value) {
                ProjectTask::whereFlowItemId($id)->change([
                    'flow_item_name' => $value
                ]);
            }
            //
            $projectFlow = ProjectFlow::with(['projectFlowItem'])->whereProjectId($this->id)->find($projectFlow->id);
            $itemIds = $projectFlow->projectFlowItem->pluck('id')->toArray();
            foreach ($projectFlow->projectFlowItem as $item) {
                $turns = $item->turns;
                foreach ($idc as $oid => $nid) {
                    if (in_array($oid, $turns)) {
                        $turns = array_diff($turns, [$oid]);
                        $turns[] = $nid;
                    }
                }
                if (!in_array($item->id, $turns)) {
                    $turns[] = $item->id;
                }
                $turns = array_values(array_filter(array_unique(array_intersect($turns, $itemIds))));
                sort($turns);
                $item->turns = $turns;
                ProjectFlowItem::whereId($item->id)->update([ 'turns' => Base::array2json($turns) ]);
            }
            return $projectFlow;
        });
    }

    /**
     * 判断用户是否有权限创建项目（依据系统设置「项目创建权限」）
     * @param int $userid
     * @return bool
     */
    public static function userCanCreate($userid)
    {
        // 范围已在 Setting::getSettingAttribute() 归一化（默认 ['all']）
        $modes = Base::settingFind('system', 'project_add_permission', ['all']);
        // 「所有人」：放行（与具体用户无关，避免未携带身份时被误判为无权）
        if (in_array('all', $modes)) {
            return true;
        }
        $user = User::find(intval($userid));
        if (empty($user)) {
            return false;
        }
        // 系统管理员始终可创建项目（不受开关限制）
        if ($user->isAdmin()) {
            return true;
        }
        // 部门负责人/部门管理员
        if (in_array('departmentOwner', $modes) && UserDepartment::getManagedDepartments($user->userid)->isNotEmpty()) {
            return true;
        }
        // 指定人员
        if (in_array('appoint', $modes)) {
            return in_array($user->userid, Base::settingFind('system', 'project_add_userids', []));
        }
        return false;
    }

    /**
     * 创建项目
     * @param $params
     * - name   项目名称
     * - desc
     * - flow
     * - personal
     * - columns
     * @return array
     */
    public static function createProject($params, $userid)
    {
        $name = trim(Arr::get($params, 'name', ''));
        $desc = trim(Arr::get($params, 'desc', ''));
        $flow = trim(Arr::get($params, 'flow', 'close'));
        $isPersonal = intval(Arr::get($params, 'personal'));
        // 个人项目为系统自动创建，不受创建权限限制
        if (!$isPersonal && !self::userCanCreate($userid)) {
            return Base::retError('当前仅指定人员可以创建项目');
        }
        if (mb_strlen($name) < 2) {
            return Base::retError('项目名称不可以少于2个字');
        } elseif (mb_strlen($name) > 32) {
            return Base::retError('项目名称最多只能设置32个字');
        }
        if (mb_strlen($desc) > 255) {
            return Base::retError('项目介绍最多只能设置255个字');
        }
        // 列表
        $columns = explode(",", Arr::get($params, 'columns'));
        $insertColumns = [];
        $sort = 0;
        foreach ($columns AS $column) {
            $column = trim($column);
            if ($column) {
                $insertColumns[] = [
                    'name' => $column,
                    'sort' => $sort++,
                ];
            }
        }
        if (empty($insertColumns)) {
            $insertColumns[] = [
                'name' => 'Default',
                'sort' => 0,
            ];
        }
        if (count($insertColumns) > 30) {
            return Base::retError('项目列表最多不能超过30个');
        }
        // 开始创建
        $project = Project::createInstance([
            'name' => $name,
            'desc' => $desc,
            'userid' => $userid,
        ]);
        if ($isPersonal) {
            if (Project::whereUserid($userid)->wherePersonal(1)->exists()) {
                return Base::retError('个人项目已存在，无须重复创建');
            }
            $project->personal = 1;
        }
        AbstractModel::transaction(function() use ($flow, $insertColumns, $project) {
            $project->save();
            ProjectUser::createInstance([
                'project_id' => $project->id,
                'userid' => $project->userid,
                'owner' => 1,
            ])->save();
            foreach ($insertColumns AS $column) {
                $column['project_id'] = $project->id;
                ProjectColumn::createInstance($column)->save();
            }
            $dialog = WebSocketDialog::createGroup($project->name, $project->userid, 'project', $project->userid);
            if (empty($dialog)) {
                throw new ApiException('创建项目聊天室失败');
            }
            $project->dialog_id = $dialog->id;
            $project->save();
            //
            if ($flow == 'open') {
                $project->addFlow(Base::json2array('[{"id":-10,"name":"待处理","status":"start","turns":[-10,-11,-12,-13,-14],"userids":[],"usertype":"add","userlimit":0,"columnid":0},{"id":-11,"name":"进行中","status":"progress","turns":[-10,-11,-12,-13,-14],"userids":[],"usertype":"add","userlimit":0,"columnid":0},{"id":-12,"name":"待测试","status":"test","turns":[-10,-11,-12,-13,-14],"userids":[],"usertype":"add","userlimit":0,"columnid":0},{"id":-13,"name":"已完成","status":"end","turns":[-10,-11,-12,-13,-14],"userids":[],"usertype":"add","userlimit":0,"columnid":0},{"id":-14,"name":"已取消","status":"end","color":"#999999","turns":[-10,-11,-12,-13,-14],"userids":[],"usertype":"add","userlimit":0,"columnid":0}]'));
            }
        });
        //
        $data = Project::find($project->id);
        $data->addLog("创建项目");
        $data->pushMsg('add', $data);
        return Base::retSuccess('添加成功', $data);
    }

    /**
     * 获取项目信息（用于判断会员是否存在项目内）
     * @param int $project_id
     * @param null|bool $archived true:仅限未归档, false:仅限已归档, null:不限制
     * @param null|bool|string $mustOwner true:负责人或项目管理员都可（共享操作）；
     *                                    'primary':仅负责人（转让/删除/任命项目管理员等独占操作）；
     *                                    false:仅限非负责人；null:不限制
     * @return self
     */
    public static function userProject($project_id, $archived = true, $mustOwner = null)
    {
        $project = self::authData()->where('projects.id', intval($project_id))->first();
        if (empty($project)) {
            throw new ApiException('项目不存在或不在成员列表内', [ 'project_id' => $project_id ], -4001);
        }
        if ($archived === true && $project->archived_at != null) {
            throw new ApiException('项目已归档', [ 'project_id' => $project_id ], -4001);
        }
        if ($archived === false && $project->archived_at == null) {
            throw new ApiException('项目未归档', [ 'project_id' => $project_id ]);
        }
        if ($mustOwner === true && !$project->owner) {
            throw new ApiException('仅限项目负责人操作', [ 'project_id' => $project_id ]);
        }
        if ($mustOwner === 'primary' && (int)$project->owner !== 1) {
            throw new ApiException('仅限项目负责人操作', [ 'project_id' => $project_id ]);
        }
        if ($mustOwner === false && $project->owner) {
            throw new ApiException('禁止项目负责人操作', [ 'project_id' => $project_id ]);
        }
        return $project;
    }

    /**
     * 获取项目（含部门负责人只读视角兜底）
     * @param int $project_id
     * @param null|bool $archived true:仅限未归档, false:仅限已归档, null:不限制
     * @param null|bool|string $mustOwner 仅限 null 时尝试部门只读视角
     * @return self
     */
    public static function findForDepartmentView($project_id, $archived = true, $mustOwner = null)
    {
        $user = User::auth();
        $departmentView = UserDepartment::ownerViewContext($user, true);
        if (UserDepartment::isDepartmentReadonlyProject($departmentView, intval($project_id)) && $mustOwner === null) {
            $project = self::allData()->where('projects.id', intval($project_id))->first();
            if (empty($project)) {
                throw new ApiException('项目不存在或已被删除', [ 'project_id' => $project_id ], -4001);
            }
            if ($archived === true && $project->archived_at != null) {
                throw new ApiException('项目已归档', [ 'project_id' => $project_id ], -4001);
            }
            if ($archived === false && $project->archived_at == null) {
                throw new ApiException('项目未归档', [ 'project_id' => $project_id ]);
            }
            return $project;
        }
        return self::userProject($project_id, $archived, $mustOwner);
    }
}
