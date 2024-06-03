<?php

namespace App\Http\Controllers\Api;

use Request;
use Session;
use Redirect;
use Response;
use Madzipper;
use Carbon\Carbon;
use App\Module\Doo;
use App\Models\File;
use App\Models\User;
use App\Module\Base;
use Swoole\Coroutine;
use App\Models\Deleted;
use App\Models\Project;
use App\Module\TimeRange;
use App\Models\ProjectLog;
use App\Module\BillExport;
use App\Models\FileContent;
use App\Models\ProjectFlow;
use App\Models\ProjectTask;
use App\Models\ProjectUser;
use Illuminate\Support\Arr;
use App\Models\AbstractModel;
use App\Models\ProjectColumn;
use App\Models\ProjectInvite;
use App\Models\ProjectFlowItem;
use App\Models\ProjectTaskFile;
use App\Models\ProjectTaskUser;
use App\Models\WebSocketDialog;
use App\Exceptions\ApiException;
use App\Models\ProjectPermission;
use App\Models\ProjectTaskContent;
use App\Models\WebSocketDialogMsg;
use App\Module\BillMultipleExport;
use Illuminate\Support\Facades\DB;
use App\Models\ProjectTaskFlowChange;
use App\Models\ProjectTaskVisibilityUser;

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
     * @apiParam {String} [type]             项目类型
     * - all：全部（默认）
     * - team：团队项目
     * - personal：个人项目
     * @apiParam {String} [archived]         归档状态
     * - all：全部
     * - no：未归档（默认）
     * - yes：已归档
     * @apiParam {String} [getcolumn]        同时取列表
     * - no：不取（默认）
     * - yes：取列表
     * @apiParam {String} [getuserid]        同时取成员ID
     * - no：不取（默认）
     * - yes：取列表
     * @apiParam {String} [getstatistics]    同时取任务统计
     * - no：不取
     * - yes：取统计（默认）
     * @apiParam {Object} [keys]             搜索条件
     * - keys.name: 项目名称
     * @apiParam {String} [timerange]        时间范围（如：1678248944,1678248944）
     * - 第一个时间: 读取在这个时间之后更新的数据
     * - 第二个时间: 读取在这个时间之后删除的数据ID（第1页附加返回数据: deleted_id）
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
        $type = Request::input('type', 'all');
        $archived = Request::input('archived', 'no');
        $getcolumn = Request::input('getcolumn', 'no');
        $getuserid = Request::input('getuserid', 'no');
        $getstatistics = Request::input('getstatistics', 'yes');
        $keys = Request::input('keys');
        $timerange = TimeRange::parse(Request::input('timerange'));
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
        if ($type === 'team') {
            $builder->where('projects.personal', 0);
        } elseif ($type === 'personal') {
            $builder->where('projects.personal', 1);
        }
        //
        if ($archived == 'yes') {
            $builder->whereNotNull('projects.archived_at');
        } elseif ($archived == 'no') {
            $builder->whereNull('projects.archived_at');
        }
        //
        if (is_array($keys) || $timerange->updated) {
            $totalAll = $builder->clone()->count();
        }
        //
        if (is_array($keys)) {
            if ($keys['name']) {
                $builder->where("projects.name", "like", "%{$keys['name']}%");
            }
        }
        //
        if ($timerange->updated) {
            $builder->where('projects.updated_at', '>', $timerange->updated);
        }
        //
        $list = $builder->orderByDesc('projects.id')->paginate(Base::getPaginate(100, 50));
        $list->transform(function (Project $project) use ($getstatistics, $getuserid, $user) {
            $array = $project->toArray();
            if ($getuserid == 'yes') {
                $array['userid_list'] = ProjectUser::whereProjectId($project->id)->pluck('userid')->toArray();
            }
            if ($getstatistics == 'yes') {
                $array = array_merge($array, $project->getTaskStatistics($user->userid));
            }
            return $array;
        });
        //
        $data = $list->toArray();
        $data['total_all'] = $totalAll ?? $data['total'];
        if ($list->currentPage() === 1) {
            $data['deleted_id'] = Deleted::ids('project', $user->userid, $timerange->deleted);
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
     * @apiParam {Number} [personal]    个人项目，注册成功时创建（仅支持创建一个个人项目）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function add()
    {
        $user = User::auth();
        //
        return Project::createProject(Request::all(), $user->userid);
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
        AbstractModel::transaction(function () use ($desc, $name, $project) {
            if ($project->name != $name) {
                $project->addLog("修改项目名称", [
                    'change' => [$project->name, $name]
                ]);
                $project->name = $name;
                if ($project->dialog_id) {
                    WebSocketDialog::updateData(['id' => $project->dialog_id], ['name' => $project->name]);
                }
            }
            if ($project->desc != $desc) {
                $project->desc = $desc;
                $project->addLog("修改项目介绍");
            }
            $project->save();
        });
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
            $project->user_simple = count($array) . "|" . implode(",", array_slice($array, 0, 3));
            $project->save();
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
            return Base::retError('成员不存在');
        }
        //
        AbstractModel::transaction(function() use ($owner_userid, $project) {
            ProjectUser::whereProjectId($project->id)->change(['owner' => 0]);
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
     * @api {post} api/project/sort          10. 排序任务
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
            //
            ProjectPermission::userTaskPermission($project, ProjectPermission::TASK_LIST_SORT);
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
                    if (ProjectTask::whereId($task_id)->whereProjectId($project->id)->whereCompleteAt(null)->change([
                        'column_id' => $item['id'],
                        'sort' => $index
                    ])) {
                        ProjectTask::whereParentId($task_id)->whereProjectId($project->id)->change([
                            'column_id' => $item['id'],
                        ]);
                    }
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
        ProjectPermission::userTaskPermission($project, ProjectPermission::TASK_LIST_ADD);
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
        $project = Project::userProject($column->project_id);
        //
        ProjectPermission::userTaskPermission($project, ProjectPermission::TASK_LIST_UPDATE);
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
        $project = Project::userProject($column->project_id);
        //
        ProjectPermission::userTaskPermission($project, ProjectPermission::TASK_LIST_REMOVE);
        //
        $column->deleteColumn();
        return Base::retSuccess('删除成功', ['id' => $column->id]);
    }

    /**
     * @api {get} api/project/column/one          18. 获取任务列详细
     *
     * @apiDescription 需要token身份（限：项目负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName column__one
     *
     * @apiParam {Number} column_id        列表ID
     * @apiParam {String} [deleted]        是否读取已删除
     * - all：所有
     * - yes：已删除
     * - no：未删除（默认）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function column__one()
    {
        User::auth();
        //
        $column_id = intval(Request::input('column_id'));
        $deleted = Request::input('deleted', 'no');
        //
        $builder = ProjectColumn::whereId($column_id);
        if ($deleted == 'all') {
            $builder->withTrashed();
        } elseif ($deleted == 'yes') {
            $builder->onlyTrashed();
        }
        $column = $builder->first();
        if (empty($column)) {
            return Base::retError('列表不存在');
        }
        //
        return Base::retSuccess('success', $column);
    }


    /**
     * @api {get} api/project/task/lists          19. 任务列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__lists
     *
     * @apiParam {Object} [keys]             搜索条件
     * - keys.name: ID、任务名称
     *
     * @apiParam {Number} [project_id]       项目ID
     * @apiParam {Number} [parent_id]        主任务ID（project_id && parent_id ≤ 0 时 仅查询自己参与的任务）
     * - 大于0：指定主任务下的子任务
     * - 等于-1：表示仅主任务
     *
     * @apiParam {Array} [time]              指定时间范围，如：['2020-12-12', '2020-12-30']
     * @apiParam {String} [timerange]        时间范围（如：1678248944,1678248944）
     * - 第一个时间: 读取在这个时间之后更新的数据
     * - 第二个时间: 读取在这个时间之后删除的数据ID（第1页附加返回数据: deleted_id）
     *
     * @apiParam {String} [archived]         归档状态
     * - all：所有（parent_id > 0 时强制 all）
     * - yes：已归档
     * - no：未归档（默认）
     * @apiParam {String} [deleted]          是否读取已删除
     * - all：所有
     * - yes：已删除
     * - no：未删除（默认）
     *
     * @apiParam {Object} [sorts]              排序方式
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
        $user = User::auth();
        $userid = $user->userid;
        //
        $parent_id = intval(Request::input('parent_id'));
        $project_id = intval(Request::input('project_id'));
        $name = Request::input('name');
        $time = Request::input('time');
        $timerange = TimeRange::parse(Request::input('timerange'));
        $archived = Request::input('archived', 'no');
        $deleted = Request::input('deleted', 'no');
        $keys = Request::input('keys');
        $sorts = Request::input('sorts');
        $keys = is_array($keys) ? $keys : [];
        $sorts = is_array($sorts) ? $sorts : [];

        $builder = ProjectTask::with(['taskUser', 'taskTag']);
        //
        if ($keys['name']) {
            if (Base::isNumber($keys['name'])) {
                $builder->where("project_tasks.id", intval($keys['name']));
            } else {
                $builder->where("project_tasks.name", "like", "%{$keys['name']}%");
            }
        }
        //
        $scopeAll = false;
        if ($parent_id > 0) {
            $isArchived = str_replace(['all', 'yes', 'no'], [null, false, true], $archived);
            $isDeleted = str_replace(['all', 'yes', 'no'], [null, false, true], $deleted);
            ProjectTask::userTask($parent_id, $isArchived, $isDeleted);
            $scopeAll = true;
            $archived = 'all';
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
        if (is_array($time)) {
            if (Base::isDateOrTime($time[0]) && Base::isDateOrTime($time[1])) {
                $builder->betweenTime(Carbon::parse($time[0])->startOfDay(), Carbon::parse($time[1])->endOfDay());
            }
        }
        if ($timerange->updated) {
            $builder->where('project_tasks.updated_at', '>', $timerange->updated);
        }
        //
        if ($archived == 'yes') {
            $builder->whereNotNull('project_tasks.archived_at');
        } elseif ($archived == 'no') {
            $builder->whereNull('project_tasks.archived_at');
        }
        //
        if ($deleted == 'all') {
            $builder->withTrashed();
        } elseif ($deleted == 'yes') {
            $builder->onlyTrashed();
        }
        //
        foreach ($sorts as $column => $direction) {
            if (!in_array($column, ['complete_at', 'archived_at', 'end_at', 'deleted_at'])) continue;
            if (!in_array($direction, ['asc', 'desc'])) continue;
            $builder->orderBy('project_tasks.' . $column, $direction);
        }
        // 任务可见性条件
        $builder->leftJoin('project_users', function ($query) use($userid) {
            $query->on('project_tasks.project_id', '=', 'project_users.project_id');
            $query->where('project_users.owner', 1);
            $query->where('project_users.userid', $userid);
        });
        $builder->leftJoin('project_task_users as project_sub_task_users', function ($query) use($userid) {
            $query->on('project_sub_task_users.task_pid', '=', 'project_tasks.parent_id');
            $query->where('project_sub_task_users.userid', $userid);
        });
        $builder->leftJoin('project_task_visibility_users', function ($query) use($userid) {
            $query->on('project_task_visibility_users.task_id', '=', 'project_tasks.id');
            $query->where('project_task_visibility_users.userid', $userid);
        });
        $builder->where(function ($query) use ($userid) {
            $query->where("project_tasks.visibility", 1);
            $query->orWhere("project_users.userid", $userid);
            $query->orWhere("project_task_users.userid", $userid);
            $query->orWhere("project_task_visibility_users.userid", $userid);
            $query->orWhere("project_sub_task_users.userid", $userid);
        });
        // 优化子查询汇总
        $builder->leftJoinSub(function ($query) {
            $query->select('task_id', DB::raw('count(*) as file_num'))
                ->from('project_task_files')
                ->groupBy('task_id');
        }, 'task_files', 'task_files.task_id', '=', 'project_tasks.id');
        $builder->leftJoinSub(function ($query) {
            $query->select('dialog_id', DB::raw('count(*) as msg_num'))
                ->from('web_socket_dialog_msgs')
                ->groupBy('dialog_id');
        }, 'socket_dialog_msgs', 'socket_dialog_msgs.dialog_id', '=', 'project_tasks.dialog_id');
        $builder->leftJoinSub(function ($query) {
            $query->select('parent_id', DB::raw('count(*) as sub_num, sum(CASE WHEN complete_at IS NOT NULL THEN 1 ELSE 0 END) sub_complete') )
                ->from('project_tasks')
                ->groupBy('parent_id');
        }, 'sub_task', 'sub_task.parent_id', '=', 'project_tasks.id');
        // 给前缀“_”是为了不触发获取器
        $prefix = DB::getTablePrefix();
        $builder->selectRaw("{$prefix}task_files.file_num as _file_num");
        $builder->selectRaw("{$prefix}socket_dialog_msgs.msg_num as _msg_num");
        $builder->selectRaw("{$prefix}sub_task.sub_num as _sub_num");
        $builder->selectRaw("{$prefix}sub_task.sub_complete as _sub_complete");
        $builder->selectRaw("
            CAST(CASE
                WHEN {$prefix}project_tasks.complete_at IS NOT NULL THEN 100
                WHEN {$prefix}sub_task.sub_complete = 0 OR {$prefix}sub_task.sub_complete IS NULL THEN 0
                ELSE ({$prefix}sub_task.sub_complete / {$prefix}sub_task.sub_num * 100)
            END AS SIGNED) as _percent
        ");
        //
        $list = $builder->orderByDesc('project_tasks.id')->paginate(Base::getPaginate(200, 100));
        // 去除模型上的子汇总获取器
        $list->transform(function ($customer) {
            $customer->setAppends(["today","overdue"]);
            return $customer;
        });
        //
        $data = $list->toArray();
        // 还原字段
        foreach($data['data'] as &$item){
            $item['file_num'] = $item['_file_num'] ?: 0;
            $item['msg_num'] = $item['_msg_num'] ?: 0;
            $item['sub_num'] = $item['_sub_num'] ?: 0;
            $item['sub_complete'] = $item['_sub_complete'] ?: 0;
            $item['percent'] = $item['_percent'];
            unset($item['_file_num']);
            unset($item['_msg_num']);
            unset($item['_sub_num']);
            unset($item['_sub_complete']);
            unset($item['_percent']);
        }
        //
        if ($list->currentPage() === 1) {
            $data['deleted_id'] = Deleted::ids('projectTask', $user->userid, $timerange->deleted);
        }
        //
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/project/task/easylists          20. 任务列表-简单的
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__easylists

     * @apiParam {String} [taskid]         排除的任务ID
     * @apiParam {String} [userid]         用户ID（如：1,2）
     * @apiParam {String} [timerange]      时间范围（如：2022-03-01 12:12:12,2022-05-01 12:12:12）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__easylists()
    {
        User::auth();
        //
        $taskid = trim(Request::input('taskid'));
        $userid = Request::input('userid');
        $timerange = Request::input('timerange');
        //
        $list = ProjectTask::with(['taskUser'])
            ->select('projects.name as project_name', 'project_tasks.id', 'project_tasks.name', 'project_tasks.start_at', 'project_tasks.end_at')
            ->join('projects','project_tasks.project_id','=','projects.id')
            ->leftJoin('project_task_users', function ($query) {
                $query->on('project_tasks.id', '=', 'project_task_users.task_id')->where('project_task_users.owner', '=', 1);
            })
            ->whereIn('project_task_users.userid', is_array($userid) ? $userid : explode(',', $userid) )
            ->when(!empty($timerange), function ($query) use ($timerange) {
                if (!is_array($timerange)) {
                    $timerange = explode(',', $timerange);
                }
                if (Base::isDateOrTime($timerange[0]) && Base::isDateOrTime($timerange[1])) {
                    $query->where('project_tasks.start_at', '<=', Carbon::parse($timerange[1])->endOfDay());
                    $query->where('project_tasks.end_at', '>=', Carbon::parse($timerange[0])->startOfDay());
                }
            })
            ->when(!empty($taskid), function ($query) use ($taskid) {
                $query->where('project_tasks.id', "!=", $taskid);
            })
            ->whereNull('complete_at')
            ->distinct()
            ->orderByDesc('project_tasks.id')
            ->paginate(Base::getPaginate(200, 100));
        //
        $list->transform(function ($customer) {
            $customer->setAppends([]);
            return $customer;
        });
        //
        return Base::retSuccess('success', $list);
    }

    /**
     * @api {get} api/project/task/export          21. 导出任务（限管理员）
     *
     * @apiDescription 导出指定范围任务（已完成、未完成、已归档），返回下载地址，需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__export
     *
     * @apiParam {Array} [userid]               指定会员，如：[1, 2]
     * @apiParam {Array} [time]                 指定时间范围，如：['2020-12-12', '2020-12-30']
     * @apiParam {String} [type]
     * - createdTime 任务创建时间
     * - taskTime  任务计划时间（默认）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__export()
    {
        $user = User::auth('admin');
        //
        $userid = Base::arrayRetainInt(Request::input('userid'), true);
        $time = Request::input('time');
        $type = Request::input('type', 'taskTime');
        if (empty($userid) || empty($time)) {
            return Base::retError('参数错误');
        }
        if (count($userid) > 100) {
            return Base::retError('导出成员限制最多100个');
        }
        if (!(is_array($time) && Base::isDateOrTime($time[0]) && Base::isDateOrTime($time[1]))) {
            return Base::retError('时间选择错误');
        }
        if (Carbon::parse($time[1])->timestamp - Carbon::parse($time[0])->timestamp > 90 * 86400) {
            return Base::retError('时间范围限制最大90天');
        }
        $botUser = User::botGetOrCreate('system-msg');
        if (empty($botUser)) {
            return Base::retError('系统机器人不存在');
        }
        $dialog = WebSocketDialog::checkUserDialog($botUser, $user->userid);
        //
        go(function () use ($user, $userid, $time, $type, $botUser, $dialog) {
            Coroutine::sleep(0.1);
            $headings = [];
            $headings[] = '任务ID';
            $headings[] = '父级任务ID';
            $headings[] = '所属项目';
            $headings[] = '任务标题';
            $headings[] = '任务开始时间';
            $headings[] = '任务结束时间';
            $headings[] = '完成时间';
            $headings[] = '归档时间';
            $headings[] = '任务计划用时';
            $headings[] = '实际完成用时';
            $headings[] = '超时时间';
            $headings[] = '开发用时';
            $headings[] = '验收/测试用时';
            $headings[] = '负责人';
            $headings[] = '创建人';
            $headings[] = '状态';
            $datas = [];
            //
            $text = '<b>导出任务统计已完成。</b>';
            $text .= "\n\n";
            //
            $builder = ProjectTask::select(['project_tasks.*', 'project_task_users.userid as ownerid'])
                ->join('project_task_users', 'project_tasks.id', '=', 'project_task_users.task_id')
                ->where('project_task_users.owner', 1)
                ->whereIn('project_task_users.userid', $userid)
                ->betweenTime(Carbon::parse($time[0])->startOfDay(), Carbon::parse($time[1])->endOfDay(), $type);
            $builder->orderByDesc('project_tasks.id')->chunk(100, function ($tasks) use (&$datas) {
                /** @var ProjectTask $task */
                foreach ($tasks as $task) {
                    $flowChanges = ProjectTaskFlowChange::whereTaskId($task->id)->get();
                    $testTime = 0;//验收/测试时间
                    $taskStartTime = $task->start_at ? Carbon::parse($task->start_at)->timestamp : Carbon::parse($task->created_at)->timestamp;
                    $taskCompleteTime = $task->complete_at ? Carbon::parse($task->complete_at)->timestamp : time();
                    $totalTime = $taskCompleteTime - $taskStartTime; //开发测试总用时
                    foreach ($flowChanges as $change) {
                        if (!str_contains($change->before_flow_item_name, 'end')) {
                            $upOne = ProjectTaskFlowChange::where('id', '<', $change->id)->whereTaskId($task->id)->orderByDesc('id')->first();
                            if ($upOne) {
                                if (str_contains($change->before_flow_item_name, 'test') || str_contains($change->before_flow_item_name, '测试') || strpos($change->before_flow_item_name, '验收') !== false) {
                                    $testCtime = Carbon::parse($change->created_at)->timestamp;
                                    $tTime = Carbon::parse($upOne->created_at)->timestamp;
                                    $tMinusNum = $testCtime - $tTime;
                                    $testTime += $tMinusNum;
                                }
                            }
                        }
                    }
                    if (!$task->complete_at) {
                        $lastChange = ProjectTaskFlowChange::whereTaskId($task->id)->orderByDesc('id')->first();
                        $nowTime = time();
                        $unFinishTime = $nowTime - Carbon::parse($lastChange->created_at)->timestamp;
                        if (str_contains($lastChange->after_flow_item_name, 'test') || str_contains($lastChange->after_flow_item_name, '测试') || strpos($lastChange->after_flow_item_name, '验收') !== false) {
                            $testTime += $unFinishTime;
                        }
                    }
                    $developTime = $totalTime - $testTime;//开发时间
                    $planTime = '-';//任务计划用时
                    $overTime = '-';//超时时间
                    if ($task->end_at) {
                        $startTime = Carbon::parse($task->start_at)->timestamp;
                        $endTime = Carbon::parse($task->end_at)->timestamp;
                        $planTotalTime = $endTime - $startTime;
                        $residueTime = $planTotalTime - $totalTime;
                        if ($residueTime < 0) {
                            $overTime = Base::timeFormat(abs($residueTime));
                        }
                        $planTime = Base::timeDiff($startTime, $endTime);
                    }
                    $actualTime = $task->complete_at ? $totalTime : 0;//实际完成用时
                    $statusText = '未完成';
                    if ($task->flow_item_name) {
                        if (str_contains($task->flow_item_name, '已取消')) {
                            $statusText = '已取消';
                            $actualTime = 0;
                            $testTime = 0;
                            $developTime = 0;
                            $overTime = '-';
                        } elseif (str_contains($task->flow_item_name, '已完成')) {
                            $statusText = '已完成';
                        }
                    } elseif ($task->complete_at) {
                        $statusText = '已完成';
                    }
                    if (!isset($datas[$task->ownerid])) {
                        $datas[$task->ownerid] = [
                            'index' => 1,
                            'nickname' => Base::filterEmoji(User::userid2nickname($task->ownerid)),
                            'styles' => ["A1:P1" => ["font" => ["bold" => true]]],
                            'data' => [],
                        ];
                    }
                    $datas[$task->ownerid]['index']++;
                    if ($statusText === '未完成') {
                        $datas[$task->ownerid]['styles']["P{$datas[$task->ownerid]['index']}"] = ["font" => ["color" => ["rgb" => "ff0000"]]];  // 未完成
                    } elseif ($statusText === '已完成' && $task->end_at && Carbon::parse($task->complete_at)->gt($task->end_at)) {
                        $datas[$task->ownerid]['styles']["P{$datas[$task->ownerid]['index']}"] = ["font" => ["color" => ["rgb" => "436FF6"]]];  // 已完成超期
                    }
                    $datas[$task->ownerid]['data'][] = [
                        $task->id,
                        $task->parent_id ?: '-',
                        Base::filterEmoji($task->project?->name) ?: '-',
                        Base::filterEmoji($task->name),
                        $task->start_at ?: '-',
                        $task->end_at ?: '-',
                        $task->complete_at ?: '-',
                        $task->archived_at ?: '-',
                        $planTime ?: '-',
                        $actualTime ? Base::timeFormat($actualTime) : '-',
                        $overTime,
                        $developTime > 0 ? Base::timeFormat($developTime) : '-',
                        $testTime > 0 ? Base::timeFormat($testTime) : '-',
                        Base::filterEmoji(User::userid2nickname($task->ownerid)) . " (ID: {$task->ownerid})",
                        Base::filterEmoji(User::userid2nickname($task->userid)) . " (ID: {$task->userid})",
                        $statusText
                    ];
                }
            });
            if (empty($datas)) {
                $text .= '没有任何数据';
                WebSocketDialogMsg::sendMsg(null, $dialog->id, 'text', ['text' => $text], $botUser->userid, false, false, true);
                return;
            }
            //
            $sheets = [];
            foreach ($userid as $ownerid) {
                $data = $datas[$ownerid] ?? [
                    'nickname' => Base::filterEmoji(User::userid2nickname($ownerid)),
                    'styles' => ["A1:P1" => ["font" => ["bold" => true]]],
                    'data' => [],
                ];
                $title = (count($sheets) + 1) . "." . ($data['nickname'] ?: $ownerid);
                $sheets[] = BillExport::create()->setTitle($title)->setHeadings($headings)->setData($data['data'])->setStyles($data['styles']);
            }
            //
            $fileName = User::userid2nickname($userid[0]) ?: $userid[0];
            if (count($userid) > 1) {
                $fileName .= '等' . count($userid) . '位成员任务统计';
            }
            $fileName .= '_' . Base::time() . '.xls';
            $filePath = "temp/task/export/" . date("Ym", Base::time());
            $export = new BillMultipleExport($sheets);
            $res = $export->store($filePath . "/" . $fileName);
            if ($res != 1) {
                $text .= "导出失败，{$fileName}！";
                WebSocketDialogMsg::sendMsg(null, $dialog->id, 'text', ['text' => $text], $botUser->userid, false, false, true);
                return;
            }
            //
            $xlsPath = storage_path("app/" . $filePath . "/" . $fileName);
            $zipFile = "app/" . $filePath . "/" . Base::rightDelete($fileName, '.xls') . ".zip";
            $zipPath = storage_path($zipFile);
            if (file_exists($zipPath)) {
                Base::deleteDirAndFile($zipPath, true);
            }
            try {
                Madzipper::make($zipPath)->add($xlsPath)->close();
            } catch (\Throwable) {
            }
            //
            if (file_exists($zipPath)) {
                $base64 = base64_encode(Base::array2string([
                    'file' => $zipFile,
                ]));
                $fileUrl = Base::fillUrl('api/project/task/down?key=' . urlencode($base64));
                Session::put('task::export:userid', $user->userid);
                $text .= "文件名：{$fileName}";
                $text .= "\n";
                $text .= "文件大小：" . Base::twoFloat(filesize($zipPath) / 1024, true) . "KB";
                $text .= "\n";
                $text .= '<a href="' . $fileUrl . '" target="_blank"><button type="button" class="ivu-btn ivu-btn-warning" style="margin-top: 10px;"><span>立即下载</span></button></a>';
            } else {
                $text .= '打包失败，请稍后再试...';
            }
            WebSocketDialogMsg::sendMsg(null, $dialog->id, 'text', ['text' => $text], $botUser->userid, false, false, true);
        });
        return Base::retSuccess('success', ['msg' => '正在打包，请留意系统消息。']);
    }

    /**
     * @api {get} api/project/task/exportoverdue          22. 导出超期任务（限管理员）
     *
     * @apiDescription 导出指定范围任务（已完成、未完成、已归档），返回下载地址，需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__exportoverdue
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__exportoverdue()
    {
        $user = User::auth('admin');
        //
        $headings = [];
        $headings[] = '任务ID';
        $headings[] = '父级任务ID';
        $headings[] = '所属项目';
        $headings[] = '任务标题';
        $headings[] = '任务开始时间';
        $headings[] = '任务结束时间';
        $headings[] = '任务计划用时';
        $headings[] = '超时时间';
        $headings[] = '负责人';
        $headings[] = '创建人';
        $data = [];
        //
        ProjectTask::whereNull('complete_at')
            ->whereNotNull('end_at')
            ->where('end_at', '<=', Carbon::now())
            ->orderBy('end_at')
            ->chunk(100, function ($tasks) use (&$data) {
                /** @var ProjectTask $task */
                foreach ($tasks as $task) {
                    $taskStartTime = Carbon::parse($task->start_at ?: $task->created_at)->timestamp;
                    $totalTime = time() - $taskStartTime; //开发测试总用时
                    $planTime = '-';//任务计划用时
                    $overTime = '-';//超时时间
                    if ($task->end_at) {
                        $startTime = Carbon::parse($task->start_at)->timestamp;
                        $endTime = Carbon::parse($task->end_at)->timestamp;
                        $planTotalTime = $endTime - $startTime;
                        $residueTime = $planTotalTime - $totalTime;
                        if ($residueTime < 0) {
                            $overTime = Base::timeFormat(abs($residueTime));
                        }
                        $planTime = Base::timeDiff($startTime, $endTime);
                    }
                    $ownerIds = $task->taskUser->where('owner', 1)->pluck('userid')->toArray();
                    $ownerNames = [];
                    foreach ($ownerIds as $ownerId) {
                        $ownerNames[] = Base::filterEmoji(User::userid2nickname($ownerId)) . " (ID: {$ownerId})";
                    }
                    $data[] = [
                        $task->id,
                        $task->parent_id ?: '-',
                        Base::filterEmoji($task->project?->name) ?: '-',
                        Base::filterEmoji($task->name),
                        $task->start_at ?: '-',
                        $task->end_at ?: '-',
                        $planTime ?: '-',
                        $overTime,
                        implode("、", $ownerNames),
                        Base::filterEmoji(User::userid2nickname($task->userid)) . " (ID: {$task->userid})",
                    ];
                }
            });
        if (empty($data)) {
            return Base::retError('没有任何数据');
        }
        //
        $sheets = [
            BillExport::create()->setTitle("超期任务")->setHeadings($headings)->setData($data)->setStyles(["A1:J1" => ["font" => ["bold" => true]]])
        ];
        //
        $fileName = '超期任务_' . Base::time() . '.xls';
        $filePath = "temp/task/export/" . date("Ym", Base::time());
        $export = new BillMultipleExport($sheets);
        $res = $export->store($filePath . "/" . $fileName);
        if ($res != 1) {
            return Base::retError('导出失败，' . $fileName . '！');
        }
        $xlsPath = storage_path("app/" . $filePath . "/" . $fileName);
        $zipFile = "app/" . $filePath . "/" . Base::rightDelete($fileName, '.xls') . ".zip";
        $zipPath = storage_path($zipFile);
        if (file_exists($zipPath)) {
            Base::deleteDirAndFile($zipPath, true);
        }
        try {
            Madzipper::make($zipPath)->add($xlsPath)->close();
        } catch (\Throwable) {
        }
        //
        if (file_exists($zipPath)) {
            $base64 = base64_encode(Base::array2string([
                'file' => $zipFile,
            ]));
            Session::put('task::export:userid', $user->userid);
            return Base::retSuccess('success', [
                'size' => Base::twoFloat(filesize($zipPath) / 1024, true),
                'url' => Base::fillUrl('api/project/task/down?key=' . urlencode($base64)),
            ]);
        } else {
            return Base::retError('打包失败，请稍后再试...');
        }
    }

    /**
     * @api {get} api/project/task/down          23. 下载导出的任务
     *
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__down
     *
     * @apiParam {String} key               通过export接口得到的下载钥匙
     *
     * @apiSuccess {File} data     返回数据（直接下载文件）
     */
    public function task__down()
    {
        $userid = Session::get('task::export:userid');
        if (empty($userid)) {
            return Base::ajaxError("请求已过期，请重新导出！", [], 0, 502);
        }
        //
        $array = Base::string2array(base64_decode(urldecode(Request::input('key'))));
        $file = $array['file'];
        if (empty($file) || !file_exists(storage_path($file))) {
            return Base::ajaxError("文件不存在！", [], 0, 502);
        }
        return Response::download(storage_path($file));
    }

    /**
     * @api {get} api/project/task/one          24. 获取单个任务信息
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
        $user = User::auth();
        //
        $task_id = intval(Request::input('task_id'));
        $archived = Request::input('archived', 'no');
        //
        $isArchived = str_replace(['all', 'yes', 'no'], [null, false, true], $archived);
        $task = ProjectTask::userTask($task_id, $isArchived, true, ['taskUser', 'taskTag']);
        // 项目可见性
        $project_userid = ProjectUser::whereProjectId($task->project_id)->whereOwner(1)->value('userid');     // 项目负责人
        if ($task->visibility != 1 && $user->userid != $project_userid) {
            $taskUserids = ProjectTaskUser::whereTaskId($task_id)->pluck('userid')->toArray();                      //任务负责人、协助人
            $subTaskUserids = ProjectTaskUser::whereTaskPid($task_id)->pluck('userid')->toArray();                  //子任务负责人、协助人
            $visibleUserids = ProjectTaskVisibilityUser::whereTaskId($task_id)->pluck('userid')->toArray();         //可见人
            $visibleUserids = array_merge($taskUserids, $subTaskUserids, $visibleUserids);
            if (!in_array($user->userid, $visibleUserids)) {
                return Base::retError('无任务权限', ['task_id' => $task_id, 'force' => 1], -4002);
            }
        }
        //
        $data = $task->toArray();
        $data['project_name'] = $task->project?->name;
        $data['column_name'] = $task->projectColumn?->name;
        $data['visibility_appointor'] = $task->visibility == 1 ? [0] : ProjectTaskVisibilityUser::whereTaskId($task_id)->pluck('userid');
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/project/task/content          25. 获取任务详细描述
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__content
     *
     * @apiParam {Number} task_id               任务ID
     * @apiParam {Number} [history_id]          历史ID（获取历史版本）
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
        $history_id = intval(Request::input('history_id'));
        //
        $task = ProjectTask::userTask($task_id, null);
        //
        if ($history_id > 0) {
            $taskContent = ProjectTaskContent::whereTaskId($task->id)->whereId($history_id)->first();
            if (empty($taskContent)) {
                return Base::retError('历史版本不存在');
            }
            return Base::retSuccess('success', array_merge($taskContent->getContentInfo(), [
                'name' => $task->name,
            ]));
        }
        if (empty($task->content)) {
            return Base::retSuccess('success', json_decode('{}'));
        }
        return Base::retSuccess('success', $task->content->getContentInfo());
    }

    /**
     * @api {get} api/project/task/content_history          26. 获取任务详细历史描述
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__content_history
     *
     * @apiParam {Number} task_id            任务ID
     *
     * @apiParam {Number} [page]            当前页，默认:1
     * @apiParam {Number} [pagesize]        每页显示数量，默认:20，最大:100
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__content_history()
    {
        User::auth();
        //
        $task_id = intval(Request::input('task_id'));
        //
        $task = ProjectTask::userTask($task_id, null);
        //
        $data = ProjectTaskContent::select(['id', 'task_id', 'desc', 'userid', 'created_at'])
            ->whereTaskId($task->id)
            ->orderByDesc('id')
            ->paginate(Base::getPaginate(100, 20));
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/project/task/files          27. 获取任务文件列表
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
     * @api {get} api/project/task/filedelete          28. 删除任务文件
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
        $task = ProjectTask::userTask($file->task_id);
        //
        ProjectPermission::userTaskPermission(Project::userProject($task->project_id), ProjectPermission::TASK_UPDATE, $task);
        //
        $task->pushMsg('filedelete', $file);
        $file->delete();
        //
        return Base::retSuccess('success', $file);
    }

    /**
     * @api {get} api/project/task/filedetail          29. 获取任务文件详情
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
        return Base::retSuccess('success', File::formatFileData($data));
    }

    /**
     * @api {get} api/project/task/filedown          30. 下载任务文件
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__filedown
     *
     * @apiParam {Number} file_id            文件ID
     * @apiParam {String} down                  直接下载
     * - yes: 下载（默认）
     * - preview: 转预览地址
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
        $down = Request::input('down', 'yes');
        //
        $file = ProjectTaskFile::find($file_id);
        if (empty($file)) {
            abort(403, "This file not exist.");
        }
        //
        try {
            ProjectTask::userTask($file->task_id, null);
        } catch (\Throwable $e) {
            abort(403, $e->getMessage() ?: "This file not support download.");
        }
        //
        if ($down === 'preview') {
            return Redirect::to(FileContent::toPreviewUrl([
                'ext' => $file->ext,
                'name' => $file->name,
                'path' => $file->getRawOriginal('path'),
            ]));
        }
        //
        $filePath = public_path($file->getRawOriginal('path'));
        return Base::streamDownload($filePath, $file->name);
    }

    /**
     * @api {post} api/project/task/add          31. 添加任务
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
        $user = User::auth();
        //
        $data = Request::input();
        $project_id = intval($data['project_id']);
        $column_id = $data['column_id'];
        // 项目
        $project = Project::userProject($project_id);
        //
        ProjectPermission::userTaskPermission($project, ProjectPermission::TASK_ADD);
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


        if ($data['visibility'] == 1) {
            $data['is_visible'] = 1;
        } else {
            $projectOwner = ProjectUser::whereProjectId($task->project_id)->whereOwner(1)->pluck('userid')->toArray();  // 项目负责人
            $taskOwnerAndAssists = ProjectTaskUser::select(['userid', 'owner'])->whereTaskId($data['id'])->pluck('userid')->toArray();
            $visibleIds = array_merge($projectOwner, $taskOwnerAndAssists);
            $data['is_visible'] = in_array($user->userid, $visibleIds) ? 1 : 0;
        }

        $task->pushMsg('add', $data);
        $task->taskPush(null, 0);
        return Base::retSuccess('添加成功', $data);
    }

    /**
     * @api {get} api/project/task/addsub          32. 添加子任务
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
        $task = ProjectTask::userTask($task_id);
        if ($task->complete_at) {
            return Base::retError('主任务已完成无法添加子任务');
        }
        //
        ProjectPermission::userTaskPermission(Project::userProject($task->project_id), ProjectPermission::TASK_ADD);
        //
        $task = ProjectTask::addTask([
            'name' => $name,
            'parent_id' => $task->id,
            'project_id' => $task->project_id,
            'column_id' => $task->column_id,
            'times' => [$task->start_at, $task->end_at],
            'owner' => [User::userid()],
            'visibility' => $task->visibility,
        ]);
        $data = ProjectTask::oneTask($task->id);
        $pushUserIds = ProjectTaskUser::whereTaskId($task->id)->pluck('userid')->toArray();
        $pushUserIds[] = ProjectUser::whereProjectId($task->project_id)->whereOwner(1)->value('userid');
        foreach ($pushUserIds as $userId) {
            $task->pushMsg('add', $data, $userId);
        }
        return Base::retSuccess('添加成功', $data);
    }

    /**
     * @api {post} api/project/task/update          33. 修改任务、子任务
     *
     * @apiDescription 需要token身份（限：项目、任务负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__update
     *
     * @apiParam {Number} task_id               任务ID
     * @apiParam {String} [name]                任务描述
     * @apiParam {Array} [times]                计划时间（格式：开始时间,结束时间；如：2020-01-01 00:00,2020-01-01 23:59）
     * @apiParam {String} [loop]                重复周期，数字代表天数（子任务不支持）
     * @apiParam {Array} [owner]                修改负责人
     * @apiParam {String} [content]             任务详情（子任务不支持）
     * @apiParam {String} [color]               背景色（子任务不支持）
     * @apiParam {Array} [assist]               修改协助人员（子任务不支持）
     * @apiParam {Number} [visibility]          修改可见性
     * @apiParam {Array} [visibility_appointor] 修改可见性人员
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
        $param = Request::input();
        $task_id = intval($param['task_id']);
        //
        $task = ProjectTask::userTask($task_id);
        //
        $project = Project::userProject($task->project_id);
        if (Arr::exists($param, 'flow_item_id')) {
            ProjectPermission::userTaskPermission($project, ProjectPermission::TASK_STATUS, $task);
        }else{
            ProjectPermission::userTaskPermission($project, ProjectPermission::TASK_UPDATE, $task);
        }
        //
        $taskUser = ProjectTaskUser::select(['userid', 'owner'])->whereTaskId($task_id)->get();
        $owners = $taskUser->where('owner', 1)->pluck('userid')->toArray();
        $assists = $taskUser->where('owner', 0)->pluck('userid')->toArray();
        $visible = ProjectTaskVisibilityUser::whereTaskId($task->id)->pluck('userid')->toArray();
        // 更新任务
        $updateMarking = [];
        $task->updateTask($param, $updateMarking);
        //
        $data = ProjectTask::oneTask($task->id)->toArray();
        $data['update_marking'] = $updateMarking ?: json_decode('{}');
        $data['visibility_appointor'] = $data['visibility'] == 1 ? [] : ProjectTaskVisibilityUser::whereTaskId($task->id)->pluck('userid');
        $task->pushMsg('update', $data);
        // 可见性推送
        if ($task->parent_id == 0) {
            $subUserids = ProjectTaskUser::whereTaskPid($data['id'])->pluck('userid')->toArray();
            if (Arr::exists($param, 'visibility') || Arr::exists($param, 'visibility_appointor')) {
                if ($data['visibility'] == 1) {
                    $task->pushMsgVisibleAdd($data);
                }
                if ($param['visibility_appointor']) {
                    $newVisibleUserIds = is_array($param['visibility_appointor']) ? $param['visibility_appointor'] : [];
                    $deleteUserIds = array_diff($visible, $newVisibleUserIds, $subUserids);
                    $addUserIds = array_diff($newVisibleUserIds, $visible);
                    $task->pushMsgVisibleUpdate($data, $deleteUserIds, $addUserIds);
                }
                if ($data['visibility'] != 1 && empty($param['visibility_appointor'])) {
                    $task->pushMsgVisibleRemove();
                }
            }
            if (Arr::exists($param, 'owner') && $data['visibility'] != 1) {
                $diff = array_diff($owners, $subUserids);
                if ($diff) {
                    $task->pushMsgVisibleRemove($diff);
                }
            }
            if (Arr::exists($param, 'assist') && $data['visibility'] != 1) {
                $diff = array_diff($assists, $subUserids);
                if ($diff) {
                    $task->pushMsgVisibleRemove($diff);
                }
            }
        } else {
            if (Arr::exists($param, 'owner')) {
                $diff = array_diff($owners, $param['owner'] ?: []);
                if ($diff) {
                    $task->pushMsgVisibleRemove($diff);
                }
                $parentTask = ProjectTask::whereId($task->parent_id)->first();
                $subUserids = ProjectTaskUser::whereTaskPid($task->parent_id)->pluck('userid')->toArray();
                if ($parentTask && $parentTask->visibility != 1 && empty($subUserids)) {
                    $diff = array_diff($owners, $param['owner'] ?: [], $subUserids);
                    if ($diff) {
                        $parentTask->pushMsgVisibleRemove($diff);
                    }
                }
            }
        }
        //
        return Base::retSuccess('修改成功', $data);
    }

    /**
     * @api {get} api/project/task/dialog          34. 创建/获取聊天室
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
        $user = User::auth();
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
                $dialog = WebSocketDialog::createGroup($task->name, $task->relationUserids(), 'task');
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
        $dialogData = WebSocketDialog::find($task->dialog_id)?->formatData($user->userid);
        return Base::retSuccess('success', [
            'id' => $task->id,
            'dialog_id' => $task->dialog_id,
            'dialog_data' => $dialogData,
        ]);
    }

    /**
     * @api {get} api/project/task/archived          35. 归档任务
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
        $task = ProjectTask::userTask($task_id, $type == 'add');
        //
        if ($task->parent_id > 0) {
            return Base::retError('子任务不支持此功能');
        }
        //
        $project = Project::userProject($task->project_id);
        ProjectPermission::userTaskPermission($project, ProjectPermission::TASK_ARCHIVED, $task);
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
     * @api {get} api/project/task/remove          36. 删除任务
     *
     * @apiDescription 需要token身份（限：项目、任务负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__remove
     *
     * @apiParam {Number} task_id               任务ID
     * @apiParam {String} type
     * - recovery: 还原
     * - delete: 删除（默认）
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
        $type = Request::input('type', 'delete');
        //
        $task = ProjectTask::userTask($task_id, null, $type !== 'recovery');
        //
        $project = Project::userProject($task->project_id);
        ProjectPermission::userTaskPermission($project, ProjectPermission::TASK_REMOVE, $task);
        //
        if ($type == 'recovery') {
            $task->restoreTask();
            return Base::retSuccess('操作成功', ['id' => $task->id]);
        } else {
            $task->deleteTask();
            return Base::retSuccess('删除成功', ['id' => $task->id]);
        }
    }

    /**
     * @api {get} api/project/task/resetfromlog          37. 根据日志重置任务
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
        $task = ProjectTask::userTask($projectLog->task_id);
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
     * @api {get} api/project/task/flow          38. 任务工作流信息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__flow
     *
     * @apiParam {Number} task_id               任务ID
     * @apiParam {Number} project_id            项目ID - 存在时只返回这个项目的
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
        $project_id = intval(Request::input('project_id'));
        //
        $projectTask = ProjectTask::select(['id', 'project_id', 'complete_at', 'flow_item_id', 'flow_item_name'])->withTrashed()->find($task_id);
        if (empty($projectTask)) {
            return Base::retError('任务不存在', ['task_id' => $task_id], -4002);
        }
        //
        $projectFlowItem = null;
        if ($project_id) {
            $projectFlow = ProjectFlow::whereProjectId($project_id)->orderByDesc('id')->first();
        } else {
            $projectFlowItem = $projectTask->flow_item_id ? ProjectFlowItem::with(['projectFlow'])->find($projectTask->flow_item_id) : null;
            if ($projectFlowItem?->projectFlow) {
                $projectFlow = $projectFlowItem->projectFlow;
            } else {
                $projectFlow = ProjectFlow::whereProjectId($projectTask->project_id)->orderByDesc('id')->first();
            }
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
     * @api {get} api/project/task/move          39. 任务移动
     *
     * @apiDescription 需要token身份（限：项目、任务负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__move
     *
     * @apiParam {Number} task_id               任务ID
     * @apiParam {Number} project_id            项目ID
     * @apiParam {Number} column_id             列ID
     * @apiParam {Number} flow_item_id          工作流id
     * @apiParam {Array} owner                  负责人
     * @apiParam {Array} assist                 协助人
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__move()
    {
        User::auth();
        //
        $task_id = intval(Request::input('task_id'));
        $project_id = intval(Request::input('project_id'));
        $column_id = intval(Request::input('column_id'));
        $flow_item_id = intval(Request::input('flow_item_id'));
        $owner = Request::input('owner', []);
        $assist = Request::input('assist', []);
        $completeAt = trim(Request::input('complete_at', ''));
        //
        $task = ProjectTask::userTask($task_id);
        //
        $project = Project::userProject($task->project_id);
        ProjectPermission::userTaskPermission($project, ProjectPermission::TASK_MOVE, $task);
        //
        if ($task->project_id == $project_id && $task->column_id == $column_id) {
            return Base::retSuccess('移动成功', ['id' => $task_id]);
        }
        //
        $project = Project::userProject($project_id);
        $column = ProjectColumn::whereProjectId($project->id)->whereId($column_id)->first();
        if (empty($column)) {
            return Base::retError('列表不存在');
        }
        if ($flow_item_id) {
            $flowItem = projectFlowItem::whereProjectId($project->id)->whereId($flow_item_id)->first();
            if (empty($flowItem)) {
                return Base::retError('任务状态不存在');
            }
        } else if (!$flow_item_id && !$completeAt) {
            if (projectFlowItem::whereProjectId($project->id)->count() > 0) {
                return Base::retError('请选择移动后状态', [], 102);
            }
        }
        //
        $task->moveTask($project_id, $column_id, $flow_item_id, $owner, $assist, $completeAt);
        //
        $task = ProjectTask::userTask($task_id);
        //
        return Base::retSuccess('移动成功', $task);
    }

    /**
     * @api {get} api/project/flow/list          40. 工作流列表
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
     * @api {post} api/project/flow/save          41. 保存工作流
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
        $project_id = intval(Request::input('project_id'));
        $flows = Request::input('flows');
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
     * @api {get} api/project/flow/delete          42. 删除工作流
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
     * @api {get} api/project/log/lists          43. 获取项目、任务日志
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
            $builder->with(['projectTask:id,parent_id,name'])->whereProjectId($project->id)->whereTaskOnly(0);
        }
        //
        $list = $builder->orderByDesc('created_at')->paginate(Base::getPaginate(100, 20));
        $list->transform(function (ProjectLog $log) use ($task_id) {
            $timestamp = Carbon::parse($log->created_at)->timestamp;
            if ($task_id === 0) {
                $log->projectTask?->cancelAppend();
            }
            $log->detail = Doo::translate($log->detail);
            $log->time = [
                'ymd' => date(date("Y", $timestamp) == date("Y", Base::time()) ? "m-d" : "Y-m-d", $timestamp),
                'hi' => date("h:i", $timestamp) ,
                'week' => Doo::translate("周" . Base::getTimeWeek($timestamp)),
                'segment' => Doo::translate(Base::getTimeDayeSegment($timestamp)),
            ];
            $record = Base::json2array($log->record);
            if (is_array($record['change'])) {
                foreach ($record['change'] as &$item) {
                    $item = preg_replace_callback('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', function ($matches) {
                        $time = strtotime($matches[0]);
                        $second = date("s", $time);
                        $second = $second === "00" ? "" : ":$second";
                        if (date("Y") === date("Y", $time)) {
                            return date("m-d H:i", $time) . $second;
                        }
                        return date("Y-m-d H:i", $time) . $second;
                    }, $item);
                }
                $log->record = $record;
            }
            return $log;
        });
        //
        return Base::retSuccess('success', $list);
    }

    /**
     * @api {get} api/project/top          44. 项目置顶
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
        return Base::retSuccess("success", [
            'id' => $projectUser->project_id,
            'top_at' => $projectUser->top_at?->toDateTimeString(),
        ]);
    }

    /**
     * @api {get} api/project/permission          45. 获取项目权限设置
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName permission
     *
     * @apiParam {Number} project_id                项目ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function permission()
    {
        $user = User::auth();
        $projectId = intval(Request::input('project_id'), 0);
        $projectUser = ProjectUser::whereUserid($user->userid)->whereProjectId($projectId)->first();
        if (!$projectUser) {
            return Base::retError("项目不存在");
        }
        $projectPermission = ProjectPermission::initPermissions($projectId);
        return Base::retSuccess("success",  $projectPermission);
    }

    /**
     * @api {get} api/project/permission/update          46. 项目权限设置
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName permission__update
     *
     * @apiParam {Number} project_id                        项目ID
     * @apiParam {Array} task_add                           添加任务权限
     * @apiParam {Array} task_update                        修改任务权限
     * @apiParam {Array} task_remove                        删除任务权限
     * @apiParam {Array} task_update_complete               标记完成权限
     * @apiParam {Array} task_archived                      归档任务权限
     * @apiParam {Array} task_move                          移动任务权限
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function permission__update()
    {
        $user = User::auth();
        $projectId = intval(Request::input('project_id'), 0);
        $projectUser = ProjectUser::whereUserid($user->userid)->whereProjectId($projectId)->first();
        if (!$projectUser) {
            return Base::retError("项目不存在");
        }
        $permissions = Request::only([
            ProjectPermission::TASK_LIST_ADD,
            ProjectPermission::TASK_LIST_UPDATE,
            ProjectPermission::TASK_LIST_REMOVE,
            ProjectPermission::TASK_LIST_SORT,
            ProjectPermission::TASK_ADD,
            ProjectPermission::TASK_UPDATE,
            ProjectPermission::TASK_REMOVE,
            ProjectPermission::TASK_STATUS,
            ProjectPermission::TASK_ARCHIVED,
            ProjectPermission::TASK_MOVE,
        ]);
        $projectPermission = ProjectPermission::updatePermissions($projectId, Base::newArrayRecursive('intval', $permissions));
        return Base::retSuccess("success",  $projectPermission);
    }
}
