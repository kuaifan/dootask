<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Models\AbstractModel;
use App\Models\ProjectTask;
use App\Models\Report;
use App\Models\ReportAnalysis;
use App\Models\ReportLink;
use App\Models\ReportReceive;
use App\Models\User;
use App\Models\WebSocketDialogMsg;
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
     * @api {get} api/report/my 我发送的汇报
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup report
     * @apiName my
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
    public function my(): array
    {
        $user = User::auth();
        //
        $builder = Report::with(['receivesUser'])
            ->select(Report::LIST_FIELDS)
            ->whereUserid($user->userid);
        $keys = Request::input('keys');
        if (is_array($keys)) {
            if ($keys['key']) {
                if (str_contains($keys['key'], '@')) {
                    $builder->whereHas('sendUser', function ($q2) use ($keys) {
                        $q2->where("users.email", "LIKE", "%{$keys['key']}%");
                    });
                } elseif (Base::isNumber($keys['key'])) {
                    $builder->where(function ($query) use ($keys) {
                        $query->where("id", intval($keys['key']))
                            ->orWhere("title", "LIKE", "%{$keys['key']}%");
                    });
                } else {
                    $builder->where("title", "LIKE", "%{$keys['key']}%");
                }
            }
            if (in_array($keys['type'], [Report::WEEKLY, Report::DAILY])) {
                $builder->whereType($keys['type']);
            }
            if (is_array($keys['created_at'])) {
                if ($keys['created_at'][0] > 0) $builder->where('created_at', '>=', Base::newCarbon($keys['created_at'][0])->startOfDay());
                if ($keys['created_at'][1] > 0) $builder->where('created_at', '<=', Base::newCarbon($keys['created_at'][1])->endOfDay());
            }
        }
        $list = $builder->orderByDesc('created_at')->paginate(Base::getPaginate(50, 20));
        return Base::retSuccess('success', $list);
    }

    /**
     * @api {get} api/report/receive 我接收的汇报
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup report
     * @apiName receive
     *
     * @apiParam {Object} [keys]             搜索条件
     * - keys.key: 关键词
     * - keys.department_id: 部门ID
     * - keys.type: 汇报类型，weekly:周报，daily:日报
     * - keys.status: 状态，unread:未读，read:已读
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
        $builder = Report::with(['receivesUser'])
            ->select(Report::LIST_FIELDS);
        $builder->whereHas("receivesUser", function ($query) use ($user) {
            $query->where("report_receives.userid", $user->userid);
        });
        $keys = Request::input('keys');
        if (is_array($keys)) {
            if ($keys['key']) {
                if (str_contains($keys['key'], '@')) {
                    $builder->whereHas('sendUser', function ($q2) use ($keys) {
                        $q2->where("users.email", "LIKE", "%{$keys['key']}%");
                    });
                } elseif (Base::isNumber($keys['key'])) {
                    $builder->where(function ($query) use ($keys) {
                        $query->where("userid", intval($keys['key']))
                            ->orWhere("id", intval($keys['key']))
                            ->orWhere("title", "LIKE", "%{$keys['key']}%");
                    });
                } else {
                    $builder->where("title", "LIKE", "%{$keys['key']}%");
                }
            }
            if ($keys['department_id']) {
                $builder->whereHas('sendUser', function ($query) use ($keys) {
                    $query->where("users.department", "LIKE", "%,{$keys['department_id']},%");
                });
            }
            if (in_array($keys['type'], [Report::WEEKLY, Report::DAILY])) {
                $builder->whereType($keys['type']);
            }
            if (in_array($keys['status'], ['unread', 'read'])) {
                $builder->whereHas("receivesUser", function ($query) use ($user, $keys) {
                    $query->where("report_receives.userid", $user->userid)->where("report_receives.read", $keys['status'] === 'unread' ? 0 : 1);
                });
            }
            if (is_array($keys['created_at'])) {
                if ($keys['created_at'][0] > 0) $builder->where('created_at', '>=', Base::newCarbon($keys['created_at'][0])->startOfDay());
                if ($keys['created_at'][1] > 0) $builder->where('created_at', '<=', Base::newCarbon($keys['created_at'][1])->endOfDay());
            }
        }
        $list = $builder->orderByDesc('created_at')->paginate(Base::getPaginate(50, 20));
        if ($list->items()) {
            foreach ($list->items() as $item) {
                $item->receive_at = ReportReceive::query()->whereRid($item["id"])->whereUserid($user->userid)->value("receive_at");
            }
        }
        return Base::retSuccess('success', $list);
    }

    /**
     * @api {get} api/report/store 保存并发送工作汇报
     *
     * @apiDescription 需要token身份
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
                    "receive_at" => Carbon::now()->toDateTimeString(),
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
                ]);
            }
            $report->save();

            // 保存内容
            $content = $input["content"];
            preg_match_all("/<img\s+src=\"data:image\/(png|jpg|jpeg|webp);base64,(.*?)\"/s", $content, $matchs);
            foreach ($matchs[2] as $key => $text) {
                $tmpPath = "uploads/report/" . Carbon::parse($report->created_at)->format("Ym") . "/" . $report->id . "/attached/";
                Base::makeDir(public_path($tmpPath));
                $tmpPath .= md5($text) . "." . $matchs[1][$key];
                if (Base::saveContentImage(public_path($tmpPath), base64_decode($text))) {
                    $paramet = getimagesize(public_path($tmpPath));
                    $content = str_replace($matchs[0][$key], '<img src="' . Base::fillUrl($tmpPath) . '" original-width="' . $paramet[0] . '" original-height="' . $paramet[1] . '"', $content);
                }
            }
            $report->content = htmlspecialchars($content);
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
     * @api {get} api/report/template 生成汇报模板
     *
     * @apiDescription 需要token身份
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
        // 周报时预计算下一周期时间范围（下周）
        $next_start_time = null;
        $next_end_time = null;
        if ($type === Report::WEEKLY) {
            $next_start_time = Carbon::instance($start_time)->copy()->addWeek();
            $next_end_time = Carbon::instance($end_time)->copy()->addWeek();
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

        // 表格头部
        $labels = [
            Doo::translate('项目'),
            Doo::translate('任务'),
            Doo::translate('负责人'),
            Doo::translate('备注'),
        ];

        // 已完成的任务
        $completeDatas = [];
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
                // 排除取消态任务：不将已取消任务计入“已完成工作”
                if (ProjectTask::isCanceledFlowName($task->flow_item_name)) {
                    continue;
                }
                $complete_at = Carbon::parse($task->complete_at);
                $remark = $type == Report::WEEKLY ? ('<div style="text-align:center">[' . Doo::translate('周' . ['日', '一', '二', '三', '四', '五', '六'][$complete_at->dayOfWeek]) . ']</div>') : '&nbsp;';
                $completeDatas[] = [
                    $task->project->name,
                    $task->name,
                    $task->taskUser->where("owner", 1)->map(function ($item) {
                        return User::userid2nickname($item->userid);
                    })->implode(", "),
                    $remark,
                ];
            }
        }

        // 未完成的任务
        $unfinishedDatas = [];
        $unfinished_task = ProjectTask::buildUnfinishedTaskQuery($user->userid, $start_time, $end_time, true)->get();
        if ($unfinished_task->isNotEmpty()) {
            foreach ($unfinished_task as $task) {
                empty($task->end_at) || $end_at = Carbon::parse($task->end_at);
                $remark = (!empty($end_at) && $end_at->lt($now_dt)) ? '<div style="color:#ff0000;text-align:center">[' . Doo::translate('超期') . ']</div>' : '&nbsp;';
                $unfinishedDatas[] = [
                    $task->project->name,
                    $task->name,
                    $task->taskUser->where("owner", 1)->map(function ($item) {
                        return User::userid2nickname($item->userid);
                    })->implode(", "),
                    $remark,
                ];
            }
        }

        // 生成标题
        if ($type === Report::WEEKLY) {
            $title = $user->nickname . "的周报[" . $start_time->format("m/d") . "-" . $end_time->format("m/d") . "]";
            $title .= "[" . $start_time->month . "月第" . $start_time->weekOfMonth . "周]";
            $unfinishedTitle = '本周未完成的工作';
        } else {
            $title = $user->nickname . "的日报[" . $start_time->format("Y/m/d") . "]";
            $unfinishedTitle = '今日未完成的工作';
        }
        $title = Doo::translate($title);

        // 生成内容
        $contents = [];
        $contents[] = '<h2>' . Doo::translate('已完成工作') . '</h2>';
        $contents[] = view('report', [
            'labels' => $labels,
            'datas' => $completeDatas,
        ])->render();

        $contents[] = '<p>&nbsp;</p>';
        $contents[] = '<h2>' . Doo::translate($unfinishedTitle) . '</h2>';
        $contents[] = view('report', [
            'labels' => $labels,
            'datas' => $unfinishedDatas,
        ])->render();

        if ($type === Report::WEEKLY) {
            // 下周拟定计划：基于下周时间范围预生成候选任务
            $nextPlanDatas = [];
            if ($next_start_time && $next_end_time) {
                $next_tasks = ProjectTask::buildUnfinishedTaskQuery($user->userid, $next_start_time, $next_end_time, false)->get();
                if ($next_tasks->isNotEmpty()) {
                    foreach ($next_tasks as $task) {
                        $planTime = '-';
                        if ($task->start_at || $task->end_at) {
                            $startText = $task->start_at ? Carbon::parse($task->start_at)->format('Y-m-d H:i') : '';
                            $endText = $task->end_at ? Carbon::parse($task->end_at)->format('Y-m-d H:i') : '';
                            $planTime = trim($startText . ($endText ? (' ~ ' . $endText) : ''));
                        }
                        $nextPlanDatas[] = [
                            '[' . $task->project->name . '] ' . $task->name,
                            $planTime,
                            $task->taskUser->where("owner", 1)->map(function ($item) {
                                return User::userid2nickname($item->userid);
                            })->implode(", "),
                        ];
                    }
                }
            }
            $contents[] = '<p>&nbsp;</p>';
            $contents[] = "<h2>" . Doo::translate("下周拟定计划") . "[" . $next_start_time->format("m/d") . "-" . $next_end_time->format("m/d") . "]</h2>";
            $contents[] = view('report', [
                'labels' => [
                    Doo::translate('计划描述'),
                    Doo::translate('计划时间'),
                    Doo::translate('负责人'),
                ],
                'datas' => $nextPlanDatas,
            ])->render();
        }

        $data = [
            "time" => $start_time->toDateTimeString(),
            "sign" => $sign,
            "title" => $title,
            "content" => implode("", $contents),
        ];

        if ($one) {
            $data['id'] = $one->id;
        }
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/report/detail 报告详情
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup report
     * @apiName detail
     *
     * @apiParam {Number} [id]           报告ID
     * @apiParam {String} [code]         报告分享代码，与ID二选一，优先ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function detail(): array
    {
        $user = User::auth();
        //
        $id = intval(trim(Request::input("id")));
        $code = trim(Request::input("code"));
        //
        if (empty($id) && empty($code)) {
            return Base::retError("缺少ID参数");
        }
        //
        if (!empty($id)) {
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
        } else {
            $link = ReportLink::whereCode($code)->first();
            if (empty($link)) {
                return Base::retError("报告不存在或已被删除");
            }
            $one = Report::getOne($link->rid);
            $one->report_link = $link;
            $link->increment("num");
        }
        $analysis = ReportAnalysis::query()
            ->whereRid($one->id)
            ->whereUserid($user->userid)
            ->first();
        if ($analysis) {
            $updatedAt = $analysis->updated_at ? $analysis->updated_at->toDateTimeString() : null;
            $one->setAttribute('ai_analysis', [
                'id' => $analysis->id,
                'text' => $analysis->analysis_text,
                'model' => $analysis->model,
                'updated_at' => $updatedAt,
            ]);
        } else {
            $one->setAttribute('ai_analysis', null);
        }

        return Base::retSuccess("success", $one);
    }

    /**
     * @api {post} api/report/analysave 保存工作汇报 AI 分析
     *
     * @apiDescription 需要token身份，仅支持报告提交人或接收人保存分析
     * @apiVersion 1.0.0
     * @apiGroup report
     * @apiName analysave
     *
     * @apiParam {Number} id            报告ID
     * @apiParam {String} text          分析内容（Markdown）
     * @apiParam {String} [model]       分析使用的模型标识（可选）
     *
     * @apiSuccess {Number} ret         返回状态码（1正确、0错误）
     * @apiSuccess {String} msg         返回信息（错误描述）
     * @apiSuccess {Object} data        返回数据
     * @apiSuccess {Number} data.id     分析记录ID
     * @apiSuccess {String} data.text   分析内容（Markdown）
     * @apiSuccess {String} data.updated_at 最近更新时间
     */
    public function analysave(): array
    {
        $user = User::auth();
        $id = intval(Request::input("id"));
        if ($id <= 0) {
            return Base::retError("缺少ID参数");
        }
        $text = trim((string)Request::input('text', ''));
        if ($text === '') {
            return Base::retError("分析内容不能为空");
        }
        $model = trim((string)Request::input('model', ''));

        $report = Report::getOne($id);
        if (!$this->userCanAccessReport($report, $user)) {
            return Base::retError("无权访问该工作汇报");
        }

        $analysis = ReportAnalysis::query()
            ->whereRid($report->id)
            ->whereUserid($user->userid)
            ->first();

        if (!$analysis) {
            $analysis = ReportAnalysis::fillInstance([
                'rid' => $report->id,
                'userid' => $user->userid,
            ]);
        }

        $viewerRole = $user->profession ?: (is_array($user->identity) && !empty($user->identity) ? implode('/', $user->identity) : null);
        $focusMeta = null;
        $focus = Request::input('focus');
        if (is_array($focus)) {
            $focusMeta = array_filter(array_map('trim', $focus));
        } elseif (is_string($focus) && trim($focus) !== '') {
            $focusMeta = [trim($focus)];
        }

        $meta = array_filter([
            'viewer_role' => $viewerRole,
            'viewer_name' => $user->nickname ?? null,
            'focus' => $focusMeta,
        ], function ($value) {
            if (is_array($value)) {
                return !empty($value);
            }
            return $value !== null && $value !== '';
        });

        $analysis->updateInstance([
            'model' => $model,
            'analysis_text' => $text,
            'meta' => $meta,
        ]);
        $analysis->save();

        $analysis->refresh();

        return Base::retSuccess("success", [
            'id' => $analysis->id,
            'text' => $analysis->analysis_text,
            'model' => $analysis->model,
            'updated_at' => $analysis->updated_at ? $analysis->updated_at->toDateTimeString() : null,
        ]);
    }

    /**
     * @api {get} api/report/mark 标记已读/未读
     *
     * @apiDescription 需要token身份
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
     * @api {get} api/report/share 分享报告到消息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup report
     * @apiName share
     *
     * @apiParam {Number} id                报告id（组）
     * @apiParam {Array} dialogids          转发给的对话ID
     * @apiParam {Array} userids            转发给的成员ID
     * @apiParam {String} leave_message     转发留言
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function share()
    {
        $user = User::auth();
        //
        $id = Request::input('id');
        $dialogids = Request::input('dialogids');
        $userids = Request::input('userids');
        $leave_message = Request::input('leave_message');
        //
        if (is_array($id)) {
            if (count(Base::arrayRetainInt($id)) > 20) {
                return Base::retError("最多只能操作20条数据");
            }
            $builder = Report::whereIn("id", Base::arrayRetainInt($id));
        } else {
            $builder = Report::whereId(intval($id));
        }
        $reportMsgs = [];
        $builder ->chunkById(100, function ($list) use (&$reportMsgs, $user) {
            /** @var Report $item */
            foreach ($list as $item) {
                $reportLink = ReportLink::generateLink($item->id, $user->userid);
                $reportMsgs[] = "<a class=\"mention report\" href=\"{{RemoteURL}}single/report/detail/{$reportLink['code']}\" target=\"_blank\">%{$item->title}</a>";
            }
        });
        if (empty($reportMsgs)) {
            return Base::retError("报告不存在或已被删除");
        }
        $reportTag = count($reportMsgs) > 1 ? 'li' : 'p';
        $reportAttr = $reportTag === 'li' ? ' data-list="ordered"' : '';
        $reportMsgs = array_map(function ($item) use ($reportAttr, $reportTag) {
            return "<{$reportTag}{$reportAttr}>{$item}</{$reportTag}>";
        }, $reportMsgs);
        if ($reportTag === 'li') {
            array_unshift($reportMsgs, "<ol>");
            $reportMsgs[] = "</ol>";
        }
        if ($leave_message) {
            $reportMsgs[] = "<p>{$leave_message}</p>";
        }
        $msgText = implode("", $reportMsgs);
        //
        return WebSocketDialogMsg::sendMsgBatch($user, $userids, $dialogids, $msgText);
    }

    /**
     * @api {get} api/report/last_submitter 获取最后一次提交的接收人
     *
     * @apiDescription 需要token身份
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
     * @api {get} api/report/unread 获取未读
     *
     * @apiDescription 需要token身份
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
     * @api {get} api/report/read 标记汇报已读，可批量
     *
     * @apiDescription 需要token身份
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

    /**
     * 判断当前用户是否有权限查看/分析指定工作汇报
     * @param Report $report
     * @param User $user
     * @return bool
     */
    protected function userCanAccessReport(Report $report, User $user): bool
    {
        if ($report->userid === $user->userid) {
            return true;
        }

        return ReportReceive::query()
            ->whereRid($report->id)
            ->whereUserid($user->userid)
            ->exists();
    }
}
