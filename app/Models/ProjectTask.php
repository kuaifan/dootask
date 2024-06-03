<?php

namespace App\Models;

use DB;
use Arr;
use Request;
use Carbon\Carbon;
use App\Module\Base;
use App\Tasks\PushTask;
use App\Exceptions\ApiException;
use App\Observers\ProjectTaskObserver;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\ProjectTask
 *
 * @property int $id
 * @property int|null $parent_id 父级任务ID
 * @property int|null $project_id 项目ID
 * @property int|null $column_id 列表ID
 * @property int|null $dialog_id 聊天会话ID
 * @property int|null $flow_item_id 工作流状态ID
 * @property string|null $flow_item_name 工作流状态名称
 * @property string|null $name 标题
 * @property string|null $color 颜色
 * @property string|null $desc 描述
 * @property string|null $start_at 计划开始时间
 * @property string|null $end_at 计划结束时间
 * @property string|null $archived_at 归档时间
 * @property int|null $archived_userid 归档会员
 * @property int|null $archived_follow 跟随项目归档（项目取消归档时任务也取消归档）
 * @property string|null $complete_at 完成时间
 * @property int|null $userid 创建人
 * @property int|null $visibility 任务可见性：1-项目人员 2-任务人员 3-指定成员
 * @property int|null $p_level 优先级
 * @property string|null $p_name 优先级名称
 * @property string|null $p_color 优先级颜色
 * @property int|null $sort 排序(ASC)
 * @property string|null $loop 重复周期
 * @property string|null $loop_at 下一次重复时间
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $deleted_userid 删除会员
 * @property-read \App\Models\ProjectTaskContent|null $content
 * @property-read int $file_num
 * @property-read int $msg_num
 * @property-read bool $overdue
 * @property-read int $percent
 * @property-read int $sub_complete
 * @property-read int $sub_num
 * @property-read bool $today
 * @property-read \App\Models\Project|null $project
 * @property-read \App\Models\ProjectColumn|null $projectColumn
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectTaskFile> $taskFile
 * @property-read int|null $task_file_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectTaskTag> $taskTag
 * @property-read int|null $task_tag_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectTaskUser> $taskUser
 * @property-read int|null $task_user_count
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask allData($userid = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask authData($userid = null, $owner = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask betweenTime($start, $end, $type = 'taskTime')
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereArchivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereArchivedFollow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereArchivedUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereColumnId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereCompleteAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereDeletedUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereFlowItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereFlowItemName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereLoop($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereLoopAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask wherePColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask wherePLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask wherePName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereVisibility($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask withoutTrashed()
 * @mixin \Eloquent
 */
class ProjectTask extends AbstractModel
{
    use SoftDeletes;

    protected $appends = [
        'file_num',
        'msg_num',
        'sub_num',
        'sub_complete',
        'percent',
        'today',
        'overdue',
    ];

    /**
     * 附件数量
     * @return int
     */
    public function getFileNumAttribute()
    {
        if (!isset($this->appendattrs['file_num'])) {
            $this->appendattrs['file_num'] = $this->parent_id > 0 ? 0 : ProjectTaskFile::whereTaskId($this->id)->count();
        }
        return $this->appendattrs['file_num'];
    }

    /**
     * 消息数量
     * @return int
     */
    public function getMsgNumAttribute()
    {
        if (!isset($this->appendattrs['msg_num'])) {
            $this->appendattrs['msg_num'] = $this->dialog_id ? WebSocketDialogMsg::whereDialogId($this->dialog_id)->count() : 0;
        }
        return $this->appendattrs['msg_num'];
    }

    /**
     * 生成子任务数据
     */
    private function generateSubTaskData()
    {
        if ($this->parent_id > 0) {
            $this->appendattrs['sub_num'] = 0;
            $this->appendattrs['sub_complete'] = 0;
            $this->appendattrs['percent'] = $this->complete_at ? 100 : 0;
            return;
        }
        if (!isset($this->appendattrs['sub_num'])) {
            $builder = self::whereParentId($this->id)->whereNull('archived_at');
            $this->appendattrs['sub_num'] = $builder->count();
            $this->appendattrs['sub_complete'] = $builder->whereNotNull('complete_at')->count();
            //
            if ($this->complete_at) {
                $this->appendattrs['percent'] = 100;
            } elseif ($this->appendattrs['sub_complete'] == 0) {
                $this->appendattrs['percent'] = 0;
            } else {
                $this->appendattrs['percent'] = intval($this->appendattrs['sub_complete'] / $this->appendattrs['sub_num'] * 100);
            }
        }
    }

    /**
     * 子任务数量
     * @return int
     */
    public function getSubNumAttribute()
    {
        $this->generateSubTaskData();
        return $this->appendattrs['sub_num'];
    }

    /**
     * 子任务已完成数量
     * @return int
     */
    public function getSubCompleteAttribute()
    {
        $this->generateSubTaskData();
        return $this->appendattrs['sub_complete'];
    }

    /**
     * 进度（0-100）
     * @return int
     */
    public function getPercentAttribute()
    {
        $this->generateSubTaskData();
        return $this->appendattrs['percent'];
    }

    /**
     * 是否今日任务
     * @return bool
     */
    public function getTodayAttribute()
    {
        if ($this->end_at) {
            $end_at = Carbon::parse($this->end_at);
            if ($end_at->toDateString() == Carbon::now()->toDateString()) {
                return true;
            }
        }
        return false;
    }

    /**
     * 是否过期
     * @return bool
     */
    public function getOverdueAttribute()
    {
        if ($this->end_at) {
            if (Carbon::parse($this->end_at)->lt(Carbon::now())) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function project(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Project::class, 'id', 'project_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function projectColumn(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProjectColumn::class, 'id', 'column_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function content(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProjectTaskContent::class, 'task_id', 'id')->orderByDesc('id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function taskFile(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProjectTaskFile::class, 'task_id', 'id')->orderByDesc('id')->limit(50);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function taskUser(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProjectTaskUser::class, 'task_id', 'id')->orderByDesc('owner')->orderByDesc('id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function taskTag(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProjectTaskTag::class, 'task_id', 'id')->orderBy('id');
    }

    /**
     * 查询所有任务（与正常查询多返回owner字段）
     * @param self $query
     * @param null $userid
     * @return self
     */
    public function scopeAllData($query, $userid = null)
    {
        $userid = $userid ?: User::userid();
        $query
            ->select([
                'project_tasks.*',
                'project_task_users.owner'
            ])
            ->leftJoin('project_task_users', function ($leftJoin) use ($userid) {
                $leftJoin
                    ->on('project_task_users.userid', '=', DB::raw($userid))
                    ->on('project_tasks.id', '=', 'project_task_users.task_id');
            });
        return $query;
    }

    /**
     * 查询自己负责或参与的任务
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
                'project_tasks.*',
                'project_task_users.owner'
            ])
            ->selectRaw("1 AS assist")
            ->join('project_task_users', 'project_tasks.id', '=', 'project_task_users.task_id')
            ->where('project_task_users.userid', $userid);
        if ($owner !== null) {
            $query->where('project_task_users.owner', $owner);
        }
        return $query;
    }

    /**
     * 指定范围内的任务
     * @param $query
     * @param $start
     * @param $end
     * @param $type
     * @return mixed
     */
    public function scopeBetweenTime($query, $start, $end, $type = 'taskTime')
    {
        $query->where(function ($q1) use ($start, $end, $type) {
            switch ($type) {
                case 'createdTime':
                    $q1->where('project_tasks.created_at', '>=', $start)->where('project_tasks.created_at', '<=', $end);
                    break;

                default:
                    $q1->where(function ($q2) use ($start) {
                        $q2->where('project_tasks.start_at', '<=', $start)->where('project_tasks.end_at', '>=', $start);
                    })->orWhere(function ($q2) use ($end) {
                        $q2->where('project_tasks.start_at', '<=', $end)->where('project_tasks.end_at', '>=', $end);
                    })->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('project_tasks.start_at', '>', $start)->where('project_tasks.end_at', '<', $end);
                    });
                    break;
            }
        });
        return $query;
    }

    /**
     * 生成描述
     * @param $content
     * @return string
     */
    public static function generateDesc($content)
    {
        $content = preg_replace_callback('/<ul class="tox-checklist">(.+?)<\/ul>/is', function ($matches) {
            return preg_replace_callback('/<li([^>]*)>(.+?)<\/li>/is', function ($m) {
                if (str_contains($m[1], 'tox-checklist--checked')) {
                    return "<li{$m[1]}>[√]{$m[2]} </li>";
                } else {
                    return "<li{$m[1]}>[ ]{$m[2]} </li>";
                }
            }, $matches[0]);
        }, $content);
        return Base::cutStr(strip_tags($content), 100, 0, "...");
    }

    /**
     * 添加任务
     * @param $data
     * @return self
     */
    public static function addTask($data)
    {
        $parent_id  = intval($data['parent_id']);
        $project_id = intval($data['project_id']);
        $column_id  = intval($data['column_id']);
        $name       = $data['name'];
        $content    = $data['content'];
        $times      = $data['times'];
        $owner      = $data['owner'];
        $add_assist = intval($data['add_assist']);  // 将自己添加到参与者
        $assist     = $data['assist'];              // 参与者，此项设置时 add_assist 无效
        $subtasks   = $data['subtasks'];
        $p_level    = intval($data['p_level']);
        $p_name     = $data['p_name'];
        $p_color    = $data['p_color'];
        $top        = intval($data['top']);
        $userid     = User::userid();
        $visibility = $data['visibility_appoint'] ?? $data['visibility'];
        $visibility_userids = $data['visibility_appointor'] ?: [];
        //
        if (ProjectTask::whereProjectId($project_id)
                ->whereNull('project_tasks.complete_at')
                ->whereNull('project_tasks.archived_at')
                ->count() > 2000) {
            throw new ApiException('项目内未完成任务最多不能超过2000个');
        }
        if (ProjectTask::whereColumnId($column_id)
                ->whereNull('project_tasks.complete_at')
                ->whereNull('project_tasks.archived_at')
                ->count() > 500) {
            throw new ApiException('单个列表未完成任务最多不能超过500个');
        }
        if ($parent_id > 0 && ProjectTask::whereParentId($parent_id)
                ->whereNull('project_tasks.complete_at')
                ->whereNull('project_tasks.archived_at')
                ->count() > 50) {
            throw new ApiException('每个任务的子任务最多不能超过50个');
        }
        //
        $retPre = $parent_id ? '子任务' : '任务';
        $task = self::createInstance([
            'parent_id' => $parent_id,
            'project_id' => $project_id,
            'column_id' => $column_id,
            'p_level' => $p_level,
            'p_name' => $p_name,
            'p_color' => $p_color,
            'visibility' => $visibility ?: 1
        ]);
        if ($content) {
            $task->desc = self::generateDesc($content);
        }
        // 标题
        if (empty($name)) {
            throw new ApiException($retPre . '描述不能为空');
        } elseif (mb_strlen($name) > 255) {
            throw new ApiException($retPre . '描述最多只能设置255个字');
        }
        $task->name = $name;
        // 时间
        if ($times) {
            list($start, $end) = is_string($times) ? explode(",", $times) : (is_array($times) ? $times : []);
            if (Base::isDate($start) && Base::isDate($end) && $start != $end) {
                $task->start_at = Carbon::parse($start);
                $task->end_at = Carbon::parse($end);
            }
        }
        // 负责人
        $owner = is_array($owner) ? $owner : [$owner];
        $tmpArray = [];
        foreach ($owner as $uid) {
            if (intval($uid) == 0) continue;
            if (!ProjectUser::whereProjectId($project_id)->whereUserid($uid)->exists()) {
                throw new ApiException($retPre . '负责人填写错误');
            }
            if (ProjectTask::authData($uid)
                    ->whereNull('project_tasks.complete_at')
                    ->whereNull('project_tasks.archived_at')
                    ->count() > 500) {
                throw new ApiException(User::userid2nickname($uid) . '负责或参与的未完成任务最多不能超过500个');
            }
            $tmpArray[] = $uid;
        }
        $owner = $tmpArray;
        // 协助人员
        $assist = is_array($assist) ? $assist : [];
        if (empty($assist)) {
            // 添加自己
            if (!in_array($userid, $owner) && $add_assist) {
                $assist = [$userid];
            }
        }
        // 创建人
        $task->userid = $userid;
        // 排序位置
        if ($top) {
            $task->sort = intval(self::whereColumnId($task->column_id)->orderBy('sort')->value('sort')) - 1;
        } else {
            $task->sort = intval(self::whereColumnId($task->column_id)->orderByDesc('sort')->value('sort')) + 1;
        }
        // 工作流
        $projectFlow = ProjectFlow::whereProjectId($project_id)->orderByDesc('id')->first();
        if ($projectFlow) {
            $projectFlowItem = ProjectFlowItem::whereFlowId($projectFlow->id)->orderBy('sort')->get();
            // 赋一个开始状态
            foreach ($projectFlowItem as $item) {
                if ($item->status == 'start') {
                    $task->flow_item_id = $item->id;
                    $task->flow_item_name = $item->status . "|" . $item->name;
                    $owner = array_merge($owner, $item->userids);
                    break;
                }
            }
        }
        //
        return AbstractModel::transaction(function () use ($assist, $times, $subtasks, $content, $owner, $task, $visibility_userids) {
            $task->save();
            $owner = array_values(array_unique($owner));
            foreach ($owner as $uid) {
                ProjectTaskUser::createInstance([
                    'project_id' => $task->project_id,
                    'task_id' => $task->id,
                    'task_pid' => $task->parent_id ?: $task->id,
                    'userid' => $uid,
                    'owner' => 1,
                ])->save();
            }
            $assist = array_values(array_unique(array_diff($assist, $owner)));
            foreach ($assist as $uid) {
                ProjectTaskUser::createInstance([
                    'project_id' => $task->project_id,
                    'task_id' => $task->id,
                    'task_pid' => $task->parent_id ?: $task->id,
                    'userid' => $uid,
                    'owner' => 0,
                ])->save();
            }

            // 可见性
            foreach ($visibility_userids as $uid) {
                ProjectTaskVisibilityUser::createInstance([
                    'project_id' => $task->project_id,
                    'task_id' => $task->id,
                    'userid' => $uid
                ])->save();
            }

            if ($content) {
                ProjectTaskContent::createInstance([
                    'project_id' => $task->project_id,
                    'task_id' => $task->id,
                    'userid' => $task->userid,
                    'desc' => $task->desc,
                    'content' => [
                        'url' => ProjectTaskContent::saveContent($task->id, $content)
                    ],
                ])->save();
            }
            if ($task->parent_id == 0 && $subtasks && is_array($subtasks)) {
                foreach ($subtasks as $subtask) {
                    list($start, $end) = is_string($subtask['times']) ? explode(",", $subtask['times']) : (is_array($subtask['times']) ? $subtask['times'] : []);
                    if (Base::isDate($start) && Base::isDate($end) && $start != $end) {
                        if (Carbon::parse($start)->lt($task->start_at)) {
                            throw new ApiException('子任务开始时间不能小于主任务开始时间');
                        }
                        if (Carbon::parse($end)->gt($task->end_at)) {
                            throw new ApiException('子任务结束时间不能大于主任务结束时间');
                        }
                    } else {
                        $subtask['times'] = $times;
                    }
                    $subtask['parent_id'] = $task->id;
                    $subtask['project_id'] = $task->project_id;
                    $subtask['column_id'] = $task->column_id;
                    self::addTask($subtask);
                }
            }
            $task->addLog("创建{任务}");
            return $task;
        });
    }

    /**
     * 修改任务
     * @param $data
     * @param array $updateMarking    更新的标记
     * - is_update_project  是否更新项目数据（项目统计）
     * - is_update_content  是否更新任务详情
     * - is_update_maintask 是否更新主任务
     * - is_update_subtask  是否更新子任务
     * @return bool
     */
    public function updateTask($data, &$updateMarking = [])
    {
        //
        AbstractModel::transaction(function () use ($data, &$updateMarking) {
            // 主任务
            $mainTask = $this->parent_id > 0 ? self::find($this->parent_id) : null;
            // 工作流
            if (Arr::exists($data, 'flow_item_id')) {
                if ($this->flow_item_id == $data['flow_item_id']) {
                    throw new ApiException('任务状态未发生改变');
                }
                $flowData = [
                    'flow_item_id' => $this->flow_item_id,
                    'flow_item_name' => $this->flow_item_name,
                ];
                $currentFlowItem = null;
                $newFlowItem = ProjectFlowItem::whereProjectId($this->project_id)->find(intval($data['flow_item_id']));
                if (empty($newFlowItem) || empty($newFlowItem->projectFlow)) {
                    throw new ApiException('任务状态不存在');
                }
                if ($this->flow_item_id) {
                    // 判断符合流转
                    $currentFlowItem = ProjectFlowItem::find($this->flow_item_id);
                    if ($currentFlowItem) {
                        if (!in_array($newFlowItem->id, $currentFlowItem->turns)) {
                            throw new ApiException("当前状态[{$currentFlowItem->name}]不可流转到[{$newFlowItem->name}]");
                        }
                        if ($currentFlowItem->userlimit) {
                            $isProjectOwner = $this->useridInTheProject(User::userid()) === 2;
                            if (!$isProjectOwner && !in_array(User::userid(), $currentFlowItem->userids)) {
                                throw new ApiException("当前状态[{$currentFlowItem->name}]仅限状态负责人或项目负责人修改");
                            }
                        }
                    }
                }
                if ($newFlowItem->status == 'end') {
                    // 判断自动完成
                    if (!$this->complete_at) {
                        $flowData['complete_at'] = $this->complete_at;
                        $data['complete_at'] = date("Y-m-d H:i");
                    }
                } else {
                    // 判断自动打开
                    if ($this->complete_at) {
                        $flowData['complete_at'] = $this->complete_at;
                        $data['complete_at'] = false;
                    }
                }
                if ($newFlowItem->userids) {
                    // 判断自动添加负责人
                    $flowData['owner'] = $data['owner'] = $this->taskUser->where('owner', 1)->pluck('userid')->toArray();
                    if (in_array($newFlowItem->usertype, ["replace", "merge"])) {
                        // 流转模式、剔除模式
                        if ($this->parent_id === 0) {
                            $flowData['assist'] = $data['assist'] = $this->taskUser->where('owner', 0)->pluck('userid')->toArray();
                            $data['assist'] = array_merge($data['assist'], $data['owner']);
                        }
                        $data['owner'] = $newFlowItem->userids;
                        // 判断剔除模式：保留操作状态的人员
                        if ($newFlowItem->usertype == "merge") {
                            $data['owner'][] = User::userid();
                        }
                    } else {
                        // 添加模式
                        $data['owner'] = array_merge($data['owner'], $newFlowItem->userids);
                    }
                    $data['owner'] = array_values(array_unique($data['owner']));
                    if (isset($data['assist'])) {
                        $data['assist'] = array_values(array_unique(array_diff($data['assist'], $data['owner'])));
                    }
                }
                if ($newFlowItem->columnid && ProjectColumn::whereProjectId($this->project_id)->whereId($newFlowItem->columnid)->exists()) {
                    $data['column_id'] = $newFlowItem->columnid;
                }
                $this->flow_item_id = $newFlowItem->id;
                $this->flow_item_name = $newFlowItem->status . "|" . $newFlowItem->name;
                $this->addLog("修改{任务}状态", [
                    'flow' => $flowData,
                    'change' => [$currentFlowItem?->name, $newFlowItem->name]
                ]);
                ProjectTaskFlowChange::createInstance([
                    'task_id' => $this->id,
                    'userid' => User::userid(),
                    'before_flow_item_id' => $flowData['flow_item_id'],
                    'before_flow_item_name' => $flowData['flow_item_name'],
                    'after_flow_item_id' => $this->flow_item_id,
                    'after_flow_item_name' => $this->flow_item_name,
                ])->save();
            }
            // 状态
            if (Arr::exists($data, 'complete_at')) {
                // 子任务：主任务已完成时无法修改
                if ($mainTask?->complete_at) {
                    throw new ApiException('主任务已完成，无法修改子任务状态');
                }
                if (Base::isDate($data['complete_at'])) {
                    // 标记已完成
                    if ($this->complete_at) {
                        throw new ApiException('任务已完成');
                    }
                    $this->completeTask(Carbon::now(), isset($newFlowItem) ? $newFlowItem->name : null);
                } else {
                    // 标记未完成
                    if (!$this->complete_at) {
                        throw new ApiException('未完成任务');
                    }
                    $this->completeTask(null);
                }
                $updateMarking['is_update_project'] = true;
            }
            // 标题
            if (Arr::exists($data, 'name') && $this->name != $data['name']) {
                if (empty($data['name'])) {
                    throw new ApiException('任务描述不能为空');
                } elseif (mb_strlen($data['name']) > 255) {
                    throw new ApiException('任务描述最多只能设置255个字');
                }
                $this->addLog("修改{任务}标题", [
                    'change' => [$this->name, $data['name']]
                ]);
                $this->name = $data['name'];
                if ($this->dialog_id) {
                    WebSocketDialog::updateData(['id' => $this->dialog_id], ['name' => $this->name]);
                }
            }
            // 负责人
            if (Arr::exists($data, 'owner')) {
                $older = $this->taskUser->where('owner', 1)->pluck('userid')->toArray();
                $array = [];
                $owner = is_array($data['owner']) ? $data['owner'] : [$data['owner']];
                if (count($owner) > 10) {
                    throw new ApiException('任务负责人最多不能超过10个');
                }
                foreach ($owner as $uid) {
                    if (intval($uid) == 0) continue;
                    if (!$this->project->useridInTheProject($uid)) continue;
                    $row = ProjectTaskUser::where("task_id", $this->id)
                        ->where("userid", $uid)
                        ->where("owner", '!=', 2)
                        ->first();
                    if (empty($row)) {
                        ProjectTaskUser::createInstance([
                            'task_id' => $this->id,
                            'userid' => $uid,
                            'project_id' => $this->project_id,
                            'task_pid' => $this->parent_id ?: $this->id,
                            'owner' => 1,
                        ])->save();
                    } else {
                        $row->project_id = $this->project_id;
                        $row->task_pid = $this->parent_id ?: $this->id;
                        $row->owner = 1;
                        $row->save();
                    }
                    $array[] = $uid;
                }
                if ($array) {
                    if (count($older) == 0 && count($array) == 1 && $array[0] == User::userid()) {
                        $this->addLog("认领{任务}");
                    } else {
                        $this->addLog("修改{任务}负责人", ['userid' => $array]);
                    }
                    $this->taskPush(array_values(array_diff($array, $older)), 0);
                }
                $rows = ProjectTaskUser::whereTaskId($this->id)->whereOwner(1)->whereNotIn('userid', $array)->get();
                if ($rows->isNotEmpty()) {
                    $this->addLog("删除{任务}负责人", ['userid' => $rows->pluck('userid')]);
                    foreach ($rows as $row) {
                        $row->delete();
                    }
                }
                $updateMarking['is_update_project'] = true;
                $this->syncDialogUser();
            }
            // 可见性
            if (Arr::exists($data, 'visibility') || Arr::exists($data, 'visibility_appointor')) {
                if (Arr::exists($data, 'visibility')) {
                    $this->visibility = $data["visibility"];
                    ProjectTask::whereParentId($data['task_id'])->change(['visibility' => $data["visibility"]]);
                }
                ProjectTaskVisibilityUser::whereTaskId($data['task_id'])->delete();
                if (Arr::exists($data, 'visibility_appointor')) {
                    foreach ($data['visibility_appointor'] as $uid) {
                        if ($uid) {
                            ProjectTaskVisibilityUser::createInstance([
                                'project_id' => $this->project_id,
                                'task_id' => $this->id,
                                'userid' => $uid
                            ])->save();
                        }
                    }
                    if (!Arr::exists($data, 'visibility')) {
                        ProjectTaskObserver::visibilityUpdate($this);
                    }
                }
            }
            // 计划时间（原则：子任务时间在主任务时间内）
            if (Arr::exists($data, 'times')) {
                $oldAt = [Carbon::parse($this->start_at), Carbon::parse($this->end_at)];
                $oldStringAt = $this->start_at ? ($oldAt[0]->toDateTimeString() . '~' . $oldAt[1]->toDateTimeString()) : '';
                $this->start_at = null;
                $this->end_at = null;
                $times = $data['times'];
                list($start, $end, $desc) = is_string($times) ? explode(",", $times) : (is_array($times) ? $times : []);
                if (Base::isDate($start) && Base::isDate($end) && $start != $end) {
                    $start_at = Carbon::parse($start);
                    $end_at = Carbon::parse($end);
                    if ($this->parent_id > 0) {
                        // 判断同步主任务时间（子任务时间 超出 主任务）
                        if ($mainTask) {
                            $isUp = false;
                            if ($start_at->lt(Carbon::parse($mainTask->start_at))) {
                                $mainTask->start_at = $start_at;
                                $isUp = true;
                            }
                            if ($end_at->gt(Carbon::parse($mainTask->end_at))) {
                                $mainTask->end_at = $end_at;
                                $isUp = true;
                            }
                            if ($isUp) {
                                $updateMarking['is_update_maintask'] = true;
                                $mainTask->addLog("同步修改{任务}时间");
                                $mainTask->save();
                            }
                        }
                    }
                    $this->start_at = $start_at;
                    $this->end_at = $end_at;
                } else {
                    if ($this->parent_id > 0) {
                        // 清空子任务时间（子任务时间等于主任务时间）
                        $this->start_at = $mainTask->start_at;
                        $this->end_at = $mainTask->end_at;
                    }
                }
                if ($this->parent_id == 0) {
                    // 判断同步子任务时间（主任务时间 不在 子任务时间 之外）
                    self::whereParentId($this->id)->chunk(100, function($list) use ($oldAt, &$updateMarking) {
                        /** @var self $subTask */
                        foreach ($list as $subTask) {
                            $start_at = Carbon::parse($subTask->start_at);
                            $end_at = Carbon::parse($subTask->end_at);
                            $isUp = false;
                            if (empty($subTask->start_at) || $start_at->eq($oldAt[0]) || $start_at->lt(Carbon::parse($this->start_at))) {
                                $subTask->start_at = $this->start_at;
                                $isUp = true;
                            }
                            if (empty($subTask->end_at) || $end_at->eq($oldAt[1]) || $end_at->gt(Carbon::parse($this->end_at))) {
                                $subTask->end_at = $this->end_at;
                                $isUp = true;
                            }
                            if ($subTask->start_at && Carbon::parse($subTask->start_at)->gt($subTask->end_at)) {
                                $subTask->start_at = $this->start_at;
                                $isUp = true;
                            }
                            if ($isUp) {
                                $updateMarking['is_update_subtask'] = true;
                                $subTask->addLog("同步修改{任务}时间");
                                $subTask->save();
                            }
                        }
                    });
                }
                $newStringAt = $this->start_at ? ($this->start_at->toDateTimeString() . '~' . $this->end_at->toDateTimeString()) : '';
                $newDesc = $desc ? "（备注：{$desc}）" : "";
                $this->addLog("修改{任务}时间" . $newDesc, [
                    'change' => [$oldStringAt, $newStringAt]
                ]);
                $this->taskPush(null, 3, $newDesc);
            }
            // 以下仅顶级任务可修改
            if ($this->parent_id === 0) {
                // 重复周期
                $loopAt = $this->loop_at;
                $loopDesc = $this->loopDesc();
                if (Arr::exists($data, 'loop')) {
                    $this->loop = $data['loop'];
                    if (!$this->refreshLoop()) {
                        throw new ApiException('重复周期选择错误');
                    }
                } elseif (Arr::exists($data, 'times')) {
                    // 更新任务时间也要更新重复周期
                    $this->refreshLoop();
                }
                $oldLoop = $loopAt ? Carbon::parse($loopAt)->toDateTimeString() : null;
                $newLoop = $this->loop_at ? Carbon::parse($this->loop_at)->toDateTimeString() : null;
                if ($oldLoop != $newLoop) {
                    $this->addLog("修改{任务}下个周期", [
                        'change' => [$oldLoop, $newLoop]
                    ]);
                }
                if ($loopDesc != $this->loopDesc()) {
                    $this->addLog("修改{任务}重复周期", [
                        'change' => [$loopDesc, $this->loopDesc()]
                    ]);
                }
                // 协助人员
                if (Arr::exists($data, 'assist')) {
                    $array = [];
                    $assist = is_array($data['assist']) ? $data['assist'] : [$data['assist']];
                    if (count($assist) > 10) {
                        throw new ApiException('任务协助人员最多不能超过10个');
                    }
                    foreach ($assist as $uid) {
                        if (intval($uid) == 0) continue;
                        if (!$this->project->useridInTheProject($uid)) continue;
                        //
                        ProjectTaskUser::updateInsert([
                            'task_id' => $this->id,
                            'userid' => $uid,
                        ], [
                            'project_id' => $this->project_id,
                            'task_pid' => $this->id,
                            'owner' => 0,
                        ]);
                        $array[] = $uid;
                    }
                    if ($array) {
                        $this->addLog("修改{任务}协助人员", ['userid' => $array]);
                    }
                    $rows = ProjectTaskUser::whereTaskId($this->id)->whereOwner(0)->whereNotIn('userid', $array)->get();
                    if ($rows->isNotEmpty()) {
                        $this->addLog("删除{任务}协助人员", ['userid' => $rows->pluck('userid')]);
                        foreach ($rows as $row) {
                            $row->delete();
                        }
                    }
                    $this->syncDialogUser();
                }
                // 背景色
                if (Arr::exists($data, 'color') && $this->color != $data['color']) {
                    $this->addLog("修改{任务}背景色", [
                        'change' => [$this->color, $data['color']]
                    ]);
                    $this->color = $data['color'];
                }
                // 列表
                if (Arr::exists($data, 'column_id')) {
                    $oldName = ProjectColumn::whereProjectId($this->project_id)->whereId($this->column_id)->value('name');
                    $column = ProjectColumn::whereProjectId($this->project_id)->whereId($data['column_id'])->first();
                    if (empty($column)) {
                        throw new ApiException('请选择正确的列表');
                    }
                    $this->addLog("修改{任务}列表", [
                        'change' => [$oldName, $column->name]
                    ]);
                    $this->column_id = $column->id;
                }
                // 内容
                if (Arr::exists($data, 'content')) {
                    $logRecord = [];
                    $logContent = ProjectTaskContent::whereTaskId($this->id)->orderByDesc('id')->first();
                    if ($logContent) {
                        $logRecord['link'] = [
                            'title' => '查看历史',
                            'url' => 'single/task/content/' . $this->id . '?history_id=' . $logContent->id,
                        ];
                    }
                    $this->desc = self::generateDesc($data['content']);
                    ProjectTaskContent::createInstance([
                        'project_id' => $this->project_id,
                        'task_id' => $this->id,
                        'userid' => User::userid(),
                        'desc' => $this->desc,
                        'content' => [
                            'url' => ProjectTaskContent::saveContent($this->id, $data['content'])
                        ],
                    ])->save();
                    $this->addLog("修改{任务}详细描述", $logRecord);
                    $updateMarking['is_update_content'] = true;
                }
                // 优先级
                $p = false;
                $oldPName = $this->p_name;
                if (Arr::exists($data, 'p_level') && $this->p_level != $data['p_level']) {
                    $this->p_level = intval($data['p_level']);
                    $p = true;
                }
                if (Arr::exists($data, 'p_name') && $this->p_name != $data['p_name']) {
                    $this->p_name = trim($data['p_name']);
                    $p = true;
                }
                if (Arr::exists($data, 'p_color') && $this->p_color != $data['p_color']) {
                    $this->p_color = trim($data['p_color']);
                    $p = true;
                }
                if ($p) {
                    $this->addLog("修改{任务}优先级", [
                        'change' => [$oldPName, $this->p_name]
                    ]);
                }
            }
            $this->save();
            if ($this->start_at instanceof \DateTimeInterface) $this->start_at = $this->start_at->format('Y-m-d H:i:s');
            if ($this->end_at instanceof \DateTimeInterface) $this->end_at = $this->end_at->format('Y-m-d H:i:s');
        });
        return true;
    }

    /**
     * 刷新重复周期时间
     * @param bool $save 是否执行保存
     * @return bool
     */
    public function refreshLoop($save = false)
    {
        $success = true;
        if ($this->start_at) {
            $base = Carbon::parse($this->start_at);
            if ($base->lt(Carbon::today())) {
                // 如果任务开始时间小于今天则基数时间为今天
                $base = Carbon::parse(date("Y-m-d {$base->toTimeString()}"));
            }
        } else {
            // 未设置任务时间时基数时间为今天
            $base = Carbon::today();
        }
        switch ($this->loop) {
            case "day":
                $this->loop_at = $base->addDay();
                break;
            case "weekdays":
                $this->loop_at = $base->addWeekday();
                break;
            case "week":
                $this->loop_at = $base->addWeek();
                break;
            case "twoweeks":
                $this->loop_at = $base->addWeeks(2);
                break;
            case "month":
                $this->loop_at = $base->addMonth();
                break;
            case "year":
                $this->loop_at = $base->addYear();
                break;
            case "never":
                $this->loop_at = null;
                break;
            default:
                if (Base::isNumber($this->loop)) {
                    $this->loop_at = $base->addDays($this->loop);
                } else {
                    $this->loop_at = null;
                    $success = false;
                }
                break;
        }
        if ($success && $save) {
            $this->save();
        }
        return $success;
    }

    /**
     * 获取周期描述
     * @return string
     */
    public function loopDesc() {
        $loopDesc = "从不";
        switch ($this->loop) {
            case "day":
                $loopDesc = "每天";
                break;
            case "weekdays":
                $loopDesc = "每个工作日";
                break;
            case "week":
                $loopDesc = "每周";
                break;
            case "twoweeks":
                $loopDesc = "每两周";
                break;
            case "month":
                $loopDesc = "每月";
                break;
            case "year":
                $loopDesc = "每年";
                break;
            default:
                if (Base::isNumber($this->loop)) {
                    $loopDesc = "每{$this->loop}天";
                }
                break;
        }
        return $loopDesc;
    }

    /**
     * 复制任务
     * @return self
     */
    public function copyTask()
    {
        if ($this->parent_id > 0) {
            throw new ApiException('子任务禁止复制');
        }
        return AbstractModel::transaction(function() {
            // 复制任务
            $task = $this->replicate();
            $task->dialog_id = 0;
            $task->archived_at = null;
            $task->archived_userid = 0;
            $task->archived_follow = 0;
            $task->complete_at = null;
            $task->created_at = Carbon::now();
            $task->save();
            // 复制任务内容
            if ($this->content) {
                $tmp = $this->content->replicate();
                $tmp->task_id = $task->id;
                $tmp->created_at = Carbon::now();
                $tmp->save();
            }
            // 复制任务附件
            foreach ($this->taskFile as $taskFile) {
                $tmp = $taskFile->replicate();
                $tmp->task_id = $task->id;
                $tmp->created_at = Carbon::now();
                $tmp->save();
            }
            // 复制任务成员
            foreach ($this->taskUser as $taskUser) {
                $tmp = $taskUser->replicate();
                $tmp->task_id = $task->id;
                $tmp->task_pid = $task->id;
                $tmp->created_at = Carbon::now();
                $tmp->save();
            }
            //
            return $task;
        });
    }

    /**
     * 同步项目成员至聊天室
     */
    public function syncDialogUser()
    {
        if ($this->parent_id > 0) {
            $task = self::find($this->parent_id);
            $task?->syncDialogUser();
            return;
        }
        if (empty($this->dialog_id)) {
            return;
        }
        AbstractModel::transaction(function() {
            $userids = $this->relationUserids();
            foreach ($userids as $userid) {
                WebSocketDialogUser::updateInsert([
                    'dialog_id' => $this->dialog_id,
                    'userid' => $userid,
                ], [
                    'important' => 1
                ]);
            }
            WebSocketDialogUser::whereDialogId($this->dialog_id)->whereNotIn('userid', $userids)->whereImportant(1)->remove();
        });
    }

    /**
     * 获取任务所有人员（负责人、协助人员、子任务负责人）
     * @return array
     */
    public function relationUserids()
    {
        $userids = ProjectTaskUser::whereTaskId($this->id)->orderByDesc('owner')->orderByDesc('id')->pluck('userid')->toArray();
        $items = ProjectTask::with(['taskUser'])->where('parent_id', $this->id)->whereNull('archived_at')->get();
        foreach ($items as $item) {
            $userids = array_merge($userids, $item->taskUser->pluck('userid')->toArray());
        }
        return array_values(array_filter(array_unique($userids)));
    }

    /**
     * 会员id是否在项目里
     * @param int $userid
     * @return int 0:不存在、1存在、2存在且是管理员
     */
    public function useridInTheProject($userid)
    {
        $user = ProjectUser::whereProjectId($this->project_id)->whereUserid(intval($userid))->first();
        if (empty($user)) {
            return 0;
        }
        return $user->owner ? 2 : 1;
    }

    /**
     * 会员id是否在任务里
     * @param int $userid
     * @return int 0:不存在、1存在、2存在且是管理员
     */
    public function useridInTheTask($userid)
    {
        $user = ProjectTaskUser::whereTaskId($this->id)->whereUserid(intval($userid))->first();
        if (empty($user)) {
            return 0;
        }
        return $user->owner ? 2 : 1;
    }

    /**
     * 权限版本
     * @param int $level
     * 1：负责人
     * 2：协助人/负责人
     * 3：创建人/协助人/负责人
     * 4：任务群聊成员/3
     * @return bool
     */
    public function permission($level = 1)
    {
        if ($level >= 4) {
            return $this->permission(3) || $this->existDialogUser();
        }
        if ($level >= 3 && $this->isCreater()) {
            return true;
        }
        if ($level >= 2 && $this->isAssister()) {
            return true;
        }
        return $this->isOwner();
    }

    /**
     * 判断是否在任务对话里
     * @return bool
     */
    public function existDialogUser()
    {
        return $this->dialog_id && WebSocketDialogUser::whereDialogId($this->dialog_id)->whereUserid(User::userid())->exists();
    }

    /**
     * 判断是否创建者
     * @return bool
     */
    public function isCreater()
    {
        return $this->userid == User::userid();
    }

    /**
     * 判断是否协助人员
     * @return bool
     */
    public function isAssister()
    {
        $row = $this;
        while ($row->parent_id > 0) {
            $row = self::find($row->parent_id);
        }
        return ProjectTaskUser::whereTaskId($row->id)->whereUserid(User::userid())->whereOwner(0)->exists();
    }

    /**
     * 判断是否负责人（或者是主任务的负责人）
     * @return bool
     */
    public function isOwner()
    {
        if ($this->owner) {
            return true;
        }
        if ($this->parent_id > 0) {
            $mainTask = self::allData()->find($this->parent_id);
            if ($mainTask->owner) {
                return true;
            }
        }
        return false;
    }

    /**
     * 是否有负责人
     * @return bool
     */
    public function hasOwner()
    {
        if (!isset($this->appendattrs['has_owner'])) {
            $this->appendattrs['has_owner'] = ProjectTaskUser::whereTaskId($this->id)->whereOwner(1)->exists();
        }
        return $this->appendattrs['has_owner'];
    }

    /**
     * 标记已完成、未完成
     * @param Carbon|null $complete_at 完成时间
     * @param String $complete_name 已完成名称（留空为：已完成）
     * @return bool
     */
    public function completeTask($complete_at, $complete_name = null)
    {
        AbstractModel::transaction(function () use ($complete_at, $complete_name) {
            $addMsg = $this->parent_id == 0 && $this->dialog_id > 0;
            if ($complete_at === null) {
                // 标记未完成
                $this->complete_at = null;
                $this->addLog("标记{任务}未完成");
                if ($addMsg) {
                    WebSocketDialogMsg::sendMsg(null, $this->dialog_id, 'notice', [
                        'notice' => "标记任务未完成"
                    ], 0, true, true);
                }
            } else {
                // 标记已完成
                if ($this->parent_id == 0) {
                    if (self::whereParentId($this->id)->whereCompleteAt(null)->exists()) {
                        throw new ApiException('子任务未完成');
                    }
                }
                if (!$this->hasOwner()) {
                    throw new ApiException('请先领取任务');
                }
                if (empty($complete_name)) {
                    $complete_name = '已完成';
                }
                $this->complete_at = $complete_at;
                $this->addLog("标记{任务}{$complete_name}");
                if ($addMsg) {
                    WebSocketDialogMsg::sendMsg(null, $this->dialog_id, 'notice', [
                        'notice' => "标记任务{$complete_name}"
                    ], 0, true, true);
                }
            }
            $this->save();
        });
        return true;
    }

    /**
     * 归档任务、取消归档
     * @param Carbon|null $archived_at 归档时间
     * @return bool
     */
    public function archivedTask($archived_at, $isAuto = false)
    {
        if (!$this->complete_at) {
            $flowItems = ProjectFlowItem::whereProjectId($this->project_id)->whereStatus('end')->pluck('name');
            if ($flowItems) {
                $flowItems = implode(",", array_values(array_unique($flowItems->toArray())));
            }
            if (empty($flowItems)) {
                $flowItems = "已完成";
            }
            throw new ApiException('仅限【' . $flowItems . '】状态的任务归档');
        }
        AbstractModel::transaction(function () use ($isAuto, $archived_at) {
            if ($archived_at === null) {
                // 还原任务栏
                if (!$this->projectColumn) {
                    $this->projectColumn()->restore();
                    if($projectColumn = $this->projectColumn()->first()){
                        $projectColumn->pushMsg('recovery', $projectColumn);
                    }
                }
                // 取消归档
                $this->archived_at = null;
                $this->archived_userid = User::userid();
                $this->archived_follow = 0;
                $this->addLog("任务取消归档");
            } else {
                // 归档任务
                if ($isAuto === true) {
                    $logText = "自动任务归档";
                    $userid = 0;
                } else {
                    $logText = "任务归档";
                    $userid = User::userid();
                }
                $this->archived_at = $archived_at;
                $this->archived_userid = $userid;
                $this->archived_follow = 0;
                $this->addLog($logText, [], $userid);
            }
            $this->pushMsg($archived_at === null ? 'recovery' : 'archived', [
                'id' => $this->id,
                'archived_at' => $this->archived_at,
                'archived_userid' => $this->archived_userid,
            ]);
            self::whereParentId($this->id)->change([
                'archived_at' => $this->archived_at,
                'archived_userid' => $this->archived_userid,
                'archived_follow' => $this->archived_follow,
            ]);
            $this->save();
        });
        return true;
    }

    /**
     * 删除任务
     * @param bool $pushMsg 是否推送
     * @return bool
     */
    public function deleteTask($pushMsg = true)
    {
        AbstractModel::transaction(function () {
            if ($this->dialog_id) {
                $dialog = WebSocketDialog::find($this->dialog_id);
                $dialog?->deleteDialog();
            }
            self::whereParentId($this->id)->remove();
            $this->deleted_userid = User::userid();
            $this->save();
            $this->addLog("删除{任务}");
            $this->delete();
        });
        if ($pushMsg) {
            $this->pushMsg('delete');
        }
        return true;
    }

    /**
     * 还原任务
     * @param bool $pushMsg 是否推送
     * @return bool
     */
    public function restoreTask($pushMsg = true)
    {
        AbstractModel::transaction(function () {
            if ($this->dialog_id) {
                $dialog = WebSocketDialog::withTrashed()->find($this->dialog_id);
                $dialog?->restoreDialog();
            }
            self::whereParentId($this->id)->withTrashed()->restore();
            $this->addLog("还原{任务}");
            $this->restore();
        });
        if ($pushMsg) {
            $this->pushMsg('restore');
        }
        return true;
    }

    /**
     * 添加任务日志
     * @param string $detail
     * @param array $record
     * @param int $userid
     * @return ProjectLog
     */
    public function addLog($detail, $record = [], $userid = 0, $taskOnly = 0)
    {
        $detail = str_replace("{任务}", $this->parent_id ? "子任务" : "任务", $detail);
        $array = [
            'project_id' => $this->project_id,
            'column_id' => $this->column_id,
            'task_id' => $this->parent_id ?: $this->id,
            'userid' => $userid ?: User::userid(),
            'detail' => $detail,
        ];
        if ($this->parent_id) {
            $record['subtitle'] = $this->name;
        }
        if ($record) {
            $array['record'] = $record;
        }
        if ($taskOnly) {
            $array['task_only'] = $taskOnly;
        }
        $log = ProjectLog::createInstance($array);
        $log->save();
        return $log;
    }

    /**
     * 推送消息
     * @param string $action
     * @param array|self $data      发送内容，默认为[id, parent_id, project_id, column_id, dialog_id]
     * @param array $userid         指定会员，默认为项目所有成员
     */
    public function pushMsg($action, $data = null, $userid = null)
    {
        if (!$this->project) {
            return;
        }
        if ($data === null) {
            $data = [
                'id' => $this->id,
                'parent_id' => $this->parent_id,
                'project_id' => $this->project_id,
                'column_id' => $this->column_id,
                'dialog_id' => $this->dialog_id,
            ];
        } elseif ($data instanceof self) {
            $data = $data->toArray();
        }
        //
        if ($userid === null) {
            $userids = $this->project->relationUserids();
        } else {
            $userids = is_array($userid) ? $userid : [$userid];
        }
        //
        $array = [];
        if (Arr::exists($data, 'owner') || Arr::exists($data, 'assist')) {
            $taskUser = ProjectTaskUser::select(['userid', 'owner'])->whereTaskId($data['id'])->get();
            // 负责人
            $owners = $taskUser->where('owner', 1)->pluck('userid')->toArray();
            $owners = array_intersect($userids, $owners);
            if ($owners) {
                $array[] = [
                    'userid' => array_values($owners),
                    'data' => array_merge($data, [
                        'owner' => 1,
                        'assist' => 1,
                    ])
                ];
            }
            // 协助人
            $assists = $taskUser->where('owner', 0)->pluck('userid')->toArray();
            $assists = array_intersect($userids, $assists);
            if ($assists) {
                $array[] = [
                    'userid' => array_values($assists),
                    'data' => array_merge($data, [
                        'owner' => 0,
                        'assist' => 1,
                    ])
                ];
            }
            // 其他人
            switch ($data['visibility']) {
                case 1:
                    // 项目人员，除了负责人、协助人项目其他人
                    $userids = array_diff($userids, $owners, $assists);
                    break;
                case 2:
                    // 任务人员，除了负责人、协助人
                    $userids = [];
                    break;
                case 3:
                    // 指定成员
                    $specifys = ProjectTaskVisibilityUser::select(['userid'])->whereTaskId($data['id'])->pluck('userid')->toArray();
                    $userids = array_diff($specifys, $owners, $assists);
                    break;
                default:
                    $userids = [];
                    break;
            }
            if ($userids) {
                $array[] = [
                    'userid' => array_values($userids),
                    'data' => array_merge($data, [
                        'owner' => 0,
                        'assist' => 0,
                    ])
                ];
            }
        }
        //
        foreach ($array as $item) {
            $params = [
                'ignoreFd' => Request::header('fd'),
                'userid' => $item['userid'],
                'msg' => [
                    'type' => 'projectTask',
                    'action' => $action,
                    'data' => $item['data'],
                ]
            ];
            $task = new PushTask($params, false);
            Task::deliver($task);
        }
    }

    /**
     * 添加可见性任务 推送
     * @param array|self $data 发送内容，默认为[id, parent_id, project_id, column_id, dialog_id]
     */
    public function pushMsgVisibleAdd($data = null, array $pushUserIds = [])
    {
        if (!$this->project) {
            return;
        }
        if ($data === null) {
            $data = [
                'id' => $this->id,
                'parent_id' => $this->parent_id,
                'project_id' => $this->project_id,
                'column_id' => $this->column_id,
                'dialog_id' => $this->dialog_id,
            ];
        } elseif ($data instanceof self) {
            $data = $data->toArray();
        }
        //
        $array = [];
        if ($pushUserIds) {
            $userids = $pushUserIds;
        } elseif ($this->visibility != 1) {
            $userids = ProjectTaskUser::whereTaskId($this->id)->orWhere('task_pid', '=', $this->id)->pluck('userid')->toArray();
            if ($this->visibility == 3) {
                $userids = array_merge($userids, ProjectTaskVisibilityUser::whereTaskId($this->id)->pluck('userid')->toArray());
            }
        } else {
            $userids = ProjectUser::whereProjectId($this->project_id)->pluck('userid')->toArray();  // 项目成员
        }
        //
        $array[] = [
            'userid' => array_values($userids),
            'data' => $data
        ];
        //
        foreach ($array as $item) {
            $params = [
                'ignoreFd' => '0',
                'userid' => $item['userid'],
                'msg' => [
                    'type' => 'projectTask',
                    'action' => 'add',
                    'data' => $item['data'],
                ]
            ];
            $task = new PushTask($params, false);
            Task::deliver($task);
        }
    }

    /**
     * 删除可见性任务 推送
     * @param array $userids
     * @return void
     */
    public function pushMsgVisibleRemove(array $userids = [])
    {
        if (!$this->project) {
            return;
        }
        $data = [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'project_id' => $this->project_id,
            'column_id' => $this->column_id,
            'dialog_id' => $this->dialog_id,
        ];
        //
        $array = [];
        if (empty($userids)) {
            // 默认 项目成员 与 项目负责人，任务负责人、协助人的差集
            $projectUserids = ProjectUser::whereProjectId($this->project_id)->pluck('userid')->toArray();  // 项目成员
            $projectOwner = ProjectUser::whereProjectId($this->project_id)->whereOwner(1)->pluck('userid')->toArray();  // 项目负责人
            $taskOwnerAndAssists = ProjectTaskUser::select(['userid', 'owner'])->whereIn('owner', [0, 1])->whereTaskId($this->id)->pluck('userid')->toArray();
            $subUserids = ProjectTaskUser::whereTaskPid($this->id)->pluck('userid')->toArray();
            $userids = array_diff($projectUserids, $projectOwner, $taskOwnerAndAssists, $subUserids);
        }
        //
        $array[] = [
            'userid' => array_values($userids),
            'data' => $data
        ];
        //
        foreach ($array as $item) {
            $params = [
                'ignoreFd' => '0',
                'userid' => $item['userid'],
                'msg' => [
                    'type' => 'projectTask',
                    'action' => 'delete',
                    'data' => $item['data'],
                ]
            ];
            $task = new PushTask($params, false);
            Task::deliver($task);
        }
    }

    /**
     * 更新可见性任务 推送
     * @param array|self $data 发送内容，默认为[id, parent_id, project_id, column_id, dialog_id]
     */
    public function pushMsgVisibleUpdate($data, array $deleteUserIds = [], array $addUserIds = [])
    {
        if ($deleteUserIds) {
            $this->pushMsgVisibleRemove($deleteUserIds);
        }
        if ($addUserIds) {
            $this->pushMsgVisibleAdd($data, $addUserIds);
        }
    }

    /**
     * 任务提醒
     * @param $userids
     * @param int $type 0-新任务、1-即将超时、2-已超时、3-修改时间
     * @param string $suffix 描述后缀
     * @return void
     */
    public function taskPush($userids, int $type, string $suffix = "")
    {
        if ($userids === null) {
            $userids = $this->taskUser->pluck('userid')->toArray();
        }
        if (empty($userids)) {
            return;
        }
        $owners = $this->taskUser->pluck('owner', 'userid')->toArray();
        $receivers = User::whereIn('userid', $userids)->whereNull('disable_at')->get();
        if (empty($receivers)) {
            return;
        }
        //
        $userid = User::userid();
        //
        $botUser = User::botGetOrCreate('task-alert');
        if (empty($botUser)) {
            return;
        }

        $dataId = $this->parent_id ?: $this->id;
        $taskHtml = "<span class=\"mention task\" data-id=\"{$dataId}\">#{$this->name}</span>";
        $text = match ($type) {
            1 => "您的任务 {$taskHtml} 即将超时。",
            2 => "您的任务 {$taskHtml} 已经超时。",
            3 => "您的任务 {$taskHtml} 时间已修改。",
            default => "您有一个新任务 {$taskHtml}。",
        };

        /** @var User $user */
        foreach ($receivers as $receiver) {
            $data = [
                'type' => $type,
                'userid' => $receiver->userid,
                'task_id' => $this->id,
            ];
            if (in_array($type, [1, 2]) && ProjectTaskPushLog::where($data)->exists()) {
                continue;
            }
            //
            $replace = $owners[$receiver->userid] ? "您负责的任务" : "您协助的任务";
            $dialog = WebSocketDialog::checkUserDialog($botUser, $receiver->userid);
            if ($dialog) {
                ProjectTaskPushLog::createInstance($data)->save();
                WebSocketDialogMsg::sendMsg(null, $dialog->id, 'text', [
                    'text' => str_replace("您的任务", $replace, $text) . $suffix
                ], in_array($type, [0, 3]) ? $userid : $botUser->userid);
            }
        }
    }

    /**
     * 移动任务
     * @param int $project_id
     * @param int $column_id
     * @param int $flowItemId
     * @param array $owner
     * @param array $assist
     * @return bool
     */
    public function moveTask(int $projectId, int $columnId,int $flowItemId = 0,array $owner = [], array $assist = [], string $completeAt='')
    {
        AbstractModel::transaction(function () use ($projectId, $columnId, $flowItemId, $owner, $assist, $completeAt) {
            $newTaskUser =  array_merge($owner, $assist);
            //
            $this->project_id = $projectId;
            $this->column_id = $columnId;
            // 任务内容
            if ($this->content) {
                $this->content->project_id = $projectId;
                $this->content->save();
            }
            // 任务文件
            foreach ($this->taskFile as $taskFile) {
                $taskFile->project_id = $projectId;
                $taskFile->save();
            }
            // 任务标签
            foreach ($this->taskTag as $taskTag) {
                $taskTag->project_id = $projectId;
                $taskTag->save();
            }
            // 任务用户
            $this->updateTask([
                'owner' => $owner,
                'assist' => $assist
            ]);
            foreach ($this->taskUser as $taskUser) {
                if (in_array($taskUser->id, $newTaskUser)) {
                    $taskUser->project_id = $projectId;
                    $taskUser->save();
                }
            }
            //
            if ($flowItemId) {
                $flowItem = projectFlowItem::whereProjectId($projectId)->whereId($flowItemId)->first();
                $this->flow_item_id = $flowItemId;
                $this->flow_item_name = $flowItem->status . "|" . $flowItem->name;
                if ($flowItem->status == 'end') {
                    $this->completeTask(Carbon::now(), $flowItem->name);
                } else {
                    $this->completeTask(null);
                }
            } else {
                $this->flow_item_id = 0;
                $this->flow_item_name = '';
            }
            //
            if ($completeAt) {
                $this->complete_at = $completeAt;
            }
            //
            $this->save();
            //
            $this->addLog("移动{任务}");
        });
        $this->pushMsg('update');
        return true;
    }

    /**
     * 获取任务
     * @param $task_id
     * @return ProjectTask|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public static function oneTask($task_id)
    {
        $data = self::with(['taskUser', 'taskTag'])->allData()->where("project_tasks.id", intval($task_id))->first();
        if ($data && $data->parent_id === 0) {
            if ($data->owner || ProjectTaskUser::select(['owner'])->whereTaskId($data->id)->whereUserid(User::userid())->exists()) {
                $data->assist = 1;
            } else {
                $data->assist = 0;
            }
        }
        return $data;
    }

    /**
     * 获取任务（会员有任务权限 或 会员存在项目内）
     * @param int $task_id
     * @param bool $archived true:仅限未归档, false:仅限已归档, null:不限制
     * @param bool $trashed true:仅限未删除, false:仅限已删除, null:不限制
     * @param array $with
     * @return self
     */
    public static function userTask($task_id, $archived = true, $trashed = true, $with = [])
    {
        $builder = self::with($with)->allData()->where("project_tasks.id", intval($task_id));
        if ($trashed === false) {
            $builder->onlyTrashed();
        } elseif ($trashed === null) {
            $builder->withTrashed();
        }
        $task = $builder->first();
        //
        if (empty($task)) {
            throw new ApiException('任务不存在', ['task_id' => $task_id], -4002);
        }
        if ($archived === true && $task->archived_at != null) {
            throw new ApiException('任务已归档', ['task_id' => $task_id]);
        }
        if ($archived === false && $task->archived_at == null) {
            throw new ApiException('任务未归档', ['task_id' => $task_id]);
        }
        //
        try {
            $project = Project::userProject($task->project_id);
        } catch (\Throwable $e) {
            if ($task->owner !== null || $task->permission(4)) {
                $project = Project::find($task->project_id);
                if (empty($project)) {
                    throw new ApiException('项目不存在或已被删除', [ 'task_id' => $task_id ], -4002);
                }
            } else {
                throw new ApiException($e->getMessage(), [ 'task_id' => $task_id ], -4002);
            }
        }
        //
        return $task;
    }
}
