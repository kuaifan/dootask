<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Models\AbstractModel;
use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\ProjectFlow;
use App\Models\ProjectFlowItem;
use App\Models\ProjectInvite;
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
use Response;

/**
 * @apiDefine project
 *
 * 项目
 */
class ProjectController extends AbstractController
{
    /**
     * @api {get} api/project/lists          01. 获取项目列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName lists
     *
     * @apiParam {String} [all]              是否查看所有项目（限制管理员）
     * @apiParam {String} [archived]         归档状态
     * - all：全部
     * - no：未归档（默认）
     * - yes：已归档
     * @apiParam {String} [getcolumn]        同时取项目列表
     * - no：不取（默认）
     * - yes：取列表
     * @apiParam {Object} [keys]             搜索条件
     * - keys.name: 项目名称
     *
     * @apiParam {Number} [page]        当前页，默认:1
     * @apiParam {Number} [pagesize]    每页显示数量，默认:50，最大:100
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     * @apiSuccessExample {json} sampleData:
    {
        "data": [
            {
                "id": 7,
                "name": "🏢 产品官网项目",
                "desc": "设置各小组成员的工作列表，各自领取或领导分配任务，将做好的任务分期归档，方便复盘！",
                "userid": 1,
                "dialog_id": 15,
                "archived_at": null,
                "archived_userid": 0,
                "created_at": "2022-01-02 06:23:15",
                "updated_at": "2022-01-02 07:12:33",

                "owner": 1,         // 是否项目负责人
                "owner_userid": 1,  // 项目负责人ID

                "task_num": 9,
                "task_complete": 0,
                "task_percent": 0,
                "task_my_num": 8,
                "task_my_complete": 0,
                "task_my_percent": 0,
            },
        ],
        "current_page": 1,  // 当前页数
        "last_page": 1,     // 下一页数
        "total": 6,         // 总计数（当前查询条件）
        "total_all": 6      // 总计数（全部）
    }
     */
    public function lists()
    {
        $user = User::auth();
        //
        $all = Request::input('all');
        $archived = Request::input('archived', 'no');
        $getcolumn = Request::input('getcolumn', 'no');
        //
        if ($all) {
            $user->identity('admin');
            $builder = Project::allData();
        } else {
            $builder = Project::authData();
        }
        //
        if ($getcolumn == 'yes') {
            $builder->with(['projectColumn']);
        }
        //
        if ($archived == 'yes') {
            $builder->whereNotNull('projects.archived_at');
        } elseif ($archived == 'no') {
            $builder->whereNull('projects.archived_at');
        }
        //
        $keys = Request::input('keys');
        if (is_array($keys)) {
            $buildClone = $builder->clone();
            if ($keys['name']) {
                $builder->where("projects.name", "like", "%{$keys['name']}%");
            }
        }
        //
        $list = $builder->orderByDesc('projects.id')->paginate(Base::getPaginate(100, 50));
        $list->transform(function (Project $project) use ($user) {
            return array_merge($project->toArray(), $project->getTaskStatistics($user->userid));
        });
        //
        $data = $list->toArray();
        if (isset($buildClone)) {
            $data['total_all'] = $buildClone->count();
        } else {
            $data['total_all'] = $data['total'];
        }
        //
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/project/one          02. 获取一个项目信息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName one
     *
     * @apiParam {Number} project_id     项目ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     * @apiSuccessExample {json} sampleData:
    {
        "id": 7,
        "name": "🏢 产品官网项目",
        "desc": "设置各小组成员的工作列表，各自领取或领导分配任务，将做好的任务分期归档，方便复盘！",
        "userid": 1,
        "dialog_id": 15,
        "archived_at": null,
        "archived_userid": 0,
        "created_at": "2022-01-02 06:23:15",
        "updated_at": "2022-01-02 07:12:33",

        "owner": 1,         // 是否项目负责人
        "owner_userid": 1,  // 项目负责人ID

        "project_user": [   // 项目成员
            {
                "id": 2,
                "project_id": 2,
                "userid": 1,
                "owner": 1,
                "created_at": "2022-01-02 00:55:32",
                "updated_at": "2022-01-02 00:55:32"
            }
        ],

        "task_num": 9,
        "task_complete": 0,
        "task_percent": 0,
        "task_my_num": 8,
        "task_my_complete": 0,
        "task_my_percent": 0,
    }
     */
    public function one()
    {
        $user = User::auth();
        //
        $project_id = intval(Request::input('project_id'));
        //
        $project = Project::userProject($project_id);
        $data = array_merge($project->toArray(), $project->getTaskStatistics($user->userid), [
            'project_user' => $project->projectUser,
        ]);
        //
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/project/add          03. 添加项目
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName add
     *
     * @apiParam {String} name          项目名称
     * @apiParam {String} [desc]        项目介绍
     * @apiParam {String} [columns]     列表，格式：列表名称1,列表名称2
     * @apiParam {String} [flow]        开启流程
     * - open: 开启
     * - close: 关闭（默认）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function add()
    {
        $user = User::auth();
        // 项目名称
        $name = trim(Request::input('name', ''));
        $desc = trim(Request::input('desc', ''));
        $flow = trim(Request::input('flow', 'close'));
        if (mb_strlen($name) < 2) {
            return Base::retError('项目名称不可以少于2个字');
        } elseif (mb_strlen($name) > 32) {
            return Base::retError('项目名称最多只能设置32个字');
        }
        if (mb_strlen($desc) > 255) {
            return Base::retError('项目介绍最多只能设置255个字');
        }
        // 列表
        $columns = explode(",", Request::input('columns'));
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
            'userid' => $user->userid,
        ]);
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
            $dialog = WebSocketDialog::createGroup(null, $project->userid, 'project');
            if (empty($dialog)) {
                throw new ApiException('创建项目聊天室失败');
            }
            $project->dialog_id = $dialog->id;
            $project->save();
            //
            if ($flow == 'open') {
                $project->addFlow(Base::json2array('[{"id":"-10","name":"\u5f85\u5904\u7406","status":"start","turns":["-10","-11","-12","-13"],"usertype":"add","userlimit":"0","sort":"0"},{"id":"-11","name":"\u8fdb\u884c\u4e2d","status":"progress","turns":["-10","-11","-12","-13"],"usertype":"add","userlimit":"0","sort":"1"},{"id":"-12","name":"\u5df2\u5b8c\u6210","status":"end","turns":["-10","-11","-12","-13"],"usertype":"add","userlimit":"0","sort":"2"},{"id":"-13","name":"\u5df2\u53d6\u6d88","status":"end","turns":["-10","-11","-12","-13"],"usertype":"add","userlimit":"0","sort":"3"}]'));
            }
        });
        //
        $data = Project::find($project->id);
        $data->addLog("创建项目");
        $data->pushMsg('add', $data);
        return Base::retSuccess('添加成功', $data);
    }

    /**
     * @api {get} api/project/update          04. 修改项目
     *
     * @apiDescription 需要token身份（限：项目负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName update
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {String} name              项目名称
     * @apiParam {String} [desc]            项目介绍
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function update()
    {
        User::auth();
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
        $project = Project::userProject($project_id, true, true);
        //
        if ($project->name != $name) {
            $project->addLog("修改项目名称", [
                'change' => [$project->name, $name]
            ]);
            $project->name = $name;
        }
        if ($project->desc != $desc) {
            $project->desc = $desc;
            $project->addLog("修改项目介绍");
        }
        $project->save();
        $project->pushMsg('update', $project);
        //
        return Base::retSuccess('修改成功', $project);
    }

    /**
     * @api {get} api/project/user          05. 修改项目成员
     *
     * @apiDescription 需要token身份（限：项目负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName user
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {Number} userid            成员ID 或 成员ID组
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function user()
    {
        User::auth();
        //
        $project_id = intval(Request::input('project_id'));
        $userid = Request::input('userid');
        $userid = is_array($userid) ? $userid : [$userid];
        //
        if (count($userid) > 100) {
            return Base::retError('项目人数最多100个');
        }
        //
        $project = Project::userProject($project_id, true, true);
        //
        $deleteUser = AbstractModel::transaction(function() use ($project, $userid) {
            $array = [];
            foreach ($userid as $uid) {
                if ($project->joinProject($uid)) {
                    $array[] = $uid;
                }
            }
            $deleteRows = ProjectUser::whereProjectId($project->id)->whereNotIn('userid', $array)->get();
            $deleteUser = $deleteRows->pluck('userid');
            foreach ($deleteRows as $row) {
                $row->exitProject();
            }
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
     * @api {get} api/project/invite          06. 获取邀请链接
     *
     * @apiDescription 需要token身份（限：项目负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName invite
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {String} refresh           刷新链接
     * - no: 只获取（默认）
     * - yes: 刷新链接，之前的将失效
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function invite()
    {
        User::auth();
        //
        $project_id = intval(Request::input('project_id'));
        $refresh = Request::input('refresh', 'no');
        //
        $project = Project::userProject($project_id, true, true);
        //
        $invite = Base::settingFind('system', 'project_invite');
        if ($invite == 'close') {
            return Base::retError('未开放此功能');
        }
        //
        $projectInvite = ProjectInvite::whereProjectId($project->id)->first();
        if (empty($projectInvite)) {
            $projectInvite = ProjectInvite::createInstance([
                'project_id' => $project->id,
                'code' => Base::generatePassword(64),
            ]);
            $projectInvite->save();
        } else {
            if ($refresh == 'yes') {
                $projectInvite->code = Base::generatePassword(64);
                $projectInvite->save();
            }
        }
        return Base::retSuccess('success', [
            'url' => Base::fillUrl('manage/project/invite?code=' . $projectInvite->code),
            'num' => $projectInvite->num
        ]);
    }

    /**
     * @api {get} api/project/invite/info          07. 通过邀请链接code获取项目信息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName invite__info
     *
     * @apiParam {String} code
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function invite__info()
    {
        User::auth();
        //
        $code = Request::input('code');
        //
        $invite = Base::settingFind('system', 'project_invite');
        if ($invite == 'close') {
            return Base::retError('未开放此功能');
        }
        //
        $projectInvite = ProjectInvite::with(['project'])->whereCode($code)->first();
        if (empty($projectInvite)) {
            return Base::retError('邀请code不存在');
        }
        return Base::retSuccess('success', $projectInvite);
    }

    /**
     * @api {get} api/project/invite/join          08. 通过邀请链接code加入项目
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName invite__join
     *
     * @apiParam {String} code
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function invite__join()
    {
        $user = User::auth();
        //
        $code = Request::input('code');
        //
        $invite = Base::settingFind('system', 'project_invite');
        if ($invite == 'close') {
            return Base::retError('未开放此功能');
        }
        //
        $projectInvite = ProjectInvite::with(['project'])->whereCode($code)->first();
        if (empty($projectInvite)) {
            return Base::retError('邀请code不存在');
        }
        if ($projectInvite->already) {
            return Base::retSuccess('已加入', $projectInvite);
        }
        if (!$projectInvite->project?->joinProject($user->userid)) {
            return Base::retError('加入失败，请稍后再试');
        }
        $projectInvite->num++;
        $projectInvite->save();
        //
        $projectInvite->project->syncDialogUser();
        $projectInvite->project->addLog("通过邀请链接加入项目");
        //
        $data = $projectInvite->toArray();
        $data['already'] = true;
        return Base::retSuccess('加入成功', $data);
    }

    /**
     * @api {get} api/project/transfer          09. 移交项目
     *
     * @apiDescription 需要token身份（限：项目负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName transfer
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {Number} owner_userid      新的项目负责人ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function transfer()
    {
        User::auth();
        //
        $project_id = intval(Request::input('project_id'));
        $owner_userid = intval(Request::input('owner_userid'));
        //
        $project = Project::userProject($project_id, true, true);
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
            $project->addLog("移交项目给", ['userid' => $owner_userid]);
        });
        //
        $project->pushMsg('detail');
        return Base::retSuccess('移交成功', ['id' => $project->id]);
    }

    /**
     * @api {get} api/project/sort          10. 排序任务
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName sort
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {Object} sort              排序数据
     * @apiParam {Number} [only_column]     仅更新列表
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function sort()
    {
        User::auth();
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
        $project->pushMsg('sort');
        return Base::retSuccess('调整成功');
    }

    /**
     * @api {get} api/project/exit          11. 退出项目
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName exit
     *
     * @apiParam {Number} project_id        项目ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function exit()
    {
        $user = User::auth();
        //
        $project_id = intval(Request::input('project_id'));
        //
        $project = Project::userProject($project_id, true, false);
        //
        AbstractModel::transaction(function() use ($user, $project) {
            $row = ProjectUser::whereProjectId($project->id)->whereUserid($user->userid)->first();
            $row?->exitProject();
            $project->syncDialogUser();
            $project->addLog("退出项目");
            $project->pushMsg('delete', null, $user->userid);
        });
        return Base::retSuccess('退出成功', ['id' => $project->id]);
    }

    /**
     * @api {get} api/project/archived          12. 归档项目
     *
     * @apiDescription 需要token身份（限：项目负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName archived
     *
     * @apiParam {Number} project_id            项目ID
     * @apiParam {String} [type]                类型
     * - add：归档（默认）
     * - recovery：还原归档
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function archived()
    {
        User::auth();
        //
        $project_id = intval(Request::input('project_id'));
        $type = Request::input('type', 'add');
        //
        $project = Project::userProject($project_id, $type == 'add', true);
        //
        if ($type == 'recovery') {
            $project->archivedProject(null);
        } elseif ($type == 'add') {
            $project->archivedProject(Carbon::now());
        }
        return Base::retSuccess('操作成功', ['id' => $project->id]);
    }

    /**
     * @api {get} api/project/remove          13. 删除项目
     *
     * @apiDescription 需要token身份（限：项目负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName remove
     *
     * @apiParam {Number} project_id        项目ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function remove()
    {
        User::auth();
        //
        $project_id = intval(Request::input('project_id'));
        //
        $project = Project::userProject($project_id, null, true);
        //
        $project->deleteProject();
        return Base::retSuccess('删除成功', ['id' => $project->id]);
    }

    /**
     * @api {get} api/project/column/lists          14. 获取任务列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName column__lists
     *
     * @apiParam {Number} project_id        项目ID
     *
     * @apiParam {Number} [page]            当前页，默认:1
     * @apiParam {Number} [pagesize]        每页显示数量，默认:100，最大:200
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function column__lists()
    {
        User::auth();
        //
        $project_id = intval(Request::input('project_id'));
        // 项目
        $project = Project::userProject($project_id);
        //
        $list = ProjectColumn::whereProjectId($project->id)
            ->orderBy('sort')
            ->orderBy('id')
            ->paginate(Base::getPaginate(200, 100));
        //
        return Base::retSuccess('success', $list);
    }

    /**
     * @api {get} api/project/column/add          15. 添加任务列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName column__add
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {String} name              列表名称
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function column__add()
    {
        User::auth();
        //
        $project_id = intval(Request::input('project_id'));
        $name = trim(Request::input('name'));
        // 项目
        $project = Project::userProject($project_id);
        //
        if (empty($name)) {
            return Base::retError('列表名称不能为空');
        }
        if (ProjectColumn::whereProjectId($project->id)->count() > 50) {
            return Base::retError('项目列表最多不能超过50个');
        }
        //
        $column = ProjectColumn::createInstance([
            'project_id' => $project->id,
            'name' => $name,
        ]);
        $column->sort = intval(ProjectColumn::whereProjectId($project->id)->orderByDesc('sort')->value('sort')) + 1;
        $column->save();
        $column->addLog("创建列表：" . $column->name);
        //
        $data = ProjectColumn::find($column->id);
        $data->project_task = [];
        $data->pushMsg("add", $data);
        return Base::retSuccess('添加成功', $data);
    }

    /**
     * @api {get} api/project/column/update          16. 修改任务列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName column__update
     *
     * @apiParam {Number} column_id         列表ID
     * @apiParam {String} [name]            列表名称
     * @apiParam {String} [color]           颜色
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function column__update()
    {
        User::auth();
        //
        $data = Request::all();
        $column_id = intval($data['column_id']);
        // 列表
        $column = ProjectColumn::whereId($column_id)->first();
        if (empty($column)) {
            return Base::retError('列表不存在');
        }
        // 项目
        Project::userProject($column->project_id);
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
        $column->pushMsg("update", $column);
        return Base::retSuccess('修改成功', $column);
    }

    /**
     * @api {get} api/project/column/remove          17. 删除任务列表
     *
     * @apiDescription 需要token身份（限：项目负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName column__remove
     *
     * @apiParam {Number} column_id         列表ID（留空为添加列表）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function column__remove()
    {
        User::auth();
        //
        $column_id = intval(Request::input('column_id'));
        // 列表
        $column = ProjectColumn::whereId($column_id)->first();
        if (empty($column)) {
            return Base::retError('列表不存在');
        }
        // 项目
        Project::userProject($column->project_id, true, true);
        //
        $column->deleteColumn();
        return Base::retSuccess('删除成功', ['id' => $column->id]);
    }

    /**
     * @api {get} api/project/task/lists          18. 任务列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__lists
     *
     * @apiParam {Object} [keys]             搜索条件
     * - keys.name: 任务名称
     * @apiParam {Number} [project_id]       项目ID
     * @apiParam {Number} [parent_id]        主任务ID（project_id && parent_id ≤ 0 时 仅查询自己参与的任务）
     * - 大于0：指定主任务下的子任务
     * - 等于-1：表示仅主任务
     * @apiParam {String} [name]             任务描述关键词
     * @apiParam {Array} [time]              指定时间范围，如：['2020-12-12', '2020-12-30']
     * @apiParam {String} [time_before]      指定时间之前，如：2020-12-30 00:00:00（填写此项时 time 参数无效）
     * @apiParam {String} [complete]         完成状态
     * - all：所有（默认）
     * - yes：已完成
     * - no：未完成
     * @apiParam {String} [archived]         归档状态
     * - all：所有
     * - yes：已归档
     * - no：未归档（默认）
     * @apiParam {Object} sorts              排序方式
     * - sorts.complete_at  完成时间：asc|desc
     * - sorts.archived_at  归档时间：asc|desc
     * - sorts.end_at  到期时间：asc|desc
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__lists()
    {
        User::auth();
        //
        $builder = ProjectTask::with(['taskUser', 'taskTag']);
        //
        $parent_id = intval(Request::input('parent_id'));
        $project_id = intval(Request::input('project_id'));
        $name = Request::input('name');
        $time = Request::input('time');
        $time_before = Request::input('time_before');
        $complete = Request::input('complete', 'all');
        $archived = Request::input('archived', 'no');
        $keys = Request::input('keys');
        $sorts = Request::input('sorts');
        $keys = is_array($keys) ? $keys : [];
        $sorts = is_array($sorts) ? $sorts : [];
        //
        if ($keys['name']) {
            $builder->where("project_tasks.name", "like", "%{$keys['name']}%");
        }
        //
        $scopeAll = false;
        if ($parent_id > 0) {
            ProjectTask::userTask($parent_id, str_replace(['all', 'yes', 'no'], [null, false, true], $archived));
            $scopeAll = true;
            $builder->where('project_tasks.parent_id', $parent_id);
        } elseif ($parent_id === -1) {
            $builder->where('project_tasks.parent_id', 0);
        }
        if ($project_id > 0) {
            Project::userProject($project_id);
            $scopeAll = true;
            $builder->where('project_tasks.project_id', $project_id);
        }
        if ($scopeAll) {
            $builder->allData();
        } else {
            $builder->authData();
        }
        //
        if ($name) {
            $builder->where(function($query) use ($name) {
                $query->where("project_tasks.name", "like", "%{$name}%");
            });
        }
        //
        if (Base::isDateOrTime($time_before)) {
            $builder->whereNotNull('project_tasks.end_at')->where('project_tasks.end_at', '<', Carbon::parse($time_before));
        } elseif (is_array($time)) {
            if (Base::isDateOrTime($time[0]) && Base::isDateOrTime($time[1])) {
                $builder->betweenTime(Carbon::parse($time[0])->startOfDay(), Carbon::parse($time[1])->endOfDay());
            }
        }
        //
        if ($complete === 'yes') {
            $builder->whereNotNull('project_tasks.complete_at');
        } elseif ($complete === 'no') {
            $builder->whereNull('project_tasks.complete_at');
        }
        //
        if ($archived == 'yes') {
            $builder->whereNotNull('project_tasks.archived_at');
        } elseif ($archived == 'no') {
            $builder->whereNull('project_tasks.archived_at');
        }
        //
        foreach ($sorts as $column => $direction) {
            if (!in_array($column, ['complete_at', 'archived_at', 'end_at'])) continue;
            if (!in_array($direction, ['asc', 'desc'])) continue;
            $builder->orderBy('project_tasks.' . $column, $direction);
        }
        //
        $list = $builder->orderByDesc('project_tasks.id')->paginate(Base::getPaginate(200, 100));
        //
        return Base::retSuccess('success', $list);
    }

    /**
     * @api {get} api/project/task/one          19. 获取单个任务信息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__one
     *
     * @apiParam {Number} task_id            任务ID
     * @apiParam {String} [archived]         归档状态
     * - all：所有
     * - yes：已归档
     * - no：未归档（默认）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__one()
    {
        User::auth();
        //
        $task_id = intval(Request::input('task_id'));
        $archived = Request::input('archived', 'no');
        //
        $task = ProjectTask::userTask($task_id, str_replace(['all', 'yes', 'no'], [null, false, true], $archived), false, ['taskUser', 'taskTag']);
        //
        $data = $task->toArray();
        $data['project_name'] = $task->project?->name;
        $data['column_name'] = $task->projectColumn?->name;
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/project/task/content          20. 获取任务详细描述
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__content
     *
     * @apiParam {Number} task_id            任务ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__content()
    {
        User::auth();
        //
        $task_id = intval(Request::input('task_id'));
        //
        $task = ProjectTask::userTask($task_id, null);
        //
        return Base::retSuccess('success', $task->content ?: json_decode('{}'));
    }

    /**
     * @api {get} api/project/task/files          21. 获取任务文件列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__files
     *
     * @apiParam {Number} task_id            任务ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__files()
    {
        User::auth();
        //
        $task_id = intval(Request::input('task_id'));
        //
        $task = ProjectTask::userTask($task_id, null);
        //
        return Base::retSuccess('success', $task->taskFile);
    }

    /**
     * @api {get} api/project/task/filedelete          22. 删除任务文件
     *
     * @apiDescription 需要token身份（限：项目、任务负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__filedelete
     *
     * @apiParam {Number} file_id            文件ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__filedelete()
    {
        User::auth();
        //
        $file_id = intval(Request::input('file_id'));
        //
        $file = ProjectTaskFile::find($file_id);
        if (empty($file)) {
            return Base::retError('文件不存在或已被删除');
        }
        //
        $task = ProjectTask::userTask($file->task_id, true, true);
        //
        $task->pushMsg('filedelete', $file);
        $file->delete();
        //
        return Base::retSuccess('success', $file);
    }

    /**
     * @api {get} api/project/task/filedetail          23. 获取任务文件详情
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__filedetail
     *
     * @apiParam {Number} file_id           文件ID
     * @apiParam {String} only_update_at    仅获取update_at字段
     * - no (默认)
     * - yes
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__filedetail()
    {
        User::auth();
        //
        $file_id = intval(Request::input('file_id'));
        $only_update_at = Request::input('only_update_at', 'no');
        //
        $file = ProjectTaskFile::find($file_id);
        if (empty($file)) {
            return Base::retError("文件不存在");
        }
        //
        if ($only_update_at == 'yes') {
            return Base::retSuccess('success', [
                'id' => $file->id,
                'update_at' => Carbon::parse($file->updated_at)->toDateTimeString()
            ]);
        }
        //
        $data = $file->toArray();
        $data['path'] = $file->getRawOriginal('path');
        //
        ProjectTask::userTask($file->task_id, null);
        //
        $codeExt = ['txt'];
        $officeExt = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
        $localExt = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'ico', 'raw', 'tif', 'tiff', 'mp3', 'wav', 'mp4', 'flv', 'avi', 'mov', 'wmv', 'mkv', '3gp', 'rm'];
        $filePath = public_path($data['path']);
        if (in_array($data['ext'], $codeExt) && $data['size'] < 2 * 1024 * 1024) {
            // 文本预览，限制2M内的文件
            $data['content'] = file_get_contents($filePath);
            $data['file_mode'] = 1;
        } elseif (in_array($data['ext'], $officeExt)) {
            // office预览
            $data['file_mode'] = 2;
        } else {
            // 其他预览
            if (in_array($data['ext'], $localExt)) {
                $url = Base::fillUrl($data['path']);
            } else {
                $url = 'http://' . env('APP_IPPR') . '.3/' . $data['path'];
            }
            $data['url'] = base64_encode($url);
            $data['file_mode'] = 3;
        }
        //
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/project/task/filedown          24. 下载任务文件
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__filedown
     *
     * @apiParam {Number} file_id            文件ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__filedown()
    {
        User::auth();
        //
        $file_id = intval(Request::input('file_id'));
        //
        $file = ProjectTaskFile::find($file_id);
        if (empty($file)) {
            abort(403, "This file not exist.");
        }
        //
        try {
            ProjectTask::userTask($file->task_id, null);
        } catch (\Exception $e) {
            abort(403, $e->getMessage() ?: "This file not support download.");
        }
        //
        return Response::download(public_path($file->getRawOriginal('path')), $file->name);
    }

    /**
     * @api {post} api/project/task/add          25. 添加任务
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__add
     *
     * @apiParam {Number} project_id            项目ID
     * @apiParam {mixed} [column_id]            列表ID，任意值自动创建，留空取第一个
     * @apiParam {String} name                  任务描述
     * @apiParam {String} [content]             任务详情
     * @apiParam {Array} [times]                计划时间（格式：开始时间,结束时间；如：2020-01-01 00:00,2020-01-01 23:59）
     * @apiParam {Number} [owner]               负责人
     * @apiParam {Array} [subtasks]             子任务（格式：[{name,owner,times}]）
     * @apiParam {Number} [top]                 添加的任务排到列表最前面
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__add()
    {
        User::auth();
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
            $newColumn = $column->find($column->id)->toArray();
            $newColumn['project_task'] = [];
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
        $data = ProjectTask::oneTask($task->id);
        if ($newColumn) {
            $data = $data->toArray();
            $data['new_column'] = $newColumn;
        }
        $task->pushMsg('add', $data);
        return Base::retSuccess('添加成功', $data);
    }

    /**
     * @api {get} api/project/task/addsub          26. 添加子任务
     *
     * @apiDescription 需要token身份（限：项目、任务负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__addsub
     *
     * @apiParam {Number} task_id               任务ID
     * @apiParam {String} name                  任务描述
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__addsub()
    {
        User::auth();
        //
        $task_id = intval(Request::input('task_id'));
        $name = Request::input('name');
        //
        $task = ProjectTask::userTask($task_id, true, true);
        if ($task->complete_at) {
            return Base::retError('主任务已完成无法添加子任务');
        }
        //
        $task = ProjectTask::addTask([
            'name' => $name,
            'parent_id' => $task->id,
            'project_id' => $task->project_id,
            'column_id' => $task->column_id,
            'times' => [$task->start_at, $task->end_at],
            'owner' => [User::userid()]
        ]);
        $data = ProjectTask::oneTask($task->id);
        $task->pushMsg('add', $data);
        return Base::retSuccess('添加成功', $data);
    }

    /**
     * @api {post} api/project/task/update          27. 修改任务、子任务
     *
     * @apiDescription 需要token身份（限：项目、任务负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__update
     *
     * @apiParam {Number} task_id               任务ID
     * @apiParam {String} [name]                任务描述
     * @apiParam {Array} [times]                计划时间（格式：开始时间,结束时间；如：2020-01-01 00:00,2020-01-01 23:59）
     * @apiParam {Array} [owner]                修改负责人
     * @apiParam {String} [content]             任务详情（子任务不支持）
     * @apiParam {String} [color]               背景色（子任务不支持）
     * @apiParam {Array} [assist]               修改协助人员（子任务不支持）
     *
     * @apiParam {Number} [p_level]             优先级相关（子任务不支持）
     * @apiParam {String} [p_name]              优先级相关（子任务不支持）
     * @apiParam {String} [p_color]             优先级相关（子任务不支持）
     *
     * @apiParam {Number} [flow_item_id]        任务状态，工作流状态ID
     * @apiParam {String|false} [complete_at]   完成时间（如：2020-01-01 00:00，false表示未完成）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__update()
    {
        User::auth();
        //
        parse_str(Request::getContent(), $data);
        $task_id = intval($data['task_id']);
        //
        $task = ProjectTask::userTask($task_id, true, 2);
        // 更新任务
        $updateMarking = [];
        $task->updateTask($data, $updateMarking);
        //
        $data = ProjectTask::oneTask($task->id)->toArray();
        $data['update_marking'] = $updateMarking ?: json_decode('{}');
        $task->pushMsg('update', $data);
        //
        return Base::retSuccess('修改成功', $data);
    }

    /**
     * @api {get} api/project/task/dialog          28. 创建/获取聊天室
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__dialog
     *
     * @apiParam {Number} task_id               任务ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__dialog()
    {
        User::auth();
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
     * @api {get} api/project/task/archived          29. 归档任务
     *
     * @apiDescription 需要token身份（限：项目、任务负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__archived
     *
     * @apiParam {Number} task_id               任务ID
     * @apiParam {String} [type]                类型
     * - add：归档（默认）
     * - recovery：还原归档
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__archived()
    {
        User::auth();
        //
        $task_id = intval(Request::input('task_id'));
        $type = Request::input('type', 'add');
        //
        $task = ProjectTask::userTask($task_id, $type == 'add', true);
        //
        if ($task->parent_id > 0) {
            return Base::retError('子任务不支持此功能');
        }
        //
        if ($type == 'recovery') {
            $task->archivedTask(null);
        } elseif ($type == 'add') {
            $task->archivedTask(Carbon::now());
        }
        return Base::retSuccess('操作成功', [
            'id' => $task->id,
            'archived_at' => $task->archived_at,
            'archived_userid' => $task->archived_userid,
        ]);
    }

    /**
     * @api {get} api/project/task/remove          30. 删除任务
     *
     * @apiDescription 需要token身份（限：项目、任务负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__remove
     *
     * @apiParam {Number} task_id               任务ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__remove()
    {
        User::auth();
        //
        $task_id = intval(Request::input('task_id'));
        //
        $task = ProjectTask::userTask($task_id, null, true);
        //
        $task->deleteTask();
        return Base::retSuccess('删除成功', ['id' => $task->id]);
    }

    /**
     * @api {get} api/project/task/resetfromlog          31. 根据日志重置任务
     *
     * @apiDescription 需要token身份（限：项目、任务负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__resetfromlog
     *
     * @apiParam {Number} task_id               任务ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__resetfromlog()
    {
        User::auth();
        //
        $id = intval(Request::input('id'));
        //
        $projectLog = ProjectLog::find($id);
        if (empty($projectLog) || empty($projectLog->task_id)) {
            return Base::retError('记录不存在');
        }
        //
        $task = ProjectTask::userTask($projectLog->task_id, true, true);
        //
        $record = $projectLog->record;
        if ($record['flow'] && is_array($record['flow'])) {
            $rawData = $record['flow'];
            $newFlowItem = ProjectFlowItem::find(intval($rawData['flow_item_id']));
            if (empty($newFlowItem)) {
                return Base::retError('流程不存在或已被删除');
            }
            return AbstractModel::transaction(function() use ($rawData, $task, $newFlowItem) {
                $currentFlowItem = $task->flow_item_id ? ProjectFlowItem::find($task->flow_item_id) : null;
                //
                $task->flow_item_id = $newFlowItem->id;
                $task->flow_item_name = $newFlowItem->name;
                $task->addLog("重置{任务}状态", [
                    'change' => [$currentFlowItem?->name, $newFlowItem->name]
                ]);
                //
                $updateMarking = [];
                $data = array_intersect_key($rawData, array_flip(['complete_at', 'owner', 'assist']));
                $task->updateTask($data, $updateMarking);
                //
                $data = ProjectTask::oneTask($task->id)->toArray();
                $data["flow_item_name"] = $newFlowItem->status . "|" . $newFlowItem->name;
                $data['update_marking'] = $updateMarking ?: json_decode('{}');
                $task->pushMsg('update', $data);
                //
                return Base::retSuccess('重置成功', $data);
            });
        } else {
            return Base::retError('暂不支持此操作');
        }
    }

    /**
     * @api {get} api/project/task/flow          32. 任务工作流信息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__flow
     *
     * @apiParam {Number} task_id               任务ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__flow()
    {
        User::auth();
        //
        $task_id = intval(Request::input('task_id'));
        //
        $projectTask = ProjectTask::select(['id', 'project_id', 'complete_at', 'flow_item_id', 'flow_item_name'])->find($task_id);
        if (empty($projectTask)) {
            return Base::retError('任务不存在', [ 'task_id' => $task_id ], -4002);
        }
        //
        $projectFlowItem = $projectTask->flow_item_id ? ProjectFlowItem::with(['projectFlow'])->find($projectTask->flow_item_id) : null;
        if ($projectFlowItem?->projectFlow) {
            $projectFlow = $projectFlowItem->projectFlow;
        } else {
            $projectFlow = ProjectFlow::whereProjectId($projectTask->project_id)->orderByDesc('id')->first();
        }
        if (empty($projectFlow)) {
            return Base::retSuccess('success', [
                'task_id' => $projectTask->id,
                'flow_item_id' => 0,
                'turns' => [],
            ]);
        }
        //
        $turns = ProjectFlowItem::select(['id', 'name', 'status', 'turns'])->whereFlowId($projectFlow->id)->orderBy('sort')->get();
        if (empty($projectFlowItem)) {
            $data = [
                'task_id' => $projectTask->id,
                'flow_item_id' => 0,
                'turns' => $turns,
            ];
            if ($projectTask->complete_at) {
                // 赋一个结束状态
                foreach ($turns as $turn) {
                    if ($turn->status == 'end' || preg_match("/complete|done|完成/i", $turn->name)) {
                        $data['flow_item_id'] = $turn->id;
                        break;
                    }
                }
                if (empty($data['flow_item_id'])) {
                    foreach ($turns as $turn) {
                        if ($turn->status == 'end') {
                            $data['flow_item_id'] = $turn->id;
                            break;
                        }
                    }
                }
            } else {
                // 赋一个开始状态
                foreach ($turns as $turn) {
                    if ($turn->status == 'start') {
                        $data['flow_item_id'] = $turn->id;
                        break;
                    }
                }
            }
        } else {
            $data = [
                'task_id' => $projectTask->id,
                'flow_item_id' => $projectFlowItem->id,
                'turns' => $turns,
            ];
        }
        //
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/project/flow/list          33. 工作流列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName flow__list
     *
     * @apiParam {Number} project_id               项目ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function flow__list()
    {
        User::auth();
        //
        $project_id = intval(Request::input('project_id'));
        //
        $project = Project::userProject($project_id, true);
        //
        $list = ProjectFlow::with(['ProjectFlowItem'])->whereProjectId($project->id)->get();
        return Base::retSuccess('success', $list);
    }

    /**
     * @api {post} api/project/flow/save          34. 保存工作流
     *
     * @apiDescription 需要token身份（限：项目负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName flow__save
     *
     * @apiParam {Number} project_id               项目ID
     * @apiParam {Array} flows                     工作流数据
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function flow__save()
    {
        User::auth();
        //
        $project_id = intval(Base::getContentValue('project_id'));
        $flows = Base::getContentValue('flows');
        //
        if (!is_array($flows)) {
            return Base::retError('参数错误');
        }
        if (count($flows) > 10) {
            return Base::retError('流程状态最多不能超过10个');
        }
        //
        $project = Project::userProject($project_id, true, true);
        //
        return Base::retSuccess('保存成功', $project->addFlow($flows));
    }

    /**
     * @api {get} api/project/flow/delete          35. 删除工作流
     *
     * @apiDescription 需要token身份（限：项目负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName flow__delete
     *
     * @apiParam {Number} project_id               项目ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function flow__delete()
    {
        User::auth();
        //
        $project_id = intval(Request::input('project_id'));
        //
        $project = Project::userProject($project_id, true, true);
        //
        return AbstractModel::transaction(function() use ($project) {
            ProjectFlow::whereProjectId($project->id)->chunk(100, function($list) {
                foreach ($list as $item) {
                    $item->deleteFlow();
                }
            });
            return Base::retSuccess('删除成功');
        });
    }

    /**
     * @api {get} api/project/log/lists          36. 获取项目、任务日志
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName log__lists
     *
     * @apiParam {Number} project_id            项目ID
     * @apiParam {Number} task_id               任务ID（与 项目ID 二选一，任务ID优先）
     *
     * @apiParam {Number} [page]                当前页，默认:1
     * @apiParam {Number} [pagesize]            每页显示数量，默认:20，最大:100
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function log__lists()
    {
        User::auth();
        //
        $project_id = intval(Request::input('project_id'));
        $task_id = intval(Request::input('task_id'));
        //
        $builder = ProjectLog::select(["*"]);
        if ($task_id > 0) {
            $task = ProjectTask::userTask($task_id, null);
            $builder->whereTaskId($task->id);
        } else {
            $project = Project::userProject($project_id);
            $builder->with(['projectTask:id,parent_id,name'])->whereProjectId($project->id);
        }
        //
        $list = $builder->orderByDesc('created_at')->paginate(Base::getPaginate(100, 20));
        $list->transform(function (ProjectLog $log) use ($task_id) {
            $timestamp = Carbon::parse($log->created_at)->timestamp;
            if ($task_id === 0) {
                $log->projectTask?->cancelAppend();
            }
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

    /**
     * @api {get} api/project/top       37. 项目置顶
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName top
     *
     * @apiParam {Number} project_id            项目ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function top()
    {
        $user = User::auth();
        $projectId = intval(Request::input('project_id'));
        $projectUser = ProjectUser::whereUserid($user->userid)->whereProjectId($projectId)->first();
        if (!$projectUser) {
            return Base::retError("项目不存在");
        }
        $projectUser->top_at = $projectUser->top_at ? null : Carbon::now();
        $projectUser->save();
        return Base::retSuccess("success", $projectId);
    }
}
