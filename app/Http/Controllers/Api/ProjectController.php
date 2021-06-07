<?php

namespace App\Http\Controllers\Api;

use App\Models\AbstractModel;
use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\ProjectLog;
use App\Models\ProjectTask;
use App\Models\ProjectUser;
use App\Models\User;
use App\Models\WebSocketDialogMsg;
use App\Module\Base;
use Request;

/**
 * @apiDefine project
 *
 * 项目
 */
class ProjectController extends AbstractController
{
    private $projectSelect = [
        '*',
        'projects.id AS id',
        'projects.userid AS userid',
        'projects.created_at AS created_at',
        'projects.updated_at AS updated_at',
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
     * 项目详情
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
        if ($project) {
            $owner_user = $project->projectUser->where('owner', 1)->first();
            $project->owner_userid = $owner_user ? $owner_user->userid : 0;
        }
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
        $inorder = 0;
        foreach ($columns AS $column) {
            $column = trim($column);
            if ($column) {
                $insertColumns[] = [
                    'name' => $column,
                    'inorder' => $inorder++,
                ];
            }
        }
        if (empty($insertColumns)) {
            $insertColumns[] = [
                'name' => 'Default',
                'inorder' => 0,
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
            return Base::retSuccess('添加成功');
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
        return Base::retSuccess('修改成功');
    }

    /**
     * 修改项目成员
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {Number} userid            成员ID或成员ID组
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
     * 消息列表
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {Number} [task_id]         任务ID
     *
     * @apiParam {Number} [page]            当前页，默认:1
     * @apiParam {Number} [pagesize]        每页显示数量，默认:30，最大:100
     */
    public function msg__lists()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $project_id = intval(Request::input('project_id'));
        $task_id = intval(Request::input('task_id'));
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
        $builder = WebSocketDialogMsg::whereDialogId($project->dialog_id);
        if ($task_id > 0) {
            $builder->whereExtraInt($task_id);
        }
        $list = $builder->orderByDesc('id')->paginate(Base::getPaginate(100, 30));
        //
        return Base::retSuccess('success', $list);
    }

    /**
     * 发送消息
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {Number} [task_id]         任务ID
     * @apiParam {String} text              消息内容
     */
    public function msg__sendtext()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $project_id = intval(Request::input('project_id'));
        $task_id = intval(Request::input('task_id'));
        $text = trim(Request::input('text'));
        //
        if (mb_strlen($text) < 1) {
            return Base::retError('消息内容不能为空');
        } elseif (mb_strlen($text) > 20000) {
            return Base::retError('消息内容最大不能超过20000字');
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
        //
        $msg = [
            'text' => $text
        ];
        //
        return WebSocketDialogMsg::addGroupMsg($project->dialog_id, 'text', $msg, $user->userid, $task_id);
    }

    /**
     * 添加、修改 任务列表
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
        $column_id = intval(Request::input('column_id'));
        $name = trim(Request::input('name'));
        if (empty($name)) {
            return Base::retError('列表名称不能为空');
        }
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
        if ($column_id > 0) {
            $column = ProjectColumn::find($column_id);
        } else {
            $column = ProjectColumn::createInstance([
                'project_id' => $project->id,
            ]);
        }
        if ($column) {
            $column->name = $name;
            $column->save();
            return Base::retSuccess('添加成功', $column);
        } else {
            return Base::retError('列表不存在');
        }
    }

    /**
     * {post}添加任务
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {Number} [column_id]       列表ID，留空取第一个
     * @apiParam {String} name              任务描述
     * @apiParam {String} [content]         任务详情
     * @apiParam {Array} [times]            计划时间（格式：开始时间,结束时间；如：2020-01-01 00:00,2020-01-01 23:59）
     * @apiParam {Number} [owner]           负责人，留空为自己
     * @apiParam {Array} [subtasks]         子任务（格式：[{name,owner,times}]）
     */
    public function task__add()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        $project_id = Base::getPostInt('project_id');
        $column_id = Base::getPostValue('column_id');
        $name = Base::getPostValue('name');
        $content = Base::getPostValue('content');
        $times = Base::getPostValue('times');
        $owner = Base::getPostValue('owner');
        $subtasks = Base::getPostValue('subtasks');
        $p_level = Base::getPostValue('p_level');
        $p_name = Base::getPostValue('p_name');
        $p_color = Base::getPostValue('p_color');
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
        if (is_array($column_id)) {
            $column_id = Base::arrayFirst($column_id);
        }
        if (empty($column_id)) {
            $column = $project->projectColumn->first();
        } elseif (intval($column_id) > 0) {
            $column = $project->projectColumn->where('id', $column_id)->first();
        } else {
            $column = ProjectColumn::whereProjectId($project->id)->whereName($column_id)->first();
            if (empty($column)) {
                $column = ProjectColumn::createInstance([
                    'project_id' => $project->id,
                    'name' => $column_id,
                ]);
                $column->save();
            }
        }
        if (empty($column)) {
            return Base::retError('任务列表不存在或已被删除');
        }
        //
        return ProjectTask::addTask([
            'parent_id' => 0,
            'project_id' => $project->id,
            'column_id' => $column->id,
            'name' => $name,
            'content' => $content,
            'times' => $times,
            'owner' => $owner,
            'subtasks' => $subtasks,
            'p_level' => $p_level,
            'p_name' => $p_name,
            'p_color' => $p_color,
        ]);
    }
}
