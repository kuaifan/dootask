<?php

namespace App\Http\Controllers\Api;

use DB;
use Request;
use Redirect;
use Carbon\Carbon;
use App\Tasks\PushTask;
use App\Module\Doo;
use App\Models\File;
use App\Models\User;
use App\Module\Base;
use App\Module\Extranet;
use App\Module\TimeRange;
use App\Models\FileContent;
use App\Models\AbstractModel;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogUser;
use App\Models\WebSocketDialogMsgRead;
use App\Models\WebSocketDialogMsgTodo;
use App\Models\WebSocketDialogMsgTranslate;
use Hhxsv5\LaravelS\Swoole\Task\Task;

/**
 * @apiDefine dialog
 *
 * 对话
 */
class DialogController extends AbstractController
{
    /**
     * @api {get} api/dialog/lists          01. 对话列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName lists
     *
     * @apiParam {String} [timerange]        时间范围（如：1678248944,1678248944）
     * - 第一个时间: 读取在这个时间之后更新的数据
     * - 第二个时间: 读取在这个时间之后删除的数据ID（第1页附加返回数据: deleted_id）
     *
     * @apiParam {Number} [page]            当前页，默认:1
     * @apiParam {Number} [pagesize]        每页显示数量，默认:50，最大:100
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function lists()
    {
        $user = User::auth();
        //
        $timerange = TimeRange::parse(Request::input());
        //
        $data = WebSocketDialog::getDialogList($user->userid, $timerange->updated, $timerange->deleted);
        //
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/dialog/beyond          02. 列表外对话
     *
     * @apiDescription 需要token身份，列表外的未读对话 和 列表外的待办对话
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName beyond
     *
     * @apiParam {String} unread_at         在这个时间之前未读的数据
     * - 格式1：2021-01-01 00:00:00
     * - 格式2：1612051200
     * @apiParam {String} todo_at           在这个时间之前待办的数据
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function beyond()
    {
        $user = User::auth();
        //
        $unreadAt = Request::input('unread_at');
        $todoAt = Request::input('todo_at');
        //
        $data = WebSocketDialog::getDialogBeyond($user->userid, Base::newCarbon($unreadAt), Base::newCarbon($todoAt));
        //
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/dialog/search          03. 搜索会话
     *
     * @apiDescription 根据消息关键词搜索相关会话，需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName search
     *
     * @apiParam {String} key         消息关键词
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function search()
    {
        $user = User::auth();
        //
        $key = trim(Request::input('key'));
        if (empty($key)) {
            return Base::retError('请输入搜索关键词');
        }
        // 搜索会话
        $list = DB::table('web_socket_dialog_users as u')
            ->select(['d.*', 'u.top_at', 'u.last_at', 'u.mark_unread', 'u.silence', 'u.hide', 'u.color', 'u.updated_at as user_at'])
            ->join('web_socket_dialogs as d', 'u.dialog_id', '=', 'd.id')
            ->where('u.userid', $user->userid)
            ->where('d.name', 'LIKE', "%{$key}%")
            ->whereNull('d.deleted_at')
            ->orderByDesc('u.top_at')
            ->orderByDesc('u.last_at')
            ->take(20)
            ->get()
            ->map(function($item) use ($user) {
                return WebSocketDialog::synthesizeData($item, $user->userid);
            })
            ->all();
        // 搜索联系人
        if (count($list) < 20 && Base::judgeClientVersion("0.21.60")) {
            $users = User::select(User::$basicField)
                ->where(function ($query) use ($key) {
                    if (str_contains($key, "@")) {
                        $query->where("email", "like", "%{$key}%");
                    } else {
                        $query->where("nickname", "like", "%{$key}%")->orWhere("pinyin", "like", "%{$key}%");
                    }
                })->orderBy('userid')
                ->take(20 - count($list))
                ->get();
            $users->transform(function (User $item) use ($user) {
                $id = 'u:' . $item->userid;
                $lastAt = null;
                $lastMsg = null;
                $dialog = WebSocketDialog::getUserDialog($user->userid, $item->userid, now()->addDay());
                if ($dialog) {
                    $id = $dialog->id;
                    $row = WebSocketDialogMsg::whereDialogId($dialog->id)->orderByDesc('id')->first();
                    if ($row) {
                        $lastAt = Carbon::parse($row->created_at)->toDateTimeString();
                        $lastMsg = WebSocketDialog::lastMsgFormat($row->toArray());
                    }
                }
                return [
                    'id' => $id,
                    'type' => 'user',
                    'name' => $item->nickname,
                    'dialog_user' => $item,
                    'last_at' => $lastAt,
                    'last_msg' => $lastMsg,
                ];
            });
            $list = array_merge($list, $users->toArray());
        }
        // 搜索消息会话
        if (count($list) < 20) {
            $prefix = DB::getTablePrefix();
            if (preg_match('/[+\-><()~*"@]/', $key)) {
                $against = "\"{$key}\"";
            } else {
                $against = "*{$key}*";
            }
            $msgs = DB::table('web_socket_dialog_users as u')
                ->select(['d.*', 'u.top_at', 'u.last_at', 'u.mark_unread', 'u.silence', 'u.hide', 'u.color', 'u.updated_at as user_at', 'm.id as search_msg_id'])
                ->join('web_socket_dialogs as d', 'u.dialog_id', '=', 'd.id')
                ->join('web_socket_dialog_msgs as m', 'm.dialog_id', '=', 'd.id')
                ->where('u.userid', $user->userid)
                ->whereNull('d.deleted_at')
                ->whereRaw("MATCH({$prefix}m.key) AGAINST('{$against}' IN BOOLEAN MODE)")
                ->orderByDesc('m.id')
                ->take(20 - count($list))
                ->get()
                ->map(function($item) use ($user) {
                    return WebSocketDialog::synthesizeData($item, $user->userid);
                })
                ->all();
            $list = array_merge($list, $msgs);
        }
        //
        return Base::retSuccess('success', $list);
    }

    /**
     * @api {get} api/dialog/search/tag          04. 搜索标注会话
     *
     * @apiDescription 根据消息关键词搜索相关会话，需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName search__tag
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function search__tag()
    {
        $user = User::auth();
        // 搜索会话
        $msgs = DB::table('web_socket_dialog_users as u')
            ->select(['d.*', 'u.top_at', 'u.last_at', 'u.mark_unread', 'u.silence', 'u.hide', 'u.color', 'u.updated_at as user_at', 'm.id as search_msg_id'])
            ->join('web_socket_dialogs as d', 'u.dialog_id', '=', 'd.id')
            ->join('web_socket_dialog_msgs as m', 'm.dialog_id', '=', 'd.id')
            ->where('u.userid', $user->userid)
            ->whereNull('d.deleted_at')
            ->where('m.tag', '>', 0)
            ->orderByDesc('m.id')
            ->take(50)
            ->get()
            ->map(function($item) use ($user) {
                return WebSocketDialog::synthesizeData($item, $user->userid);
            })
            ->all();
        //
        return Base::retSuccess('success', $msgs);
    }

    /**
     * @api {get} api/dialog/one          05. 获取单个会话信息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName one
     *
     * @apiParam {Number} dialog_id         对话ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function one()
    {
        $user = User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        //
        $item = DB::table('web_socket_dialog_users as u')
            ->select(['d.*', 'u.top_at', 'u.last_at', 'u.mark_unread', 'u.silence', 'u.hide', 'u.color', 'u.updated_at as user_at'])
            ->join('web_socket_dialogs as d', 'u.dialog_id', '=', 'd.id')
            ->where('u.userid', $user->userid)
            ->where('d.id', $dialog_id)
            ->whereNull('d.deleted_at')
            ->first();
        return Base::retSuccess('success', WebSocketDialog::synthesizeData($item, $user->userid));
    }

    /**
     * @api {get} api/dialog/user          06. 获取会话成员
     *
     * @apiDescription  需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName user
     *
     * @apiParam {Number} dialog_id            会话ID
     * @apiParam {Number} [getuser]            获取会员详情（1: 返回会员昵称、邮箱等基本信息，0: 默认不返回）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function user()
    {
        User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $getuser = intval(Request::input('getuser', 0));
        //
        $dialog = WebSocketDialog::checkDialog($dialog_id);
        //
        if ($getuser === 1) {
            $data = $dialog->dialogUser->toArray();
            $array = [];
            foreach ($data as $item) {
                $res = User::userid2basic($item['userid']);
                if ($res) {
                    $array[] = array_merge($item, $res->toArray());
                }
            }
            $array = array_filter($array, function ($item) {
                return $item['userid'] > 0;
            });
        } else {
            $data = WebSocketDialogUser::select(['web_socket_dialog_users.*', 'users.bot'])
                ->join('users', 'web_socket_dialog_users.userid', '=', 'users.userid')
                ->where('web_socket_dialog_users.dialog_id', $dialog_id)
                ->orderBy('web_socket_dialog_users.id')
                ->get();
            $array = $data->toArray();
        }
        return Base::retSuccess('success', $array);
    }

    /**
     * @api {get} api/dialog/todo          07. 获取会话待办
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName todo
     *
     * @apiParam {Number} [dialog_id]            会话ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function todo()
    {
        $user = User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        //
        $builder = WebSocketDialogMsgTodo::whereUserid($user->userid)->whereDoneAt(null);
        if ($dialog_id > 0) {
            WebSocketDialog::checkDialog($dialog_id);
            $builder->whereDialogId($dialog_id);
        }
        //
        $list = $builder->orderByDesc('id')->take(50)->get();
        return Base::retSuccess("success", $list);
    }

    /**
     * @api {get} api/dialog/top          08. 会话置顶
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName top
     *
     * @apiParam {Number} dialog_id            会话ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function top()
    {
        $user = User::auth();
        $dialogId = intval(Request::input('dialog_id'));
        $dialogUser = WebSocketDialogUser::whereUserid($user->userid)->whereDialogId($dialogId)->first();
        if (!$dialogUser) {
            return Base::retError("会话不存在");
        }
        $dialogUser->top_at = $dialogUser->top_at ? null : Carbon::now();
        $dialogUser->save();
        return Base::retSuccess("success", [
            'id' => $dialogUser->dialog_id,
            'top_at' => $dialogUser->top_at?->toDateTimeString(),
        ]);
    }



    /**
     * @api {get} api/dialog/hide          09. 会话隐藏
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName hide
     *
     * @apiParam {Number} dialog_id            会话ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function hide()
    {
        $user = User::auth();
        $dialogId = intval(Request::input('dialog_id'));
        $dialogUser = WebSocketDialogUser::whereUserid($user->userid)->whereDialogId($dialogId)->first();
        if (!$dialogUser) {
            return Base::retError("会话不存在");
        }
        if ($dialogUser->top_at) {
            return Base::retError("置顶会话无法隐藏");
        }
        $dialogUser->hide = 1;
        $dialogUser->save();
        return Base::retSuccess("success", [
            'id' => $dialogUser->dialog_id,
            'hide' => 1,
        ]);
    }

    /**
     * @api {get} api/dialog/tel          10. 获取对方联系电话
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName tel
     *
     * @apiParam {Number} dialog_id            会话ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function tel()
    {
        $user = User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        //
        $dialog = WebSocketDialog::checkDialog($dialog_id);
        if ($dialog->type !== 'user') {
            return Base::retError("会话类型错误");
        }
        $dialogUser = $dialog->dialogUser->where('userid', '!=', $user->userid)->first();
        if (empty($dialogUser)) {
            return Base::retError("会话对象不存在");
        }
        $callUser = User::find($dialogUser->userid);
        if (empty($callUser) || empty($callUser->tel)) {
            return Base::retError("对方未设置联系电话");
        }
        if ($user->isTemp()) {
            return Base::retError("无法查看联系电话");
        }
        //
        $add = null;
        $res = WebSocketDialogMsg::sendMsg(null, $dialog->id, 'notice', [
            'notice' => $user->nickname . " 查看了 " . $callUser->nickname . " 的联系电话"
        ]);
        if (Base::isSuccess($res)) {
            $add = $res['data'];
        }
        //
        return Base::retSuccess("success", [
            'tel' => $callUser->tel,
            'add' => $add ?: null
        ]);
    }

    /**
     * @api {get} api/dialog/open/user          11. 打开会话
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName open__user
     *
     * @apiParam {Number} userid         对话会员ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function open__user()
    {
        $user = User::auth();
        //
        $userid = intval(Request::input('userid'));
        if (empty($userid)) {
            return Base::retError('错误的会话');
        }
        //
        $dialog = WebSocketDialog::checkUserDialog($user, $userid);
        if (empty($dialog)) {
            return Base::retError('打开会话失败');
        }
        $data = WebSocketDialog::synthesizeData($dialog->id, $user->userid);
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/dialog/msg/list          12. 获取消息列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__list
     *
     * @apiParam {Number} dialog_id         对话ID
     * @apiParam {Number} [msg_id]          消息ID
     * @apiParam {Number} [position_id]     此消息ID前后的数据
     * @apiParam {Number} [prev_id]         此消息ID之前的数据
     * @apiParam {Number} [next_id]         此消息ID之后的数据
     * - position_id、prev_id、next_id 只有一个有效，优先循序为：position_id > prev_id > next_id
     * @apiParam {String} [msg_type]        消息类型
     * - tag: 标记
     * - link: 链接
     * - text: 文本
     * - image: 图片
     * - file: 文件
     * - record: 录音
     * - meeting: 会议
     *
     * @apiParam {Number} [take]            获取条数，默认:50，最大:100
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__list()
    {
        $user = User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $msg_id = intval(Request::input('msg_id'));
        $position_id = intval(Request::input('position_id'));
        $prev_id = intval(Request::input('prev_id'));
        $next_id = intval(Request::input('next_id'));
        $msg_type = trim(Request::input('msg_type'));
        $take = Base::getPaginate(100, 50);
        $data = [];
        //
        $dialog = WebSocketDialog::checkDialog($dialog_id);
        $reDialog = true;
        //
        $builder = WebSocketDialogMsg::select([
            'web_socket_dialog_msgs.*',
            'read.mention',
            'read.dot',
            'read.read_at',
        ])->leftJoin('web_socket_dialog_msg_reads as read', function ($leftJoin) use ($user) {
            $leftJoin
                ->on('read.userid', '=', DB::raw($user->userid))
                ->on('read.msg_id', '=', 'web_socket_dialog_msgs.id');
        })->where('web_socket_dialog_msgs.dialog_id', $dialog_id);
        //
        if ($msg_type) {
            if ($msg_type === 'tag') {
                $builder->where('tag', '>', 0);
            } elseif ($msg_type === 'todo') {
                $builder->where('todo', '>', 0);
            } elseif ($msg_type === 'link') {
                $builder->whereLink(1);
            } elseif (in_array($msg_type, ['text', 'image', 'file', 'record', 'meeting'])) {
                $builder->whereMtype($msg_type);
            } else {
                return Base::retError('参数错误');
            }
            $reDialog = false;
        }
        if ($msg_id > 0) {
            $builder->whereReplyId($msg_id);
            $reDialog = false;
        }
        //
        if ($position_id > 0) {
            $array = $builder->clone()
                ->where('web_socket_dialog_msgs.id', '>=', $position_id)
                ->orderBy('web_socket_dialog_msgs.id')
                ->take(intval($take / 2))
                ->get();
            $prev_id = intval($array->last()?->id);
        }
        //
        $cloner = $builder->clone();
        if ($prev_id > 0) {
            $cloner->where('web_socket_dialog_msgs.id', '<=', $prev_id)->orderByDesc('web_socket_dialog_msgs.id');
            $reDialog = false;
        } elseif ($next_id > 0) {
            $cloner->where('web_socket_dialog_msgs.id', '>=', $next_id)->orderBy('web_socket_dialog_msgs.id');
            $reDialog = false;
        } else {
            $cloner->orderByDesc('web_socket_dialog_msgs.id');
        }
        $list = $cloner->take($take)->get()->sortByDesc('id', SORT_NUMERIC)->values();
        //
        if ($list->isNotEmpty()) {
            $list->transform(function (WebSocketDialogMsg $item) {
                $item->next_id = 0;
                $item->prev_id = 0;
                return $item;
            });
            $first = $list->first();
            $first->next_id = intval($builder->clone()
                ->where('web_socket_dialog_msgs.id', '>', $first->id)
                ->orderBy('web_socket_dialog_msgs.id')
                ->value('id'));
            $last = $list->last();
            $last->prev_id = intval($builder->clone()
                ->where('web_socket_dialog_msgs.id', '<', $last->id)
                ->orderByDesc('web_socket_dialog_msgs.id')
                ->value('id'));
        }
        $data['list'] = $list;
        $data['time'] = Base::time();
        // 记录当前打开的任务对话
        if ($dialog->type == 'group' && $dialog->group_type == 'task') {
            $user->task_dialog_id = $dialog->id;
            $user->save();
        }
        // 去掉标记未读
        $isMarkDialogUser = WebSocketDialogUser::whereDialogId($dialog->id)->whereUserid($user->userid)->whereMarkUnread(1)->first();
        if ($isMarkDialogUser) {
            $isMarkDialogUser->mark_unread = 0;
            $isMarkDialogUser->save();
        }
        //
        if ($reDialog) {
            $data['dialog'] = WebSocketDialog::synthesizeData($dialog, $user->userid, true);
            $data['todo'] = $data['dialog']['todo_num'] > 0 ? WebSocketDialogMsgTodo::whereDialogId($dialog->id)->whereUserid($user->userid)->whereDoneAt(null)->orderByDesc('id')->take(50)->get() : [];
            $data['top'] = $dialog->top_msg_id ? WebSocketDialogMsg::whereId($dialog->top_msg_id)->first() : null;
        }
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/dialog/msg/latest          13. 获取最新消息列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__latest
     *
     * @apiParam {Array} [dialogs]          对话ID列表
     * - 格式：[{id:会话ID, latest_id:此消息ID之后的数据}, ...]
     * @apiParam {Number} [take]            每个会话获取多少条，默认:25，最大:50
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__latest()
    {
        if (!Base::judgeClientVersion('0.34.47')) {
            return Base::retSuccess('success', ['data' => []]);
        }
        //
        $user = User::auth();
        //
        $dialogs = Request::input('dialogs');
        if (empty($dialogs) || !is_array($dialogs)) {
            return Base::retError('参数错误');
        }
        $builder = WebSocketDialogMsg::select([
            'web_socket_dialog_msgs.*',
            'read.mention',
            'read.dot',
            'read.read_at',
        ])->leftJoin('web_socket_dialog_msg_reads as read', function ($leftJoin) use ($user) {
            $leftJoin
                ->on('read.userid', '=', DB::raw($user->userid))
                ->on('read.msg_id', '=', 'web_socket_dialog_msgs.id');
        });
        $data = [];
        $num = 0;
        foreach ($dialogs as $item) {
            $dialog_id = intval($item['id']);
            $latest_id = intval($item['latest_id']);
            if ($dialog_id <= 0) {
                continue;
            }
            if ($num >= 5) {
                break;
            }
            $num++;
            WebSocketDialog::checkDialog($dialog_id);
            //
            $cloner = $builder->clone();
            $cloner->where('web_socket_dialog_msgs.dialog_id', $dialog_id);
            if ($latest_id > 0) {
                $cloner->where('web_socket_dialog_msgs.id', '>', $latest_id);
            }
            $cloner->orderByDesc('web_socket_dialog_msgs.id');
            $list = $cloner->take(Base::getPaginate(50, 25, 'take'))->get();
            if ($list->isNotEmpty()) {
                $data = array_merge($data, $list->toArray());
            }
        }
        return Base::retSuccess('success', compact('data'));
    }

    /**
     * @api {get} api/dialog/msg/search          14. 搜索消息位置
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__search
     *
     * @apiParam {Number} dialog_id         对话ID
     * @apiParam {String} key               搜索关键词
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__search()
    {
        User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $key = trim(Request::input('key'));
        //
        if (empty($key)) {
            return Base::retError('关键词不能为空');
        }
        //
        WebSocketDialog::checkDialog($dialog_id);
        //
        $data = WebSocketDialogMsg::whereDialogId($dialog_id)
            ->where('key', 'LIKE', "%{$key}%")
            ->take(200)
            ->pluck('id');
        return Base::retSuccess('success', compact('data'));
    }

    /**
     * @api {get} api/dialog/msg/one          15. 获取单条消息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__one
     *
     * @apiParam {Number} msg_id            消息ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__one()
    {
        User::auth();
        //
        $msg_id = intval(Request::input('msg_id'));
        //
        $msg = WebSocketDialogMsg::whereId($msg_id)->first();
        if (empty($msg)) {
            return Base::retError("消息不存在或已被删除");
        }
        WebSocketDialog::checkDialog($msg->dialog_id);
        //
        return Base::retSuccess('success', $msg);
    }

    /**
     * @api {get} api/dialog/msg/dot          16. 聊天消息去除点
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__dot
     *
     * @apiParam {Number} id         消息ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__dot()
    {
        $user = User::auth();
        //
        $id = intval(Request::input('id'));
        //
        $msg = WebSocketDialogMsg::find($id);
        if (empty($msg)) {
            return Base::retError("消息不存在或已被删除");
        }
        //
        WebSocketDialogMsgRead::whereMsgId($id)->whereUserid($user->userid)->change(['dot' => 0]);
        //
        return Base::retSuccess('success', [
            'id' => $msg->id,
            'dot' => 0,
        ]);
    }

    /**
     * @api {get} api/dialog/msg/read          17. 已读聊天消息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__read
     *
     * @apiParam {Object} id         消息ID（组）
     * - 1、多个ID用逗号分隔，如：1,2,3
     * - 2、另一种格式：{"id": "[会话ID]"}，如：{"2": 0, "3": 10}
     * -- 会话ID：标记id之后的消息已读
     * -- 其他：标记已读
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__read()
    {
        $user = User::auth();
        //
        $id = Request::input('id');
        $ids = $id && is_array($id) ? $id : array_fill_keys(Base::explodeInt($id), 'r');
        //
        $dialogIds = [];
        $markIds = [];
        WebSocketDialogMsg::whereIn('id', array_keys($ids))->chunkById(100, function($list) use ($ids, $user, &$dialogIds, &$markIds) {
            /** @var WebSocketDialogMsg $item */
            foreach ($list as $item) {
                $item->readSuccess($user->userid);
                $dialogIds[$item->dialog_id] = $item->dialog_id;
                if ($ids[$item->id] == $item->dialog_id) {
                    $markIds[$item->dialog_id] = min($item->id, $markIds[$item->dialog_id] ?? 0);
                }
            }
        });
        //
        foreach ($markIds as $dialogId => $msgId) {
            WebSocketDialogMsgRead::whereDialogId($dialogId)
                ->whereUserid($user->userid)
                ->whereReadAt(null)
                ->where('msg_id', '>=', $msgId)
                ->chunkById(100, function ($list) {
                    WebSocketDialogMsgRead::onlyMarkRead($list);
                });
        }
        //
        $data = [];
        $dialogUsers = WebSocketDialogUser::with(['webSocketDialog'])->whereUserid($user->userid)->whereIn('dialog_id', array_values($dialogIds))->get();
        foreach ($dialogUsers as $dialogUser) {
            if (!$dialogUser->webSocketDialog) {
                continue;
            }
            $dialogUser->updated_at = Carbon::now();
            $dialogUser->save();
            //
            $unreadData = WebSocketDialog::generateUnread($dialogUser->dialog_id, $user->userid);
            $data[] = [
                'id' => $dialogUser->dialog_id,
                'unread' => $unreadData['unread'],
                'unread_one' => $unreadData['unread_one'],
                'mention' => $unreadData['mention'],
                'mention_ids' => $unreadData['mention_ids'],
                'user_at' =>  Carbon::parse($dialogUser->updated_at)->toDateTimeString('millisecond'),
                'user_ms' => Carbon::parse($dialogUser->updated_at)->valueOf()
            ];
        }
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/dialog/msg/unread          18. 获取未读消息数据
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__unread
     *
     * @apiParam {Number} dialog_id         对话ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     * @apiSuccessExample {json} data:
    {
        "id": 43,
        "unread": 308,
        "mention": 11,
        "user_at": "2020-12-12 00:00:00.000",
        "user_ms": 1677558147167,
    }
     */
    public function msg__unread()
    {
        $dialog_id = intval(Request::input('dialog_id'));
        //
        $dialogUser = WebSocketDialogUser::with(['webSocketDialog'])->whereDialogId($dialog_id)->whereUserid(User::userid())->first();
        if (empty($dialogUser?->webSocketDialog)) {
            return Base::retError('会话不存在');
        }
        $unreadData = WebSocketDialog::generateUnread($dialog_id, $dialogUser->userid);
        //
        return Base::retSuccess('success', [
            'id' => $dialog_id,
            'unread' => $unreadData['unread'],
            'unread_one' => $unreadData['unread_one'],
            'mention' => $unreadData['mention'],
            'mention_ids' => $unreadData['mention_ids'],
            'user_at' => Carbon::parse($dialogUser->updated_at)->toDateTimeString('millisecond'),
            'user_ms' => Carbon::parse($dialogUser->updated_at)->valueOf()
        ]);
    }

    /**
     * @api {get} api/dialog/msg/checked          19. 设置消息checked
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__checked
     *
     * @apiParam {Number} dialog_id         对话ID
     * @apiParam {Number} msg_id            消息ID
     * @apiParam {Number} index             li 位置
     * @apiParam {Number} checked           标记、取消标记
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     * @apiSuccessExample {json} data:
    {
        "id": 43,
        "msg": {
            // ....
        },
    }
     */
    public function msg__checked()
    {
        $user = User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $msg_id = intval(Request::input('msg_id'));
        $index = intval(Request::input('index'));
        $checked = intval(Request::input('checked'));
        //
        $dialogMsg = WebSocketDialogMsg::whereId($msg_id)->whereDialogId($dialog_id)->first();
        if (empty($dialogMsg)) {
            return Base::retError('消息不存在');
        }
        if ($dialogMsg->userid != $user->userid) {
            return Base::retError('仅支持修改自己的消息');
        }
        if ($dialogMsg->type !== 'text') {
            return Base::retError('仅支持文本消息');
        }
        //
        $oldMsg = Base::json2array($dialogMsg->getRawOriginal('msg'));
        $oldText = $oldMsg['text'] ?? '';
        $newText = preg_replace_callback('/<li[^>]*>/i', function ($matches) use ($index, $checked) {
            static $i = 0;
            if ($i++ == $index) {
                $checked = $checked ? 'checked' : 'unchecked';
                return '<li data-list="' . $checked . '">';
            }
            return $matches[0];
        }, $oldText);
        //
        $dialogMsg->updateInstance([
            'msg' => array_merge($oldMsg, ['text' => $newText]),
        ]);
        $dialogMsg->save();
        //
        return Base::retSuccess('success', [
            'id' => $dialogMsg->id,
            'msg' => $dialogMsg->msg,
        ]);
    }

    /**
     * @api {post} api/dialog/msg/stream          20. 通知成员监听消息
     *
     * @apiDescription 通知指定会员EventSource监听流动消息
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__stream
     *
     * @apiParam {Number} dialog_id      对话ID
     * @apiParam {Number} userid         通知会员ID
     * @apiParam {String} stream_url     流动消息地址
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__stream()
    {
        // $dialog_id = intval(Request::input('dialog_id'));
        $userid = intval(Request::input('userid'));
        $stream_url = trim(Request::input('stream_url'));
        //
        if ($userid <= 0) {
            return Base::retError('参数错误');
        }
        //
        $params = [
            'userid' => $userid,
            'msg' => [
                'type' => 'msgStream',
                'stream_url' => $stream_url,
            ]
        ];
        $task = new PushTask($params, false);
        Task::deliver($task);
        //
        return Base::retSuccess('success');
    }

    /**
     * @api {post} api/dialog/msg/sendtext          21. 发送消息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__sendtext
     *
     * @apiParam {Number} dialog_id         对话ID
     * @apiParam {String} text              消息内容
     * @apiParam {String} [key]             搜索关键词 (不设置根据内容自动生成)
     * @apiParam {String} [text_type]       消息类型
     * - html: HTML（默认）
     * - md: MARKDOWN
     * @apiParam {Number} [update_id]       更新消息ID（优先大于 reply_id）
     * @apiParam {String} [update_mark]     是否更新标记
     * - no: 不标记（仅机器人支持）
     * - yes: 标记（默认）
     * @apiParam {Number} [reply_id]        回复ID
     * @apiParam {String} [silence]         是否静默发送
     * - no: 正常发送（默认）
     * - yes: 静默发送
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__sendtext()
    {
        $user = User::auth();
        $user->checkChatInformation();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $dialog_ids = trim(Request::input('dialog_ids'));
        $update_id = intval(Request::input('update_id'));
        $update_mark = !($user->bot && in_array(strtolower(trim(Request::input('update_mark'))), ['no', 'false', '0']));
        $reply_id = intval(Request::input('reply_id'));
        $text = trim(Request::input('text'));
        $key = trim(Request::input('key'));
        $text_type = strtolower(trim(Request::input('text_type')));
        $silence = in_array(strtolower(trim(Request::input('silence'))), ['yes', 'true', '1']);
        $markdown = in_array($text_type, ['md', 'markdown']);
        //
        $result = [];
        $dialogIds = $dialog_ids ? explode(',', $dialog_ids) : [$dialog_id ?: 0];
        foreach ($dialogIds as $dialog_id) {
            //
            WebSocketDialog::checkDialog($dialog_id);
            //
            if ($update_id > 0) {
                $action = $update_mark ? "update-$update_id" : "change-$update_id";
            } elseif ($reply_id > 0) {
                $action = "reply-$reply_id";
            } else {
                $action = "";
            }
            //
            if (!$markdown) {
                $text = WebSocketDialogMsg::formatMsg($text, $dialog_id);
            }
            $strlen = mb_strlen($text);
            $noimglen = mb_strlen(preg_replace("/<img[^>]*?>/i", "", $text));
            if ($strlen < 1) {
                return Base::retError('消息内容不能为空');
            }
            if ($noimglen > 200000) {
                return Base::retError('消息内容最大不能超过200000字');
            }
            if ($noimglen > 5000) {
                // 内容过长转成文件发送
                $path = "uploads/chat/" . date("Ym") . "/" . $dialog_id . "/";
                Base::makeDir(public_path($path));
                $path = $path . md5($text) . ".htm";
                $file = public_path($path);
                file_put_contents($file, $text);
                $size = filesize(public_path($path));
                if (empty($size)) {
                    return Base::retError('消息发送保存失败');
                }
                $ext = $markdown ? 'md' : 'htm';
                $fileData = [
                    'name' => "LongText-{$strlen}.{$ext}",
                    'size' => $size,
                    'file' => $file,
                    'path' => $path,
                    'url' => Base::fillUrl($path),
                    'thumb' => '',
                    'width' => -1,
                    'height' => -1,
                    'ext' => $ext,
                ];
                if (empty($key)) {
                    $key = mb_substr(strip_tags($text), 0, 200);
                }
                $result = WebSocketDialogMsg::sendMsg($action, $dialog_id, 'file', $fileData, $user->userid, false, false, $silence, $key);
            } else {
                $msgData = ['text' => $text];
                if ($markdown) {
                    $msgData['type'] = 'md';
                }
                $result = WebSocketDialogMsg::sendMsg($action, $dialog_id, 'text', $msgData, $user->userid, false, false, $silence, $key);
            }
        }
        return $result;
    }

    /**
     * @api {post} api/dialog/msg/sendrecord          22. 发送语音
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__sendrecord
     *
     * @apiParam {Number} dialog_id             对话ID
     * @apiParam {Number} [reply_id]            回复ID
     * @apiParam {String} base64                语音base64
     * @apiParam {Number} duration              语音时长（毫秒）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__sendrecord()
    {
        $user = User::auth();
        $user->checkChatInformation();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $reply_id = intval(Request::input('reply_id'));
        //
        WebSocketDialog::checkDialog($dialog_id);
        //
        $action = $reply_id > 0 ? "reply-$reply_id" : "";
        $path = "uploads/chat/" . date("Ym") . "/" . $dialog_id . "/";
        $base64 = Request::input('base64');
        $duration = intval(Request::input('duration'));
        if ($duration < 600) {
            return Base::retError('说话时间太短');
        }
        $data = Base::record64save([
            "base64" => $base64,
            "path" => $path,
        ]);
        if (Base::isError($data)) {
            return Base::retError($data['msg']);
        } else {
            $recordData = $data['data'];
            $recordData['size'] *= 1024;
            $recordData['duration'] = $duration;
            return WebSocketDialogMsg::sendMsg($action, $dialog_id, 'record', $recordData, $user->userid);
        }
    }

    /**
     * @api {post} api/dialog/msg/sendfile          23. 文件上传
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__sendfile
     *
     * @apiParam {Number} dialog_id             对话ID
     * @apiParam {Number} [reply_id]            回复ID
     * @apiParam {Number} [image_attachment]    图片是否也存到附件
     * @apiParam {String} [filename]            post-文件名称
     * @apiParam {String} [image64]             post-base64图片（二选一）
     * @apiParam {File} [files]                 post-文件对象（二选一）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__sendfile()
    {
        $user = User::auth();
        //
        $dialogIds = [intval(Request::input('dialog_id'))];
        $replyId = intval(Request::input('reply_id'));
        $imageAttachment = intval(Request::input('image_attachment'));
        $files = Request::file('files');
        $image64 = Request::input('image64');
        $fileName = Request::input('filename');
        return WebSocketDialog::sendMsgFiles($user, $dialogIds, $files, $image64, $fileName, $replyId, $imageAttachment);
    }

    /**
     * @api {post} api/dialog/msg/sendfiles          24. 群发文件上传
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__sendfile
     *
     * @apiParam {String} user_ids              用户ID
     * @apiParam {String} dialog_ids            对话ID（user_ids 二选一）
     * @apiParam {Number} [reply_id]            回复ID
     * @apiParam {Number} [image_attachment]    图片是否也存到附件
     * @apiParam {String} [filename]            post-文件名称
     * @apiParam {String} [image64]             post-base64图片（二选一）
     * @apiParam {File} [files]                 post-文件对象（二选一）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__sendfiles()
    {
        $user = User::auth();
        //
        $files = Request::file('files');
        $image64 = Request::input('image64');
        $fileName = Request::input('filename');
        $replyId = intval(Request::input('reply_id'));
        $imageAttachment = intval(Request::input('image_attachment'));
        //
        $dialogIds = trim(Request::input('dialog_ids'));
        if ($dialogIds) {
            $dialogIds = explode(',', $dialogIds);
        } else {
            $dialogIds = [];
        }
        // 用户
        $userIds = trim(Request::input('user_ids'));
        if ($userIds) {
            $userIds = explode(',', $userIds);
            foreach ($userIds as $userId) {
                $dialog = WebSocketDialog::checkUserDialog($user, $userId);
                if (empty($dialog)) {
                    return Base::retError('打开会话失败');
                }
                $dialogIds[] = $dialog->id;
            }
        }
        //
        if (empty($dialogIds)) {
            return Base::retError('找不到会话');
        }
        //
        return WebSocketDialog::sendMsgFiles($user, $dialogIds, $files, $image64, $fileName, $replyId, $imageAttachment);
    }

    /**
     * @api {get} api/dialog/msg/sendfileid          25. 通过文件ID发送文件
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__sendfileid
     *
     * @apiParam {Number} file_id           消息ID
     * @apiParam {Array} dialogids          转发给的对话ID
     * @apiParam {Array} userids            转发给的成员ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__sendfileid()
    {
        $user = User::auth();
        //
        $file_id = intval(Request::input("file_id"));
        $dialogids = Request::input('dialogids');
        $userids = Request::input('userids');
        //
        if (empty($dialogids) && empty($userids)) {
            return Base::retError("请选择转发对话或成员");
        }
        //
        $file = File::permissionFind($file_id, $user);
        $fileLink = $file->getShareLink($user->userid);
        $fileMsg = "<a class=\"mention file\" href=\"{{RemoteURL}}single/file/{$fileLink['code']}\" target=\"_blank\">~{$file->getNameAndExt()}</a>";
        //
        return AbstractModel::transaction(function() use ($user, $fileMsg, $userids, $dialogids) {
            $msgs = [];
            $already = [];
            if ($dialogids) {
                if (!is_array($dialogids)) {
                    $dialogids = [$dialogids];
                }
                foreach ($dialogids as $dialogid) {
                    $res = WebSocketDialogMsg::sendMsg(null, $dialogid, 'text', ['text' => $fileMsg], $user->userid);
                    if (Base::isSuccess($res)) {
                        $msgs[] = $res['data'];
                        $already[] = $dialogid;
                    }
                }
            }
            if ($userids) {
                if (!is_array($userids)) {
                    $userids = [$userids];
                }
                foreach ($userids as $userid) {
                    if (!User::whereUserid($userid)->exists()) {
                        continue;
                    }
                    $dialog = WebSocketDialog::checkUserDialog($user, $userid);
                    if ($dialog && !in_array($dialog->id, $already)) {
                        $res = WebSocketDialogMsg::sendMsg(null, $dialog->id, 'text', ['text' => $fileMsg], $user->userid);
                        if (Base::isSuccess($res)) {
                            $msgs[] = $res['data'];
                        }
                    }
                }
            }
            return Base::retSuccess('发送成功', [
                'msgs' => $msgs
            ]);
        });
    }

    /**
     * @api {post} api/dialog/msg/sendanon          26. 发送匿名消息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__sendanon
     *
     * @apiParam {Number} userid            对方会员ID
     * @apiParam {String} text              消息内容
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__sendanon()
    {
        User::auth();
        //
        $userid = intval(Request::input('userid'));
        $text = trim(Request::input('text'));
        //
        $anonMessage = Base::settingFind('system', 'anon_message', 'open');
        if ($anonMessage != 'open') {
            return Base::retError("匿名消息功能暂停使用");
        }
        //
        $toUser = User::whereUserid($userid)->first();
        if (empty($toUser) || $toUser->bot) {
            return Base::retError("匿名消息仅允许发送给个人");
        }
        if ($toUser->isDisable()) {
            return Base::retError("对方已离职");
        }
        $strlen = mb_strlen($text);
        if ($strlen < 1) {
            return Base::retError('消息内容不能为空');
        }
        if ($strlen > 2000) {
            return Base::retError('消息内容最大不能超过2000字');
        }
        //
        $botUser = User::botGetOrCreate('anon-msg');
        if (empty($botUser)) {
            return Base::retError('匿名机器人不存在');
        }
        $dialog = WebSocketDialog::checkUserDialog($botUser, $toUser->userid);
        if (empty($dialog)) {
            return Base::retError('匿名机器人会话不存在');
        }
        return WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
            'type' => 'content',
            'content' => $text,
        ], $botUser->userid, true);
    }

    /**
     * @api {post} api/dialog/msg/sendlocation          27. 发送位置消息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__sendlocation
     *
     * @apiParam {Number} dialog_id             对话ID
     * @apiParam {String} type                  位置类型
     * - bd: 百度地图
     * @apiParam {Number} lng                   经度
     * @apiParam {Number} lat                   纬度
     * @apiParam {String} title                 位置名称
     * @apiParam {Number} [distance]            距离（米）
     * @apiParam {String} [address]             位置地址
     * @apiParam {String} [thumb]               预览图片（url）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__sendlocation()
    {
        $user = User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $type = strtolower(trim(Request::input('type')));
        $lng = floatval(Request::input('lng'));
        $lat = floatval(Request::input('lat'));
        $title = trim(Request::input('title'));
        $distance = intval(Request::input('distance'));
        $address = trim(Request::input('address'));
        $thumb = trim(Request::input('thumb'));
        //
        if (empty($lng) || $lng < -180 || $lng > 180
            || empty($lat) || $lat < -90 || $lat > 90) {
            return Base::retError('经纬度错误');
        }
        if (empty($title)) {
            return Base::retError('位置名称不能为空');
        }
        //
        WebSocketDialog::checkDialog($dialog_id);
        //
        if ($type == 'bd') {
            $msgData = [
                'type' => $type,
                'lng' => $lng,
                'lat' => $lat,
                'title' => $title,
                'distance' => $distance,
                'address' => $address,
                'thumb' => $thumb,
            ];
            return WebSocketDialogMsg::sendMsg(null, $dialog_id, 'location', $msgData, $user->userid);
        }
        return Base::retError('位置类型错误');
    }

    /**
     * @api {get} api/dialog/msg/readlist          28. 获取消息阅读情况
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__readlist
     *
     * @apiParam {Number} msg_id            消息ID（需要是消息的发送人）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__readlist()
    {
        $user = User::auth();
        //
        $msg_id = intval(Request::input('msg_id'));
        //
        $msg = WebSocketDialogMsg::whereId($msg_id)->whereUserid($user->userid)->first();
        if (empty($msg)) {
            return Base::retError('不是发送人');
        }
        //
        $read = WebSocketDialogMsgRead::whereMsgId($msg_id)->get();
        return Base::retSuccess('success', $read ?: []);
    }

    /**
     * @api {get} api/dialog/msg/detail          29. 消息详情
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__detail
     *
     * @apiParam {Number} msg_id            消息ID
     * @apiParam {String} only_update_at    仅获取update_at字段
     * - no (默认)
     * - yes
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__detail()
    {
        User::auth();
        //
        $msg_id = intval(Request::input('msg_id'));
        $only_update_at = Request::input('only_update_at', 'no');
        //
        $dialogMsg = WebSocketDialogMsg::whereId($msg_id)->first();
        if (empty($dialogMsg)) {
            return Base::retError("文件不存在");
        }
        //
        if ($only_update_at == 'yes') {
            return Base::retSuccess('success', [
                'id' => $dialogMsg->id,
                'update_at' => Carbon::parse($dialogMsg->updated_at)->toDateTimeString()
            ]);
        }
        //
        $data = $dialogMsg->toArray();
        //
        if ($data['type'] == 'file') {
            $msg = Base::json2array($dialogMsg->getRawOriginal('msg'));
            $msg = File::formatFileData($msg);
            $data['content'] = $msg['content'];
            $data['file_mode'] = $msg['file_mode'];
        }
        //
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/dialog/msg/download          30. 文件下载
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__download
     *
     * @apiParam {Number} msg_id                消息ID
     * @apiParam {String} down                  直接下载
     * - yes: 下载（默认）
     * - preview: 转预览地址
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__download()
    {
        User::auth();
        //
        $msg_id = intval(Request::input('msg_id'));
        $down = Request::input('down', 'yes');
        //
        $msg = WebSocketDialogMsg::whereId($msg_id)->first();
        if (empty($msg)) {
            abort(403, "This file not exist.");
        }
        if ($msg->type != 'file') {
            abort(403, "This file not support download.");
        }
        $array = Base::json2array($msg->getRawOriginal('msg'));
        //
        if ($down === 'preview') {
            return Redirect::to(FileContent::toPreviewUrl($array));
        }
        //
        $filePath = public_path($array['path']);
        return Base::streamDownload($filePath, $array['name']);
    }

    /**
     * @api {get} api/dialog/msg/withdraw          31. 聊天消息撤回
     *
     * @apiDescription 消息撤回限制24小时内，需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__withdraw
     *
     * @apiParam {Number} msg_id            消息ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__withdraw()
    {
        $user = User::auth();
        $msg_id = intval(Request::input("msg_id"));
        $msg = WebSocketDialogMsg::whereId($msg_id)->whereUserid($user->userid)->first();
        if (empty($msg)) {
            return Base::retError("消息不存在或已被删除");
        }
        $msg->withdrawMsg();
        return Base::retSuccess("success");
    }

    /**
     * @api {get} api/dialog/msg/voice2text          32. 语音消息转文字
     *
     * @apiDescription 将语音消息转文字，需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__voice2text
     *
     * @apiParam {Number} msg_id            消息ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__voice2text()
    {
        $user = User::auth();
        //
        $msg_id = intval(Request::input("msg_id"));
        $msg = WebSocketDialogMsg::whereId($msg_id)->first();
        if (empty($msg)) {
            return Base::retError("消息不存在或已被删除");
        }
        if ($msg->type !== 'record') {
            return Base::retError("仅支持语音消息");
        }
        $msgData = Base::json2array($msg->getRawOriginal('msg'));
        if ($msgData['text']) {
            $textUserid = is_array($msgData['text_userid']) ? $msgData['text_userid'] : [];
            if (!in_array($user->userid, $textUserid)) {
                $textUserid[] = $user->userid;
                $msg->updateInstance([
                    'msg' => array_merge($msgData, ['text_userid' => $textUserid]),
                ]);
                $msg->save();
            }
            return Base::retSuccess("success", $msg);
        }
        WebSocketDialog::checkDialog($msg->dialog_id);
        //
        $res = Extranet::openAItranscriptions(public_path($msgData['path']));
        if (Base::isError($res)) {
            return $res;
        }
        //
        $msg->updateInstance([
            'msg' => array_merge($msgData, ['text' => $res['data'], 'text_userid' => [$user->userid]]),
        ]);
        $msg->save();
        return Base::retSuccess("success", $msg);
    }

    /**
     * @api {get} api/dialog/msg/translation          33. 翻译消息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__translation
     *
     * @apiParam {Number} msg_id            消息ID
     * @apiParam {String} [language]        目标语言，默认当前语言
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__translation()
    {
        User::auth();
        //
        $msg_id = intval(Request::input("msg_id"));
        $language = Base::inputOrHeader('language');
        $targetLanguage = Doo::getLanguages($language);
        //
        if (empty($targetLanguage)) {
            return Base::retError("参数错误");
        }
        $msg = WebSocketDialogMsg::whereId($msg_id)->first();
        if (empty($msg)) {
            return Base::retError("消息不存在或已被删除");
        }
        if (!in_array($msg->type, ['text', 'record'])) {
            return Base::retError("此消息不支持翻译");
        }
        WebSocketDialog::checkDialog($msg->dialog_id);
        //
        $row = WebSocketDialogMsgTranslate::whereMsgId($msg_id)->whereLanguage($language)->first();
        if ($row) {
            return Base::retSuccess("success", $row->only(['msg_id', 'language', 'content']));
        }
        //
        $msgData = Base::json2array($msg->getRawOriginal('msg'));
        if (empty($msgData['text'])) {
            return Base::retError("消息内容为空");
        }
        $res = Extranet::openAItranslations($msgData['text'], $targetLanguage);
        if (Base::isError($res)) {
            return $res;
        }
        $row = WebSocketDialogMsgTranslate::createInstance([
            'dialog_id' => $msg->dialog_id,
            'msg_id' => $msg_id,
            'language' => $language,
            'content' => $res['data'],
        ]);
        $row->save();
        //
        return Base::retSuccess("success", $row->only(['msg_id', 'language', 'content']));
    }

    /**
     * @api {get} api/dialog/msg/mark          34. 消息标记操作
     *
     * @apiDescription  需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__mark
     *
     * @apiParam {Number} dialog_id             会话ID
     * @apiParam {String} type                  类型
     * - read: 已读
     * - unread: 未读
     * @apiParam {Number} [after_msg_id]        仅标记已读指定之后（含）的消息
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__mark()
    {
        $user = User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $type = Request::input('type');
        $after_msg_id = intval(Request::input('after_msg_id'));
        //
        $dialogUser = WebSocketDialogUser::with(['webSocketDialog'])->whereDialogId($dialog_id)->whereUserid($user->userid)->first();
        if (empty($dialogUser?->webSocketDialog)) {
            return Base::retError('会话不存在');
        }
        switch ($type) {
            case 'read':
                // 标记已读
                $builder = WebSocketDialogMsgRead::whereDialogId($dialog_id)->whereUserid($user->userid)->whereReadAt(null);
                if ($after_msg_id > 0) {
                    $builder->where('msg_id', '>=', $after_msg_id);
                }
                $builder->chunkById(100, function ($list) {
                    WebSocketDialogMsgRead::onlyMarkRead($list);
                });
                break;

            case 'unread':
                // 标记未读
                break;

            default:
                return Base::retError("参数错误");
        }
        $dialogUser->mark_unread = $type == 'unread' ? 1 : 0;
        $dialogUser->save();
        $unreadData = WebSocketDialog::generateUnread($dialog_id, $user->userid);
        return Base::retSuccess("success", [
            'id' => $dialog_id,
            'unread' => $unreadData['unread'],
            'unread_one' => $unreadData['unread_one'],
            'mention' => $unreadData['mention'],
            'mention_ids' => $unreadData['mention_ids'],
            'user_at' => Carbon::parse($dialogUser->updated_at)->toDateTimeString('millisecond'),
            'user_ms' => Carbon::parse($dialogUser->updated_at)->valueOf(),
            'mark_unread' => $dialogUser->mark_unread,
        ]);
    }

    /**
     * @api {get} api/dialog/msg/silence          35. 消息免打扰
     *
     * @apiDescription  需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__silence
     *
     * @apiParam {Number} dialog_id             会话ID
     * @apiParam {String} type                  类型
     * - set
     * - cancel
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__silence()
    {
        $user = User::auth();
        $dialogId = intval(Request::input('dialog_id'));
        $type = Request::input('type');
        $dialogUser = WebSocketDialogUser::whereUserid($user->userid)->whereDialogId($dialogId)->first();
        if (!$dialogUser) {
            return Base::retError("会话不存在");
        }
        //
        $dialogData = WebSocketDialog::find($dialogId);
        if (empty($dialogData)) {
            return Base::retError("会话不存在");
        }
        if ($dialogData->type === 'group' && $dialogData->group_type !== 'user') {
            return Base::retError("此会话不允许设置免打扰");
        }
        //
        switch ($type) {
            case 'set':
                $data['silence'] = 0;
                WebSocketDialogMsgRead::whereUserid($user->userid)
                    ->whereReadAt(null)
                    ->whereDialogId($dialogId)
                    ->chunkById(100, function ($list) {
                        WebSocketDialogMsgRead::onlyMarkRead($list);
                    });
                $dialogUser->silence = 1;
                $dialogUser->save();
                break;

            case 'cancel':
                $dialogUser->silence = 0;
                $dialogUser->save();
                break;

            default:
                return Base::retError("参数错误");
        }
        $data = [
            'id' => $dialogId,
            'silence' => $dialogUser->silence,
        ];
        return Base::retSuccess("success", $data);
    }

    /**
     * @api {get} api/dialog/msg/forward          36. 转发消息给
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__forward
     *
     * @apiParam {Number} msg_id                消息ID
     * @apiParam {Array} dialogids              转发给的对话ID
     * @apiParam {Array} userids                转发给的成员ID
     * @apiParam {Number} show_source           是否显示原发送者信息
     * @apiParam {Array} leave_message          转发留言
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__forward()
    {
        $user = User::auth();
        //
        $msg_id = intval(Request::input("msg_id"));
        $dialogids = Request::input('dialogids');
        $userids = Request::input('userids');
        $show_source = intval(Request::input("show_source"));
        $leave_message = Request::input('leave_message');
        //
        if (empty($dialogids) && empty($userids)) {
            return Base::retError("请选择转发对话或成员");
        }
        //
        $msg = WebSocketDialogMsg::whereId($msg_id)->first();
        if (empty($msg)) {
            return Base::retError("消息不存在或已被删除");
        }
        WebSocketDialog::checkDialog($msg->dialog_id);
        //
        return $msg->forwardMsg($dialogids, $userids, $user, $show_source, $leave_message);
    }

    /**
     * @api {get} api/dialog/msg/emoji          37. emoji回复
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__emoji
     *
     * @apiParam {Number} msg_id            消息ID
     * @apiParam {String} symbol            回复或取消的emoji表情
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__emoji()
    {
        $user = User::auth();
        //
        $msg_id = intval(Request::input("msg_id"));
        $symbol = Request::input("symbol");
        //
        if (!preg_match("/^[\u{d800}-\u{dbff}]|[\u{dc00}-\u{dfff}]$/", $symbol)) {
            return Base::retError("参数错误");
        }
        //
        $msg = WebSocketDialogMsg::whereId($msg_id)->first();
        if (empty($msg)) {
            return Base::retError("消息不存在或已被删除");
        }
        WebSocketDialog::checkDialog($msg->dialog_id);
        //
        return $msg->emojiMsg($symbol, $user->userid);
    }

    /**
     * @api {get} api/dialog/msg/tag          38. 标注/取消标注
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__tag
     *
     * @apiParam {Number} msg_id            消息ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__tag()
    {
        $user = User::auth();
        //
        $msg_id = intval(Request::input("msg_id"));
        //
        $msg = WebSocketDialogMsg::whereId($msg_id)->first();
        if (empty($msg)) {
            return Base::retError("消息不存在或已被删除");
        }
        WebSocketDialog::checkDialog($msg->dialog_id);
        //
        return $msg->toggleTagMsg($user->userid);
    }

    /**
     * @api {get} api/dialog/msg/todo          39. 设待办/取消待办
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__todo
     *
     * @apiParam {Number} msg_id            消息ID
     * @apiParam {String} type              设待办对象
     * - all: 会话全部成员（默认）
     * - user: 会话指定成员
     * @apiParam {Array} userids            会员ID组
     * - type=user 有效，格式: [userid1, userid2, userid3]
     * - 可通过 type=user 及 userids:[] 一起使用来清除所有人的待办
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__todo()
    {
        Base::checkClientVersion('0.37.18');
        $user = User::auth();
        //
        $msg_id = intval(Request::input("msg_id"));
        $type = trim(Request::input("type", "all"));
        $userids = Request::input('userids');
        //
        $msg = WebSocketDialogMsg::whereId($msg_id)->first();
        if (empty($msg)) {
            return Base::retError("消息不存在或已被删除");
        }
        $dialog = WebSocketDialog::checkDialog($msg->dialog_id);
        //
        if ($type === 'all') {
            $userids = $dialog->dialogUser->pluck('userid')->toArray();
        } else {
            $userids = is_array($userids) ? $userids : [];
        }
        return $msg->toggleTodoMsg($user->userid, $userids);
    }

    /**
     * @api {get} api/dialog/msg/todolist          40. 获取消息待办情况
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__todolist
     *
     * @apiParam {Number} msg_id            消息ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__todolist()
    {
        User::auth();
        //
        $msg_id = intval(Request::input('msg_id'));
        //
        $msg = WebSocketDialogMsg::whereId($msg_id)->first();
        if (empty($msg)) {
            return Base::retError("消息不存在或已被删除");
        }
        WebSocketDialog::checkDialog($msg->dialog_id);
        //
        $todo = WebSocketDialogMsgTodo::whereMsgId($msg_id)->get();
        return Base::retSuccess('success', $todo ?: []);
    }

    /**
     * @api {get} api/dialog/msg/done          41. 完成待办
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__done
     *
     * @apiParam {Number} id            待办数据ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__done()
    {
        $user = User::auth();
        //
        $id = intval(Request::input("id"));
        //
        $add = [];
        $todo = WebSocketDialogMsgTodo::whereId($id)->whereUserid($user->userid)->first();
        if ($todo && empty($todo->done_at)) {
            $todo->done_at = Carbon::now();
            $todo->save();
            //
            $msg = WebSocketDialogMsg::find($todo->msg_id);
            if ($msg) {
                $res = WebSocketDialogMsg::sendMsg(null, $todo->dialog_id, 'todo', [
                    'action' => 'done',
                    'data' => [
                        'id' => $msg->id,
                        'type' => $msg->type,
                        'msg' => $msg->quoteTextMsg(),
                    ]
                ]);
                if (Base::isSuccess($res)) {
                    $add = $res['data'];
                }
                //
                $msg->webSocketDialog?->pushMsg('update', [
                    'id' => $msg->id,
                    'todo' => $msg->todo,
                    'dialog_id' => $msg->dialog_id,
                ]);
            }
        }
        //
        return Base::retSuccess("待办已完成", [
            'add' => $add ?: null
        ]);
    }

    /**
     * @api {get} api/dialog/msg/color          42. 设置颜色
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__color
     *
     * @apiParam {Number} dialog_id          会话ID
     * @apiParam {String} color              颜色
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__color()
    {
        $user = User::auth();

        $dialogId = intval(Request::input('dialog_id'));
        $color = Request::input('color','');
        $dialogUser = WebSocketDialogUser::whereUserid($user->userid)->whereDialogId($dialogId)->first();
        if (!$dialogUser) {
            return Base::retError("会话不存在");
        }
        //
        $dialogData = WebSocketDialog::find($dialogId);
        if (empty($dialogData)) {
            return Base::retError("会话不存在");
        }
        //
        $dialogUser->color = $color;
        $dialogUser->save();
        //
        $data = [
            'id' => $dialogId,
            'color' => $color
        ];
        return Base::retSuccess("success", $data);
    }

    /**
     * @api {get} api/dialog/group/add          43. 新增群组
     *
     * @apiDescription  需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName group__add
     *
     * @apiParam {String} [avatar]              群头像
     * @apiParam {String} [chat_name]           群名称
     * @apiParam {Array} userids                群成员，格式: [userid1, userid2, userid3]
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function group__add()
    {
        $user = User::auth();
        //
        $avatar = Request::input('avatar');
        $avatar = $avatar ? Base::unFillUrl(is_array($avatar) ? $avatar[0]['path'] : $avatar) : '';
        $chatName = trim(Request::input('chat_name'));
        $userids = Request::input('userids');
        //
        if (!is_array($userids)) {
            return Base::retError('请选择群成员');
        }
        $userids = array_merge([$user->userid], $userids);
        $userids = array_values(array_filter(array_unique($userids)));
        if (count($userids) < 2) {
            return Base::retError('群成员至少2人');
        }
        //
        if (empty($chatName)) {
            $array = [];
            foreach ($userids as $userid) {
                $array[] = User::userid2nickname($userid);
                if (count($array) >= 8 || strlen(implode(", ", $array)) > 100) {
                    $array[] = "...";
                    break;
                }
            }
            $chatName = implode(", ", $array);
        }
        if ($user->isTemp()) {
            return Base::retError('无法创建群组');
        }
        $dialog = WebSocketDialog::createGroup($chatName, $userids, 'user', $user->userid);
        if (empty($dialog)) {
            return Base::retError('创建群组失败');
        }
        if ($avatar) {
            $dialog->avatar = $avatar;
            $dialog->save();
        }
        $data = WebSocketDialog::synthesizeData($dialog, $user->userid);
        $userids = array_values(array_diff($userids, [$user->userid]));
        $dialog->pushMsg("groupAdd", null, $userids);
        return Base::retSuccess('创建成功', $data);
    }

    /**
     * @api {get} api/dialog/group/edit          44. 修改群组
     *
     * @apiDescription  需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName group__edit
     *
     * @apiParam {Number} dialog_id             会话ID
     * @apiParam {String} [avatar]              群头像
     * @apiParam {String} [chat_name]           群名称
     * @apiParam {Number} [admin]               系统管理员操作（1：只判断是不是系统管理员，否则判断是否群管理员）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function group__edit()
    {
        $user = User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $admin = intval(Request::input('admin'));
        //
        if ($admin === 1) {
            $user->checkAdmin();
            $dialog = WebSocketDialog::find($dialog_id);
            if (empty($dialog)) {
                WebSocketDialogMsgRead::forceRead($dialog_id, $user->userid);
                return Base::retError('对话不存在或已被删除', ['dialog_id' => $dialog_id], -4003);
            }
        } else {
            $dialog = WebSocketDialog::checkDialog($dialog_id, true);
        }
        //
        $data = ['id' => $dialog->id];
        $array = [];
        if (Request::exists('avatar')) {
            $avatar = Request::input('avatar');
            $avatar = $avatar ? Base::unFillUrl(is_array($avatar) ? $avatar[0]['path'] : $avatar) : '';
            $data['avatar'] = Base::fillUrl($array['avatar'] = $avatar);
        }
        if (Request::exists('chat_name') && $dialog->group_type === 'user') {
            $chatName = trim(Request::input('chat_name'));
            if (mb_strlen($chatName) < 2) {
                return Base::retError('群名称至少2个字');
            }
            if (mb_strlen($chatName) > 100) {
                return Base::retError('群名称最长限制100个字');
            }
            $data['name'] = $array['name'] = $chatName;
        }
        //
        if ($array) {
            $dialog->updateInstance($array);
            $dialog->save();
            WebSocketDialogUser::whereDialogId($dialog->id)->change(['updated_at' => Carbon::now()->toDateTimeString('millisecond')]);
        }
        //
        return Base::retSuccess('修改成功', $data);
    }

    /**
     * @api {get} api/dialog/group/adduser          45. 添加群成员
     *
     * @apiDescription  需要token身份
     * - 有群主时：只有群主可以邀请
     * - 没有群主时：群内成员都可以邀请
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName group__adduser
     *
     * @apiParam {Number} dialog_id             会话ID
     * @apiParam {Array} userids                新增的群成员，格式: [userid1, userid2, userid3]
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function group__adduser()
    {
        $user = User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $userids = Base::json2array(Request::input('userids'));
        //
        if (!is_array($userids)) {
            return Base::retError('请选择群成员');
        }
        //
        $dialog = WebSocketDialog::checkDialog($dialog_id, "auto");
        //
        $dialog->checkGroup();
        $dialog->joinGroup($userids, $user->userid);
        $dialog->pushMsg("groupJoin", null, $userids);
        return Base::retSuccess('添加成功');
    }

    /**
     * @api {get} api/dialog/group/deluser          46. 移出（退出）群成员
     *
     * @apiDescription  需要token身份
     * - 只有群主、邀请人可以踢人
     * - 群主、任务人员、项目人员不可被踢或退出
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName group__adduser
     *
     * @apiParam {Number} dialog_id             会话ID
     * @apiParam {Array} [userids]              移出的群成员，格式: [userid1, userid2, userid3]
     * - 留空表示自己退出
     * - 有值表示移出，仅限群主操作
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function group__deluser()
    {
        $user = User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $userids = Base::json2array(Request::input('userids'));
        //
        $type = 'remove';
        if (empty($userids)) {
            $type = 'exit';
            $userids = [$user->userid];
        }
        //
        if (!is_array($userids)) {
            return Base::retError('请选择群成员');
        }
        //
        $dialog = WebSocketDialog::checkDialog($dialog_id);
        //
        $dialog->checkGroup();
        $dialog->exitGroup($userids, $type);
        $dialog->pushMsg("groupExit", null, $userids);
        return Base::retSuccess($type === 'remove' ? '移出成功' : '退出成功');
    }

    /**
     * @api {get} api/dialog/group/transfer          47. 转让群组
     *
     * @apiDescription  需要token身份
     * - 只有群主且是个人类型群可以解散
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName group__transfer
     *
     * @apiParam {Number} dialog_id             会话ID
     * @apiParam {Number} userid                新的群主
     * @apiParam {String} check_owner           转让验证  yes-需要验证  no-不需要验证
     * @apiParam {String} key                   密钥（APP_KEY）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function group__transfer()
    {
        if (!Base::is_internal_ip(Base::getIp()) || Request::input("key") !== env('APP_KEY')) {
            $user = User::auth();
        }
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $userid = intval(Request::input('userid'));
        $check_owner = trim(Request::input('check_owner', 'yes')) === 'yes';
        //
        if ($check_owner && $userid === $user?->userid) {
            return Base::retError('你已经是群主');
        }
        if (!User::whereUserid($userid)->exists()) {
            return Base::retError('请选择有效的新群主');
        }
        //
        $dialog = WebSocketDialog::checkDialog($dialog_id, $check_owner);
        //
        $dialog->checkGroup($check_owner ? 'user' : null);
        $dialog->owner_id = $userid;
        if ($dialog->save()) {
            $dialog->joinGroup($userid, 0);
            $dialog->pushMsg("groupUpdate", [
                'id' => $dialog->id,
                'owner_id' => $dialog->owner_id,
            ]);
        }
        return Base::retSuccess('转让成功');
    }

    /**
     * @api {get} api/dialog/group/disband          48. 解散群组
     *
     * @apiDescription  需要token身份
     * - 只有群主且是个人类型群可以解散
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName group__disband
     *
     * @apiParam {Number} dialog_id             会话ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function group__disband()
    {
        User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        //
        $dialog = WebSocketDialog::checkDialog($dialog_id, true);
        //
        $dialog->checkGroup('user');
        $dialog->deleteDialog();
        return Base::retSuccess('解散成功');
    }

    /**
     * @api {get} api/dialog/group/searchuser          49. 搜索个人群（仅限管理员）
     *
     * @apiDescription  需要token身份，用于创建部门搜索个人群组
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName group__searchuser
     *
     * @apiParam {String} key             关键词
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function group__searchuser()
    {
        User::auth('admin');
        //
        $key = trim(Request::input('key'));
        //
        $builder = WebSocketDialog::whereType('group')->whereGroupType('user');
        if ($key) {
            $builder->where('name', 'like', "%{$key}%");
        }
        return Base::retSuccess('success', [
            'list' => $builder->take(20)->get()
        ]);
    }

    /**
     * @api {post} api/dialog/okr/add          50. 创建OKR评论会话
     *
     * @apiDescription  需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName okr__add
     *
     * @apiParam {String} name                   标题
     * @apiParam {Number} link_id                关联id
     * @apiParam {Array}  userids                群成员，格式: [userid1, userid2, userid3]
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function okr__add()
    {
        $user = User::auth();
        //
        $name = trim(Request::input('name'));
        $link_id = intval(Request::input('link_id'));
        $userids = Request::input('userids');
        //
        if (empty($name)) {
            return Base::retError('群名称至少2个字');
        }
        //
        $dialog = WebSocketDialog::createGroup($name, $userids, 'okr', $user->userid);
        if (empty($dialog)) {
            return Base::retError('创建群组失败');
        }
        if ($link_id) {
            $dialog->link_id = $link_id;
            $dialog->save();
        }
        return Base::retSuccess('创建成功', $dialog);
    }

    /**
     * @api {post} api/dialog/okr/push          51. 推送OKR相关信息
     *
     * @apiDescription  需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName okr__push
     *
     * @apiParam {String}  text                  发送内容
     * @apiParam {Number}  userid                成员ID
     * @apiParam {String}  key                   密钥（APP_KEY）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function okr__push()
    {
        if (!Base::is_internal_ip(Base::getIp()) || Request::input("key") !== env('APP_KEY')) {
            User::auth();
        }
        $text = trim(Request::input('text'));
        $userid = intval(Request::input('userid'));
        //
        $botUser = User::botGetOrCreate('okr-alert');
        if (empty($botUser)) {
            return Base::retError('机器人不存在');
        }
        //
        $dialog = WebSocketDialog::checkUserDialog($botUser, $userid);
        if ($dialog) {
            WebSocketDialogMsg::sendMsg(null, $dialog->id, 'text', ['text' => $text], $botUser->userid);
        }
        return Base::retSuccess('success', $dialog);
    }

    /**
     * @api {post} api/dialog/msg/wordchain          52. 发送接龙消息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__wordchain
     *
     * @apiParam {Number} dialog_id         对话ID
     * @apiParam {String} uuid              接龙ID
     * @apiParam {String} text              接龙内容
     * @apiParam {Array}  list              接龙列表
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__wordchain()
    {
        $user = User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $uuid = trim(Request::input('uuid'));
        $text = trim(Request::input('text'));
        $list = Request::input('list') ?? [];
        //
        WebSocketDialog::checkDialog($dialog_id);
        $strlen = mb_strlen($text);
        $noimglen = mb_strlen(preg_replace("/<img[^>]*?>/i", "", $text));
        if ($strlen < 1 || empty($list)) {
            return Base::retError('内容不能为空');
        }
        if ($noimglen > 200000) {
            return Base::retError('内容最大不能超过200000字');
        }
        //
        return AbstractModel::transaction(function () use ($user, $uuid, $dialog_id, $list, $text) {
            if ($uuid) {
                $dialogMsg = WebSocketDialogMsg::whereDialogId($dialog_id)
                    ->lockForUpdate()
                    ->whereType('word-chain')
                    ->orderByDesc('created_at')
                    ->where('msg', 'like', "%$uuid%")
                    ->value('msg');
                //
                $createId = $dialogMsg['createid'] ?? $user->userid;
                // 新增
                $msgList = $dialogMsg['list'] ?? [];
                $addList = array_udiff($list, $msgList, function($a, $b) {
                    return ($a['id'] ?? 0) - $b['id'];
                });
                foreach ($addList as $key => $item) {
                    $item['id'] = intval(round(microtime(true) * 1000)) + $key;
                    $msgList[] = $item;
                }
                // 编辑更新
                $lists = array_column($list,null,'id');
                foreach ($msgList as $key => $item) {
                    if (isset($lists[$item['id']]) && $item['userid'] == $user->userid) {
                        $msgList[$key] = $lists[$item['id']];
                    }
                }
                $list = $msgList;
            } else {
                $createId = $user->userid;
                $uuid = Base::generatePassword(36);
                foreach ($list as $key => $item) {
                    $list[$key]['id'] = intval(round(microtime(true) * 1000)) + $key;
                }
            }
            //
            usort($list, function ($a, $b) {
                return $a['id'] - $b['id'];
            });
            //
            $msgData = [
                'text' => $text,
                'list' => $list,
                'userid' => $user->userid,
                'createid' => $createId,
                'uuid' => $uuid,
            ];
            return WebSocketDialogMsg::sendMsg(null, $dialog_id, 'word-chain', $msgData, $user->userid);
        });
    }

    /**
     * @api {post} api/dialog/msg/vote          53. 发起投票
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__vote
     *
     * @apiParam {Number} dialog_id             对话ID
     * @apiParam {String} text                  投票内容
     * @apiParam {Array}  type                  投票类型
     * @apiParam {String} [uuid]                投票ID
     * @apiParam {Array}  [list]                投票列表
     * @apiParam {Number} [multiple]            多选
     * @apiParam {Number} [anonymous]           匿名
     * @apiParam {Array}  [vote]                投票
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__vote()
    {
        $user = User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $uuid = trim(Request::input('uuid'));
        $text = trim(Request::input('text'));
        $type = trim(Request::input('type', 'create'));
        $multiple = intval(Request::input('multiple')) ?: 0;
        $anonymous = intval(Request::input('anonymous')) ?: 0;
        $list = Request::input('list');
        $vote = Request::input('vote') ?: [];
        $votes = is_array($vote) ? $vote : [$vote];
        //
        WebSocketDialog::checkDialog($dialog_id);
        //
        $action = null;
        if ($type != 'create') {
            if ($type == 'vote' && empty($votes)) {
                return Base::retError('参数错误');
            }
            if (empty($uuid)) {
                return Base::retError('参数错误');
            }
            return AbstractModel::transaction(function () use ($user, $uuid, $dialog_id, $type, $votes) {
                //
                $dialogMsgs = WebSocketDialogMsg::whereDialogId($dialog_id)
                    ->lockForUpdate()
                    ->whereType('vote')
                    ->orderByDesc('created_at')
                    ->where('msg', 'like', "%$uuid%")
                    ->get();
                //
                $result = [];
                if ($type == 'again') {
                    $res = WebSocketDialogMsg::sendMsg(null, $dialog_id, 'vote', $dialogMsgs[0]->msg, $user->userid);
                    if (Base::isError($res)) {
                        return $res;
                    }
                    $result[] = $res['data'];
                } else {
                    foreach ($dialogMsgs as $dialogMsg) {
                        $action = "change-{$dialogMsg->id}";
                        $msgData = $dialogMsg->msg;
                        if ($type == 'finish') {
                            $msgData['state'] = 0;
                        } else {
                            $msgDataVotes = $msgData['votes'] ?? [];
                            if (in_array($user->userid, array_column($msgDataVotes, 'userid'))) {
                                return Base::retError('不能重复投票');
                            }
                            $msgDataVotes[] = [
                                'userid' => $user->userid,
                                'votes' => $votes,
                            ];
                            $msgData['votes'] = $msgDataVotes;
                        }
                        //
                        $res = WebSocketDialogMsg::sendMsg($action, $dialog_id, 'vote', $msgData, $user->userid);
                        if (Base::isError($res)) {
                            return $res;
                        }
                        $result[] = $res['data'];
                    }
                }
                //
                return Base::retSuccess('发送成功', $result);
            });
        } else {
            $strlen = mb_strlen($text);
            $noimglen = mb_strlen(preg_replace("/<img[^>]*?>/i", "", $text));
            if ($strlen < 1) {
                return Base::retError('内容不能为空');
            }
            if ($noimglen > 200000) {
                return Base::retError('内容最大不能超过200000字');
            }
            $msgData = [
                'text' => $text,
                'list' => $list,
                'userid' => $user->userid,
                'uuid' => $uuid ?: Base::generatePassword(36),
                'multiple' => $multiple,
                'anonymous' => $anonymous,
                'votes' => [],
                'state' => 1
            ];
            $res = WebSocketDialogMsg::sendMsg($action, $dialog_id, 'vote', $msgData, $user->userid);
            if (Base::isError($res)) {
                return $res;
            }
            return Base::retSuccess('发送成功', [$res['data']]);
        }
    }

    /**
     * @api {get} api/dialog/msg/top          54. 置顶/取消置顶
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__top
     *
     * @apiParam {Number} msg_id            消息ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__top()
    {
        $user = User::auth();
        //
        $msg_id = intval(Request::input("msg_id"));
        //
        $msg = WebSocketDialogMsg::whereId($msg_id)->first();
        if (empty($msg)) {
            return Base::retError("消息不存在或已被删除");
        }
        $dialog = WebSocketDialog::checkDialog($msg->dialog_id);
        //
        $before = $dialog->top_msg_id;
        $beforeTopUserid = $dialog->top_userid;
        $dialog->top_msg_id = $msg->id == $before ? 0 : $msg->id;
        $dialog->top_userid = $dialog->top_msg_id ? $user->userid : 0;
        $dialog->save();
        //
        $data = [
            'add' => null,
            'update' => [
                'dialog_id' => $dialog->id,
                'top_msg_id' => $dialog->top_msg_id,
                'top_userid' => $dialog->top_userid,
            ]
        ];
        $res = WebSocketDialogMsg::sendMsg(null, $dialog->id, 'top', [
            'action' => $dialog->top_msg_id ? 'add' : 'remove',
            'data' => [
                'id' => $msg->id,
                'type' => $msg->type,
                'msg' => $msg->quoteTextMsg()
            ]
        ], $user->userid);
        if (Base::isSuccess($res)) {
            $data['add'] = $res['data'];
            $dialog->pushMsg('updateTopMsg', $data['update']);
        } else {
            $dialog->top_msg_id = $before;
            $dialog->top_userid = $beforeTopUserid;
            $dialog->save();
        }
        //
        return Base::retSuccess($dialog->top_msg_id ? '置顶成功' : '取消成功', $data);
    }

    /**
     * @api {get} api/dialog/msg/topinfo          55. 获取置顶消息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__topinfo
     *
     * @apiParam {Number} dialog_id            会话ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__topinfo()
    {
        User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        //
        $dialog = WebSocketDialog::checkDialog($dialog_id);
        //
        $topMsg = WebSocketDialogMsg::whereId($dialog->top_msg_id)->first();
        //
        return Base::retSuccess('success', $topMsg);
    }

    /**
     * @api {get} api/dialog/sticker/search          56. 搜索在线表情
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName sticker__search
     *
     * @apiParam {String} key            关键词
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function sticker__search()
    {
        User::auth();
        //
        $key = trim(Request::input('key'));
        return Base::retSuccess('success', [
            'list' => Extranet::sticker($key)
        ]);
    }
}
