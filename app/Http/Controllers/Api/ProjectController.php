<?php

namespace App\Http\Controllers\Api;

use App\Models\AbstractModel;
use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\ProjectLog;
use App\Models\ProjectTask;
use App\Models\ProjectUser;
use App\Models\User;
use App\Module\Base;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Request;

/**
 * @apiDefine project
 *
 * 项目
 */
class ProjectController extends AbstractController
{
    private $projectSelect = [
        'projects.*',
        'project_users.owner',
    ];

    /**
     * 项目列表
     *
     * @apiParam {Number} [page]        当前页，默认:1
     * @apiParam {Number} [pagesize]    每页显示数量，默认:100，最大:200
     */
    public function lists()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $list = Project::select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('project_users.userid', $user->userid)
            ->orderByDesc('projects.id')
            ->paginate(Base::getPaginate(200, 100));
        //
        return Base::retSuccess('success', $list);
    }

    /**
     * 单个项目信息
     *
     * @apiParam {Number} project_id     项目ID
     */
    public function one()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $project_id = intval(Request::input('project_id'));
        //
        $project = Project::select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if (empty($project)) {
            return Base::retError('项目不存在或不在成员列表内');
        }
        //
        return Base::retSuccess('success', $project);
    }

    /**
     * 单个项目详情（比"单个项目信息"多）
     *
     * @apiParam {Number} project_id     项目ID
     */
    public function detail()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $project_id = intval(Request::input('project_id'));
        //
        $project = Project::with(['projectColumn' => function($query) {
            $query->with(['projectTask' => function($taskQuery) {
                $taskQuery->with(['taskUser', 'taskTag'])->where('parent_id', 0);
            }]);
        }, 'projectUser'])
            ->select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if (empty($project)) {
            return Base::retError('项目不存在或不在成员列表内');
        }
        $owner_user = $project->projectUser->where('owner', 1)->first();
        $project->owner_userid = $owner_user ? $owner_user->userid : 0;
        //
        return Base::retSuccess('success', $project);
    }

    /**
     * 添加项目
     *
     * @apiParam {String} name          项目名称
     * @apiParam {String} [desc]        项目描述
     * @apiParam {Array} [columns]      流程，格式[流程1, 流程2]
     */
    public function add()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //项目名称
        $name = trim(Request::input('name', ''));
        $desc = trim(Request::input('desc', ''));
        if (mb_strlen($name) < 2) {
            return Base::retError('项目名称不可以少于2个字');
        } elseif (mb_strlen($name) > 32) {
            return Base::retError('项目名称最多只能设置32个字');
        }
        if (mb_strlen($desc) > 255) {
            return Base::retError('项目描述最多只能设置255个字');
        }
        //流程
        $columns = Request::input('columns');
        if (!is_array($columns)) $columns = [];
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
            return Base::retError('项目流程最多不能超过30个');
        }
        //开始创建
        $project = Project::createInstance([
            'name' => $name,
            'desc' => $desc,
            'userid' => $user->userid,
        ]);
        return AbstractModel::transaction(function() use ($insertColumns, $project) {
            $project->save();
            ProjectUser::createInstance([
                'project_id' => $project->id,
                'userid' => $project->userid,
                'owner' => 1,
            ])->save();
            ProjectLog::createInstance([
                'project_id' => $project->id,
                'userid' => $project->userid,
                'detail' => '创建项目',
            ])->save();
            foreach ($insertColumns AS $column) {
                $column['project_id'] = $project->id;
                ProjectColumn::createInstance($column)->save();
            }
            return Base::retSuccess('添加成功', $project->find($project->id));
        });
    }

    /**
     * 修改项目
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {String} name              项目名称
     * @apiParam {String} [desc]            项目描述
     */
    public function edit()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $project_id = intval(Request::input('project_id'));
        $name = trim(Request::input('name', ''));
        $desc = trim(Request::input('desc', ''));
        if (mb_strlen($name) < 2) {
            return Base::retError('项目名称不可以少于2个字');
        } elseif (mb_strlen($name) > 32) {
            return Base::retError('项目名称最多只能设置32个字');
        }
        if (mb_strlen($desc) > 255) {
            return Base::retError('项目描述最多只能设置255个字');
        }
        //
        $project = Project::select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if (empty($project)) {
            return Base::retError('项目不存在或不在成员列表内');
        }
        if (!$project->owner) {
            return Base::retError('你不是项目负责人');
        }
        //
        $project->name = $name;
        $project->desc = $desc;
        $project->save();
        //
        return Base::retSuccess('修改成功', $project);
    }

    /**
     * 排序任务
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {Object} sort              排序数据
     * @apiParam {Number} [only_column]     仅更新列表
     */
    public function sort()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $project_id = intval(Request::input('project_id'));
        $sort = Base::json2array(Request::input('sort'));
        $only_column = intval(Request::input('only_column'));
        //
        $project = Project::select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if (empty($project)) {
            return Base::retError('项目不存在或不在成员列表内');
        }
        //
        if ($only_column) {
            // 排序列表
            $index = 0;
            foreach ($sort as $item) {
                if (!is_array($item)) continue;
                if (!intval($item['id'])) continue;
                ProjectColumn::whereId($item['id'])->whereProjectId($project->id)->update([
                    'sort' => $index
                ]);
                $index++;
            }
        } else {
            // 排序任务
            foreach ($sort as $item) {
                if (!is_array($item)) continue;
                if (!intval($item['id'])) continue;
                if (!is_array($item['task'])) continue;
                $index = 0;
                foreach ($item['task'] as $task_id) {
                    ProjectTask::whereId($task_id)->whereProjectId($project->id)->update([
                        'column_id' => $item['id'],
                        'sort' => $index
                    ]);
                    ProjectTask::whereParentId($task_id)->whereProjectId($project->id)->update([
                        'column_id' => $item['id'],
                    ]);
                    $index++;
                }
            }
        }
        return Base::retSuccess('调整成功');
    }

    /**
     * 修改项目成员
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {Number} userid            成员ID 或 成员ID组
     */
    public function user()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $project_id = intval(Request::input('project_id'));
        $userid = Request::input('userid');
        $userid = is_array($userid) ? $userid : [$userid];
        //
        $project = Project::select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if (empty($project)) {
            return Base::retError('项目不存在或不在成员列表内');
        }
        if (!$project->owner) {
            return Base::retError('你不是项目负责人');
        }
        //
        return AbstractModel::transaction(function() use ($project, $userid) {
            $array = [];
            foreach ($userid as $value) {
                if ($value > 0 && $project->joinProject($value)) {
                    $array[] = $value;
                }
            }
            $delUser = ProjectUser::whereProjectId($project->id)->whereNotIn('userid', $array)->get();
            if ($delUser->isNotEmpty()) {
                foreach ($delUser as $value) {
                    $value->exitProject();
                }
            }
            return Base::retSuccess('修改成功');
        });
    }

    /**
     * 移交项目
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {Number} owner_userid      新的项目负责人ID
     */
    public function transfer()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $project_id = intval(Request::input('project_id'));
        $owner_userid = intval(Request::input('owner_userid'));
        //
        $project = Project::select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if (empty($project)) {
            return Base::retError('项目不存在或不在成员列表内');
        }
        if (!$project->owner) {
            return Base::retError('你不是项目负责人');
        }
        //
        if (!User::whereUserid($owner_userid)->exists()) {
            return Base::retError('会员不存在');
        }
        //
        return AbstractModel::transaction(function() use ($owner_userid, $project) {
            ProjectUser::whereProjectId($project->id)->update(['owner' => 0]);
            ProjectUser::updateInsert([
                'project_id' => $project->id,
                'userid' => $owner_userid,
            ], [
                'owner' => 1,
            ]);
            //
            return Base::retSuccess('移交成功');
        });
    }

    /**
     * 退出项目
     *
     * @apiParam {Number} project_id        项目ID
     */
    public function exit()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $project_id = intval(Request::input('project_id'));
        //
        $project = Project::select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if (empty($project)) {
            return Base::retError('项目不存在或不在成员列表内');
        }
        //
        if ($project->owner) {
            return Base::retError('项目负责人无法退出项目');
        }
        //
        $projectUser = ProjectUser::whereProjectId($project->id)->whereUserid($user->userid)->first();
        if ($projectUser->exitProject()) {
            return Base::retSuccess('退出成功');
        }
        return Base::retError('退出失败');
    }

    /**
     * 删除项目
     *
     * @apiParam {Number} project_id        项目ID
     */
    public function delete()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $project_id = intval(Request::input('project_id'));
        //
        $project = Project::select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if (empty($project)) {
            return Base::retError('项目不存在或不在成员列表内');
        }
        if (!$project->owner) {
            return Base::retError('你不是项目负责人');
        }
        //
        if ($project->deleteProject()) {
            return Base::retSuccess('删除成功');
        }
        return Base::retError('删除失败');
    }

    /**
     * 添加任务列表
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {String} name              列表名称
     */
    public function column__add()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $project_id = intval(Request::input('project_id'));
        $name = trim(Request::input('name'));
        // 项目
        $project = Project::select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if (empty($project)) {
            return Base::retError('项目不存在或不在成员列表内');
        }
        //
        if (empty($name)) {
            return Base::retError('列表名称不能为空');
        }
        $column = ProjectColumn::createInstance([
            'project_id' => $project->id,
            'name' => $name,
        ]);
        $column->sort = intval(ProjectColumn::whereProjectId($project->id)->orderByDesc('sort')->value('sort')) + 1;
        $column->save();
        //
        $data = $column->find($column->id);
        $data->project_task = [];
        return Base::retSuccess('添加成功', $data);
    }

    /**
     * 修改任务列表
     *
     * @apiParam {Number} column_id         列表ID
     * @apiParam {String} [name]            列表名称
     * @apiParam {String} [color]           颜色
     */
    public function column__update()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $data = Request::all();
        $column_id = intval($data['column_id']);
        // 列表
        $column = ProjectColumn::whereId($column_id)->first();
        if (empty($column)) {
            return Base::retError('列表不存在');
        }
        // 项目
        $project = Project::select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $column->project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if (empty($project)) {
            return Base::retError('项目不存在或不在成员列表内');
        }
        //
        if (Arr::exists($data, 'name')) $column->name = $data['name'];
        if (Arr::exists($data, 'color')) $column->color = $data['color'];
        $column->save();
        return Base::retSuccess('修改成功', $column);
    }

    /**
     * 删除任务列表
     *
     * @apiParam {Number} column_id         列表ID（留空为添加列表）
     */
    public function column__delete()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $column_id = intval(Request::input('column_id'));
        // 列表
        $column = ProjectColumn::whereId($column_id)->first();
        if (empty($column)) {
            return Base::retError('列表不存在');
        }
        // 项目
        $project = Project::select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $column->project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if (empty($project)) {
            return Base::retError('项目不存在或不在成员列表内');
        }
        //
        if ($column->deleteColumn()) {
            return Base::retSuccess('删除成功');
        }
        return Base::retError('删除失败');
    }

    /**
     * 获取任务
     *
     * @apiParam {Number} task_id            任务ID
     */
    public function task__one()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $task_id = intval(Request::input('task_id'));
        // 任务
        $task = ProjectTask::with(['taskUser', 'taskTag'])->whereId($task_id)->first();
        if (empty($task)) {
            return Base::retError('任务不存在');
        }
        // 项目
        $project = Project::select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $task->project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if (empty($project)) {
            return Base::retError('项目不存在或不在成员列表内');
        }
        //
        $task->project_name = $project->name;
        $task->column_name = ProjectColumn::whereId($task->column_id)->value('name');
        //
        return Base::retSuccess('success', $task);
    }

    /**
     * 获取子任务
     *
     * @apiParam {Number} task_id            任务ID
     */
    public function task__sublist()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $task_id = intval(Request::input('task_id'));
        // 任务
        $task = ProjectTask::whereId($task_id)->first();
        if (empty($task)) {
            return Base::retError('任务不存在');
        }
        // 项目
        $project = Project::select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $task->project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if (empty($project)) {
            return Base::retError('项目不存在或不在成员列表内');
        }
        //
        $data = ProjectTask::with(['taskUser', 'taskTag'])->where('parent_id', $task->id)->whereNull('archived_at')->get();
        return Base::retSuccess('success', $data);
    }

    /**
     * 获取任务详细描述
     *
     * @apiParam {Number} task_id            任务ID
     */
    public function task__content()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $task_id = intval(Request::input('task_id'));
        // 任务
        $task = ProjectTask::whereId($task_id)->first();
        if (empty($task)) {
            return Base::retError('任务不存在');
        }
        // 项目
        $project = Project::select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $task->project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if (empty($project)) {
            return Base::retError('项目不存在或不在成员列表内');
        }
        //
        return Base::retSuccess('success', $task->content);
    }

    /**
     * 获取任务文件列表
     *
     * @apiParam {Number} task_id            任务ID
     */
    public function task__files()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $task_id = intval(Request::input('task_id'));
        // 任务
        $task = ProjectTask::whereId($task_id)->first();
        if (empty($task)) {
            return Base::retError('任务不存在');
        }
        // 项目
        $project = Project::select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $task->project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if (empty($project)) {
            return Base::retError('项目不存在或不在成员列表内');
        }
        //
        return Base::retSuccess('success', $task->taskFile);
    }

    /**
     * {post} 添加任务
     *
     * @apiParam {Number} project_id            项目ID
     * @apiParam {mixed} [column_id]            列表ID，任意值自动创建，留空取第一个
     * @apiParam {String} name                  任务描述
     * @apiParam {String} [content]             任务详情
     * @apiParam {Array} [times]                计划时间（格式：开始时间,结束时间；如：2020-01-01 00:00,2020-01-01 23:59）
     * @apiParam {mixed} [owner]                负责人，留空为自己
     * @apiParam {Array} [subtasks]             子任务（格式：[{name,owner,times}]）
     * @apiParam {Number} [top]                 添加的任务排到列表最前面
     */
    public function task__add()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        parse_str(Request::getContent(), $data);
        $project_id = intval($data['project_id']);
        $column_id = $data['column_id'];
        // 项目
        $project = Project::select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if (empty($project)) {
            return Base::retError('项目不存在或不在成员列表内');
        }
        // 列表
        $column = null;
        $newColumn = null;
        if (is_array($column_id)) {
            $column_id = Base::arrayFirst($column_id);
        }
        if ($column_id) {
            if (intval($column_id) > 0) {
                $column = $project->projectColumn->find($column_id);
            }
            if (empty($column)) {
                $column = ProjectColumn::whereProjectId($project->id)->whereName($column_id)->first();
            }
        }
        if (empty($column)) {
            $column = ProjectColumn::createInstance([
                'project_id' => $project->id,
                'name' => $column_id ?: 'Default',
            ]);
            $column->sort = intval(ProjectColumn::whereProjectId($project->id)->orderByDesc('sort')->value('sort')) + 1;
            $column->save();
            $newColumn = $column->find($column->id);
            $newColumn->project_task = [];
        }
        if (empty($column)) {
            return Base::retError('任务列表不存在或已被删除');
        }
        //
        $result = ProjectTask::addTask(array_merge($data, [
            'parent_id' => 0,
            'project_id' => $project->id,
            'column_id' => $column->id,
        ]));
        if (Base::isSuccess($result)) {
            $result['data'] = [
                'new_column' => $newColumn,
                'in_top' => intval($data['top']),
                'task' => ProjectTask::with(['taskUser', 'taskTag'])->find($result['data']['id']),
            ];
        }
        return $result;
    }

    /**
     * {post} 修改任务、子任务
     *
     * @apiParam {Number} task_id               任务ID
     * @apiParam {String} [name]                任务描述
     * @apiParam {String} [color]               任务描述（子任务不支持）
     * @apiParam {String} [content]             任务详情（子任务不支持）
     * @apiParam {Array} [times]                计划时间（格式：开始时间,结束时间；如：2020-01-01 00:00,2020-01-01 23:59）
     * @apiParam {mixed} [owner]                修改负责人
     *
     * @apiParam {String|false} [complete_at]   完成时间（如：2020-01-01 00:00，false表示未完成）
     */
    public function task__update()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        parse_str(Request::getContent(), $data);
        $task_id = intval($data['task_id']);
        // 任务
        $task = ProjectTask::whereId($task_id)->first();
        if (empty($task)) {
            return Base::retError('任务不存在');
        }
        // 项目
        $project = Project::select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $task->project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if (empty($project)) {
            return Base::retError('项目不存在或不在成员列表内');
        }
        //
        if (Base::isDate($data['complete_at'])) {
            // 标记已完成
            if ($task->complete_at) {
                return Base::retError('任务已完成');
            }
            $result = $task->completeTask(Carbon::now());
        } elseif (Arr::exists($data, 'complete_at')) {
            // 标记未完成
            if (!$task->complete_at) {
                return Base::retError('未完成任务');
            }
            $result = $task->completeTask(null);
        } else {
            // 更新任务
            $result = $task->updateTask($data);
        }
        if (Base::isSuccess($result)) {
            $result['data'] = $task->toArray();
        }
        return $result;
    }

    /**
     * 归档任务
     *
     * @apiParam {Number} task_id               任务ID
     */
    public function task__archived()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $task_id = intval(Request::input('task_id'));
        // 任务
        $task = ProjectTask::whereId($task_id)->first();
        if (empty($task)) {
            return Base::retError('任务不存在');
        }
        // 项目
        $project = Project::select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $task->project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if (empty($project)) {
            return Base::retError('项目不存在或不在成员列表内');
        }
        //
        return $task->archivedTask(Carbon::now());
    }

    /**
     * 删除任务
     *
     * @apiParam {Number} task_id               任务ID
     */
    public function task__delete()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $task_id = intval(Request::input('task_id'));
        // 任务
        $task = ProjectTask::whereId($task_id)->first();
        if (empty($task)) {
            return Base::retError('任务不存在');
        }
        // 项目
        $project = Project::select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $task->project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if (empty($project)) {
            return Base::retError('项目不存在或不在成员列表内');
        }
        //
        return $task->deleteTask();
    }
}
