<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Module\Base;
use App\Tasks\PushTask;
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
 * @property int|null $dialog_id 聊天会话ID
 * @property string|null $archived_at 归档时间
 * @property int|null $archived_userid 归档会员
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int $owner_userid
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProjectColumn[] $projectColumn
 * @property-read int|null $project_column_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProjectLog[] $projectLog
 * @property-read int|null $project_log_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProjectUser[] $projectUser
 * @property-read int|null $project_user_count
 * @method static \Illuminate\Database\Eloquent\Builder|Project allData($userid = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Project authData($userid = null, $owner = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Project newQuery()
 * @method static \Illuminate\Database\Query\Builder|Project onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereArchivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereArchivedUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereUserid($value)
 * @method static \Illuminate\Database\Query\Builder|Project withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Project withoutTrashed()
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
            ])
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('project_users.userid', $userid);
        if ($owner !== null) {
            $query->where('project_users.owner', $owner);
        }
        return $query;
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
            $userids = $this->relationUserids();
            foreach ($userids as $userid) {
                WebSocketDialogUser::updateInsert([
                    'dialog_id' => $this->dialog_id,
                    'userid' => $userid,
                ]);
            }
            WebSocketDialogUser::whereDialogId($this->dialog_id)->whereNotIn('userid', $userids)->delete();
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
                $this->pushMsg('add', $this);
                ProjectTask::whereProjectId($this->id)->whereArchivedFollow(1)->update([
                    'archived_at' => null,
                    'archived_follow' => 0
                ]);
            } else {
                // 归档项目
                $this->archived_at = $archived_at;
                $this->archived_userid = User::userid();
                $this->addLog("项目归档");
                $this->pushMsg('archived');
                ProjectTask::whereProjectId($this->id)->whereArchivedAt(null)->update([
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
     * @param array|self $data      发送内容，默认为[id=>项目ID]
     * @param array $userid         指定会员，默认为项目所有成员
     */
    public function pushMsg($action, $data = null, $userid = null)
    {
        if ($data === null) {
            $data = ['id' => $this->id];
        } elseif ($data instanceof self) {
            $data = $data->toArray();
        }
        //
        $array = [$userid, []];
        if ($userid === null) {
            $array[0] = $this->relationUserids();
        } elseif (!is_array($userid)) {
            $array[0] = [$userid];
        }
        //
        if (isset($data['owner'])) {
            $owners = ProjectUser::whereProjectId($data['id'])->whereOwner(1)->pluck('userid')->toArray();
            $array = [array_intersect($array[0], $owners), array_diff($array[0], $owners)];
        }
        //
        foreach ($array as $index => $item) {
            if ($index > 0) {
                $data['owner'] = 0;
            }
            $params = [
                'ignoreFd' => Request::header('fd'),
                'userid' => array_values($item),
                'msg' => [
                    'type' => 'project',
                    'action' => $action,
                    'data' => $data,
                ]
            ];
            $task = new PushTask($params, false);
            Task::deliver($task);
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
            foreach ($flows as $item) {
                $id = intval($item['id']);
                $turns = Base::arrayRetainInt($item['turns'] ?: [], true);
                $userids = Base::arrayRetainInt($item['userids'] ?: [], true);
                $usertype = trim($item['usertype']);
                $userlimit = intval($item['userlimit']);
                if ($usertype == 'replace' && empty($userids)) {
                    throw new ApiException("状态[{$item['name']}]设置错误，设置流转模式时必须填写状态负责人");
                }
                if ($usertype == 'merge' && empty($userids)) {
                    throw new ApiException("状态[{$item['name']}]设置错误，设置剔除模式时必须填写状态负责人");
                }
                if ($userlimit && empty($userids)) {
                    throw new ApiException("状态[{$item['name']}]设置错误，设置限制负责人时必须填写状态负责人");
                }
                $flow = ProjectFlowItem::updateInsert([
                    'id' => $id,
                    'project_id' => $this->id,
                    'flow_id' => $projectFlow->id,
                ], [
                    'name' => trim($item['name']),
                    'status' => trim($item['status']),
                    'sort' => intval($item['sort']),
                    'turns' => $turns,
                    'userids' => $userids,
                    'usertype' => trim($item['usertype']),
                    'userlimit' => $userlimit,
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
                        $upTaskList[$flow->id] = $flow->status . "|" . $flow->name;
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
                ProjectTask::whereFlowItemId($id)->update([
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
     * 获取项目信息（用于判断会员是否存在项目内）
     * @param int $project_id
     * @param null|bool $archived true:仅限未归档, false:仅限已归档, null:不限制
     * @param null|bool $mustOwner true:仅限项目负责人, false:仅限非项目负责人, null:不限制
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
        if ($mustOwner === false && $project->owner) {
            throw new ApiException('禁止项目负责人操作', [ 'project_id' => $project_id ]);
        }
        return $project;
    }
}
