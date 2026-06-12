<?php

namespace App\Http\Controllers\Api;

use Request;
use Redirect;
use Response;
use Carbon\Carbon;
use App\Module\Down;
use App\Module\Doo;
use App\Models\File;
use App\Models\User;
use App\Module\Base;
use App\Module\Timer;
use Swoole\Coroutine;
use App\Module\AI;
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
use App\Models\ProjectTaskTag;
use App\Models\ProjectTaskUser;
use App\Models\WebSocketDialog;
use App\Exceptions\ApiException;
use App\Models\ProjectPermission;
use App\Models\ProjectTaskContent;
use App\Models\WebSocketDialogMsg;
use App\Module\BillMultipleExport;
use App\Models\UserRecentItem;
use Illuminate\Support\Facades\DB;
use App\Models\ProjectTaskFlowChange;
use App\Models\ProjectTaskVisibilityUser;
use App\Models\ProjectTaskTemplate;
use App\Models\ProjectTag;
use App\Models\ProjectTaskRelation;
use App\Models\ProjectTaskAiEvent;
use App\Models\UserDepartment;
use App\Module\AiTaskSuggestion;
use App\Observers\ProjectTaskObserver;

/**
 * @apiDefine project
 *
 * 项目
 */
class ProjectController extends AbstractController
{
    /**
     * @api {get} api/project/lists 获取项目列表
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
        $departmentView = UserDepartment::ownerViewContext($user);
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
        } elseif ($departmentView['enabled']) {
            $projectIds = array_values(array_unique(array_merge($departmentView['own_project_ids'], $departmentView['project_ids'])));
            $builder = Project::allData()->whereIn('projects.id', $projectIds);
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
        $list = $builder
            ->orderByDesc('project_users.top_at')
            ->orderBy('project_users.sort')
            ->orderByDesc('projects.id')
            ->paginate(Base::getPaginate(100, 50));
        $list->transform(function (Project $project) use ($getstatistics, $getuserid, $user, $departmentView) {
            $array = $project->toArray();
            $array = UserDepartment::appendDepartmentReadonlyProject($array, $departmentView);
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
     * @api {get} api/project/one 获取一个项目信息
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
        $departmentView = UserDepartment::ownerViewContext($user, true);
        //
        $project_id = intval(Request::input('project_id'));
        //
        $project = Project::findForDepartmentView($project_id);
        $data = array_merge($project->toArray(), $project->getTaskStatistics($user->userid), [
            'project_user' => $project->projectUser,
        ]);
        $data = UserDepartment::appendDepartmentReadonlyProject($data, $departmentView);
        //
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/project/add 添加项目
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
     * @api {get} api/project/update 修改项目
     *
     * @apiDescription 需要token身份（限：项目负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName update
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {String} name              项目名称
     * @apiParam {String} [desc]            项目介绍
     * @apiParam {String} [archive_method]  归档方式
     * @apiParam {Number} [archive_days]    自动归档天数
     * @apiParam {String} [ai_auto_analyze] AI自动分析（open|close）
     * @apiParam {String} [task_template_share] 共享模板（open|close）
     * @apiParam {String} [department_owner_view] 部门负责人视角可见（open|close）
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
        $archive_method = Request::input('archive_method');
        $archive_days = intval(Request::input('archive_days'));
        $ai_auto_analyze = Request::input('ai_auto_analyze');
        $task_template_share = Request::input('task_template_share');
        $department_owner_view = Request::input('department_owner_view');
        if (mb_strlen($name) < 2) {
            return Base::retError('项目名称不可以少于2个字');
        } elseif (mb_strlen($name) > 32) {
            return Base::retError('项目名称最多只能设置32个字');
        }
        if (mb_strlen($desc) > 255) {
            return Base::retError('项目介绍最多只能设置255个字');
        }
        if ($archive_method == 'custom') {
            if ($archive_days < 1 || $archive_days > 365) {
                return Base::retError('自动归档天数设置错误，范围：1-365');
            }
        }
        //
        $project = Project::userProject($project_id, true, true);
        AbstractModel::transaction(function () use ($archive_days, $archive_method, $ai_auto_analyze, $task_template_share, $department_owner_view, $desc, $name, $project) {
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
            if ($project->archive_method != $archive_method) {
                $project->addLog("修改归档方式", [
                    'change' => [$project->archive_method, $archive_method]
                ]);
                $project->archive_method = $archive_method;
            }
            if ($project->archive_method == 'custom') {
                $project->addLog("修改自动归档天数", [
                    'change' => [$project->archive_days, $archive_days]
                ]);
                $project->archive_days = $archive_days;
            }
            if (in_array($ai_auto_analyze, ['open', 'close']) && $project->ai_auto_analyze != $ai_auto_analyze) {
                $project->addLog("修改AI自动分析", [
                    'change' => [$project->ai_auto_analyze, $ai_auto_analyze]
                ]);
                $project->ai_auto_analyze = $ai_auto_analyze;
            }
            if (in_array($task_template_share, ['open', 'close']) && $project->task_template_share != $task_template_share) {
                $project->addLog("修改共享模板", [
                    'change' => [$project->task_template_share, $task_template_share]
                ]);
                $project->task_template_share = $task_template_share;
            }
            if (in_array($department_owner_view, ['open', 'close']) && $project->department_owner_view != $department_owner_view) {
                $project->addLog("修改负责人视角可见", [
                    'change' => [$project->department_owner_view, $department_owner_view]
                ]);
                $project->department_owner_view = $department_owner_view;
            }
            $project->save();
        });
        $project->pushMsg('update');
        //
        return Base::retSuccess('修改成功', $project);
    }

    /**
     * @api {post} api/project/user 修改项目成员
     *
     * @apiDescription 需要token身份（限：项目负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName user
     *
     * @apiParam {Number}   project_id    项目ID
     * @apiParam {Number[]} userid        成员userid数组（最终完整列表）
     * @apiParam {Number[]} [deputy_userid] 项目管理员userid数组（可选，仅负责人有效；必须是 userid 子集）
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
        $userid = array_values(array_unique(array_map('intval', $userid)));
        //
        $deputy_userid = Request::input('deputy_userid');
        if ($deputy_userid !== null) {
            $deputy_userid = is_array($deputy_userid) ? $deputy_userid : [$deputy_userid];
            $deputy_userid = array_values(array_unique(array_map('intval', $deputy_userid)));
        }
        //
        if (count($userid) > 100) {
            return Base::retError('项目人数最多100个');
        }
        //
        $project = Project::userProject($project_id, true, true);
        //
        // 仅负责人可设置项目管理员；项目管理员/其他角色提交 deputy_userid 一律忽略
        $isPrimary = (int)$project->owner === ProjectUser::OWNER_PRIMARY;
        $applyDeputy = $isPrimary && $deputy_userid !== null;
        //
        // 业务闭环：项目必须且只能有一个主负责人，最终成员列表必须包含该负责人
        $primaryOwnerIds = ProjectUser::whereProjectId($project->id)
            ->whereOwner(ProjectUser::OWNER_PRIMARY)
            ->pluck('userid')
            ->map(fn($v) => (int)$v)
            ->toArray();
        if (count($primaryOwnerIds) !== 1) {
            return Base::retError('项目负责人数据异常，请先修复项目负责人');
        }
        $primaryOwnerId = $primaryOwnerIds[0];
        if (!in_array($primaryOwnerId, $userid, true)) {
            return Base::retError('项目成员列表必须包含项目负责人');
        }
        // 项目管理员可以管理普通成员，但不能借成员列表移除其他项目管理员
        if (!$isPrimary) {
            $currentDeputyIds = ProjectUser::whereProjectId($project->id)
                ->whereOwner(ProjectUser::OWNER_DEPUTY)
                ->pluck('userid')
                ->map(fn($v) => (int)$v)
                ->toArray();
            if (!empty(array_diff($currentDeputyIds, $userid))) {
                return Base::retError('项目管理员不能移除项目负责人或项目管理员');
            }
        }
        //
        if ($applyDeputy) {
            if (!empty(array_diff($deputy_userid, $userid))) {
                return Base::retError('项目管理员必须是项目成员');
            }
            if (in_array((int)$project->owner_userid, $deputy_userid, true)) {
                return Base::retError('负责人不能任命为项目管理员');
            }
        }
        //
        $deleteUser = AbstractModel::transaction(function() use ($project, $userid, $applyDeputy, $deputy_userid) {
            $array = [];
            foreach ($userid as $uid) {
                if ($project->joinProject($uid)) {
                    $array[] = $uid;
                }
            }
            $deleteRows = ProjectUser::whereProjectId($project->id)->whereNotIn('userid', $array)->get();
            $deleteUserids = $deleteRows->pluck('userid');
            foreach ($deleteRows as $row) {
                $row->exitProject();
            }
            //
            // 项目管理员 diff（仅负责人有效）
            if ($applyDeputy) {
                $currentDeputies = ProjectUser::whereProjectId($project->id)
                    ->where('owner', ProjectUser::OWNER_DEPUTY)
                    ->pluck('userid')->toArray();
                $toPromote = array_values(array_diff($deputy_userid, $currentDeputies));
                $toDemote = array_values(array_diff($currentDeputies, $deputy_userid));
                if (!empty($toPromote)) {
                    ProjectUser::whereProjectId($project->id)
                        ->whereIn('userid', $toPromote)
                        ->where('owner', ProjectUser::OWNER_MEMBER)
                        ->change(['owner' => ProjectUser::OWNER_DEPUTY]);
                }
                if (!empty($toDemote)) {
                    ProjectUser::whereProjectId($project->id)
                        ->whereIn('userid', $toDemote)
                        ->where('owner', ProjectUser::OWNER_DEPUTY)
                        ->change(['owner' => ProjectUser::OWNER_MEMBER]);
                }
            }
            //
            $project->syncDialogUser();
            $project->addLog("修改项目成员");
            $project->user_simple = count($array) . "|" . implode(",", array_slice($array, 0, 3));
            $project->save();
            return $deleteUserids->toArray();
        });
        //
        $project->pushMsg('delete', null, $deleteUser);
        $project->pushMsg('detail');
        return Base::retSuccess('修改成功', ['id' => $project->id]);
    }

    /**
     * @api {get} api/project/invite 获取邀请链接
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
        $user = User::auth();
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
                'code' => base64_encode("{$project->id},{$user->userid}," . Base::generatePassword()),
            ]);
            $projectInvite->save();
        } else {
            if ($refresh == 'yes') {
                $projectInvite->code = base64_encode("{$project->id},{$user->userid}," . Base::generatePassword());
                $projectInvite->save();
            }
        }
        return Base::retSuccess('success', [
            'url' => Base::fillUrl('manage/project/invite/' . $projectInvite->code),
            'num' => $projectInvite->num
        ]);
    }

    /**
     * @api {get} api/project/invite/info 通过邀请链接code获取项目信息
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
     * @api {get} api/project/invite/join 通过邀请链接code加入项目
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
     * @api {get} api/project/transfer 移交项目
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
        $project = Project::userProject($project_id, true, 'primary');
        //
        if (!User::whereUserid($owner_userid)->exists()) {
            return Base::retError('成员不存在');
        }
        //
        AbstractModel::transaction(function() use ($owner_userid, $project) {
            // 仅清除原负责人 owner=1（项目管理员 owner=2 保留）
            ProjectUser::whereProjectId($project->id)
                ->whereOwner(ProjectUser::OWNER_PRIMARY)
                ->change(['owner' => 0]);
            // 设新负责人 owner=1（如新负责人原本是项目管理员，从 2 升为 1）
            ProjectUser::updateInsert([
                'project_id' => $project->id,
                'userid' => $owner_userid,
            ], [
                'owner' => ProjectUser::OWNER_PRIMARY,
            ]);
            // 同步项目群 owner_id
            if ($project->dialog_id > 0) {
                $dialog = WebSocketDialog::find($project->dialog_id);
                if ($dialog) {
                    $dialog->owner_id = $owner_userid;
                    $dialog->save();
                }
            }
            // 同步成员 + role（syncDialogUser 已根据 owner 设置 role）
            $project->syncDialogUser();
            $project->addLog("移交项目给", ['userid' => $owner_userid]);
        });
        //
        // pushMsg 带 deputy_userids，前端可直接更新项目管理员列表无需重拉
        $project->pushMsg('detail', [
            'owner_userid' => $project->fresh()->owner_userid,
            'deputy_userids' => $project->fresh()->deputy_userids,
        ]);
        return Base::retSuccess('移交成功', ['id' => $project->id]);
    }

    /**
     * @api {post} api/project/adddeputy 任命项目管理员（仅负责人可操作）
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName adddeputy
     *
     * @apiParam {Number} project_id    项目ID
     * @apiParam {Number} userid        要任命的项目成员 userid
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息
     * @apiSuccess {Object} data    返回数据
     */
    public function adddeputy()
    {
        User::auth();
        $project_id = intval(Request::input('project_id'));
        $userid = intval(Request::input('userid'));

        if ($userid <= 0) {
            return Base::retError('请选择有效的成员');
        }

        $project = Project::userProject($project_id, true, 'primary');

        $member = ProjectUser::where('project_id', $project->id)
            ->where('userid', $userid)->first();
        if (!$member) {
            return Base::retError('该用户不是项目成员');
        }
        if ((int)$member->owner === ProjectUser::OWNER_PRIMARY) {
            return Base::retError('不能将负责人任命为项目管理员');
        }
        if ((int)$member->owner !== ProjectUser::OWNER_DEPUTY) {
            AbstractModel::transaction(function() use ($project, $member) {
                $member->owner = ProjectUser::OWNER_DEPUTY;
                $member->save();
                $project->syncDialogUser(); // 同步群 role
                $project->addLog('任命项目管理员', ['userid' => $member->userid]);
            });
            $project->pushMsg('detail', [
                'deputy_userids' => $project->fresh()->deputy_userids,
            ]);
        }

        return Base::retSuccess('任命成功');
    }

