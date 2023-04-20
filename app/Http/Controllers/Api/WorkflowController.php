<?php

namespace App\Http\Controllers\Api;

use Request;
use App\Models\User;
use App\Module\Base;
use App\Module\Ihttp;
use App\Tasks\PushTask;
use App\Models\WebSocketDialog;
use App\Models\WorkflowProcMsg;
use App\Exceptions\ApiException;
use App\Models\WebSocketDialogMsg;
use Hhxsv5\LaravelS\Swoole\Task\Task;

/**
 * @apiDefine workflow
 *
 * 工作流
 */
class WorkflowController extends AbstractController
{
    private $flow_url = '';
    public function __construct()
    {
        $this->flow_url = env('FLOW_URL') ?: 'http://dootask-workflow-'.env('APP_ID');
    }

    /**
     * @api {get} api/workflow/verifyToken          01. 验证APi登录
     *
     * @apiVersion 1.0.0
     * @apiGroup users
     * @apiName verifyToken
     *
     * @apiSuccess {String} version
     * @apiSuccess {String} publish
     */
    public function verifyToken()
    {
        try {
            $user = User::auth();
            $user->checkAdmin();
            return Base::retSuccess('成功');
        } catch (\Throwable $th) {
            return response('身份无效', 400)->header('Content-Type', 'text/plain');
        }
    }

