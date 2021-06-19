<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Models\AbstractModel;
use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\ProjectLog;
use App\Models\ProjectTask;
use App\Models\ProjectTaskFile;
use App\Models\ProjectUser;
use App\Models\User;
use App\Models\WebSocketDialog;
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
    /**
     * 获取项目列表
     *
     * @apiParam {Number} [page]        当前页，默认:1
     * @apiParam {Number} [pagesize]    每页显示数量，默认:100，最大:200
     */
    public function lists()
    {
        $user = User::auth();
        //
        $list = Project::select(Project::projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('project_users.userid', $user->userid)
            ->orderByDesc('projects.id')
            ->paginate(Base::getPaginate(200, 100));
        //
        return Base::retSuccess('success', $list);
    }

    /**
     * 获取项目基础信息
     *
     * @apiParam {Number} project_id     项目ID
     */
    public function basic()
    {
        user::auth();
        //
        $project_id = intval(Request::input('project_id'));
        //
        $project = Project::userProject($project_id);
        //
        return Base::retSuccess('success', $project);
    }

    /**
     * 获取项目详细信息
     *
     * @apiParam {Number} project_id     项目ID
     */
    public function detail()
    {
        $user = User::auth();
        //
        $project_id = intval(Request::input('project_id'));
        //
        $project = Project::with(['projectColumn' => function($query) {
            $query->with(['projectTask' => function($taskQuery) {
                $taskQuery->with(['taskUser', 'taskTag'])->where('parent_id', 0);
            }]);
        }, 'projectUser'])
            ->select(project::projectSelect)
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
     * @apiParam {String} [desc]        项目介绍
     * @apiParam {Array} [columns]      流程，格式[流程1, 流程2]
     */
    public function add()
    {
        $user = User::auth();
        //项目名称
        $name = trim(Request::input('name', ''));
        $desc = trim(Request::input('desc', ''));
        if (mb_strlen($name) < 2) {
            return Base::retError('项目名称不可以少于2个字');
        } elseif (mb_strlen($name) > 32) {
            return Base::retError('项目名称最多只能设置32个字');
        }
        if (mb_strlen($desc) > 255) {
            return Base::retError('项目介绍最多只能设置255个字');
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
        AbstractModel::transaction(function() use ($insertColumns, $project) {
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
        });
        //
        $data = $project->find($project->id);
        $data->addLog("创建项目");
        $data->pushMsg('add', $data->toArray());
        return Base::retSuccess('添加成功', $data);
    }

    /**
     * 修改项目
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {String} name              项目名称
     * @apiParam {String} [desc]            项目介绍
     */
    public function update()
    {
        user::auth();
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
            return Base::retError('项目介绍最多只能设置255个字');
        }
        //
        $project = Project::userProject($project_id);
        if (!$project->owner) {
            return Base::retError('你不是项目负责人');
        }
        //
        if ($project->name != $name) {
            $project->addLog("修改项目名称：{$project->name} => {$name}");
            $project->name = $name;
        }
        if ($project->desc != $desc) {
            $project->desc = $desc;
            $project->addLog("修改项目介绍");
        }
        $project->save();
        $project->pushMsg('update', $project->toArray());
        //
        return Base::retSuccess('修改成功', $project);
    }

    /**
     * 修改项目成员
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {Number} userid            成员ID 或 成员ID组
     */
    public function user()
    {
        user::auth();
        //
        $project_id = intval(Request::input('project_id'));
        $userid = Request::input('userid');
        $userid = is_array($userid) ? $userid : [$userid];
        //
        $project = Project::userProject($project_id);
        if (!$project->owner) {
            return Base::retError('你不是项目负责人');
        }
        //
        $deleteUser = AbstractModel::transaction(function() use ($project, $userid) {
            $array = [];
            foreach ($userid as $uid) {
                if ($project->joinProject($uid)) {
                    $array[] = $uid;
                }
            }
            $delete = ProjectUser::whereProjectId($project->id)->whereNotIn('userid', $array);
            $deleteUser = $delete->pluck('userid');
            $delete->delete();
            $project->syncDialogUser();
            $project->addLog("修改项目成员");
            return $deleteUser->toArray();
        });
        //
        $project->pushMsg('delete', null, $deleteUser);
        $project->pushMsg('detail');
        return Base::retSuccess('修改成功', ['id' => $project->id]);
    }

    /**
     * 移交项目
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {Number} owner_userid      新的项目负责人ID
     */
    public function transfer()
    {
        user::auth();
        //
        $project_id = intval(Request::input('project_id'));
        $owner_userid = intval(Request::input('owner_userid'));
        //
        $project = Project::userProject($project_id);
        if (!$project->owner) {
            return Base::retError('你不是项目负责人');
        }
        //
        if (!User::whereUserid($owner_userid)->exists()) {
            return Base::retError('会员不存在');
        }
        //
        AbstractModel::transaction(function() use ($owner_userid, $project) {
            ProjectUser::whereProjectId($project->id)->update(['owner' => 0]);
            ProjectUser::updateInsert([
                'project_id' => $project->id,
                'userid' => $owner_userid,
            ], [
                'owner' => 1,
            ]);
            $project->syncDialogUser();
            $project->addLog("移交项目给会员ID：" . $owner_userid);
        });
        //
        $project->pushMsg('detail');
        return Base::retSuccess('移交成功', ['id' => $project->id]);
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
        user::auth();
        //
        $project_id = intval(Request::input('project_id'));
        $sort = Base::json2array(Request::input('sort'));
        $only_column = intval(Request::input('only_column'));
        //
        $project = Project::userProject($project_id);
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
            $project->addLog("调整列表排序");
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
            $project->addLog("调整任务排序");
        }
        $project->pushMsg('detail');
        return Base::retSuccess('调整成功');
    }

    /**
     * 退出项目
     *
     * @apiParam {Number} project_id        项目ID
     */
    public function exit()
    {
        $user = User::auth();
        //
        $project_id = intval(Request::input('project_id'));
        //
        $project = Project::userProject($project_id);
        //
        if ($project->owner) {
            return Base::retError('项目负责人无法退出项目');
        }
        //
        AbstractModel::transaction(function() use ($user, $project) {
            ProjectUser::whereProjectId($project->id)->whereUserid($user->userid)->delete();
            $project->syncDialogUser();
            $project->addLog("会员ID：" . $user->userid . " 退出项目");
            $project->pushMsg('delete', null, $user->userid);
        });
        return Base::retSuccess('退出成功', ['id' => $project->id]);
    }

    /**
     * 删除项目
     *
     * @apiParam {Number} project_id        项目ID
     */
    public function delete()
    {
        user::auth();
        //
        $project_id = intval(Request::input('project_id'));
        //
        $project = Project::userProject($project_id);
        if (!$project->owner) {
            return Base::retError('你不是项目负责人');
        }
        //
        $project->deleteProject();
        return Base::retSuccess('删除成功', ['id' => $project->id]);
    }

    /**
     * 添加任务列表
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {String} name              列表名称
     */
    public function column__add()
    {
        user::auth();
        //
        $project_id = intval(Request::input('project_id'));
        $name = trim(Request::input('name'));
        // 项目
        $project = Project::userProject($project_id);
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
        $column->addLog("创建列表：" . $column->name);
        //
        $data = $column->find($column->id);
        $data->project_task = [];
        $data->pushMsg("add", $data->toArray());
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
        $user = User::auth();
        //
        $data = Request::all();
        $column_id = intval($data['column_id']);
        // 列表
        $column = ProjectColumn::whereId($column_id)->first();
        if (empty($column)) {
            return Base::retError('列表不存在');
        }
        // 项目
        $project = Project::select(project::projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $column->project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if (empty($project)) {
            return Base::retError('项目不存在或不在成员列表内');
        }
        //
        if (Arr::exists($data, 'name') && $column->name != $data['name']) {
            $column->addLog("修改列表名称：{$column->name} => {$data['name']}");
            $column->name = $data['name'];
        }
        if (Arr::exists($data, 'color') && $column->color != $data['color']) {
            $column->addLog("修改列表颜色：{$column->color} => {$data['color']}");
            $column->color = $data['color'];
        }
        $column->save();
        $column->pushMsg("update", $column->toArray());
        return Base::retSuccess('修改成功', $column);
    }

    /**
     * 删除任务列表
     *
     * @apiParam {Number} column_id         列表ID（留空为添加列表）
     */
    public function column__delete()
    {
        $user = User::auth();
        //
        $column_id = intval(Request::input('column_id'));
        // 列表
        $column = ProjectColumn::whereId($column_id)->first();
        if (empty($column)) {
            return Base::retError('列表不存在');
        }
        // 项目
        $project = Project::select(project::projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $column->project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if (empty($project)) {
            return Base::retError('项目不存在或不在成员列表内');
        }
        //
        $column->deleteColumn();
        return Base::retSuccess('删除成功', $column->toArray());
    }

    /**
     * 任务列表
     *
     * @apiParam {String} name          任务名称（包含）
     * @apiParam {Array} time           时间范围，格式：数组，如：[2020-12-12,2020-20-12]
     */
    public function task__lists()
    {
        $user = user::auth();
        //
        $builder = ProjectTask::select(ProjectTask::taskSelect);
        //
        $name = Request::input('name');
        $time = Request::input('time');
        if ($name) {
            $builder->where(function($query) use ($name) {
                $query->where('project_tasks.name', 'like', '%,' . $name . ',%');
            });
        }
        if (is_array($time)) {
            if (Base::isDateOrTime($time[0]) && Base::isDateOrTime($time[1])) {
                $between = [
                    Carbon::parse($time[0])->startOfDay(),
                    Carbon::parse($time[1])->endOfDay()
                ];
                $builder->where(function($query) use ($between) {
                    $query->whereBetween('project_tasks.start_at', $between)->orWhereBetween('project_tasks.end_at', $between);
                });
            }
        }
        //
        $list = $builder
            ->join('project_task_users', 'project_tasks.id', '=', 'project_task_users.task_pid')
            ->whereNull('project_tasks.archived_at')
            ->where('project_tasks.parent_id', 0)
            ->where('project_task_users.userid', $user->userid)
            ->orderByDesc('project_tasks.id')
            ->paginate(Base::getPaginate(200, 100));
        //
        return Base::retSuccess('success', $list);
    }

    /**
     * 获取任务基础信息
     *
     * @apiParam {Number} task_id            任务ID
     */
    public function task__basic()
    {
        user::auth();
        //
        $task_id = intval(Request::input('task_id'));
        //
        $task = ProjectTask::userTask($task_id, ['taskUser', 'taskTag']);
        //
        $task->project_name = Project::whereId($task->project_id)->value('name');
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
        user::auth();
        //
        $task_id = intval(Request::input('task_id'));
        //
        $task = ProjectTask::userTask($task_id);
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
        user::auth();
        //
        $task_id = intval(Request::input('task_id'));
        //
        $task = ProjectTask::userTask($task_id);
        //
        return Base::retSuccess('success', $task->content ?: json_decode('{}'));
    }

    /**
     * 获取任务文件列表
     *
     * @apiParam {Number} task_id            任务ID
     */
    public function task__files()
    {
        user::auth();
        //
        $task_id = intval(Request::input('task_id'));
        //
        $task = ProjectTask::userTask($task_id);
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
     * @apiParam {Number} [owner]               负责人，留空为自己
     * @apiParam {Array} [subtasks]             子任务（格式：[{name,owner,times}]）
     * @apiParam {Number} [top]                 添加的任务排到列表最前面
     */
    public function task__add()
    {
        user::auth();
        parse_str(Request::getContent(), $data);
        $project_id = intval($data['project_id']);
        $column_id = $data['column_id'];
        // 项目
        $project = Project::userProject($project_id);
        // 列表
        $column = null;
        $newColumn = null;
        if ($column_id) {
            if (intval($column_id) > 0) {
                $column = $project->projectColumn->find($column_id);
            }
            if (empty($column)) {
                $column = ProjectColumn::whereProjectId($project->id)->whereName($column_id)->first();
            }
        } else {
            $column = ProjectColumn::whereProjectId($project->id)->orderBy('id')->first();
        }
        if (empty($column)) {
            $column = ProjectColumn::createInstance([
                'project_id' => $project->id,
                'name' => $column_id ?: 'Default',
            ]);
            $column->sort = intval(ProjectColumn::whereProjectId($project->id)->orderByDesc('sort')->value('sort')) + 1;
            $column->save();
            $column->addLog("创建列表：" . $column->name);
            $newColumn = $column->find($column->id);
            $newColumn->project_task = [];
        }
        if (empty($column)) {
            return Base::retError('任务列表不存在或已被删除');
        }
        //
        $task = ProjectTask::addTask(array_merge($data, [
            'parent_id' => 0,
            'project_id' => $project->id,
            'column_id' => $column->id,
        ]));
        $data = [
            'new_column' => $newColumn,
            'in_top' => intval($data['top']),
            'task' => ProjectTask::with(['taskUser', 'taskTag'])->find($task->id),
        ];
        $task->pushMsg('add', $data);
        return Base::retSuccess('添加成功', $data);
    }

    /**
     * 添加子任务
     *
     * @apiParam {Number} task_id               任务ID
     * @apiParam {String} name                  任务描述
     */
    public function task__addsub()
    {
        user::auth();
        //
        $task_id = intval(Request::input('task_id'));
        $name = Request::input('name');
        //
        $task = ProjectTask::userTask($task_id);
        //
        $task = ProjectTask::addTask([
            'name' => $name,
            'parent_id' => $task->id,
            'project_id' => $task->project_id,
            'column_id' => $task->column_id,
        ]);
        $data = [
            'new_column' => null,
            'in_top' => 0,
            'task' => ProjectTask::with(['taskUser', 'taskTag'])->find($task->id),
        ];
        $task->pushMsg('add', $data);
        return Base::retSuccess('添加成功', $data);
    }

    /**
     * {post} 修改任务、子任务
     *
     * @apiParam {Number} task_id               任务ID
     * @apiParam {String} [name]                任务描述
     * @apiParam {Array} [times]                计划时间（格式：开始时间,结束时间；如：2020-01-01 00:00,2020-01-01 23:59）
     * @apiParam {Number} [owner]               修改负责人
     * @apiParam {String} [content]             任务详情（子任务不支持）
     * @apiParam {String} [color]               背景色（子任务不支持）
     * @apiParam {Array} [assist]               修改协助人员（子任务不支持）
     *
     * @apiParam {Number} [p_level]             优先级相关（子任务不支持）
     * @apiParam {String} [p_name]              优先级相关（子任务不支持）
     * @apiParam {String} [p_color]             优先级相关（子任务不支持）
     *
     * @apiParam {String|false} [complete_at]   完成时间（如：2020-01-01 00:00，false表示未完成）
     */
    public function task__update()
    {
        user::auth();
        //
        parse_str(Request::getContent(), $data);
        $task_id = intval($data['task_id']);
        //
        $task = ProjectTask::userTask($task_id);
        //
        $updateComplete = false;
        $updateContent = false;
        if (Base::isDate($data['complete_at'])) {
            // 标记已完成
            if ($task->complete_at) {
                return Base::retError('任务已完成');
            }
            $task->completeTask(Carbon::now());
            $updateComplete = true;
        } elseif (Arr::exists($data, 'complete_at')) {
            // 标记未完成
            if (!$task->complete_at) {
                return Base::retError('未完成任务');
            }
            $task->completeTask(null);
            $updateComplete = true;
        } else {
            // 更新任务
            $task->updateTask($data, $updateContent);
        }
        $data = $task->toArray();
        $data['is_update_complete'] = $updateComplete;
        $data['is_update_content'] = $updateContent;
        $task->pushMsg('update', $data);
        return Base::retSuccess('修改成功', $data);
    }

    /**
     * {post} 上传文件
     *
     * @apiParam {Number} task_id               任务ID
     * @apiParam {String} [filename]            post-文件名称
     * @apiParam {String} [image64]             post-base64图片（二选一）
     * @apiParam {File} [files]                 post-文件对象（二选一）
     */
    public function task__upload()
    {
        $user = User::auth();
        //
        $task_id = Base::getPostInt('task_id');
        //
        $task = ProjectTask::userTask($task_id);
        //
        $path = "uploads/task/" . $task->id . "/";
        $image64 = Base::getPostValue('image64');
        $fileName = Base::getPostValue('filename');
        if ($image64) {
            $data = Base::image64save([
                "image64" => $image64,
                "path" => $path,
                "fileName" => $fileName,
            ]);
        } else {
            $data = Base::upload([
                "file" => Request::file('files'),
                "type" => 'file',
                "path" => $path,
                "fileName" => $fileName,
            ]);
        }
        //
        if (Base::isError($data)) {
            return Base::retError($data['msg']);
        } else {
            $fileData = $data['data'];
            $file = ProjectTaskFile::createInstance([
                'project_id' => $task->project_id,
                'task_id' => $task->id,
                'name' => $fileData['name'],
                'size' => $fileData['size'] * 1024,
                'ext' => $fileData['ext'],
                'path' => $fileData['path'],
                'thumb' => Base::unFillUrl($fileData['thumb']),
                'userid' => $user->userid,
            ]);
            $file->save();
            $file = $file->find($file->id);
            $task->addLog("上传文件：" . $file->name);
            $task->pushMsg('upload', $file->toArray());
            return Base::retSuccess("上传成功", $file);
        }
    }

    /**
     * 创建/获取聊天室
     *
     * @apiParam {Number} task_id               任务ID
     */
    public function task__dialog()
    {
        user::auth();
        //
        $task_id = intval(Request::input('task_id'));
        //
        $task = ProjectTask::userTask($task_id);
        //
        if ($task->parent_id > 0) {
            return Base::retError('子任务不支持此功能');
        }
        //
        AbstractModel::transaction(function() use ($task) {
            if (empty($task->dialog_id)) {
                $task->lockForUpdate();
                $dialog = WebSocketDialog::createGroup(null, $task->relationUserids(), 'task');
                if ($dialog) {
                    $task->dialog_id = $dialog->id;
                    $task->save();
                }
            }
            if (empty($task->dialog_id)) {
                throw new ApiException('创建聊天失败');
            }
        });
        //
        $task->pushMsg('dialog');
        return Base::retSuccess('success', [
            'id' => $task->id,
            'dialog_id' => $task->dialog_id,
        ]);
    }

    /**
     * 归档任务
     *
     * @apiParam {Number} task_id               任务ID
     */
    public function task__archived()
    {
        user::auth();
        //
        $task_id = intval(Request::input('task_id'));
        //
        $task = ProjectTask::userTask($task_id);
        //
        if ($task->parent_id > 0) {
            return Base::retError('子任务不支持此功能');
        }
        //
        $task->archivedTask(Carbon::now());
        return Base::retSuccess('保存成功', $task);
    }

    /**
     * 删除任务
     *
     * @apiParam {Number} task_id               任务ID
     */
    public function task__delete()
    {
        user::auth();
        //
        $task_id = intval(Request::input('task_id'));
        //
        $task = ProjectTask::userTask($task_id);
        //
        $task->deleteTask();
        return Base::retSuccess('删除成功', $task);
    }

    /**
     * 获取项目、任务日志
     *
     * @apiParam {Number} project_id            项目ID
     * @apiParam {Number} task_id               任务ID（与 项目ID 二选一，任务ID优先）
     *
     * @apiParam {Number} [page]                当前页，默认:1
     * @apiParam {Number} [pagesize]            每页显示数量，默认:20，最大:100
     */
    public function log__lists()
    {
        user::auth();
        //
        $project_id = intval(Request::input('project_id'));
        $task_id = intval(Request::input('task_id'));
        //
        $builder = ProjectLog::with(['user']);
        if ($task_id > 0) {
            $task = ProjectTask::userTask($task_id);
            $builder->whereTaskId($task->id);
        } else {
            $project = Project::userProject($project_id);
            $builder->whereTaskId($project->id);
        }
        //
        $list = $builder->orderByDesc('created_at')->paginate(Base::getPaginate(100, 20));
        $list->transform(function (ProjectLog $log) {
            $timestamp = Carbon::parse($log->created_at)->timestamp;
            $log->time = [
                'ymd' => date(date("Y", $timestamp) == date("Y", Base::time()) ? "m-d" : "Y-m-d", $timestamp),
                'hi' => date("h:i", $timestamp) ,
                'week' => "周" . Base::getTimeWeek($timestamp),
                'segment' => Base::getTimeDayeSegment($timestamp),
            ];
            return $log;
        });
        //
        return Base::retSuccess('success', $list);
    }
}
