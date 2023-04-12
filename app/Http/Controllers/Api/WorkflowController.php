<?php

namespace App\Http\Controllers\Api;

use Cache;
use Request;
use Carbon\Carbon;
use App\Models\User;
use App\Module\Base;
use App\Module\Ihttp;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Models\WorkflowProcMsg;

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
        $data['name'] = Request::input('name');
        $procdef = Ihttp::ihttp_post($this->flow_url.'/api/v1/workflow/procdef/findAll', json_encode($data));
        $procdef = json_decode($procdef['data'] ?? '', true);
        if (!$procdef || $procdef['status'] != 200) {
            return Base::retError($procdef['message'] ?? '查询流程失败');
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
        $data['id'] = Request::input('id');
        $procdef = Ihttp::ihttp_get($this->flow_url.'/api/v1/workflow/procdef/delById?'.http_build_query($data));
        $procdef = json_decode($procdef['data'] ?? '', true);
        if (!$procdef || $procdef['status'] != 200) {
            return Base::retError($procdef['message'] ?? '删除流程失败');
        }
        return Base::retSuccess('success', Base::arrayKeyToUnderline($procdef['data']));
    }

    /**
     * @api {post} api/workflow/process/start          04. 启动流程
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup workflow
     * @apiName process__start
     *
     * @apiQuery {String} proc_name            流程名称
     * @apiQuery {Number} userid               用户ID
     * @apiQuery {Number} department_id        部门ID
     * @apiQuery {Array} [var]                 启动流程类型信息（格式：[{type,startTime,endTime,description}]）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function process__start()
    {
        $data['userid'] = Request::input('userid');
        $data['department_id'] = intval(Request::input('department_id'));
        $data['proc_name'] = Request::input('proc_name');
        //
        $var = json_decode(Request::input('var'), true);
        $data['var'] = $var;
        $process = Ihttp::ihttp_post($this->flow_url.'/api/v1/workflow/process/start', json_encode(Base::arrayKeyToCamel($data)));
        $process = json_decode($process['data'] ?? '', true);
        if (!$process || $process['status'] != 200) {
            return Base::retError($process['message'] ?? '流程启动失败');
        }
        //
        $process = Base::arrayKeyToUnderline($process['data']);
        $process = $this->getProcessById($process['id']); //获取最新的流程信息
        if ($process['candidate']) {
            $userid = explode(',', $process['candidate']);
            $toUser = User::whereIn('userid', $userid)->get();
            $botUser = User::botGetOrCreate('approval-alert');
            if (empty($botUser)) {
                return Base::retError('审批机器人不存在');
            }
            foreach ($toUser as $val) {
                if ($val->bot) {
                    continue;
                }
                $dialog = WebSocketDialog::checkUserDialog($botUser, $val->userid);
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

        return Base::retSuccess('success', $process);
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
     * @apiQuery {Number} userid                用户ID
     * @apiQuery {String} comment               评论
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__complete()
    {
        $data['task_id'] = intval(Request::input('task_id'));
        $data['pass'] = Request::input('pass');
        $data['userid'] = Request::input('userid');
        $data['comment'] = Request::input('comment');
        $task = Ihttp::ihttp_post($this->flow_url.'/api/v1/workflow/task/complete', json_encode(Base::arrayKeyToCamel($data)));
        $task = json_decode($task['data'] ?? '', true);
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
        }
        // 抄送人
        $notifier = $this->handleProcessNode($process, $task['step']);
        if ($notifier && $pass == 'pass') {
            foreach ($notifier as $val) {
                $dialog = WebSocketDialog::checkUserDialog($botUser, $val['target_id']);
                $this->workflowMsg('workflow_notifier', $dialog, $botUser, $process, $process);
            }
        }
        return Base::retSuccess('success', $task);
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
     * @apiQuery {String} userid                用户ID
     * @apiQuery {Number} proc_inst_id          流程实例ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task__withdraw()
    {
        $data['task_id'] = intval(Request::input('task_id'));
        $data['userid'] = Request::input('userid');
        $data['proc_inst_id'] = intval(Request::input('proc_inst_id'));
        $task = Ihttp::ihttp_post($this->flow_url.'/api/v1/workflow/task/withdraw', json_encode(Base::arrayKeyToCamel($data)));
        $task = json_decode($task['data'] ?? '', true);
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
        return Base::retSuccess('success', Base::arrayKeyToUnderline($task['data']));
    }

    /**
     * @api {post} api/workflow/process/findTask          07. 查询我审批的流程
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup workflow
     * @apiName process__findTask
     *
     * @apiQuery {Number} userid                用户ID
     * @apiQuery {Number} page                  页码
     * @apiQuery {Number} page_size             每页条数
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function process__findTask()
    {
        $data['userid'] = Request::input('userid');
        $data['pageIndex'] = intval(Request::input('page'));
        $data['pageSize'] = intval(Request::input('page_size'));
        $process = Ihttp::ihttp_post($this->flow_url.'/api/v1/workflow/process/findTask', json_encode(Base::arrayKeyToCamel($data)));
        $process = json_decode($process['data'] ?? '', true);
        if (!$process || $process['status'] != 200) {
            return Base::retError($process['message'] ?? '查询失败');
        }
        return Base::retSuccess('success', Base::arrayKeyToUnderline($process['data']));
    }

    /**
     * @api {post} api/workflow/identitylink/findParticipant          08. 查询流程审批人与评论
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
        $proc_inst_id = Request::input('proc_inst_id');
        $identitylink = Ihttp::ihttp_get($this->flow_url.'/api/v1/workflow/identitylink/findParticipant?procInstId=' . $proc_inst_id);
        $identitylink = json_decode($identitylink['data'] ?? '', true);
        if (!$identitylink || $identitylink['status'] != 200) {
            return Base::retError($identitylink['message'] ?? 'fail');
        }
        return Base::retSuccess('success', Base::arrayKeyToUnderline($identitylink['data']));
    }

    /**
     * @api {post} api/workflow/process/startByMyself          09. 查询我发起的流程
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup workflow
     * @apiName process__startByMyself
     *
     * @apiQuery {Number} userid               用户ID
     * @apiQuery {Number} page                  页码
     * @apiQuery {Number} page_size             每页条数
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function process__startByMyself()
    {
        $data['userid'] = Request::input('userid');
        $data['pageIndex'] = intval(Request::input('page'));
        $data['pageSize'] = intval(Request::input('page_size'));
        $process = Ihttp::ihttp_post($this->flow_url.'/api/v1/workflow/process/startByMyself', json_encode($data));
        info($process);
        $process = json_decode($process['data'] ?? '', true);
        if (!$process || $process['status'] != 200) {
            return Base::retError($process['message'] ?? 'fail');
        }
        return Base::retSuccess('success', Base::arrayKeyToUnderline($process['data']));
    }

    /**
     * @api {post} api/workflow/process/findProcNotify          10. 查询抄送我的流程
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup workflow
     * @apiName process__findProcNotify
     *
     * @apiQuery {Number} userid               用户ID
     * @apiQuery {Number} page                  页码
     * @apiQuery {Number} page_size             每页条数
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function process__findProcNotify()
    {
        $data['userid'] = Request::input('userid');
        $data['pageIndex'] = intval(Request::input('page'));
        $data['pageSize'] = intval(Request::input('page_size'));

        $process = Ihttp::ihttp_post($this->flow_url.'/api/v1/workflow/process/findProcNotify', json_encode($data));
        info($process);
        $process = json_decode($process['data'] ?? '', true);
        if (!$process || $process['status'] != 200) {
            return Base::retError($process['message'] ?? 'fail');
        }
        return Base::retSuccess('success', Base::arrayKeyToUnderline($process['data']));
    }

    /**
     * @api {get} api/workflow/process/detail          11. 根据流程ID查询流程详情
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
        //{"id":"2"}
        $data['id'] = intval(Request::input('id'));
        $workflow = $this->getProcessById($data['id']);
        return Base::retSuccess('success', $workflow);
    }

    // 审批机器人消息-审核人
    public function workflowMsg($type, $dialog, $botUser, $toUser, $process, $action = null)
    {
        $data = [
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
            $proc_msg->userid = $toUser->userid;
            $proc_msg->save();
        }
        return true;
    }

    // 根据ID获取流程
    public function getProcessById($id)
    {
        $data['id'] = intval($id);
        $process = Ihttp::ihttp_get($this->flow_url."/api/v1/workflow/process/findById?".http_build_query($data));
        $process = json_decode($process['data'] ?? '', true);
        if (!$process || $process['status'] != 200) {
            return Base::retError($process['message'] ?? 'fail');
        }
        return Base::arrayKeyToUnderline($process['data']);
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
        $user = Ihttp::ihttp_get($this->flow_url."/api/v1/user/identitylink/findParticipant?".http_build_query($data));
        $user = json_decode($user['data'] ?? '', true);
        if (!$user || $user['status'] != 200) {
            return Base::retError($user['message'] ?? 'fail');
        }
        return $user;
    }

}
