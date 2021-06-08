<?php

namespace App\Models;

use App\Module\Base;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ProjectTask
 *
 * @package App\Models
 * @property int $id
 * @property int|null $parent_id 父级任务ID
 * @property int|null $project_id 项目ID
 * @property int|null $column_id 列表ID
 * @property string|null $name 标题
 * @property string|null $desc 描述
 * @property string|null $start_at 计划开始时间
 * @property string|null $end_at 计划结束时间
 * @property string|null $archived_at 归档时间
 * @property string|null $complete_at 完成时间
 * @property int|null $userid 创建人
 * @property int|null $p_level 优先级
 * @property string|null $p_name 优先级名称
 * @property string|null $p_color 优先级颜色
 * @property int|null $sort 排序(ASC)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int $dialog_id
 * @property-read int $file_num
 * @property-read int $msg_num
 * @property-read bool $overdue
 * @property-read int $percent
 * @property-read int $sub_num
 * @property-read bool $today
 * @property-read \App\Models\Project|null $project
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProjectTaskTag[] $taskTag
 * @property-read int|null $task_tag_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProjectTaskUser[] $taskUser
 * @property-read int|null $task_user_count
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask newQuery()
 * @method static \Illuminate\Database\Query\Builder|ProjectTask onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereArchivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereColumnId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereCompleteAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTask whereId($value)
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
 * @method static \Illuminate\Database\Query\Builder|ProjectTask withTrashed()
 * @method static \Illuminate\Database\Query\Builder|ProjectTask withoutTrashed()
 * @mixin \Eloquent
 */
class ProjectTask extends AbstractModel
{
    use SoftDeletes;

    protected $appends = [
        'file_num',
        'msg_num',
        'sub_num',
        'dialog_id',
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
        if (!isset($this->attributes['file_num'])) {
            $this->attributes['file_num'] = ProjectTaskFile::whereTaskId($this->id)->count();
        }
        return $this->attributes['file_num'];
    }

    /**
     * 消息数量
     * @return int
     */
    public function getMsgNumAttribute()
    {
        if (!isset($this->attributes['msg_num'])) {
            $this->attributes['msg_num'] = WebSocketDialogMsg::whereDialogId($this->dialog_id)->whereExtraInt($this->id)->count();
        }
        return $this->attributes['msg_num'];
    }

    /**
     * 子任务数量
     * @return int
     */
    public function getSubNumAttribute()
    {
        if ($this->parent_id > 0) {
            return 0;
        }
        if (!isset($this->attributes['sub_num'])) {
            $this->attributes['sub_num'] = self::whereParentId($this->id)->count();
        }
        return $this->attributes['sub_num'];
    }

    /**
     * 对话ID
     * @return int
     */
    public function getDialogIdAttribute()
    {
        if (!isset($this->attributes['dialog_id'])) {
            $this->attributes['dialog_id'] = intval(Project::whereId($this->project_id)->value('dialog_id'));
        }
        return $this->attributes['dialog_id'];
    }

