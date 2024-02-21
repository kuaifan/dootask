<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Models\AbstractModel;
use App\Models\ProjectTask;
use App\Models\Report;
use App\Models\ReportReceive;
use App\Models\User;
use App\Module\Base;
use App\Module\Doo;
use App\Tasks\PushTask;
use Carbon\Carbon;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Validation\Rule;
use Request;
use Illuminate\Support\Facades\Validator;

/**
 * @apiDefine report
 *
 * 汇报
 */
class ReportController extends AbstractController
{
    /**
     * @api {get} api/report/my          01. 我发送的汇报
     *
     * @apiVersion 1.0.0
     * @apiGroup report
     * @apiName my
     *
     * @apiParam {Object} [keys]             搜索条件
     * - keys.type: 汇报类型，weekly:周报，daily:日报
     * - keys.created_at: 汇报时间
     * @apiParam {Number} [page]        当前页，默认:1
     * @apiParam {Number} [pagesize]    每页显示数量，默认:20，最大:50
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function my(): array
    {
        $user = User::auth();
        //
        $builder = Report::with(['receivesUser'])->whereUserid($user->userid);
        $keys = Request::input('keys');
        if (is_array($keys)) {
            if (in_array($keys['type'], [Report::WEEKLY, Report::DAILY])) {
                $builder->whereType($keys['type']);
            }
            if (is_array($keys['created_at'])) {
                if ($keys['created_at'][0] > 0) $builder->where('created_at', '>=', date('Y-m-d H:i:s', Base::dayTimeF($keys['created_at'][0])));
                if ($keys['created_at'][1] > 0) $builder->where('created_at', '<=', date('Y-m-d H:i:s', Base::dayTimeE($keys['created_at'][1])));
            }
        }
        $list = $builder->orderByDesc('created_at')->paginate(Base::getPaginate(50, 20));
        return Base::retSuccess('success', $list);
    }

    /**
     * @api {get} api/report/receive          02. 我接收的汇报
     *
     * @apiVersion 1.0.0
     * @apiGroup report
     * @apiName receive
     *
     * @apiParam {Object} [keys]             搜索条件
     * - keys.key: 关键词
     * - keys.type: 汇报类型，weekly:周报，daily:日报
     * - keys.created_at: 汇报时间
     * @apiParam {Number} [page]        当前页，默认:1
     * @apiParam {Number} [pagesize]    每页显示数量，默认:20，最大:50
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function receive(): array
    {
        $user = User::auth();
        $builder = Report::with(['receivesUser']);
        $builder->whereHas("receivesUser", function ($query) use ($user) {
            $query->where("report_receives.userid", $user->userid);
        });
        $keys = Request::input('keys');
        if (is_array($keys)) {
            if ($keys['key']) {
                $builder->where(function($query) use ($keys) {
                    $query->whereHas('sendUser', function ($q2) use ($keys) {
                        $q2->where("users.email", "LIKE", "%{$keys['key']}%");
                    })->orWhere("title", "LIKE", "%{$keys['key']}%");
                });
            }
            if (in_array($keys['type'], [Report::WEEKLY, Report::DAILY])) {
                $builder->whereType($keys['type']);
            }
            if (is_array($keys['created_at'])) {
                if ($keys['created_at'][0] > 0) $builder->where('created_at', '>=', date('Y-m-d H:i:s', Base::dayTimeF($keys['created_at'][0])));
                if ($keys['created_at'][1] > 0) $builder->where('created_at', '<=', date('Y-m-d H:i:s', Base::dayTimeE($keys['created_at'][1])));
            }
        }
        $list = $builder->orderByDesc('created_at')->paginate(Base::getPaginate(50, 20));
        if ($list->items()) {
            foreach ($list->items() as $item) {
                $item->receive_time = ReportReceive::query()->whereRid($item["id"])->whereUserid($user->userid)->value("receive_time");
            }
        }
        return Base::retSuccess('success', $list);
    }

    /**
     * @api {get} api/report/store          03. 保存并发送工作汇报
     *
     * @apiVersion 1.0.0
     * @apiGroup report
     * @apiName store
     *
     * @apiParam {Number} id            汇报ID，0为新建
     * @apiParam {String} [sign]        唯一签名，通过[api/report/template]接口返回
     * @apiParam {String} title         汇报标题
     * @apiParam {Array}  type          汇报类型，weekly:周报，daily:日报
     * @apiParam {Number} content       内容
     * @apiParam {Number} [receive]     汇报对象
     * @apiParam {Number} offset        时间偏移量
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function store(): array
    {
        $user = User::auth();
        //
        $input = [
            "id" => Request::input("id", 0),
            "sign" => Request::input("sign"),
            "title" => Request::input("title"),
            "type" => Request::input("type"),
            "content" => Request::input("content"),
            "receive" => Request::input("receive"),
            // 以当前日期为基础的周期偏移量。例如选择了上一周那么就是 -1，上一天同理。
            "offset" => Request::input("offset", 0),
        ];
        $validator = Validator::make($input, [
            'id' => 'numeric',
            'title' => 'required',
            'type' => ['required', Rule::in([Report::WEEKLY, Report::DAILY])],
            'content' => 'required',
            'offset' => ['numeric', 'max:0'],
        ], [
            'id.numeric' => 'ID只能是数字',
            'title.required' => '请填写标题',
            'type.required' => '请选择汇报类型',
            'type.in' => '汇报类型错误',
            'content.required' => '请填写汇报内容',
            'offset.numeric' => '工作汇报周期格式错误，只能是数字',
            'offset.max' => '只能提交当天/本周或者之前的的工作汇报',
        ]);
        if ($validator->fails())
            return Base::retError($validator->errors()->first());

        // 接收人
        if (is_array($input["receive"])) {
            // 删除当前登录人
            $input["receive"] = array_diff($input["receive"], [$user->userid]);

            // 查询用户是否存在
            if (count($input["receive"]) !== User::whereIn("userid", $input["receive"])->count())
                return Base::retError("用户不存在");

            foreach ($input["receive"] as $userid) {
                $input["receive_content"][] = [
                    "receive_time" => Carbon::now()->toDateTimeString(),
                    "userid" => $userid,
                    "read" => 0,
                ];
            }
        }

        // 在事务中运行
        return AbstractModel::transaction(function () use ($input, $user) {
            $id = $input["id"];
            if ($id) {
                // 编辑
                $report = Report::getOne($id);
                $report->updateInstance([
                    "title" => $input["title"],
                    "type" => $input["type"],
                    "content" => htmlspecialchars($input["content"]),
                ]);
            } else {
                // 生成唯一标识
                $sign = Base::isNumber($input["sign"]) ? $input["sign"] : Report::generateSign($input["type"], $input["offset"]);
                // 检查唯一标识是否存在
                if (empty($input["id"]) && Report::query()->whereSign($sign)->whereType($input["type"])->count() > 0) {
                    throw new ApiException("请勿重复提交工作汇报");
                }
                $report = Report::createInstance([
                    "sign" => $sign,
                    "title" => $input["title"],
                    "type" => $input["type"],
                    "userid" => $user->userid,
                    "content" => htmlspecialchars($input["content"]),
                ]);
            }
            $report->save();

            // 删除关联
            $report->Receives()->delete();
            if ($input["receive_content"]) {
                // 保存接收人
                $report->Receives()->createMany($input["receive_content"]);
            }

            // 推送消息
            $userids = [];
            foreach ($input["receive_content"] as $item) {
                $userids[] = $item['userid'];
            }
            if ($userids) {
                $params = [
                    'ignoreFd' => Request::header('fd'),
                    'userid' => $userids,
                    'msg' => [
                        'type' => 'report',
                        'action' => 'unreadUpdate',
                    ]
                ];
                Task::deliver(new PushTask($params, false));
            }
            //
            return Base::retSuccess('保存成功', $report);
        });
    }

    /**
     * @api {get} api/report/template          04. 生成汇报模板
     *
     * @apiVersion 1.0.0
     * @apiGroup report
     * @apiName template
     *
     * @apiParam {Array}  [type]         汇报类型，weekly:周报，daily:日报
     * @apiParam {Number} [offset]       偏移量
     * @apiParam {String} [date]         时间
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function template(): array
    {
        $user = User::auth();
        $type = trim(Request::input("type"));
        $offset = abs(intval(Request::input("offset", 0)));
        $id = intval(Request::input("offset", 0));
        $now_dt = trim(Request::input("date")) ? Carbon::parse(Request::input("date")) : Carbon::now();
        // 获取开始时间
        if ($type === Report::DAILY) {
            $start_time = Carbon::today();
            if ($offset > 0) {
                // 将当前时间调整为偏移量当天结束
                $now_dt->subDays($offset)->endOfDay();
                // 开始时间偏移量计算
                $start_time->subDays($offset);
            }
            $end_time = Carbon::instance($start_time)->endOfDay();
        } else {
            $start_time = Carbon::now();
            if ($offset > 0) {
                // 将当前时间调整为偏移量当周结束
                $now_dt->subWeeks($offset)->endOfDay();
                // 开始时间偏移量计算
                $start_time->subWeeks($offset);
            }
            $start_time->startOfWeek();
            $end_time = Carbon::instance($start_time)->endOfWeek();
        }
        // 生成唯一标识
        $sign = Report::generateSign($type, 0, Carbon::instance($start_time));
        $one = Report::whereSign($sign)->whereType($type)->first();
        // 如果已经提交了相关汇报
        if ($one && $id > 0) {
            return Base::retSuccess('success', [
                "id" => $one->id,
                "sign" => $one->sign,
                "title" => $one->title,
                "content" => $one->content,
            ]);
        }

        // 已完成的任务
        $completeContent = "";
        $complete_task = ProjectTask::query()
            ->whereNotNull("complete_at")
            ->whereBetween("complete_at", [$start_time->toDateTimeString(), $end_time->toDateTimeString()])
            ->whereHas("taskUser", function ($query) use ($user) {
                $query->where("userid", $user->userid);
            })
            ->orderByDesc("id")
            ->get();
        if ($complete_task->isNotEmpty()) {
            foreach ($complete_task as $task) {
                $complete_at = Carbon::parse($task->complete_at);
                $pre = $type == Report::WEEKLY ? ('<span>[' . Doo::translate('周' . ['日', '一', '二', '三', '四', '五', '六'][$complete_at->dayOfWeek]) . ']</span>&nbsp;') : '';
                $completeContent .= "<li>{$pre}[{$task->project->name}] {$task->name}</li>";
            }
        } else {
            $completeContent = '<li>&nbsp;</li>';
        }

        // 未完成的任务
        $unfinishedContent = "";
        $unfinished_task = ProjectTask::query()
            ->join("projects", "projects.id", "=", "project_tasks.project_id")
            ->whereNull("projects.archived_at")
            ->whereNull("project_tasks.complete_at")
            ->whereNotNull("project_tasks.start_at")
            ->where("project_tasks.end_at", "<", $end_time->toDateTimeString())
            ->whereHas("taskUser", function ($query) use ($user) {
                $query->where("userid", $user->userid);
            })
            ->select("project_tasks.*")
            ->orderByDesc("project_tasks.id")
            ->get();
        if ($unfinished_task->isNotEmpty()) {
            foreach ($unfinished_task as $task) {
                empty($task->end_at) || $end_at = Carbon::parse($task->end_at);
                $pre = (!empty($end_at) && $end_at->lt($now_dt)) ? '<span style="color:#ff0000;">[' . Doo::translate('超期') . ']</span>&nbsp;' : '';
                $unfinishedContent .= "<li>{$pre}[{$task->project->name}] {$task->name}</li>";
            }
        } else {
            $unfinishedContent = '<li>&nbsp;</li>';
        }
        // 生成标题
        if ($type === Report::WEEKLY) {
            $title = $user->nickname . "的周报[" . $start_time->format("m/d") . "-" . $end_time->format("m/d") . "]";
            $title .= "[" . $start_time->month . "月第" . $start_time->weekOfMonth . "周]";
        } else {
            $title = $user->nickname . "的日报[" . $start_time->format("Y/m/d") . "]";
        }
        // 生成内容
        $content = '<h2>' . Doo::translate('已完成工作') . '</h2><ol>' .
            $completeContent . '</ol><h2>' .
            Doo::translate('未完成的工作') . '</h2><ol>' .
            $unfinishedContent . '</ol>';
        if ($type === Report::WEEKLY) {
            $content .= "<h2>" . Doo::translate("下周拟定计划") . "[" . $start_time->addWeek()->format("m/d") . "-" . $end_time->addWeek()->format("m/d") . "]</h2><ol><li>&nbsp;</li></ol>";
        }
        $data = [
            "time" => $start_time->toDateTimeString(),
            "sign" => $sign,
            "title" => $title,
            "content" => $content,
            "complete_task" => $complete_task,
            "unfinished_task" => $unfinished_task,
        ];
        if ($one) {
            $data['id'] = $one->id;
        }
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/report/detail          05. 报告详情
     *
     * @apiVersion 1.0.0
     * @apiGroup report
     * @apiName detail
     *
     * @apiParam {Number} [id]           报告id
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function detail(): array
    {
        $user = User::auth();
        $id = intval(trim(Request::input("id")));
        if (empty($id))
            return Base::retError("缺少ID参数");

        $one = Report::getOne($id);
        $one->type_val = $one->getRawOriginal("type");

        // 标记为已读
        if (!empty($one->receivesUser)) {
            foreach ($one->receivesUser as $item) {
                if ($item->userid === $user->userid && $item->pivot->read === 0) {
                    $one->receivesUser()->updateExistingPivot($user->userid, [
                        "read" => 1,
                    ]);
                }
            }
        }

        return Base::retSuccess("success", $one);
    }

    /**
     * @api {get} api/report/mark          06. 标记已读/未读
     *
     * @apiVersion 1.0.0
     * @apiGroup report
     * @apiName mark
     *
     * @apiParam {Number} id            报告id（组）
     * @apiParam {Number} action        操作
     * - read: 标记已读（默认）
     * - unread: 标记未读
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function mark(): array
    {
        $user = User::auth();
        //
        $id = Request::input('id');
        $action = Request::input('action');
        //
        if (is_array($id)) {
            if (count(Base::arrayRetainInt($id)) > 100) {
                return Base::retError("最多只能操作100条数据");
            }
            $builder = Report::whereIn("id", Base::arrayRetainInt($id));
        } else {
            $builder = Report::whereId(intval($id));
        }
        $builder ->chunkById(100, function ($list) use ($action, $user) {
            /** @var Report $item */
            foreach ($list as $item) {
                $item->receivesUser()->updateExistingPivot($user->userid, [
                    "read" => $action === 'unread' ? 0 : 1,
                ]);
            }
        });
        return Base::retSuccess("操作成功");
    }

    /**
     * @api {get} api/report/last_submitter          07. 获取最后一次提交的接收人
     *
     * @apiVersion 1.0.0
     * @apiGroup report
     * @apiName last_submitter
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function last_submitter(): array
    {
        $one = Report::getLastOne();
        return Base::retSuccess("success", empty($one["receives"]) ? [] : $one["receives"]);
    }

    /**
     * @api {get} api/report/unread          08. 获取未读
     *
     * @apiVersion 1.0.0
     * @apiGroup report
     * @apiName unread
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function unread(): array
    {
        $user = User::auth();
        //
        $total = Report::select('reports.id')
            ->join('report_receives', 'report_receives.rid', '=', 'reports.id')
            ->where('report_receives.userid', $user->userid)
            ->where('report_receives.read', 0)
            ->count();
        //
        return Base::retSuccess("success", compact("total"));
    }

    /**
     * @api {get} api/report/read          09. 标记汇报已读，可批量
     *
     * @apiVersion 1.0.0
     * @apiGroup report
     * @apiName read
     *
     * @apiParam {String} [ids]      报告id
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function read(): array
    {
        $user = User::auth();
        $ids = Request::input("ids");
        if (!is_array($ids) && !is_string($ids)) {
            return Base::retError("请传入正确的工作汇报Id");
        }

        if (is_string($ids)) {
            $ids = Base::explodeInt($ids);
        }

        $data = Report::with(["receivesUser" => function (BelongsToMany $query) use ($user) {
            $query->where("report_receives.userid", $user->userid)->where("read", 0);
        }])->whereIn("id", $ids)->get();

        if ($data->isNotEmpty()) {
            foreach ($data as $item) {
                (!empty($item->receivesUser) && $item->receivesUser->isNotEmpty()) && $item->receivesUser()->updateExistingPivot($user->userid, [
                    "read" => 1,
                ]);
            }
        }
        return Base::retSuccess("success", $data);
    }
}