    /**
     * @api {post} api/workflow/procdef/all          02. 查询流程定义
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup workflow
     * @apiName procdef__all
     *
     * @apiQuery {String} name               流程名称
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function procdef__all()
    {
        User::auth();
        $data['name'] = Request::input('name');
        $ret = Ihttp::ihttp_post($this->flow_url.'/api/v1/workflow/procdef/findAll', json_encode($data));
        $procdef = json_decode($ret['ret'] == 1 ? $ret['data'] : '{}', true);
        if (!$procdef || $procdef['status'] != 200 || $ret['ret'] == 0) {
            return Base::retError($procdef['message'] ?? '查询失败');
        }
        return Base::retSuccess('success', Base::arrayKeyToUnderline($procdef['data']));
    }

    /**
     * @api {get} api/workflow/procdef/del          03. 删除流程定义
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup workflow
     * @apiName procdef__del
     *
     * @apiQuery {String} id               流程ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function procdef__del()
    {
        User::auth('admin');
        $data['id'] = Request::input('id');
        $ret = Ihttp::ihttp_get($this->flow_url.'/api/v1/workflow/procdef/delById?'.http_build_query($data));
        $procdef = json_decode($ret['ret'] == 1 ? $ret['data'] : '{}', true);
        if (!$procdef || $procdef['status'] != 200) {
            return Base::retError($procdef['message'] ?? '删除失败');
        }
        return Base::retSuccess('success', Base::arrayKeyToUnderline($procdef['data']));
    }

    /**
     * @api {post} api/workflow/process/start          04. 启动流程（审批中）
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup workflow
     * @apiName process__start
     *
     * @apiQuery {String} proc_name            流程名称
     * @apiQuery {Number} department_id        部门ID
     * @apiQuery {Array} [var]                 启动流程类型信息（格式：[{type,startTime,endTime,description}]）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function process__start()
    {
        $user = User::auth();
        $data['userid'] = (string)$user->userid;
        $data['department_id'] = intval(Request::input('department_id'));
        $data['proc_name'] = Request::input('proc_name');
        //
        $var = json_decode(Request::input('var'), true);
        $data['var'] = $var;
        $ret = Ihttp::ihttp_post($this->flow_url.'/api/v1/workflow/process/start', json_encode(Base::arrayKeyToCamel($data)));
        $process = json_decode($ret['ret'] == 1 ? $ret['data'] : '{}', true);
        if (!$process || $process['status'] != 200) {
            return Base::retError($process['message'] ?? '启动失败');
        }
        //
        $process = Base::arrayKeyToUnderline($process['data']);
        $process = $this->getProcessById($process['id']); //获取最新的流程信息
        if ($process['candidate']) {
            $userid = explode(',', $process['candidate']);
            $toUser = User::whereIn('userid', $userid)->get()->toArray();
            $botUser = User::botGetOrCreate('approval-alert');
            if (empty($botUser)) {
                return Base::retError('审批机器人不存在');
            }
            foreach ($toUser as $val) {
                if ($val['bot']) {
                    continue;
                }
                $dialog = WebSocketDialog::checkUserDialog($botUser, $val['userid']);
                if (empty($dialog)) {
                    continue;
                }
                $this->workflowMsg('workflow_reviewer', $dialog, $botUser, $val, $process, 'start');
            }
            // 抄送人
            $notifier = $this->handleProcessNode($process);
            if ($notifier) {
                foreach ($notifier as $val) {
                    $dialog = WebSocketDialog::checkUserDialog($botUser, $val['target_id']);
                    $this->workflowMsg('workflow_notifier', $dialog, $botUser, $process, $process);
                }
            }
        }

        return Base::retSuccess('创建成功', $process);
    }

    /**
     * @api {post} api/workflow/task/complete          05. 审批
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup workflow
     * @apiName task__complete
     *
     * @apiQuery {Number} task_id               流程ID
     * @apiQuery {String} pass                  标题 [true-通过，false-拒绝]
     * @apiQuery {String} comment               评论
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__complete()
    {
        $user = User::auth();
        $data['userid'] = (string)$user->userid;
        $data['task_id'] = intval(Request::input('task_id'));
        $data['pass'] = Request::input('pass');
        $data['comment'] = Request::input('comment');
        $ret = Ihttp::ihttp_post($this->flow_url.'/api/v1/workflow/task/complete', json_encode(Base::arrayKeyToCamel($data)));
        $task = json_decode($ret['ret'] == 1 ? $ret['data'] : '{}', true);
        if (!$task || $task['status'] != 200) {
            return Base::retError($task['message'] ?? '审批失败');
        }
        //
        $task = Base::arrayKeyToUnderline($task['data']);
        $pass = $data['pass'] == 'true' ? 'pass' : 'refuse';
        $process = $this->getProcessById($task['proc_inst_id']);
        $botUser = User::botGetOrCreate('approval-alert');
        if (empty($botUser)) {
            return Base::retError('审批机器人不存在');
        }
        // 在流程信息关联的用户中查找
        $toUser = WorkflowProcMsg::where('proc_inst_id', $process['id'])->get()->toArray();
        foreach ($toUser as $val) {
            $dialog = WebSocketDialog::checkUserDialog($botUser, $val['userid']);
            if (empty($dialog)) {
                continue;
            }
            $this->workflowMsg('workflow_reviewer', $dialog, $botUser, $val, $process, $pass);
        }
        // 发起人
        if($process['is_finished'] == true) {
            $dialog = WebSocketDialog::checkUserDialog($botUser, $process['start_user_id']);
            $this->workflowMsg('workflow_submitter', $dialog, $botUser, ['userid' => $data['userid']], $process, $pass);
        }else if ($process['candidate']) {
            // 下个审批人
            $userid = explode(',', $process['candidate']);
            $toUser = User::whereIn('userid', $userid)->get()->toArray();
            foreach ($toUser as $val) {
                if ($val['bot']) {
                    continue;
                }
                $dialog = WebSocketDialog::checkUserDialog($botUser, $val['userid']);
                if (empty($dialog)) {
                    continue;
                }
                $this->workflowMsg('workflow_reviewer', $dialog, $botUser, $val, $process,'start');
            }
        }
        
        // 抄送人
        $notifier = $this->handleProcessNode($process, $task['step']);
        if ($notifier && $pass == 'pass') {
            foreach ($notifier as $val) {
                $dialog = WebSocketDialog::checkUserDialog($botUser, $val['target_id']);
                $this->workflowMsg('workflow_notifier', $dialog, $botUser, $process, $process);
            }
        }
        return Base::retSuccess( $pass == 'pass' ? '已通过' : '已拒绝', $task);
    }

    /**
     * @api {post} api/workflow/task/withdraw          06. 撤回
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup workflow
     * @apiName task__withdraw
     *
     * @apiQuery {Number} task_id               流程ID
     * @apiQuery {Number} proc_inst_id          流程实例ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__withdraw()
    {
        $user = User::auth();
        $data['userid'] = (string)$user->userid;
        $data['task_id'] = intval(Request::input('task_id'));
        $data['proc_inst_id'] = intval(Request::input('proc_inst_id'));
        $ret = Ihttp::ihttp_post($this->flow_url.'/api/v1/workflow/task/withdraw', json_encode(Base::arrayKeyToCamel($data)));
        $task = json_decode($ret['ret'] == 1 ? $ret['data'] : '{}', true);
        if (!$task || $task['status'] != 200) {
            return Base::retError($task['message'] ?? '撤回失败');
        }
        //
        $process = $this->getProcessById($data['proc_inst_id']);
        $botUser = User::botGetOrCreate('approval-alert');
        if (empty($botUser)) {
            return Base::retError('审批机器人不存在');
        }
        // 在流程信息关联的用户中查找
        $toUser = WorkflowProcMsg::where('proc_inst_id', $process['id'])->get()->toArray();
        foreach ($toUser as $val) {
            $dialog = WebSocketDialog::checkUserDialog($botUser, $val['userid']);
            if (empty($dialog)) {
                continue;
            }
            //发送撤回提醒
            $this->workflowMsg('workflow_reviewer', $dialog, $botUser, $val, $process, 'withdraw');
        }
        return Base::retSuccess('已撤回', Base::arrayKeyToUnderline($task['data']));
    }

    /**
     * @api {post} api/workflow/process/findTask          07. 查询需要我审批的流程（审批中）
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup workflow
     * @apiName process__findTask
     *
     * @apiQuery {String} proc_def_name         流程名称
     * @apiQuery {String} sort                  排序[asc升序，desc降序]
     * @apiQuery {Number} page                  页码
     * @apiQuery {Number} page_size             每页条数
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function process__findTask()
    {
        $user = User::auth();
        $data['userid'] = (string)$user->userid;
        $data['procName'] = Request::input('proc_def_name');
        $data['sort'] = Request::input('sort');
        $data['pageIndex'] = intval(Request::input('page'));
        $data['pageSize'] = intval(Request::input('page_size'));
        $ret = Ihttp::ihttp_post($this->flow_url.'/api/v1/workflow/process/findTask', json_encode(Base::arrayKeyToCamel($data)));
        $process = json_decode($ret['ret'] == 1 ? $ret['data'] : '{}', true);
        if (!$process || $process['status'] != 200) {
            return Base::retError($process['message'] ?? '查询失败');
        }
        //
        $res = Base::arrayKeyToUnderline($process['data']);
        foreach ($res['rows'] as &$val) {
            $info = User::whereUserid($val['start_user_id'])->first();
            if (!$info) {
                continue;
            }
            $val['userimg'] = User::getAvatar($info->userid, $info->userimg, $info->email, $info->nickname);
        }
        return Base::retSuccess('success',$res);
    }

    /**
     * @api {post} api/workflow/process/startByMyselfAll          08. 查询我启动的流程（全部）
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup workflow
     * @apiName process__startByMyselfAll
     *
     * @apiQuery {String} proc_def_name         流程分类
     * @apiQuery {String} state                 流程状态[0待审批，1审批中，2通过，3拒绝，4撤回]
     * @apiQuery {Number} page                  页码
     * @apiQuery {Number} page_size             每页条数
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function process__startByMyselfAll()
    {
        $user = User::auth();
        $data['userid'] = (string)$user->userid;
        $data['procName'] = Request::input('proc_def_name'); //分类
        $data['state'] = intval(Request::input('state')); //状态
        $data['pageIndex'] = intval(Request::input('page'));
        $data['pageSize'] = intval(Request::input('page_size'));
        $ret = Ihttp::ihttp_post($this->flow_url.'/api/v1/workflow/process/startByMyselfAll', json_encode($data));
        $process = json_decode($ret['ret'] == 1 ? $ret['data'] : '{}', true);
        if (!$process || $process['status'] != 200) {
            return Base::retError($process['message'] ?? '查询失败');
        }
        //
        $res = Base::arrayKeyToUnderline($process['data']);
        foreach ($res['rows'] as &$val) {
            $info = User::whereUserid($val['start_user_id'])->first();
            if (!$info) {
                continue;
            }
            $val['userimg'] = User::getAvatar($info->userid, $info->userimg, $info->email, $info->nickname);
        }
        return Base::retSuccess('success', $res);
    }

    /**
     * @api {post} api/workflow/process/startByMyself          09. 查询我启动的流程（审批中）
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup workflow
     * @apiName process__startByMyself
     *
     * @apiQuery {Number} page                  页码
     * @apiQuery {Number} page_size             每页条数
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function process__startByMyself()
    {
        $user = User::auth();
        $data['userid'] = (string)$user->userid;
        $data['pageIndex'] = intval(Request::input('page'));
        $data['pageSize'] = intval(Request::input('page_size'));
        $ret = Ihttp::ihttp_post($this->flow_url.'/api/v1/workflow/process/startByMyself', json_encode($data));
        $process = json_decode($ret['ret'] == 1 ? $ret['data'] : '{}', true);
        if (!$process || $process['status'] != 200) {
            return Base::retError($process['message'] ?? '查询失败');
        }
        //
        $res = Base::arrayKeyToUnderline($process['data']);
        foreach ($res['rows'] as &$val) {
            $info = User::whereUserid($val['start_user_id'])->first();
            if (!$info) {
                continue;
            }
            $val['userimg'] = User::getAvatar($info->userid, $info->userimg, $info->email, $info->nickname);
        }
        return Base::retSuccess('success', $res);
    }

    /**
     * @api {post} api/workflow/process/findProcNotify          10. 查询抄送我的流程（审批中）
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup workflow
     * @apiName process__findProcNotify
     *
     * @apiQuery {Number} userid               用户ID
     * @apiQuery {String} proc_def_name        流程分类
     * @apiQuery {String} sort                 排序[asc升序，desc降序]
     * @apiQuery {Number} page                  页码
     * @apiQuery {Number} page_size             每页条数
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function process__findProcNotify()
    {
        $user = User::auth();
        $data['userid'] = (string)$user->userid;
        $data['procName'] = Request::input('proc_def_name');
        $data['sort'] = Request::input('sort');
        $data['pageIndex'] = intval(Request::input('page'));
        $data['pageSize'] = intval(Request::input('page_size'));

        $ret = Ihttp::ihttp_post($this->flow_url.'/api/v1/workflow/process/findProcNotify', json_encode($data));
        $process = json_decode($ret['ret'] == 1 ? $ret['data'] : '{}', true);
        if (!$process || $process['status'] != 200) {
            return Base::retError($process['message'] ?? '查询失败');
        }
        //
        $res = Base::arrayKeyToUnderline($process['data']);
        foreach ($res['rows'] as &$val) {
            $info = User::whereUserid($val['start_user_id'])->first();
            if (!$info) {
                continue;
            }
            $val['userimg'] = User::getAvatar($info->userid, $info->userimg, $info->email, $info->nickname);
        }
        return Base::retSuccess('success', $res);
    }

    /**
     * @api {get} api/workflow/identitylink/findParticipant          11. 查询流程实例的参与者（审批中）
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup workflow
     * @apiName identitylink__findParticipant
     *
     * @apiQuery {Number} proc_inst_id             流程实例ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function identitylink__findParticipant()
    {
        User::auth();
        $proc_inst_id = Request::input('proc_inst_id');
        $ret = Ihttp::ihttp_get($this->flow_url.'/api/v1/workflow/identitylink/findParticipant?procInstId=' . $proc_inst_id);
        $identitylink = json_decode($ret['ret'] == 1 ? $ret['data'] : '{}', true);
        if (!$identitylink || $identitylink['status'] != 200) {
            return Base::retError($identitylink['message'] ?? '查询失败');
        }
        //
        $res = Base::arrayKeyToUnderline($identitylink['data']);
        foreach ($res as &$val) {
            $info = User::whereUserid($val['userid'])->first();
            if (!$info) {
                continue;
            }
            $val['userimg'] = User::getAvatar($info->userid, $info->userimg, $info->email, $info->nickname);
        }
        return Base::retSuccess('success', $res);
    }

    /**
     * @api {post} api/workflow/procHistory/findTask          12. 查询需要我审批的流程（已结束）
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup workflow
     * @apiName procHistory__findTask
     *
     * @apiQuery {String} proc_def_name        流程分类
     * @apiQuery {String} sort                 排序[asc升序，desc降序]
     * @apiQuery {Number} page                  页码
     * @apiQuery {Number} page_size             每页条数
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function procHistory__findTask()
    {
        $user = User::auth();
        $data['userid'] = (string)$user->userid;
        $data['procName'] = Request::input('proc_def_name');
        $data['sort'] = Request::input('sort');
        $data['pageIndex'] = intval(Request::input('page'));
        $data['pageSize'] = intval(Request::input('page_size'));
        $ret = Ihttp::ihttp_post($this->flow_url.'/api/v1/workflow/procHistory/findTask', json_encode(Base::arrayKeyToCamel($data)));
        $process = json_decode($ret['ret'] == 1 ? $ret['data'] : '{}', true);
        if (!$process || $process['status'] != 200) {
            return Base::retError($process['message'] ?? '查询失败');
        }
        //
        $res = Base::arrayKeyToUnderline($process['data']);
        foreach ($res['rows'] as &$val) {
            $info = User::whereUserid($val['start_user_id'])->first();
            if (!$info) {
                continue;
            }
            $val['userimg'] = User::getAvatar($info->userid, $info->userimg, $info->email, $info->nickname);
        }
        return Base::retSuccess('success', $res);
    }

    /**
     * @api {post} api/workflow/procHistory/startByMyself          13. 查询我启动的流程（已结束）
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup workflow
     * @apiName procHistory__startByMyself
     *
     * @apiQuery {Number} page                  页码
     * @apiQuery {Number} page_size             每页条数
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function procHistory__startByMyself()
    {
        $user = User::auth();
        $data['userid'] = (string)$user->userid;
        $data['pageIndex'] = intval(Request::input('page'));
        $data['pageSize'] = intval(Request::input('page_size'));
        $ret = Ihttp::ihttp_post($this->flow_url.'/api/v1/workflow/procHistory/startByMyself', json_encode($data));
        $process = json_decode($ret['ret'] == 1 ? $ret['data'] : '{}', true);
        if (!$process || $process['status'] != 200) {
            return Base::retError($process['message'] ?? '查询失败');
        }
        //
        $res = Base::arrayKeyToUnderline($process['data']);
        foreach ($res['rows'] as &$val) {
            $info = User::whereUserid($val['start_user_id'])->first();
            if (!$info) {
                continue;
            }
            $val['userimg'] = User::getAvatar($info->userid, $info->userimg, $info->email, $info->nickname);
        }
        return Base::retSuccess('success', $res);
    }

    /**
     * @api {post} api/workflow/procHistory/findProcNotify          14. 查询抄送我的流程（已结束）
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup workflow
     * @apiName procHistory__findProcNotify
     *
     * @apiQuery {String} proc_def_name         流程分类
     * @apiQuery {String} sort                  排序[asc升序，desc降序]
     * @apiQuery {Number} page                  页码
     * @apiQuery {Number} page_size             每页条数
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function procHistory__findProcNotify()
    {
        $user = User::auth();
        $data['userid'] = (string)$user->userid;
        $data['procName'] = Request::input('proc_def_name');
        $data['sort'] = Request::input('sort');
        $data['pageIndex'] = intval(Request::input('page'));
        $data['pageSize'] = intval(Request::input('page_size'));

        $ret = Ihttp::ihttp_post($this->flow_url.'/api/v1/workflow/procHistory/findProcNotify', json_encode($data));
        $process = json_decode($ret['ret'] == 1 ? $ret['data'] : '{}', true);
        if (!$process || $process['status'] != 200) {
            return Base::retError($process['message'] ?? '查询失败');
        }
        //
        $res = Base::arrayKeyToUnderline($process['data']);
        foreach ($res['rows'] as &$val) {
            $info = User::whereUserid($val['start_user_id'])->first();
            if (!$info) {
                continue;
            }
            $val['userimg'] = User::getAvatar($info->userid, $info->userimg, $info->email, $info->nickname);
        }
        return Base::retSuccess('success', $res);
    }

    /**
     * @api {get} api/workflow/identitylinkHistory/findParticipant          15. 查询流程实例的参与者（已结束）
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup workflow
     * @apiName identitylinkHistory__findParticipant
     *
     * @apiQuery {Number} proc_inst_id             流程实例ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function identitylinkHistory__findParticipant()
    {
        User::auth();
        $proc_inst_id = Request::input('proc_inst_id');
        $ret = Ihttp::ihttp_get($this->flow_url.'/api/v1/workflow/identitylinkHistory/findParticipant?procInstId=' . $proc_inst_id);
        $identitylink = json_decode($ret['ret'] == 1 ? $ret['data'] : '{}', true);
        if (!$identitylink || $identitylink['status'] != 200) {
            return Base::retError($identitylink['message'] ?? '查询失败');
        }
        //
        $res = Base::arrayKeyToUnderline($identitylink['data']);
        foreach ($res as &$val) {
            $info = User::whereUserid($val['userid'])->first();
            if (!$info) {
                continue;
            }
            $val['userimg'] = User::getAvatar($info->userid, $info->userimg, $info->email, $info->nickname);
        }
        return Base::retSuccess('success', $res);
    }

    /**
     * @api {get} api/workflow/process/detail          16. 根据流程ID查询流程详情
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup workflow
     * @apiName process__detail
     *
     * @apiQuery {Number} id               流程ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function process__detail()
    {
        User::auth();
        $data['id'] = intval(Request::input('id'));
        $workflow = $this->getProcessById($data['id']);
        return Base::retSuccess('success', $workflow);
    }

    // 审批机器人消息
    public function workflowMsg($type, $dialog, $botUser, $toUser, $process, $action = null)
    {
        $data = [
            'id' => $process['id'],
            'nickname' => User::userid2nickname($type == 'workflow_submitter' ? $toUser['userid'] : $process['start_user_id']),
            'proc_def_name' => $process['proc_def_name'],
            'department' => $process['department'],
            'type' => $process['var']['type'],
            'start_time' => $process['var']['start_time'],
            'end_time' => $process['var']['end_time'],
        ];
        $text = view('push.bot', ['type' => $type, 'action' => $action, 'data' => (object)$data])->render();
        $text = preg_replace("/^\x20+/", "", $text);
        $text = preg_replace("/\n\x20+/", "\n", $text);
        $msg_action = null;
        if ($action == 'withdraw' || $action == 'pass' || $action == 'refuse') {
            // 如果任务没有完成，则不需要更新消息
            if ($process['is_finished'] != true) {
                return true;
            }
            // 任务完成，给发起人发送消息
            if($type == 'workflow_submitter' && $action != 'withdraw'){
                return WebSocketDialogMsg::sendMsg($msg_action, $dialog->id, 'text', ['text' => $text], $botUser->userid, false, false, true);
            }
            // 查找最后一条消息msg_id
            $msg_action = 'update-'.$toUser['msg_id'];
        }
        $msg = WebSocketDialogMsg::sendMsg($msg_action, $dialog->id, 'text', ['text' => $text], $botUser->userid, false, false, true);
        // 关联信息
        if ($action == 'start') {
            $proc_msg = new WorkflowProcMsg();
            $proc_msg->proc_inst_id = $process['id'];
            $proc_msg->msg_id = $msg['data']->id;
            $proc_msg->userid = $toUser['userid'];
            $proc_msg->save();
        }

        // 更新工作报告 未读数量
        if($type == 'workflow_reviewer' && $toUser['userid']){
            $params = [
                'userid' => [ $toUser['userid'], User::auth()->userid() ],
                'msg' => [
                    'type' => 'workflow',
                    'action' => 'backlog',
                    'userid' => $toUser['userid'],
                ]
            ];
            Task::deliver(new PushTask($params, false));
        }

        return true;
    }

    // 根据ID获取流程
    public function getProcessById($id)
    {
        $data['id'] = intval($id);
        $ret = Ihttp::ihttp_get($this->flow_url."/api/v1/workflow/process/findById?".http_build_query($data));
        $process = json_decode($ret['ret'] == 1 ? $ret['data'] : '{}', true);
        if (!$process || $process['status'] != 200) {
            throw new ApiException($process['message'] ?? '查询失败');
        }
        //
        $res = Base::arrayKeyToUnderline($process['data']);
        foreach ($res['node_infos'] as &$val) {
            if (isset($val['node_user_list'])) {
                $node = $val['node_user_list'];
                foreach ($node as $k => &$item) {
                    $info = User::whereUserid($item['target_id'])->first();
                    if (!$info) {
                        continue;
                    }
                    $val['node_user_list'][$k]['userimg'] = User::getAvatar($info->userid, $info->userimg, $info->email, $info->nickname);
                }
            }else if($val['aprover_id']){
                $info = User::whereUserid($val['aprover_id'])->first();
                $val['userimg'] = $info ? User::getAvatar($info->userid, $info->userimg, $info->email, $info->nickname) : '';
            }
        }
        $info = User::whereUserid($res['start_user_id'])->first();
        $res['userimg'] = $info ? User::getAvatar($info->userid, $info->userimg, $info->email, $info->nickname) : '';
        return $res;
    }

    // 处理流程节点返回是否有抄送人
    public function handleProcessNode($process, $step = 0)
    {
        // 获取流程节点
        $process_node = $process['node_infos'];
        //判断下一步是否有抄送人
        $step = $step + 1;
        $next_node = $process_node[$step] ?? [];
        if ($next_node) {
           if ($next_node['type'] == 'notifier'){
               return $next_node['node_user_list'] ?? [];
           }
        }
        return [];
    }

    // 根据ID查询流程实例的参与者（审批中）
    public function getUserProcessParticipantById($id)
    {
        $data['id'] = intval($id);
        $ret = Ihttp::ihttp_get($this->flow_url."/api/v1/workflow/identitylink/findParticipant?".http_build_query($data));
        $process = json_decode($ret['ret'] == 1 ? $ret['data'] : '{}', true);
        if (!$process || $process['status'] != 200) {
            throw new ApiException($process['message'] ?? '查询失败');
        }
        return $process;
    }

}