    /**
     * @api {post} api/project/deldeputy 罢免项目管理员（仅负责人可操作）
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName deldeputy
     *
     * @apiParam {Number} project_id    项目ID
     * @apiParam {Number} userid        要罢免的项目管理员 userid
     */
    public function deldeputy()
    {
        User::auth();
        $project_id = intval(Request::input('project_id'));
        $userid = intval(Request::input('userid'));

        if ($userid <= 0) {
            return Base::retError('请选择有效的成员');
        }

        $project = Project::userProject($project_id, true, 'primary');

        $member = ProjectUser::where('project_id', $project->id)
            ->where('userid', $userid)->first();
        if (!$member) {
            return Base::retSuccess('罢免成功'); // 幂等：本来就不是成员
        }
        if ((int)$member->owner === ProjectUser::OWNER_DEPUTY) {
            AbstractModel::transaction(function() use ($project, $member) {
                $member->owner = ProjectUser::OWNER_MEMBER;
                $member->save();
                $project->syncDialogUser();
                $project->addLog('罢免项目管理员', ['userid' => $member->userid]);
            });
            $project->pushMsg('detail', [
                'deputy_userids' => $project->fresh()->deputy_userids,
            ]);
        }

        return Base::retSuccess('罢免成功');
    }

    /**
     * @api {post} api/project/sort 排序任务
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
                    $task = ProjectTask::find($task_id);
                    if ($task && intval($task->column_id) !== intval($item['id'])) {
                        ProjectPermission::userTaskPermission($project, ProjectPermission::TASK_MOVE, $task);
                    }
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
     * @api {post} api/project/user/sort 项目列表排序
     *
     * @apiDescription 需要token身份，按当前用户对项目进行拖动排序，仅影响本人
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName user__sort
     *
     * @apiParam {Array} list   排序后的项目ID列表，如：[12,5,9]
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function user__sort()
    {
        $user = User::auth();
        $list = Base::json2array(Request::input('list'));
        if (!is_array($list)) {
            return Base::retError('参数错误');
        }
        $index = 0;
        foreach ($list as $projectId) {
            $projectId = intval($projectId);
            if ($projectId <= 0) continue;
            ProjectUser::whereUserid($user->userid)
                ->whereProjectId($projectId)
                ->update(['sort' => $index]);
            $index++;
        }
        return Base::retSuccess('排序已保存');
    }

    /**
     * @api {get} api/project/exit 退出项目
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
     * @api {get} api/project/archived 归档项目
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
     * @api {get} api/project/remove 删除项目
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
        $project = Project::userProject($project_id, null, 'primary');
        //
        $project->deleteProject();
        return Base::retSuccess('删除成功', ['id' => $project->id]);
    }

    /**
     * @api {get} api/project/column/lists 获取任务列表
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
        $project = Project::findForDepartmentView($project_id);
        //
        $list = ProjectColumn::whereProjectId($project->id)
            ->orderBy('sort')
            ->orderBy('id')
            ->paginate(Base::getPaginate(200, 100));
        //
        return Base::retSuccess('success', $list);
    }

    /**
     * @api {get} api/project/column/add 添加任务列表
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
     * @api {get} api/project/column/update 修改任务列表
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
     * @api {get} api/project/column/remove 删除任务列表
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
     * @api {get} api/project/column/one 获取任务列详细
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
     * @api {get} api/project/task/lists 任务列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__lists
     *
     * @apiParam {Object} [keys]             搜索条件
     * - keys.name: ID、任务名称、任务描述
     * - keys.tag: 标签名称
     * - keys.status: 任务状态 (completed: 已完成、uncompleted: 未完成、flow-xx: 流程状态ID)
     *
     * @apiParam {Number} [project_id]       项目ID（传入后只查询该项目内任务）
     * @apiParam {Number} [parent_id]        主任务ID（查询优先级最高）
     * - 大于0：只查该主任务下的子任务（此时 archived 强制 all，忽略 project_id/scope）
     * - 等于-1：仅主任务（可与 project_id 组合）
     * @apiParam {String} [scope]            查询范围（仅在未指定 project_id 且 parent_id ≤ 0 时生效）
     * - all_project：查询“我参与的项目”下的所有任务（仍受可见性限制）
     * @apiParam {Number} [owner]            任务身份筛选（按当前登录用户在任务中的身份）
     * - 1：我负责的任务
     * - 0：我协助的任务
     * - 不传：不过滤（默认）
     *
     * @apiParam {String} [time]             指定时间范围，如：today, week, month, year, 2020-12-12,2020-12-30
     * - today: 今天
     * - week: 本周
     * - month: 本月
     * - year: 今年
     * - 自定义时间范围，如 (字符串)：2020-12-12,2020-12-30 或 (数组)：['2020-12-12', '2020-12-30']
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
        $departmentView = UserDepartment::ownerViewContext($user, true);
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
        $scope = Request::input('scope');
        $owner = Request::input('owner');
        $owner = is_numeric($owner) ? intval($owner) : null;
        $keys = is_array($keys) ? $keys : [];
        $sorts = is_array($sorts) ? $sorts : [];
        $with_extend = array_filter(explode(',', Request::input('with_extend', '')));

        $withs = ['taskUser', 'taskTag'];
        if (in_array('project_name', $with_extend)) {
            $withs[] = 'project:id,name';
        }
        if (in_array('column_name', $with_extend)) {
            $withs[] = 'projectColumn:id,name';
        }
        $builder = ProjectTask::with($withs);
        //
        if ($keys['name']) {
            if (Base::isNumber($keys['name'])) {
                $builder->where(function ($query) use ($keys) {
                    $query->where("project_tasks.id", intval($keys['name']))
                        ->orWhere("project_tasks.name", "like", "%{$keys['name']}%")
                        ->orWhere("project_tasks.desc", "like", "%{$keys['name']}%");
                });
            } else {
                $builder->where(function ($query) use ($keys) {
                    $query->where("project_tasks.name", "like", "%{$keys['name']}%");
                    $query->orWhere("project_tasks.desc", "like", "%{$keys['name']}%");
                });
            }
        }
        if ($keys['tag']) {
            $builder->whereHas('taskTag', function ($query) use ($keys) {
                $query->where('project_task_tags.name', $keys['tag']);
            });
        }
        if ($keys['status']) {
            if ($keys['status'] == 'completed') {
                $builder->whereNotNull('project_tasks.complete_at');
            } elseif ($keys['status'] == 'uncompleted') {
                $builder->whereNull('project_tasks.complete_at');
            } elseif (str_starts_with($keys['status'], 'flow-')) {
                $flow = str_replace('flow-', '', $keys['status']);
                if (Base::isNumber($flow)) {
                    $builder->where('project_tasks.flow_item_id', intval($flow));
                } elseif ($flow) {
                    $builder->where('project_tasks.flow_item_name', 'like', "%{$flow}%");
                }
            }
        }
        //
        $scopeAll = false;
        if ($parent_id > 0) {
            $isArchived = str_replace(['all', 'yes', 'no'], [null, false, true], $archived);
            $isDeleted = str_replace(['all', 'yes', 'no'], [null, false, true], $deleted);
            ProjectTask::findForDepartmentView($parent_id, $isArchived, $isDeleted);
            $scopeAll = true;
            $archived = 'all';
            $builder->where('project_tasks.parent_id', $parent_id);
        } elseif ($parent_id === -1) {
            $builder->where('project_tasks.parent_id', 0);
        }
        if ($project_id > 0) {
            if (!UserDepartment::isDepartmentReadonlyProject($departmentView, $project_id)) {
                Project::userProject($project_id);
            }
            $scopeAll = true;
            $builder->where('project_tasks.project_id', $project_id);
        }
        if (!$scopeAll && $scope === 'all_project') {
            $scopeAll = true;
            if ($departmentView['enabled']) {
                $builder->whereIn('project_tasks.project_id', array_values(array_unique(array_merge($departmentView['own_project_ids'], $departmentView['project_ids']))));
            } else {
                $builder->whereIn('project_tasks.project_id', function ($query) use ($userid) {
                    $query->select('project_id')
                        ->from('project_users')
                        ->where('userid', $userid);
                });
            }
        }
        if ($scopeAll) {
            $builder->allData();
            if ($owner !== null) {
                $builder->where('project_task_users.owner', $owner);
            }
        } else {
            $builder->authData(null, $owner);
        }
        //
        if ($name) {
            $builder->where(function($query) use ($name) {
                $query->where("project_tasks.name", "like", "%{$name}%");
            });
        }
        //
        if (is_string($time) && $time) {
            switch ($time) {
                case 'today':
                    $builder->betweenTime(Carbon::now()->startOfDay(), Carbon::now()->endOfDay());
                    break;
                case 'week':
                    $builder->betweenTime(Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek());
                    break;
                case 'month':
                    $builder->betweenTime(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());
                    break;
                case 'year':
                    $builder->betweenTime(Carbon::now()->startOfYear(), Carbon::now()->endOfYear());
                    break;
                default:
                    if (str_contains($time, ',')) {
                        $times = explode(',', $time);
                        if (Timer::isDateOrTime($times[0]) && Timer::isDateOrTime($times[1])) {
                            $builder->betweenTime(Carbon::parse($times[0])->startOfDay(), Carbon::parse($times[1])->endOfDay());
                        }
                    }
            }
        } elseif (is_array($time)) {
            if (Timer::isDateOrTime($time[0]) && Timer::isDateOrTime($time[1])) {
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
            $query->whereIn('project_users.owner', [ProjectUser::OWNER_PRIMARY, ProjectUser::OWNER_DEPUTY]);
            $query->where('project_users.userid', $userid);
        });
        $builder->leftJoin('project_task_visibility_users', function ($query) use($userid) {
            $query->on('project_task_visibility_users.task_id', '=', 'project_tasks.id');
            $query->where('project_task_visibility_users.userid', $userid);
        });
        $builder->leftJoin('project_task_visibility_users as project_sub_task_visibility_users', function ($query) use($userid) {
            $query->on('project_sub_task_visibility_users.task_id', '=', 'project_tasks.parent_id');
            $query->where('project_sub_task_visibility_users.userid', $userid);
        });
        $builder->where(function ($query) use ($userid) {
            $query->where("project_tasks.visibility", 1);
            $query->orWhere("project_users.userid", $userid);
            $query->orWhere("project_task_users.userid", $userid);
            $query->orWhere("project_task_visibility_users.userid", $userid);
            $query->orWhere("project_sub_task_visibility_users.userid", $userid);
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
                ->whereNull('deleted_at')
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
            $item['department_readonly'] = UserDepartment::isDepartmentReadonlyProject($departmentView, intval($item['project_id']));
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
            if (in_array('project_name', $with_extend)) {
                $item['project_name'] = $item['project']['name'] ?? '';
                unset($item['project']);
            }
            if (in_array('column_name', $with_extend)) {
                $item['column_name'] = $item['project_column']['name'] ?? '';
                unset($item['project_column']);
            }
        }
        //
        if ($list->currentPage() === 1) {
            $data['deleted_id'] = Deleted::ids('projectTask', $user->userid, $timerange->deleted);
        }
        //
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/project/user/projects 会员参与的项目列表
     *
     * @apiDescription 需要token身份。用于会员卡片查看「该会员参与的项目」。
     * 权限：本人 / 系统管理员 / 对该会员具有部门负责人只读视角。
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName user__projects
     *
     * @apiParam {Number} userid            目标会员ID
     * @apiParam {String} [archived]        是否归档（all/yes/no），默认no
     * @apiParam {Object} [keys]            搜索条件（keys.name 项目名称）
     * @apiParam {Number} [page]            当前页，默认1
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function user__projects()
    {
        $viewer = User::auth();
        $targetId = intval(Request::input('userid'));
        $context = UserDepartment::userWorksContext($viewer, $targetId);
        if (!$context['allowed']) {
            return Base::retError('没有查看权限');
        }
        $readonly = !$context['is_self'] && !$context['is_admin'];
        //
        $archived = Request::input('archived', 'no');
        $keys = Request::input('keys');
        //
        $builder = Project::select(['projects.*', 'project_users.owner', 'project_users.top_at', 'project_users.sort'])
            ->join('project_users', function ($join) use ($targetId) {
                $join->on('projects.id', '=', 'project_users.project_id')
                    ->where('project_users.userid', '=', $targetId);
            });
        // 部门负责人视角：限定在允许可见的项目集合内
        if ($readonly) {
            $builder->whereIn('projects.id', $context['project_ids'] ?: [0]);
        }
        //
        if ($archived == 'yes') {
            $builder->whereNotNull('projects.archived_at');
        } elseif ($archived == 'no') {
            $builder->whereNull('projects.archived_at');
        }
        if (is_array($keys) && !empty($keys['name'])) {
            $builder->where('projects.name', 'like', "%{$keys['name']}%");
        }
        //
        $list = $builder
            ->orderByDesc('project_users.top_at')
            ->orderBy('project_users.sort')
            ->orderByDesc('projects.id')
            ->paginate(Base::getPaginate(100, 50));
        $list->transform(function (Project $project) use ($targetId, $readonly) {
            $array = $project->toArray();
            $array['department_readonly'] = $readonly;
            $array = array_merge($array, $project->getTaskStatistics($targetId));
            return $array;
        });
        //
        return Base::retSuccess('success', $list);
    }

    /**
     * @api {get} api/project/user/tasks 会员参与的任务列表
     *
     * @apiDescription 需要token身份。用于会员卡片查看「该会员参与的任务」（负责的 / 协作的）。
     * 权限：本人 / 系统管理员 / 对该会员具有部门负责人只读视角。
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName user__tasks
     *
     * @apiParam {Number} userid            目标会员ID
     * @apiParam {Number} [owner]           任务身份筛选：1=负责的，0=协作的，不传=全部
     * @apiParam {Number} [project_id]      仅查询指定项目
     * @apiParam {Object} [keys]            搜索条件（keys.name 任务名称，keys.status completed/uncompleted）
     * @apiParam {Number} [page]            当前页，默认1
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function user__tasks()
    {
        $viewer = User::auth();
        $targetId = intval(Request::input('userid'));
        $context = UserDepartment::userWorksContext($viewer, $targetId);
        if (!$context['allowed']) {
            return Base::retError('没有查看权限');
        }
        $readonly = !$context['is_self'] && !$context['is_admin'];
        //
        $owner = Request::input('owner');
        $owner = is_numeric($owner) ? intval($owner) : null;
        $project_id = intval(Request::input('project_id'));
        $keys = Request::input('keys');
        $keys = is_array($keys) ? $keys : [];
        //
        $builder = ProjectTask::with(['taskUser', 'taskTag', 'project:id,name'])
            ->select(['project_tasks.*', 'project_task_users.owner'])
            ->join('project_task_users', function ($join) use ($targetId) {
                $join->on('project_tasks.id', '=', 'project_task_users.task_id')
                    ->where('project_task_users.userid', '=', $targetId);
            });
        if ($owner !== null) {
            $builder->where('project_task_users.owner', $owner);
        }
        // 部门负责人视角：限定可见项目集合，且仅"全员可见"(visibility=1)的任务（与 findForDepartmentView 一致，避免列出打不开的任务）
        if ($readonly) {
            $builder->whereIn('project_tasks.project_id', $context['project_ids'] ?: [0]);
            $builder->where('project_tasks.visibility', 1);
        }
        if ($project_id > 0) {
            $builder->where('project_tasks.project_id', $project_id);
        }
        if (!empty($keys['name'])) {
            $builder->where(function ($query) use ($keys) {
                $query->where('project_tasks.name', 'like', "%{$keys['name']}%")
                    ->orWhere('project_tasks.desc', 'like', "%{$keys['name']}%");
            });
        }
        if (!empty($keys['status'])) {
            if ($keys['status'] == 'completed') {
                $builder->whereNotNull('project_tasks.complete_at');
            } elseif ($keys['status'] == 'uncompleted') {
                $builder->whereNull('project_tasks.complete_at');
            }
        }
        $builder->whereNull('project_tasks.archived_at');
        //
        $list = $builder->orderByDesc('project_tasks.id')->paginate(Base::getPaginate(100, 50));
        $list->transform(function (ProjectTask $task) use ($readonly) {
            $task->setAppends(['today', 'overdue']);
            $array = $task->toArray();
            $array['project_name'] = $array['project']['name'] ?? '';
            $array['department_readonly'] = $readonly;
            unset($array['project']);
            return $array;
        });
        //
        return Base::retSuccess('success', $list);
    }

    /**
     * @api {get} api/project/user/counts 会员参与的项目/任务数量
     *
     * @apiDescription 需要token身份。用于会员卡片「项目与任务」弹窗的 Tab 角标，仅返回数量（轻量）。
     * 权限：本人 / 系统管理员 / 对该会员具有部门负责人只读视角。
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName user__counts
     *
     * @apiParam {Number} userid            目标会员ID
     * @apiParam {Number} [owner]           任务身份筛选：1=负责的，0=协作的，不传=全部（仅影响任务数量）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    {project, todo, done}
     */
    public function user__counts()
    {
        $viewer = User::auth();
        $targetId = intval(Request::input('userid'));
        $context = UserDepartment::userWorksContext($viewer, $targetId);
        if (!$context['allowed']) {
            return Base::retError('没有查看权限');
        }
        $readonly = !$context['is_self'] && !$context['is_admin'];
        $owner = Request::input('owner');
        $owner = is_numeric($owner) ? intval($owner) : null;
        //
        $projectBuilder = Project::join('project_users', function ($join) use ($targetId) {
                $join->on('projects.id', '=', 'project_users.project_id')
                    ->where('project_users.userid', '=', $targetId);
            })
            ->whereNull('projects.archived_at');
        if ($readonly) {
            $projectBuilder->whereIn('projects.id', $context['project_ids'] ?: [0]);
        }
        $projectCount = $projectBuilder->distinct()->count('projects.id');
        //
        $taskBuilder = function () use ($targetId, $owner, $readonly, $context) {
            $builder = ProjectTask::join('project_task_users', function ($join) use ($targetId) {
                    $join->on('project_tasks.id', '=', 'project_task_users.task_id')
                        ->where('project_task_users.userid', '=', $targetId);
                })
                ->whereNull('project_tasks.archived_at');
            if ($owner !== null) {
                $builder->where('project_task_users.owner', $owner);
            }
            if ($readonly) {
                $builder->whereIn('project_tasks.project_id', $context['project_ids'] ?: [0]);
                $builder->where('project_tasks.visibility', 1);
            }
            return $builder;
        };
        $todoCount = $taskBuilder()->whereNull('project_tasks.complete_at')->count();
        $doneCount = $taskBuilder()->whereNotNull('project_tasks.complete_at')->count();
        //
        return Base::retSuccess('success', [
            'project' => $projectCount,
            'todo' => $todoCount,
            'done' => $doneCount,
        ]);
    }