    /**
     * 进度（0-100）
     * @return int
     */
    public function getPercentAttribute()
    {
        if ($this->parent_id > 0) {
            return 0;
        }
        $builder = self::whereParentId($this->id);
        if (!isset($this->attributes['sub_num'])) {
            $this->attributes['sub_num'] = $builder->count();
        }
        $subTaskTotal = $this->attributes['sub_num'];
        if ($subTaskTotal == 0) {
            return $this->complete_at ? 1 : 0;
        }
        $subTaskComplete = $builder->whereNotNull('complete_at')->count();
        if ($subTaskComplete == 0) {
            return 0;
        }
        return intval($subTaskComplete / $subTaskTotal * 100);
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function taskUser(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(projectTaskUser::class, 'task_id', 'id')->orderByDesc('id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function taskTag(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(projectTaskTag::class, 'task_id', 'id')->orderByDesc('id');
    }

    /**
     * 添加任务
     * @param $params
     * @return array|bool
     */
    public static function addTask($params)
    {
        $parent_id  = intval($params['parent_id']);
        $project_id = intval($params['project_id']);
        $column_id  = intval($params['column_id']);
        $name       = $params['name'];
        $content    = $params['content'];
        $times      = $params['times'];
        $owner      = $params['owner'];
        $subtasks   = $params['subtasks'];
        $p_level    = intval($params['p_level']);
        $p_name     = $params['p_name'];
        $p_color    = $params['p_color'];
        $top        = intval($params['top']);
        //
        $retPre = $parent_id ? '子任务' : '任务';
        $task = self::createInstance();
        $task->parent_id = $parent_id;
        $task->project_id = $project_id;
        $task->column_id = $column_id;
        $task->p_level = $p_level;
        $task->p_name = $p_name;
        $task->p_color = $p_color;
        if ($content) {
            $task->desc = Base::getHtml($content);
        }
        // 标题
        if (empty($name)) {
            return Base::retError($retPre . '描述不能为空');
        } elseif (mb_strlen($name) > 255) {
            return Base::retError($retPre . '描述最多只能设置255个字');
        }
        $task->name = $name;
        // 时间
        if ($times) {
            list($start, $end) = is_string($times) ? explode(",", $times) : (is_array($times) ? $times : []);
            if (Base::isDate($start) && Base::isDate($end)) {
                if ($start != $end) {
                    $task->start_at = Carbon::parse($start);
                    $task->end_at = Carbon::parse($end);
                }
            }
        }
        // 负责人
        if (is_array($owner)) {
            $owner = Base::arrayFirst($owner);
        }
        $owner = $owner ?: User::token2userid();
        if (!ProjectUser::whereProjectId($project_id)->whereUserid($owner)->exists()) {
            return Base::retError($retPre . '负责人填写错误');
        }
        // 创建人
        $task->userid = User::token2userid();
        // 排序位置
        if ($top) {
            $task->sort = intval(self::whereColumnId($task->column_id)->orderBy('sort')->value('sort')) - 1;
        } else {
            $task->sort = intval(self::whereColumnId($task->column_id)->orderByDesc('sort')->value('sort')) + 1;
        }
        //
        return AbstractModel::transaction(function() use ($subtasks, $content, $owner, $task) {
            $task->save();
            if ($owner) {
                ProjectTaskUser::createInstance([
                    'project_id' => $task->parent_id,
                    'task_id' => $task->id,
                    'userid' => $owner,
                    'owner' => 1,
                ])->save();
            }
            if ($content) {
                ProjectTaskContent::createInstance([
                    'project_id' => $task->parent_id,
                    'task_id' => $task->id,
                    'content' => $content,
                ])->save();
            }
            if ($task->parent_id == 0 && $subtasks && is_array($subtasks)) {
                foreach ($subtasks as $subtask) {
                    $subtask['parent_id'] = $task->id;
                    $subtask['project_id'] = $task->project_id;
                    $subtask['column_id'] = $task->column_id;
                    $subtask['p_level'] = $task->p_level;
                    $subtask['p_name'] = $task->p_name;
                    $subtask['p_color'] = $task->p_color;
                    $result = self::addTask($subtask);
                    if (Base::isError($result)) {
                        return $result;
                    }
                }
            }
            return Base::retSuccess('添加成功', [
                'id' => $task->id
            ]);
        });
    }

    /**
     * 修改任务
     * @param $params
     * @return array|bool
     */
    public function updateTask($params)
    {
        return AbstractModel::transaction(function () use ($params) {
            $name       = $params['name'];
            $color       = $params['color'];
            $content    = $params['content'];
            $times      = $params['times'];
            $owner      = $params['owner'];
            // 子任务禁止修改项
            if ($this->parent_id > 0) {
                $color = null;
                $content = null;
            }
            // 名称、背景色
            if ($name) $this->name = $name;
            if ($color) $this->color = $color;
            if ($content) {
                ProjectTaskContent::updateInsert([
                    'project_id' => $this->parent_id,
                    'task_id' => $this->id,
                ], [
                    'content' => $content,
                ]);
            }
            // 计划时间
            if ($times) {
                list($start, $end) = is_string($times) ? explode(",", $times) : (is_array($times) ? $times : []);
                if (Base::isDate($start) && Base::isDate($end)) {
                    if ($start != $end) {
                        $this->start_at = Carbon::parse($start);
                        $this->end_at = Carbon::parse($end);
                    }
                }
            }
            // 负责人
            if ($owner) {
                if (is_array($owner)) {
                    $owner = Base::arrayFirst($owner);
                }
                $ownerUser = ProjectTaskUser::whereTaskId($this->id)->whereOwner(1)->first();
                if ($ownerUser->userid != $owner) {
                    $ownerUser->owner = 0;
                    $ownerUser->save();
                    ProjectTaskUser::updateInsert([
                        'project_id' => $this->parent_id,
                        'task_id' => $this->id,
                        'userid' => $owner,
                    ], [
                        'owner' => 1,
                    ]);
                }
            }
            $this->save();
            return Base::retSuccess('修改成功');
        });
    }
}