    /**
     * @api {get} api/project/task/easylists 任务列表-简单的
     *
     * @apiDescription 需要token身份，主要用于判断是否有时间冲突的任务
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
        $timerange = TimeRange::parse(Request::input('timerange'));
        //
        $list = ProjectTask::with(['taskUser'])
            ->select([
                'projects.name as project_name',
                'project_tasks.project_id',
                'project_tasks.id',
                'project_tasks.name',
                'project_tasks.start_at',
                'project_tasks.end_at'
            ])
            ->join('projects','project_tasks.project_id','=','projects.id')
            ->leftJoin('project_task_users', function ($query) {
                $query->on('project_tasks.id', '=', 'project_task_users.task_id')->where('project_task_users.owner', '=', 1);
            })
            ->whereIn('project_task_users.userid', is_array($userid) ? $userid : explode(',', $userid) )
            ->when($timerange->isExist(), function ($query) use ($timerange) {
                $query->where('project_tasks.start_at', '<=', $timerange->lastTime()->endOfDay());
                $query->where('project_tasks.end_at', '>=', $timerange->firstTime()->startOfDay());
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
     * @api {get} api/project/task/export 导出任务（限管理员）
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
        if (!(is_array($time) && Timer::isDateOrTime($time[0]) && Timer::isDateOrTime($time[1]))) {
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
        $doo = Doo::load();
        go(function () use ($doo, $user, $userid, $time, $type, $botUser, $dialog) {
            Coroutine::sleep(1);
            $headings = [];
            $headings[] = $doo->translate('任务ID');
            $headings[] = $doo->translate('父级任务ID');
            $headings[] = $doo->translate('所属项目');
            $headings[] = $doo->translate('任务标题');
            $headings[] = $doo->translate('任务标签');
            $headings[] = $doo->translate('任务开始时间');
            $headings[] = $doo->translate('任务结束时间');
            $headings[] = $doo->translate('完成时间');
            $headings[] = $doo->translate('归档时间');
            $headings[] = $doo->translate('任务计划用时');
            $headings[] = $doo->translate('实际完成用时');
            $headings[] = $doo->translate('超时时间');
            $headings[] = $doo->translate('开发用时');
            $headings[] = $doo->translate('验收/测试用时');
            $headings[] = $doo->translate('负责人');
            $headings[] = $doo->translate('创建人');
            $headings[] = $doo->translate('状态');
            $datas = [];
            //
            $content = [];
            $content[] = [
                'content' => '导出任务统计已完成',
                'style' => 'font-weight: bold;padding-bottom: 4px;',
            ];
            //
            $startTime = Carbon::parse($time[0])->startOfDay();
            $endTime = Carbon::parse($time[1])->endOfDay();
            $builder = ProjectTask::with(['taskTag'])->select(['project_tasks.*', 'project_task_users.userid as ownerid'])
                ->join('project_task_users', 'project_tasks.id', '=', 'project_task_users.task_id')
                ->where('project_task_users.owner', 1)
                ->whereIn('project_task_users.userid', $userid);
            // 按导出时间类型筛选：
            // - createdTime：仅按创建时间范围筛选；
            // - 任务时间（默认）：优先使用任务计划时间筛选，但对“无计划时间”的任务，
            //   若在考核期内已完成，则按完成时间 complete_at 兜底纳入导出，避免漏掉考核期内完成的任务。
            if ($type === 'createdTime') {
                $builder->betweenTime($startTime, $endTime, $type);
            } else {
                $builder->where(function ($query) use ($startTime, $endTime) {
                    $query->betweenTime($startTime, $endTime, 'taskTime')
                        ->orWhere(function ($q2) use ($startTime, $endTime) {
                            $q2->where(function ($q3) {
                                $q3->whereNull('project_tasks.start_at')
                                    ->orWhereNull('project_tasks.end_at');
                            })->whereNotNull('project_tasks.complete_at')
                                ->whereBetween('project_tasks.complete_at', [$startTime, $endTime]);
                        });
                });
            }
            $builder->orderByDesc('project_tasks.id')->chunk(100, function ($tasks) use ($doo, &$datas) {
                /** @var ProjectTask $task */
                foreach ($tasks as $task) {
                    $flowChanges = ProjectTaskFlowChange::whereTaskId($task->id)->get();
                    $testTime = 0; // 测试时间
                    $taskStartTime = $task->start_at ? Carbon::parse($task->start_at)->timestamp : Carbon::parse($task->created_at)->timestamp;
                    $taskCompleteTime = $task->complete_at ? Carbon::parse($task->complete_at)->timestamp : time();
                    $totalTime = $taskCompleteTime - $taskStartTime; // 任务总用时
                    foreach ($flowChanges as $change) {
                        if (str_starts_with($change->before_flow_item_name, 'end')) {
                            continue;
                        }
                        $upOne = ProjectTaskFlowChange::where('id', '<', $change->id)->whereTaskId($task->id)->orderByDesc('id')->first();
                        if ($upOne) {
                            if (str_starts_with($change->before_flow_item_name, 'test')) {
                                $testCtime = Carbon::parse($change->created_at)->timestamp;
                                $tTime = Carbon::parse($upOne->created_at)->timestamp;
                                $tMinusNum = $testCtime - $tTime;
                                $testTime += $tMinusNum;
                            }
                        }
                    }
                    if (!$task->complete_at) {
                        $lastChange = ProjectTaskFlowChange::whereTaskId($task->id)->orderByDesc('id')->first();
                        $nowTime = time();
                        $unFinishTime = $nowTime - Carbon::parse($lastChange->created_at)->timestamp;
                        if (str_starts_with($lastChange->after_flow_item_name, 'test')) {
                            $testTime += $unFinishTime;
                        }
                    }
                    $developTime = $totalTime - $testTime; // 开发时间
                    $planTime = '-'; // 任务计划用时
                    $overTime = '-'; // 超时时间
                    if ($task->end_at) {
                        $startTime = Carbon::parse($task->start_at)->timestamp;
                        $endTime = Carbon::parse($task->end_at)->timestamp;
                        $planTotalTime = $endTime - $startTime;
                        $residueTime = $planTotalTime - $totalTime;
                        if ($residueTime < 0) {
                            $overTime = $doo->translate(Timer::timeFormat(abs($residueTime)));
                        }
                        $planTime = $doo->translate(Timer::timeDiff($startTime, $endTime));
                    }
                    $actualTime = $task->complete_at ? $totalTime : 0; // 实际完成用时
                    $statusText = '未完成';
                    // 状态判定规则：
                    // - flow_item_name 以 end| 开头：视为结束态，区分“已取消”和“已完成”
                    // - 非 end|，但 complete_at 有值：视为已完成（兼容无流程或历史数据）
                    if (str_starts_with($task->flow_item_name, 'end')) {
                        $statusText = '已完成';
                        if (ProjectTask::isCanceledFlowName($task->flow_item_name)) {
                            $statusText = '已取消';
                            $actualTime = 0;
                            $testTime = 0;
                            $developTime = 0;
                            $overTime = '-';
                        }
                    } elseif ($task->complete_at) {
                        $statusText = '已完成';
                    }
                    if (!isset($datas[$task->ownerid])) {
                        $datas[$task->ownerid] = [
                            'index' => 1,
                            'nickname' => Base::filterEmoji(User::userid2nickname($task->ownerid)),
                            'styles' => ["A1:Q1" => ["font" => ["bold" => true]]],
                            'data' => [],
                        ];
                    }
                    $datas[$task->ownerid]['index']++;
                    if ($statusText === '未完成') {
                        $datas[$task->ownerid]['styles']["Q{$datas[$task->ownerid]['index']}"] = ["font" => ["color" => ["rgb" => "ff0000"]]];  // 未完成
                    } elseif ($statusText === '已完成' && $task->end_at && Carbon::parse($task->complete_at)->gt($task->end_at)) {
                        $datas[$task->ownerid]['styles']["Q{$datas[$task->ownerid]['index']}"] = ["font" => ["color" => ["rgb" => "436FF6"]]];  // 已完成超期
                    }
                    $datas[$task->ownerid]['data'][] = [
                        $task->id,
                        $task->parent_id ?: '-',
                        Base::filterEmoji($task->project?->name) ?: '-',
                        Base::filterEmoji($task->name),
                        $task->taskTag->map(function ($tag) {
                            return Base::filterEmoji($tag->name);
                        })->join(', ') ?: '-',
                        $task->start_at ?: '-',
                        $task->end_at ?: '-',
                        $task->complete_at ?: '-',
                        $task->archived_at ?: '-',
                        $planTime,
                        $actualTime ? $doo->translate(Timer::timeFormat($actualTime)) : '-',
                        $overTime,
                        $developTime > 0 ? $doo->translate(Timer::timeFormat($developTime)) : '-',
                        $testTime > 0 ? $doo->translate(Timer::timeFormat($testTime)) : '-',
                        Base::filterEmoji(User::userid2nickname($task->ownerid)) . " (ID: {$task->ownerid})",
                        Base::filterEmoji(User::userid2nickname($task->userid)) . " (ID: {$task->userid})",
                        $doo->translate($statusText),
                    ];
                }
            });
            if (empty($datas)) {
                $content[] = [
                    'content' => '没有任何数据',
                    'style' => 'color: #ff0000;',
                ];
                WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
                    'type' => 'content',
                    'title' => $content[0]['content'],
                    'content' => $content,
                ], $botUser->userid, true, false, true);
                return;
            }
            //
            $sheets = [];
            foreach ($userid as $ownerid) {
                $data = $datas[$ownerid] ?? [
                    'nickname' => Base::filterEmoji(User::userid2nickname($ownerid)),
                    'styles' => ["A1:Q1" => ["font" => ["bold" => true]]],
                    'data' => [],
                ];
                $title = (count($sheets) + 1) . "." . ($data['nickname'] ?: $ownerid);
                $sheets[] = BillExport::create()->setTitle($title)->setHeadings($headings)->setData($data['data'])->setStyles($data['styles']);
            }
            //
            $fileName = User::userid2nickname($userid[0]) ?: $userid[0];
            if (count($userid) > 1) {
                $fileName .= '等' . count($userid) . '位成员的任务统计';
            } else {
                $fileName .= '的任务统计';
            }
            $fileName = $doo->translate($fileName) . '_' . Timer::time() . '.xls';
            $filePath = "temp/task/export/" . date("Ym", Timer::time());
            $export = new BillMultipleExport($sheets);
            $res = $export->store($filePath . "/" . $fileName);
            if ($res != 1) {
                $content[] = [
                    'content' => "导出失败，{$fileName}！",
                    'style' => 'color: #ff0000;',
                ];
                WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
                    'type' => 'content',
                    'title' => $content[0]['content'],
                    'content' => $content,
                ], $botUser->userid, true, false, true);
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
                Base::zipAddFiles($zipPath, $xlsPath);
            } catch (\Throwable) {
            }
            //
            if (file_exists($zipPath)) {
                $key = Down::cache_encode([
                    'file' => $zipFile,
                ]);
                $fileUrl = Base::fillUrl('api/project/task/down?key=' . $key);
                WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
                    'type' => 'file_download',
                    'title' => '导出任务统计已完成',
                    'name' => $fileName,
                    'size' => filesize($zipPath),
                    'url' => $fileUrl,
                ], $botUser->userid, true, false, true);
            } else {
                $content[] = [
                    'content' => "打包失败，请稍后再试...",
                    'style' => 'color: #ff0000;',
                ];
                WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
                    'type' => 'content',
                    'title' => $content[0]['content'],
                    'content' => $content,
                ], $botUser->userid, true, false, true);
            }
        });
        //
        WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
            'type' => 'content',
            'content' => '正在导出任务统计，请稍等...',
        ], $botUser->userid, true, false, true);
        //
        return Base::retSuccess('success');
    }

    /**
     * @api {get} api/project/task/exportoverdue 导出超期任务（限管理员）
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
        $botUser = User::botGetOrCreate('system-msg');
        if (empty($botUser)) {
            return Base::retError('系统机器人不存在');
        }
        $dialog = WebSocketDialog::checkUserDialog($botUser, $user->userid);
        //
        $doo = Doo::load();
        go(function () use ($doo, $botUser, $dialog, $user) {
            Coroutine::sleep(1);
            //
            $headings = [];
            $headings[] = $doo->translate('任务ID');
            $headings[] = $doo->translate('父级任务ID');
            $headings[] = $doo->translate('所属项目');
            $headings[] = $doo->translate('任务标题');
            $headings[] = $doo->translate('任务标签');
            $headings[] = $doo->translate('任务开始时间');
            $headings[] = $doo->translate('任务结束时间');
            $headings[] = $doo->translate('任务计划用时');
            $headings[] = $doo->translate('超时时间');
            $headings[] = $doo->translate('负责人');
            $headings[] = $doo->translate('创建人');
            $data = [];
            //
            $content = [];
            $content[] = [
                'content' => '导出超期任务已完成',
                'style' => 'font-weight: bold;padding-bottom: 4px;',
            ];
            //
            ProjectTask::with(['taskTag'])
                ->whereNull('complete_at')
                ->whereNotNull('end_at')
                ->where('end_at', '<=', Carbon::now())
                ->orderBy('end_at')
                ->chunk(100, function ($tasks) use ($doo, &$data) {
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
                                $overTime = $doo->translate(Timer::timeFormat(abs($residueTime)));
                            }
                            $planTime = $doo->translate(Timer::timeDiff($startTime, $endTime));
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
                            $task->taskTag->map(function ($tag) {
                                return Base::filterEmoji($tag->name);
                            })->join(', ') ?: '-',
                            $task->start_at ?: '-',
                            $task->end_at ?: '-',
                            $planTime,
                            $overTime,
                            implode(', ', $ownerNames),
                            Base::filterEmoji(User::userid2nickname($task->userid)) . " (ID: {$task->userid})",
                        ];
                    }
                });
            if (empty($data)) {
                $content[] = [
                    'content' => '没有任何数据',
                    'style' => 'color: #ff0000;',
                ];
                WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
                    'type' => 'content',
                    'title' => $content[0]['content'],
                    'content' => $content,
                ], $botUser->userid, true, false, true);
                return;
            }
            //
            $title = $doo->translate('超期任务');
            $sheets = [
                BillExport::create()->setTitle($title)->setHeadings($headings)->setData($data)->setStyles(["A1:J1" => ["font" => ["bold" => true]]])
            ];
            //
            $fileName = $title . '_' . Timer::time() . '.xls';
            $filePath = "temp/task/export/" . date("Ym", Timer::time());
            $export = new BillMultipleExport($sheets);
            $res = $export->store($filePath . "/" . $fileName);
            if ($res != 1) {
                $content[] = [
                    'content' => "导出失败，{$fileName}！",
                    'style' => 'color: #ff0000;',
                ];
                WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
                    'type' => 'content',
                    'title' => $content[0]['content'],
                    'content' => $content,
                ], $botUser->userid, true, false, true);
                return;
            }
            $xlsPath = storage_path("app/" . $filePath . "/" . $fileName);
            $zipFile = "app/" . $filePath . "/" . Base::rightDelete($fileName, '.xls') . ".zip";
            $zipPath = storage_path($zipFile);
            if (file_exists($zipPath)) {
                Base::deleteDirAndFile($zipPath, true);
            }
            try {
                Base::zipAddFiles($zipPath, $xlsPath);
            } catch (\Throwable) {
            }
            //
            if (file_exists($zipPath)) {
                $key = Down::cache_encode([
                    'file' => $zipFile,
                ]);
                $fileUrl = Base::fillUrl('api/project/task/down?key=' . $key);
                WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
                    'type' => 'file_download',
                    'title' => '导出超期任务已完成',
                    'name' => $fileName,
                    'size' => filesize($zipPath),
                    'url' => $fileUrl,
                ], $botUser->userid, true, false, true);
            } else {
                $content[] = [
                    'content' => "打包失败，请稍后再试...",
                    'style' => 'color: #ff0000;',
                ];
                WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
                    'type' => 'content',
                    'title' => $content[0]['content'],
                    'content' => $content,
                ], $botUser->userid, true, false, true);
            }
        });
        //
        WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
            'type' => 'content',
            'content' => '正在导出超期任务，请稍等...',
        ], $botUser->userid, true, false, true);
        //
        return Base::retSuccess('success');
    }

    /**
     * @api {get} api/project/task/down 下载导出的任务
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
        $array = Down::cache_decode();
        $file = $array['file'];
        if (empty($file) || !file_exists(storage_path($file))) {
            return Base::ajaxError("文件不存在！", [], 0, 403);
        }
        return Response::download(storage_path($file));
    }

    /**
     * @api {get} api/project/task/one 获取单个任务信息
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
        $departmentView = UserDepartment::ownerViewContext($user, true);
        //
        $task_id = intval(Request::input('task_id'));
        $archived = Request::input('archived', 'no');
        //
        $isArchived = str_replace(['all', 'yes', 'no'], [null, false, true], $archived);
        $task = ProjectTask::findForDepartmentView($task_id, $isArchived, true, ['taskUser', 'taskTag']);
        // 项目可见性
        $projectOwnerids = ProjectUser::whereProjectId($task->project_id)
            ->whereIn('owner', [ProjectUser::OWNER_PRIMARY, ProjectUser::OWNER_DEPUTY])
            ->pluck('userid')->map(fn($v) => (int)$v)->toArray();     // 项目负责人（含项目管理员）
        if ($task->visibility != 1 && !in_array($user->userid, $projectOwnerids)) {
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
        $data['department_readonly'] = UserDepartment::isDepartmentReadonlyProject($departmentView, intval($task->project_id));
        $data['project_name'] = $task->project?->name;
        $data['column_name'] = $task->projectColumn?->name;
        $data['visibility_appointor'] = $task->visibility == 1 ? [0] : ProjectTaskVisibilityUser::whereTaskId($task_id)->pluck('userid');
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/project/task/subdata 获取子任务数据
     *
     * @apiDescription 需要token身份，相对one接口，这个只获取主任务的子任务数据
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__subdata
     *
     * @apiParam {Number} task_id            任务ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__subdata()
    {
        User::auth();
        $task_id = intval(Request::input('task_id'));
        if ($task_id <= 0) {
            return Base::retError('参数错误', ['task_id' => $task_id]);
        }
        //
        $task = ProjectTask::findForDepartmentView($task_id);
        //
        return Base::retSuccess('success', [
            'id' => $task->id,
            'sub_num' => $task->sub_num,
            'sub_complete' => $task->sub_complete,
            'percent' => $task->percent,
        ]);
    }

    /**
     * @api {get} api/project/task/related 获取任务关联任务列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__related
     *
     * @apiParam {Number} task_id               任务ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__related()
    {
        User::auth();
        $task_id = intval(Request::input('task_id'));
        if ($task_id <= 0) {
            return Base::retError('参数错误', ['task_id' => $task_id]);
        }

        $task = ProjectTask::findForDepartmentView($task_id, null);

        $relations = ProjectTaskRelation::whereTaskId($task->id)
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();

        if ($relations->isEmpty()) {
            return Base::retSuccess('success', [
                'task_id' => $task->id,
                'list' => [],
            ]);
        }

        $relatedTaskIds = $relations->pluck('related_task_id')->unique()->values();
        $relatedTasks = [];
        foreach ($relatedTaskIds as $relatedId) {
            try {
                $relatedTask = ProjectTask::findForDepartmentView($relatedId, null, true, ['project', 'projectColumn']);

                $flowItemParts = explode('|', $relatedTask->flow_item_name ?: '');
                $flowItemStatus = $flowItemParts[0] ?? '';
                $flowItemName = $flowItemParts[1] ?? $relatedTask->flow_item_name;
                $flowItemColor = $flowItemParts[2] ?? '';

                $relatedTask->flow_item_status = $flowItemStatus;
                $relatedTask->flow_item_name = $flowItemName;
                $relatedTask->flow_item_color = $flowItemColor;

                $relatedTasks[$relatedTask->id] = $relatedTask;
            } catch (\Throwable $e) {
                continue;
            }
        }

        $list = [];
        foreach ($relations as $relation) {
            $relatedTask = $relatedTasks[$relation->related_task_id] ?? null;
            if (!$relatedTask) {
                continue;
            }

            if (!isset($list[$relation->related_task_id])) {
                $list[$relation->related_task_id] = [
                    'task_id' => $relation->task_id,
                    'related_task_id' => $relation->related_task_id,
                    'mention' => false,
                    'mentioned_by' => false,
                    'latest_msg_id' => $relation->msg_id,
                    'latest_at' => $relation->updated_at?->toDateTimeString(),
                    'task' => [
                        'id' => $relatedTask->id,
                        'name' => $relatedTask->name,
                        'project_id' => $relatedTask->project_id,
                        'project_name' => $relatedTask->project?->name,
                        'column_id' => $relatedTask->column_id,
                        'column_name' => $relatedTask->projectColumn?->name,
                        'complete_at' => $relatedTask->complete_at?->toDateTimeString(),
                        'archived_at' => $relatedTask->archived_at?->toDateTimeString(),
                        'flow_item_name' => $relatedTask->flow_item_name,
                        'flow_item_status' => $relatedTask->flow_item_status,
                        'flow_item_color' => $relatedTask->flow_item_color,
                    ],
                ];
            }

            if ($relation->direction === ProjectTaskRelation::DIRECTION_MENTION) {
                $list[$relation->related_task_id]['mention'] = true;
            } elseif ($relation->direction === ProjectTaskRelation::DIRECTION_MENTIONED_BY) {
                $list[$relation->related_task_id]['mentioned_by'] = true;
            }

            if ($relation->updated_at && ($list[$relation->related_task_id]['latest_at'] === null || Carbon::parse($list[$relation->related_task_id]['latest_at'])->lt($relation->updated_at))) {
                $list[$relation->related_task_id]['latest_at'] = $relation->updated_at->toDateTimeString();
                $list[$relation->related_task_id]['latest_msg_id'] = $relation->msg_id;
            }
        }

        return Base::retSuccess('success', [
            'task_id' => $task->id,
            'list' => array_values($list),
        ]);
    }

    /**
     * @api {post} api/project/task/related/delete 删除任务关联
     *
     * @apiDescription 需要token身份（限：项目、任务负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__related__delete
     *
     * @apiParam {Number} task_id               任务ID
     * @apiParam {Number} related_task_id        关联任务ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__related__delete()
    {
        User::auth();
        //
        $task_id = intval(Request::input('task_id'));
        $related_task_id = intval(Request::input('related_task_id'));
        if ($task_id <= 0 || $related_task_id <= 0) {
            return Base::retError('参数错误');
        }
        //
        $task = ProjectTask::userTask($task_id);
        //
        $project = Project::userProject($task->project_id);
        ProjectPermission::userTaskPermission($project, ProjectPermission::TASK_UPDATE, $task);
        //
        $success = ProjectTaskRelation::deleteRelation($task_id, $related_task_id);
        if (!$success) {
            return Base::retError('关联不存在');
        }
        //
        return Base::retSuccess('操作成功');
    }

    /**
     * @api {get} api/project/task/content 获取任务详细描述
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
        $task = ProjectTask::findForDepartmentView($task_id, null);
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
     * @api {get} api/project/task/content_history 获取任务详细历史描述
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
        $task = ProjectTask::findForDepartmentView($task_id, null);
        //
        $data = ProjectTaskContent::select(['id', 'task_id', 'desc', 'userid', 'created_at'])
            ->whereTaskId($task->id)
            ->orderByDesc('id')
            ->paginate(Base::getPaginate(100, 20));
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/project/task/files 获取任务文件列表
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
        $task = ProjectTask::findForDepartmentView($task_id, null);
        //
        return Base::retSuccess('success', $task->taskFile);
    }

    /**
     * @api {get} api/project/task/filedelete 删除任务文件
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
        ProjectPermission::userTaskPermission(Project::userProject($task->project_id), ProjectPermission::TASK_REMOVE, $task);
        //
        $task->addLog('删除附件：' . $file->name, [
            'file_id' => $file->id,
            'name' => $file->name,
            'size' => $file->size,
            'path' => $file->getRawOriginal('path'),
            'thumb' => $file->getRawOriginal('thumb'),
        ]);
        $task->pushMsg('filedelete', [
            'id' => $file->id,
        ]);
        $file->delete();
        //
        return Base::retSuccess('success', $file);
    }

    /**
     * @api {get} api/project/task/filedetail 获取任务文件详情
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
        $user = User::auth();
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
        File::isNeedInstallApp($file->ext);
        //
        $data = $file->toArray();
        $data['path'] = $file->getRawOriginal('path');
        //
        ProjectTask::findForDepartmentView($file->task_id, null);
        //
        UserRecentItem::record(
            $user->userid,
            UserRecentItem::TYPE_TASK_FILE,
            $file->id,
            UserRecentItem::SOURCE_PROJECT_TASK,
            $file->task_id
        );

        return Base::retSuccess('success', File::formatFileData($data));
    }

    /**
     * @api {get} api/project/task/filedown 下载任务文件
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
        abort_if(empty($file), 403, "This file not exist.");
        //
        try {
            ProjectTask::findForDepartmentView($file->task_id, null);
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
        return Base::DownloadFileResponse($filePath, $file->name);
    }

    /**
     * @api {post} api/project/task/add 添加任务
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
        $data = ProjectTask::normalizeTimes($data);
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
            $projectOwner = ProjectUser::whereProjectId($task->project_id)
                ->whereIn('owner', [ProjectUser::OWNER_PRIMARY, ProjectUser::OWNER_DEPUTY])
                ->pluck('userid')->toArray();  // 项目负责人（含项目管理员）
            $taskOwnerAndAssists = ProjectTaskUser::select(['userid', 'owner'])->whereTaskId($data['id'])->pluck('userid')->toArray();
            $visibleIds = array_merge($projectOwner, $taskOwnerAndAssists);
            $data['is_visible'] = in_array($user->userid, $visibleIds) ? 1 : 0;
        }

        $task->pushMsg('add', $data);
        $task->taskPush(null, 0);

        // 应用任务模板使用统计（不影响主流程；非成员、模板已删除或共享模板已关闭时静默忽略）
        $templateId = intval(Request::input('template_id', 0));
        if ($templateId > 0) {
            $tpl = ProjectTaskTemplate::find($templateId);
            if ($tpl) {
                $isMember = ProjectUser::where('project_id', $tpl->project_id)
                    ->where('userid', $user->userid)->exists();
                $shareEnabled = ($project->task_template_share ?: 'open') === 'open';
                if ($isMember && ($tpl->project_id == $project->id || $shareEnabled)) {
                    $tpl->incrementUsage();
                }
            }
        }

        return Base::retSuccess('添加成功', $data);
    }

    /**
     * @api {get} api/project/task/addsub 添加子任务
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
        $ownerids = ProjectUser::whereProjectId($task->project_id)
            ->whereIn('owner', [ProjectUser::OWNER_PRIMARY, ProjectUser::OWNER_DEPUTY])
            ->pluck('userid')->toArray();
        $pushUserIds = array_merge($pushUserIds, $ownerids);
        foreach ($pushUserIds as $userId) {
            $task->pushMsg('add', $data, $userId);
        }
        return Base::retSuccess('添加成功', $data);
    }

    /**
     * @api {get} api/project/task/upgrade 子任务升级为主任务
     *
     * @apiDescription 需要token身份（限：项目、任务负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__upgrade
     *
     * @apiParam {Number} task_id               子任务ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__upgrade()
    {
        $user = User::auth();
        //
        $task_id = intval(Request::input('task_id'));
        //
        $task = ProjectTask::userTask($task_id, true, true, ['taskUser']);
        if ($task->parent_id == 0) {
            return Base::retError('当前任务已是主任务');
        }
        //
        $project = Project::userProject($task->project_id);
        ProjectPermission::userTaskPermission($project, ProjectPermission::TASK_MOVE, $task);
        //
        $parentTask = ProjectTask::withTrashed()->find($task->parent_id);
        $visibilityUserids = [];
        if ($task->visibility == 3) {
            $visibilityUserids = ProjectTaskVisibilityUser::whereTaskId($task->id)->pluck('userid')->toArray();
            if (empty($visibilityUserids) && $parentTask) {
                $visibilityUserids = ProjectTaskVisibilityUser::whereTaskId($parentTask->id)->pluck('userid')->toArray();
            }
        }
        //
        DB::transaction(function () use ($task, $parentTask, $visibilityUserids) {
            $task->lockForUpdate();
            $task->parent_id = 0;
            if ($parentTask) {
                $task->p_level = $parentTask->p_level;
                $task->p_name = $parentTask->p_name;
                $task->p_color = $parentTask->p_color;
            }
            $task->save();
            ProjectTaskUser::whereTaskId($task->id)->update(['task_pid' => $task->id]);
            if ($task->visibility == 3 && !empty($visibilityUserids)) {
                ProjectTaskVisibilityUser::whereTaskId($task->id)->remove();
                foreach (array_unique($visibilityUserids) as $userid) {
                    if (!$userid) {
                        continue;
                    }
                    ProjectTaskVisibilityUser::createInstance([
                        'project_id' => $task->project_id,
                        'task_id' => $task->id,
                        'userid' => $userid,
                    ])->save();
                }
            }
            if ($parentTask) {
                $parentTask->addLog("子任务升级为主任务", [
                    'subtask' => [
                        'id' => $task->id,
                        'name' => $task->name,
                    ],
                ]);
            }
            $task->addLog("升级为主任务");
        });
        //
        $task->refresh()->loadMissing(['project', 'taskUser']);
        if ($task->visibility != 1) {
            ProjectTaskObserver::visibilityUpdate($task);
        }
        $taskData = ProjectTask::oneTask($task->id);
        $parentData = null;
        if ($parentTask && !$parentTask->trashed()) {
            $parentTask->refresh()->loadMissing(['project', 'taskUser']);
            $parentData = ProjectTask::oneTask($parentTask->id);
        }
        //
        $taskArray = $taskData ? $taskData->toArray() : [];
        $parentArray = $parentData ? $parentData->toArray() : null;
        if ($taskArray) {
            $task->pushMsg('update', $taskArray);
        }
        if ($parentArray && $parentTask) {
            $parentTask->pushMsg('update', $parentArray);
        }
        if ($parentTask && !$parentTask->trashed()) {
            $mentionRelation = ProjectTaskRelation::updateOrCreate(
                [
                    'task_id' => $task->id,
                    'related_task_id' => $parentTask->id,
                    'direction' => ProjectTaskRelation::DIRECTION_MENTIONED_BY,
                ],
                [
                    'userid' => $user->userid ?? null,
                ]
            );
            $mentionedByRelation = ProjectTaskRelation::updateOrCreate(
                [
                    'task_id' => $parentTask->id,
                    'related_task_id' => $task->id,
                    'direction' => ProjectTaskRelation::DIRECTION_MENTION,
                ],
                [
                    'userid' => $user->userid ?? null,
                ]
            );
            if ($mentionRelation->wasRecentlyCreated || $mentionRelation->wasChanged()) {
                $task->pushMsg('relation', null, null, false);
            }
            if ($mentionedByRelation->wasRecentlyCreated || $mentionedByRelation->wasChanged()) {
                $parentTask->pushMsg('relation', null, null, false);
            }
        }
        //
        return Base::retSuccess('操作成功', [
            'task' => $taskArray,
            'parent' => $parentArray,
        ]);
    }

    /**
     * @api {post} api/project/task/update 修改任务、子任务
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
     * @apiParam {Array} [task_tag]             任务标签（子任务不支持）
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
        $param = ProjectTask::normalizeTimes($param, $task);
        //
        if ($task->hasOwner()) {
            // 已经存在负责人，则需要检查权限（即：没有任务负责人时，不检查权限）
            $project = Project::userProject($task->project_id);
            $permissionKey = ProjectPermission::TASK_UPDATE;
            if (Arr::exists($param, 'times')) {
                $permissionKey = ProjectPermission::TASK_TIME;
            } else if (Arr::exists($param, 'flow_item_id')) {
                $permissionKey = ProjectPermission::TASK_STATUS;
            }
            ProjectPermission::userTaskPermission($project, $permissionKey, $task);
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
     * @api {get} api/project/task/dialog 创建/获取聊天室
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
        $dialogData = WebSocketDialog::synthesizeData($task->dialog_id, $user->userid);
        return Base::retSuccess('success', [
            'id' => $task->id,
            'dialog_id' => $task->dialog_id,
            'dialog_data' => $dialogData,
        ]);
    }

    /**
     * @api {get} api/project/task/archived 归档任务
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
     * @api {get} api/project/task/remove 删除任务
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
     * @api {get} api/project/task/resetfromlog 根据日志重置任务
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
                $data["flow_item_name"] = $newFlowItem->status . "|" . $newFlowItem->name . "|" . $newFlowItem->color;
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
     * @api {get} api/project/task/flow 任务工作流信息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__flow
     *
     * @apiParam {Number} [task_id]             任务ID
     * @apiParam {Number} [project_id]          项目ID（存在时只返回这个项目的工作流，主要用于任务移动到其他项目时）
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
        $turns = ProjectFlowItem::select(['id', 'name', 'status', 'turns', 'color'])->whereFlowId($projectFlow->id)->orderBy('sort')->get();
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
     * @api {get} api/project/task/move 任务移动
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
     * @apiParam {String} [completed]           是否已完成
     * - 没有 工作流id 时此参数才生效
     * - 有值表示已完成
     * - 空值表示未完成
     * - 不存在不改变状态
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__move()
    {
        Base::checkClientVersion('0.42.0');
        User::auth();
        //
        $task_id = intval(Request::input('task_id'));
        $project_id = intval(Request::input('project_id'));
        $column_id = intval(Request::input('column_id'));
        $flow_item_id = intval(Request::input('flow_item_id'));
        $owner = Request::input('owner', []);
        $assist = Request::input('assist', []);
        $completed = Request::exists('completed') ? (bool)Request::input('completed') : null;
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
        } else {
            if (projectFlowItem::whereProjectId($project->id)->count() > 0) {
                return Base::retError('请选择移动后状态', [], 102);
            }
        }
        //
        $task->moveTask($project_id, $column_id, $flow_item_id, $owner, $assist, $completed);
        //
        $data = [];
        $mainTask = ProjectTask::userTask($task_id)?->toArray();
        if ($mainTask) {
            $mainTask['column_name'] = ProjectColumn::whereId($mainTask['column_id'])->value('name');
            $mainTask['project_name'] = Project::whereId($mainTask['project_id'])->value('name');
            $data[] = $mainTask;
            //
            $subTasks = ProjectTask::whereParentId($task_id)->get();
            foreach ($subTasks as $subTask) {
                $data[] = [
                    'id' => $subTask->id,
                    'project_id' => $subTask->project_id,
                    'column_id' => $subTask->column_id,
                    'column_name' => $mainTask['column_name'],
                    'project_name' => $mainTask['project_name'],
                ];
            }
        }
        //
        return Base::retSuccess('移动成功', $data);
    }

    /**
     * @api {post} api/project/task/copy 复制任务
     *
     * @apiDescription 需要token身份（限：项目、任务负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__copy
     *
     * @apiParam {Number} task_id               任务ID
     * @apiParam {Number} project_id            目标项目ID
     * @apiParam {Number} column_id             目标列表ID
     * @apiParam {Number} flow_item_id          工作流id
     * @apiParam {Array} owner                  负责人
     * @apiParam {Array} assist                 协助人
     * @apiParam {String} [completed]           是否已完成（仅在没有工作流时生效）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__copy()
    {
        User::auth();
        //
        $task_id = intval(Request::input('task_id'));
        $project_id = intval(Request::input('project_id'));
        $column_id = intval(Request::input('column_id'));
        $flow_item_id = intval(Request::input('flow_item_id'));
        $owner = Request::input('owner', []);
        $assist = Request::input('assist', []);
        $completed = Request::exists('completed') ? (bool)Request::input('completed') : null;
        //
        $task = ProjectTask::userTask($task_id);
        //
        $sourceProject = Project::userProject($task->project_id);
        ProjectPermission::userTaskPermission($sourceProject, ProjectPermission::TASK_MOVE, $task);
        //
        $project = Project::userProject($project_id);
        ProjectPermission::userTaskPermission($project, ProjectPermission::TASK_ADD);
        //
        $column = ProjectColumn::whereProjectId($project->id)->whereId($column_id)->first();
        if (empty($column)) {
            return Base::retError('列表不存在');
        }
        if (ProjectTask::whereProjectId($project->id)
                ->whereNull('project_tasks.complete_at')
                ->whereNull('project_tasks.archived_at')
                ->count() > 2000) {
            return Base::retError('项目内未完成任务最多不能超过2000个');
        }
        if (ProjectTask::whereColumnId($column->id)
                ->whereNull('project_tasks.complete_at')
                ->whereNull('project_tasks.archived_at')
                ->count() > 500) {
            return Base::retError('单个列表未完成任务最多不能超过500个');
        }
        $flowItem = null;
        if ($flow_item_id) {
            $flowItem = ProjectFlowItem::whereProjectId($project->id)->whereId($flow_item_id)->first();
            if (empty($flowItem)) {
                return Base::retError('任务状态不存在');
            }
        } else {
            if (ProjectFlowItem::whereProjectId($project->id)->count() > 0) {
                return Base::retError('请选择移动后状态', [], 102);
            }
        }
        //
        $projectUserIds = ProjectUser::whereProjectId($project->id)->pluck('userid')->toArray();
        $owner = array_values(array_filter(array_unique(array_map('intval', Arr::wrap($owner)))));
        $assist = array_values(array_filter(array_unique(array_map('intval', Arr::wrap($assist)))));
        $owner = array_values(array_intersect($owner, $projectUserIds));
        $assist = array_values(array_diff(array_intersect($assist, $projectUserIds), $owner));
        //
        $newTask = AbstractModel::transaction(function () use ($task, $project, $column, $flowItem, $owner, $assist, $completed) {
            /** @var ProjectTask $task */
            $copy = $task->copyTask();
            $copy->project_id = $project->id;
            $copy->column_id = $column->id;
            $copy->sort = intval(ProjectTask::whereColumnId($column->id)->orderByDesc('sort')->value('sort')) + 1;
            $copy->flow_item_id = 0;
            $copy->flow_item_name = '';
            $copy->save();
            $copy->load(['content', 'taskFile', 'taskTag', 'taskUser']);
            if ($copy->content) {
                $copy->content->project_id = $project->id;
                $copy->content->save();
            }
            foreach ($copy->taskFile as $taskFile) {
                $taskFile->project_id = $project->id;
                $taskFile->save();
            }
            foreach ($copy->taskTag as $taskTag) {
                $taskTag->project_id = $project->id;
                $taskTag->save();
            }
            ProjectTaskUser::whereTaskId($copy->id)->remove();
            $copy->setRelation('taskUser', collect());
            $copy->setRelation('project', $project);
            $updateData = [
                'task_id' => $copy->id,
                'owner' => $owner,
            ];
            if ($copy->parent_id === 0) {
                $updateData['assist'] = $assist;
            }
            if ($flowItem) {
                $updateData['flow_item_id'] = $flowItem->id;
            } elseif ($completed !== null) {
                $updateData['complete_at'] = $completed ? Carbon::now()->toDateTimeString() : false;
            }
            $updateMarking = [];
            $copy->updateTask($updateData, $updateMarking);
            $copy->addLog('复制{任务}', [
                'copy_from' => $task->id,
            ]);
            // 复制子任务
            $task->copySubTasks($copy, [
                'reset_complete' => true,
                'update_project' => true,
            ]);
            return $copy;
        });
        //
        $data = ProjectTask::oneTask($newTask->id)->toArray();
        $data['column_name'] = $column->name;
        $data['project_name'] = $project->name;
        //
        return Base::retSuccess('复制成功', $data);
    }

    /**
     * 使用 AI 助手生成任务
     *
     * @deprecated 已废弃方法，仅保留路由占位，后续版本中移除
     */
    public function task__ai_generate()
    {
        Base::checkClientVersion('1.4.35');
    }

    /**
     * 使用 AI 助手生成项目
     *
     * @deprecated 已废弃方法，仅保留路由占位，后续版本中移除
     */
    public function ai__generate()
    {
        Base::checkClientVersion('1.4.35');
    }

    /**
     * @api {get} api/project/flow/list 工作流列表
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
        $project = Project::findForDepartmentView($project_id, true);
        //
        $list = ProjectFlow::with(['ProjectFlowItem'])->whereProjectId($project->id)->get();
        return Base::retSuccess('success', $list);
    }

    /**
     * @api {post} api/project/flow/save 保存工作流
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
     * @api {get} api/project/flow/delete 删除工作流
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
     * @api {get} api/project/log/lists 获取项目、任务日志
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
            $task = ProjectTask::findForDepartmentView($task_id, null);
            $builder->whereTaskId($task->id);
        } else {
            $project = Project::findForDepartmentView($project_id);
            $builder->with(['projectTask:id,parent_id,name'])->whereProjectId($project->id)->whereTaskOnly(0);
        }
        //
        $list = $builder->orderByDesc('created_at')->orderByDesc('id')->paginate(Base::getPaginate(100, 20));
        $list->transform(function (ProjectLog $log) use ($task_id) {
            $timestamp = Carbon::parse($log->created_at)->timestamp;
            if ($task_id === 0) {
                $log->projectTask?->cancelAppend();
            }
            $log->detail = Doo::translate($log->detail);
            $log->time = [
                'ymd' => date(date("Y", $timestamp) == date("Y", Timer::time()) ? "m-d" : "Y-m-d", $timestamp),
                'hi' => date("h:i", $timestamp) ,
                'week' => Doo::translate("周" . Timer::getWeek($timestamp)),
                'segment' => Doo::translate(Timer::getDayeSegment($timestamp)),
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
     * @api {get} api/project/top 项目置顶
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
        if ($projectUser->project) {
            $projectUser->project->updated_at = Carbon::now();
            $projectUser->project->save();
        }
        return Base::retSuccess("success", [
            'id' => $projectUser->project_id,
            'top_at' => $projectUser->top_at?->toDateTimeString(),
        ]);
    }

    /**
     * @api {get} api/project/permission 获取项目权限设置
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
     * @api {get} api/project/permission/update 项目权限设置
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
            ProjectPermission::TASK_TIME,
            ProjectPermission::TASK_STATUS,
            ProjectPermission::TASK_REMOVE,
            ProjectPermission::TASK_ARCHIVED,
            ProjectPermission::TASK_MOVE,
        ]);
        $projectPermission = ProjectPermission::updatePermissions($projectId, Base::newArrayRecursive('intval', $permissions));
        return Base::retSuccess("success",  $projectPermission);
    }

    /**
     * @api {get} api/project/task/template_list 任务模板列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__template_list
     *
     * @apiParam {Number} project_id                项目ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__template_list()
    {
        User::auth();
        //
        $projectId = intval(Request::input('project_id'));
        if (!$projectId) {
            return Base::retError('参数错误');
        }
        $templates = ProjectTaskTemplate::where('project_id', $projectId)
            ->orderBy('sort')
            ->orderByDesc('id')
            ->get();
        return Base::retSuccess('success', $templates);
    }

    /**
     * @api {get} api/project/task/template_visible 当前用户跨项目可见的全部任务模板
     *
     * @apiDescription 返回当前用户加入的所有项目下的任务模板。当前项目的模板优先排序。
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__template_visible
     *
     * @apiParam {Number} [current_project_id]  当前项目 ID（用于排序优先；可空）
     *
     * @apiSuccess {Number} ret  返回状态码（1 正确、0 错误）
     * @apiSuccess {String} msg  返回信息
     * @apiSuccess {Object[]} data 模板列表，每条包含 project_id, project_name, name, title, content, sort, is_default, userid, use_count, last_used_at
     */
    public function task__template_visible()
    {
        $user = User::auth();
        $currentProjectId = intval(Request::input('current_project_id', 0));

        $projectIds = ProjectUser::where('userid', $user->userid)->pluck('project_id');
        $currentProject = $currentProjectId > 0 ? Project::find($currentProjectId) : null;
        if ($currentProject && ($currentProject->task_template_share ?: 'open') === 'close') {
            $projectIds = collect($projectIds)->filter(fn($id) => intval($id) === $currentProjectId)->values();
        }

        $rows = ProjectTaskTemplate::with(['project:id,name'])
            ->whereIn('project_id', $projectIds)
            ->orderByRaw('project_id = ? DESC', [$currentProjectId])
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(function ($tpl) {
                return [
                    'id' => $tpl->id,
                    'project_id' => $tpl->project_id,
                    'project_name' => $tpl->project->name ?? '',
                    'name' => $tpl->name,
                    'title' => $tpl->title,
                    'content' => $tpl->content,
                    'sort' => $tpl->sort,
                    'is_default' => $tpl->is_default,
                    'userid' => $tpl->userid,
                    'use_count' => $tpl->use_count,
                    'last_used_at' => $tpl->last_used_at,
                ];
            });

        return Base::retSuccess('success', $rows);
    }

    /**
     * @api {get} api/project/task/template_search  跨项目模板搜索分页
     *
     * @apiDescription "更多"弹层用。返回当前用户跨项目可见模板，支持关键字 + 分页。
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__template_search
     *
     * @apiParam {String} [keyword]    关键字（在 name/title/content 上模糊匹配）
     * @apiParam {Number} [current_project_id] 当前项目 ID（共享模板关闭时仅返回本项目模板）
     * @apiParam {Number} [page=1]     页码
     * @apiParam {Number} [page_size=20] 每页条数（最大 50）
     *
     * @apiSuccess {Number} ret       返回状态码
     * @apiSuccess {Object} data      含 total / page / page_size / items
     */
    public function task__template_search()
    {
        $user = User::auth();
        $keyword = trim((string) Request::input('keyword', ''));
        $currentProjectId = intval(Request::input('current_project_id', 0));
        $page = max(1, intval(Request::input('page', 1)));
        $pageSize = min(50, max(1, intval(Request::input('page_size', 20))));

        $projectIds = ProjectUser::where('userid', $user->userid)->pluck('project_id');
        $currentProject = $currentProjectId > 0 ? Project::find($currentProjectId) : null;
        if ($currentProject && ($currentProject->task_template_share ?: 'open') === 'close') {
            $projectIds = collect($projectIds)->filter(fn($id) => intval($id) === $currentProjectId)->values();
        }

        $q = ProjectTaskTemplate::with(['project:id,name', 'user:userid,nickname'])
            ->whereIn('project_id', $projectIds);

        if ($keyword !== '') {
            $like = '%' . $keyword . '%';
            $q->where(function ($qq) use ($like) {
                $qq->where('name', 'like', $like)
                    ->orWhere('title', 'like', $like)
                    ->orWhere('content', 'like', $like);
            });
        }

        $total = (clone $q)->count();
        $items = $q->orderByDesc('use_count')
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->forPage($page, $pageSize)
            ->get()
            ->map(function ($tpl) {
                return [
                    'id' => $tpl->id,
                    'project_id' => $tpl->project_id,
                    'project_name' => $tpl->project->name ?? '',
                    'name' => $tpl->name,
                    'title' => $tpl->title,
                    'content' => $tpl->content,
                    'use_count' => $tpl->use_count,
                    'userid' => $tpl->userid,
                    'user_name' => $tpl->user->nickname ?? '',
                    'last_used_at' => $tpl->last_used_at,
                ];
            });

        return Base::retSuccess('success', [
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'items' => $items,
        ]);
    }

    /**
     * @api {post} api/project/task/template_save 保存任务模板
     *
     * @apiDescription 需要token身份（限：项目负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__template_save
     *
     * @apiParam {Number} project_id                项目ID
     * @apiParam {Number} [id]                      模板ID
     * @apiParam {String} name                      模板名称
     * @apiParam {String} title                     任务标题
     * @apiParam {String} content                   任务内容
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__template_save()
    {
        $user = User::auth();
        //
        $projectId = intval(Request::input('project_id'));
        if (!$projectId) {
            return Base::retError('参数错误');
        }
        Project::userProject($projectId, true, true);
        //
        $id = intval(Request::input('id', 0));
        $name = trim(Request::input('name', ''));
        $title = trim(Request::input('title', ''));
        $content = trim(Request::input('content', ''));
        if (empty($name)) {
            return Base::retError('请输入模板名称');
        }
        if (empty($title) && empty($content)) {
            return Base::retError('请输入任务标题或内容');
        }
        $data = [
            'project_id' => $projectId,
            'name' => $name,
            'title' => $title,
            'content' => $content,
            'userid' => $user->userid
        ];
        if ($id > 0) {
            $template = ProjectTaskTemplate::where('id', $id)
                ->where('project_id', $projectId)
                ->first();
            if (!$template) {
                return Base::retError('模板不存在或已被删除');
            }
            $template->update($data);
        } else {
            $templateCount = ProjectTaskTemplate::where('project_id', $projectId)->count();
            if ($templateCount >= 50) {
                return Base::retError('每个项目最多添加50个模板');
            }
            $maxSort = ProjectTaskTemplate::where('project_id', $projectId)->max('sort');
            $template = ProjectTaskTemplate::create(array_merge($data, [
                'sort' => is_numeric($maxSort) ? intval($maxSort) + 1 : 0
            ]));
        }
        return Base::retSuccess('保存成功', $template);
    }

    /**
     * @api {post} api/project/task/template_sort 排序任务模板
     *
     * @apiDescription 需要token身份（限：项目负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__template_sort
     *
     * @apiParam {Number} project_id                项目ID
     * @apiParam {Array} list                       模板ID列表，按新顺序排列
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__template_sort()
    {
        User::auth();
        $projectId = intval(Request::input('project_id'));
        $list = Base::json2array(Request::input('list'));
        if ($projectId <= 0 || !is_array($list)) {
            return Base::retError('参数错误');
        }
        $project = Project::userProject($projectId, true, true);
        $index = 0;
        $handled = [];
        foreach ($list as $templateId) {
            $templateId = intval($templateId);
            if ($templateId <= 0) continue;
            $updated = ProjectTaskTemplate::where('project_id', $projectId)
                ->where('id', $templateId)
                ->update(['sort' => $index]);
            if ($updated) {
                $handled[] = $templateId;
                $index++;
            }
        }
        $others = ProjectTaskTemplate::where('project_id', $projectId)
            ->when(!empty($handled), function ($query) use ($handled) {
                $query->whereNotIn('id', $handled);
            })
            ->orderBy('sort')
            ->orderByDesc('id')
            ->pluck('id');
        foreach ($others as $templateId) {
            ProjectTaskTemplate::where('id', $templateId)->update(['sort' => $index]);
            $index++;
        }
        $project->addLog('调整模板排序');
        return Base::retSuccess('排序已保存');
    }

    /**
     * @api {get} api/project/task/template_delete 删除任务模板
     *
     * @apiDescription 需要token身份（限：项目负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__template_delete
     *
     * @apiParam {Number} id                      模板ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__template_delete()
    {
        User::auth();
        //
        $id = intval(Request::input('id'));
        if (!$id) {
            return Base::retError('参数错误');
        }
        $template = ProjectTaskTemplate::find($id);
        if (!$template) {
            return Base::retError('模板不存在或已被删除');
        }
        Project::userProject($template->project_id, true, true);
        $template->delete();
        return Base::retSuccess('删除成功');
    }

    /**
     * @api {get} api/project/task/template_default 设置(取消)任务模板为默认
     *
     * @apiDescription 需要token身份（限：项目负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__template_default
     *
     * @apiParam {Number} id                      模板ID
     * @apiParam {Number} project_id              项目ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__template_default()
    {
        User::auth();
        //
        $id = intval(Request::input('id'));
        $projectId = intval(Request::input('project_id'));
        if (!$id || !$projectId) {
            return Base::retError('参数错误');
        }
        Project::userProject($projectId, true, true);
        //
        $template = ProjectTaskTemplate::where('id', $id)
            ->where('project_id', $projectId)
            ->first();
        if (!$template) {
            return Base::retError('模板不存在或已被删除');
        }
        if ($template->is_default) {
            $template->update(['is_default' => false]);
            return Base::retSuccess('取消成功');
        }
        //
        ProjectTaskTemplate::where('project_id', $projectId)->update(['is_default' => false]);
        $template->update(['is_default' => true]);
        return Base::retSuccess('设置成功');
    }

    /**
     * @api {post} api/project/tag/save 保存标签
     *
     * @apiDescription 需要token身份（修改：项目负责人、标签创建者；添加：项目所有成员）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName tag__save
     *
     * @apiParam {Number} project_id                项目ID
     * @apiParam {Number} [id]                      标签ID
     * @apiParam {String} name                      标签名称
     * @apiParam {String} desc                      标签描述
     * @apiParam {String} color                     标签颜色
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function tag__save()
    {
        $user = User::auth();
        //
        $projectId = intval(Request::input('project_id'));
        if (!$projectId) {
            return Base::retError('参数错误');
        }
        //
        $id = intval(Request::input('id', 0));
        $name = trim(Request::input('name', ''));
        $desc = trim(Request::input('desc', ''));
        $color = trim(Request::input('color', ''));
        if (empty($name)) {
            return Base::retError('请输入标签名称');
        }
        if (empty($color)) {
            return Base::retError('请选择标签颜色');
        }
        $data = [
            'project_id' => $projectId,
            'name' => $name,
            'desc' => $desc,
            'color' => $color,
            'userid' => $user->userid
        ];
        $project = Project::userProject($projectId);
        if ($id > 0) {
            $tag = ProjectTag::where('id', $id)
                ->where('project_id', $projectId)
                ->first();
            if (!$project->owner && $tag->userid != $user->userid) {
                return Base::retError('没有权限修改标签');
            }
            if (!$tag) {
                return Base::retError('标签不存在或已被删除');
            }
            AbstractModel::transaction(function () use ($data, $tag, $project) {
                $tagWhere = [
                    'project_id' => $tag->project_id,
                    'name' => $tag->name,
                ];
                // 获取使用该标签的任务ID
                $taskIds = ProjectTaskTag::where($tagWhere)->pluck('task_id')->toArray();
                // 更新任务
                if (!empty($taskIds)) {
                    ProjectTask::whereIn('id', $taskIds)->update(['updated_at' => Carbon::now()]);
                }
                // 更新任务标签
                ProjectTaskTag::where($tagWhere)->update([
                    'color' => $data['color'],
                    'name' => $data['name'],
                ]);
                // 更新标签
                $project->addLog("修改标签", [
                    'change' => [
                        [
                            'type' => 'tag',
                            'name' => $tag->name,
                            'color' => $tag->color
                        ],
                        [
                            'type' => 'tag',
                            'name' => $data['name'],
                            'color' => $data['color']
                        ]
                    ],
                ]);
                $tag->update($data);
            });
        } else {
            $tagCount = ProjectTag::where('project_id', $projectId)->count();
            if ($tagCount >= 100) {
                return Base::retError('每个项目最多添加100个标签');
            }
            if (ProjectTag::where([
                'project_id' => $projectId,
                'name' => $name,
            ])->exists()) {
                return Base::retError('标签已存在');
            }
            $project->addLog("添加标签", [
                'change' => [
                    'type' => 'tag',
                    'name' => $name,
                    'color' => $color
                ]
            ]);
            $maxSort = ProjectTag::where('project_id', $projectId)->max('sort');
            $data['sort'] = is_numeric($maxSort) ? intval($maxSort) + 1 : 0;
            $tag = ProjectTag::create($data);
        }
        return Base::retSuccess('保存成功', $tag);
    }

    /**
     * @api {post} api/project/tag/sort 标签排序
     *
     * @apiDescription 需要token身份（限：项目负责人）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName tag__sort
     *
     * @apiParam {Number} project_id                项目ID
     * @apiParam {Array} list                       标签ID列表，按新顺序排列
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function tag__sort()
    {
        User::auth();
        $projectId = intval(Request::input('project_id'));
        $list = Base::json2array(Request::input('list'));
        if ($projectId <= 0 || !is_array($list)) {
            return Base::retError('参数错误');
        }
        $project = Project::userProject($projectId, true, true);
        $index = 0;
        $handled = [];
        foreach ($list as $tagId) {
            $tagId = intval($tagId);
            if ($tagId <= 0) continue;
            $updated = ProjectTag::where('project_id', $projectId)
                ->where('id', $tagId)
                ->update(['sort' => $index]);
            if ($updated) {
                $handled[] = $tagId;
                $index++;
            }
        }
        $others = ProjectTag::where('project_id', $projectId)
            ->when(!empty($handled), function ($query) use ($handled) {
                $query->whereNotIn('id', $handled);
            })
            ->orderBy('sort')
            ->orderByDesc('id')
            ->pluck('id');
        foreach ($others as $tagId) {
            ProjectTag::where('id', $tagId)->update(['sort' => $index]);
            $index++;
        }
        $project->addLog("调整标签排序");
        return Base::retSuccess('排序已保存');
    }

    /**
     * @api {get} api/project/tag/delete 删除标签
     *
     * @apiDescription 需要token身份（限：项目负责人、标签创建者）
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName tag__delete
     *
     * @apiParam {Number} id                      标签ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function tag__delete()
    {
        $user = User::auth();
        //
        $id = intval(Request::input('id'));
        if (!$id) {
            return Base::retError('参数错误');
        }
        $tag = ProjectTag::find($id);
        if (!$tag) {
            return Base::retError('标签不存在或已被删除');
        }
        $project = Project::userProject($tag->project_id);
        if (!$project->owner && $tag->userid != $user->userid) {
            return Base::retError('没有权限删除标签');
        }
        //
        return AbstractModel::transaction(function () use ($tag, $project) {
            $tagWhere = [
                'project_id' => $tag->project_id,
                'name' => $tag->name,
            ];
            // 获取使用该标签的任务ID
            $taskIds = ProjectTaskTag::where($tagWhere)->pluck('task_id')->toArray();
            // 更新任务
            if (!empty($taskIds)) {
                ProjectTask::whereIn('id', $taskIds)->update(['updated_at' => Carbon::now()]);
            }
            // 删除任务标签
            ProjectTaskTag::where($tagWhere)->delete();
            // 删除标签
            $project->addLog("删除标签", [
                'change' => [
                    'type' => 'tag',
                    'name' => $tag->name,
                    'color' => $tag->color
                ],
            ]);
            $tag->delete();
            return Base::retSuccess('删除成功');
        });
    }

    /**
     * @api {get} api/project/tag/list 标签列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName tag__list
     *
     * @apiParam {Number} project_id                项目ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function tag__list()
    {
        User::auth();
        //
        $projectId = intval(Request::input('project_id'));
        if (!$projectId) {
            return Base::retError('参数错误');
        }
        $tags = ProjectTag::where('project_id', $projectId)
            ->orderBy('sort')
            ->orderByDesc('id')
            ->get();
        return Base::retSuccess('success', $tags);
    }

    /**
     * @api {post} api/project/task/ai_apply 采纳AI建议
     *
     * @apiDescription 标记AI建议为已采纳，返回建议数据供前端调用相应业务接口处理
     *
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__ai_apply
     *
     * @apiParam {Number} task_id       任务ID
     * @apiParam {Number} msg_id        消息ID
     * @apiParam {String} type          建议类型：description/subtasks/assignee/similar
     * @apiParam {Number} [userid]      用户ID（assignee类型时用于指定采纳哪个推荐）
     * @apiParam {Number} [related]     关联任务ID（similar类型时用于指定采纳哪个相似任务）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     * @apiSuccess {String} data.type   建议类型
     * @apiSuccess {Number} data.task_id 任务ID
     * @apiSuccess {Object} data.result 建议内容（格式根据type不同而异）
     */
    public function task__ai_apply()
    {
        User::auth();
        //
        $taskId = intval(Request::input('task_id'));
        $msgId = intval(Request::input('msg_id'));
        $type = trim(Request::input('type'));
        $userid = intval(Request::input('userid'));
        $related = intval(Request::input('related'));

        // 验证建议类型
        if (!in_array($type, ProjectTaskAiEvent::getEventTypes())) {
            return Base::retError('无效的建议类型');
        }

        // 验证任务
        $task = ProjectTask::userTask($taskId);
        if (!$task) {
            return Base::retError('任务不存在或无权限');
        }

        // 获取事件记录
        $event = ProjectTaskAiEvent::where('task_id', $taskId)
            ->where('event_type', $type)
            ->where('msg_id', $msgId)
            ->first();

        if (!$event) {
            return Base::retError('建议不存在');
        }

        $result = $event->result;
        if (empty($result)) {
            return Base::retError('建议内容为空');
        }

        // 标记事件为已采纳
        $event->markApplied();

        // similar 类型：创建任务关联
        if ($type === 'similar' && $related > 0) {
            ProjectTaskRelation::createRelation(
                $taskId,
                $related,
                $task->dialog_id,
                $msgId,
                User::userid()
            );
        }

        // 记录日志
        if ($type === 'assignee' && $userid > 0) {
            $user = User::find($userid);
            $task->addLog('AI建议：指派给 ' . ($user ? $user->nickname : $userid));
        } elseif ($type === 'similar' && $related > 0) {
            $task->addLog('AI建议：关联任务 #' . $related);
        } else {
            $task->addLog('AI建议：采纳' . $type . '建议');
        }

        // 更新消息状态
        $msgResult = AiTaskSuggestion::updateMessageStatus($msgId, $task->dialog_id, $type, 'applied', $userid, $related);

        // 返回建议数据和消息内容
        return Base::retSuccess('已采纳', [
            'type' => $type,
            'task_id' => $taskId,
            'result' => $result,
            'msg' => $msgResult['data'] ?? null,
        ]);
    }

    /**
     * @api {post} api/project/task/ai_dismiss 忽略AI建议
     *
     * @apiVersion 1.0.0
     * @apiGroup project
     * @apiName task__ai_dismiss
     *
     * @apiParam {Number} task_id       任务ID
     * @apiParam {Number} msg_id        消息ID
     * @apiParam {String} type          建议类型
     * @apiParam {Number} [userid]      用户ID（assignee类型时用于忽略单个推荐）
     * @apiParam {Number} [related]     关联任务ID（similar类型时用于忽略单个推荐）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__ai_dismiss()
    {
        User::auth();
        //
        $taskId = intval(Request::input('task_id'));
        $msgId = intval(Request::input('msg_id'));
        $type = trim(Request::input('type'));
        $userid = intval(Request::input('userid'));
        $related = intval(Request::input('related'));

        // 验证建议类型
        if (!in_array($type, ProjectTaskAiEvent::getEventTypes())) {
            return Base::retError('无效的建议类型');
        }

        // 验证任务
        $task = ProjectTask::userTask($taskId);
        if (!$task) {
            return Base::retError('任务不存在或无权限');
        }

        // 验证事件记录存在
        $event = ProjectTaskAiEvent::where('task_id', $taskId)
            ->where('event_type', $type)
            ->where('msg_id', $msgId)
            ->first();

        if (!$event) {
            return Base::retError('建议不存在');
        }

        // 标记事件为已忽略
        $event->markDismissed();

        // 更新消息状态
        $msgResult = AiTaskSuggestion::updateMessageStatus($msgId, $task->dialog_id, $type, 'dismissed', $userid, $related);

        // 返回消息内容
        return Base::retSuccess('已忽略', [
            'msg' => $msgResult['data'] ?? null,
        ]);
    }
}
