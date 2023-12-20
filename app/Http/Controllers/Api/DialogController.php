<?php

namespace App\Http\Controllers\Api;

use App\Tasks\PushTask;
use DB;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Request;
use Redirect;
use Carbon\Carbon;
use App\Models\File;
use App\Models\User;
use App\Module\Base;
use App\Module\TimeRange;
use App\Models\FileContent;
use App\Models\AbstractModel;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogUser;
use App\Models\WebSocketDialogMsgRead;
use App\Models\WebSocketDialogMsgTodo;

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
        $data = (new WebSocketDialog)->getDialogList($user->userid, $timerange->updated, $timerange->deleted);
        //
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/dialog/search          02. 搜索会话
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
        $dialogs = WebSocketDialog::select(['web_socket_dialogs.*', 'u.top_at', 'u.mark_unread', 'u.silence', 'u.color', 'u.updated_at as user_at'])
            ->join('web_socket_dialog_users as u', 'web_socket_dialogs.id', '=', 'u.dialog_id')
            ->where('web_socket_dialogs.name', 'LIKE', "%{$key}%")
            ->where('u.userid', $user->userid)
            ->orderByDesc('u.top_at')
            ->orderByDesc('web_socket_dialogs.last_at')
            ->take(20)
            ->get();
        $dialogs->transform(function (WebSocketDialog $item) use ($user) {
            return $item->formatData($user->userid);
        });
        $list = $dialogs->toArray();
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
            $users->transform(function (User $item) {
                return [
                    'id' => 'u:' . $item->userid,
                    'type' => 'user',
                    'name' => $item->nickname,
                    'dialog_user' => $item,
                    'last_msg' => null,
                ];
            });
            $list = array_merge($list, $users->toArray());
        }
        // 搜索消息会话
        if (count($list) < 20) {
            $msgs = WebSocketDialog::select(['web_socket_dialogs.*', 'u.top_at', 'u.mark_unread', 'u.silence', 'u.color', 'u.updated_at as user_at', 'm.id as search_msg_id'])
                ->join('web_socket_dialog_users as u', 'web_socket_dialogs.id', '=', 'u.dialog_id')
                ->join('web_socket_dialog_msgs as m', 'web_socket_dialogs.id', '=', 'm.dialog_id')
                ->where('u.userid', $user->userid)
                ->where('m.key', 'LIKE', "%{$key}%")
                ->orderByDesc('m.id')
                ->take(20 - count($list))
                ->get();
            $msgs->transform(function (WebSocketDialog $item) use ($user) {
                return $item->formatData($user->userid);
            });
            $list = array_merge($list, $msgs->toArray());
        }
        //
        return Base::retSuccess('success', $list);
    }

    /**
     * @api {get} api/dialog/search/tag          02. 搜索标注会话
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
        $msgs = WebSocketDialog::select(['web_socket_dialogs.*', 'u.top_at', 'u.mark_unread', 'u.silence', 'u.color', 'u.updated_at as user_at', 'm.id as search_msg_id'])
            ->join('web_socket_dialog_users as u', 'web_socket_dialogs.id', '=', 'u.dialog_id')
            ->join('web_socket_dialog_msgs as m', 'web_socket_dialogs.id', '=', 'm.dialog_id')
            ->where('u.userid', $user->userid)
            ->where('m.tag', '>', 0)
            ->orderByDesc('m.id')
            ->take(50)
            ->get();
        $msgs->transform(function (WebSocketDialog $item) use ($user) {
            return $item->formatData($user->userid);
        });
        //
        return Base::retSuccess('success', $msgs->toArray());
    }

    /**
     * @api {get} api/dialog/one          03. 获取单个会话信息
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
        $item = WebSocketDialog::select(['web_socket_dialogs.*', 'u.top_at', 'u.mark_unread', 'u.silence', 'u.color', 'u.updated_at as user_at'])
            ->join('web_socket_dialog_users as u', 'web_socket_dialogs.id', '=', 'u.dialog_id')
            ->where('web_socket_dialogs.id', $dialog_id)
            ->where('u.userid', $user->userid)
            ->first();
        if (empty($item)) {
            return Base::retError('会话不存在或已被删除', ['dialog_id' => $dialog_id], -4003);
        }
        return Base::retSuccess('success', $item->formatData($user->userid));
    }

    /**
     * @api {get} api/dialog/user          04. 获取会话成员
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
        $data = $dialog->dialogUser->toArray();
        if ($getuser === 1) {
            $array = [];
            foreach ($data as $item) {
                $res = User::userid2basic($item['userid']);
                if ($res) {
                    $array[] = array_merge($item, $res->toArray());
                }
            }
            $data = $array;
        }
        //
        $array = [];
        foreach ($data as $item) {
            if ($item['userid'] > 0) {
                $array[] = $item;
            }
        }
        return Base::retSuccess('success', $array);
    }

    /**
     * @api {get} api/dialog/todo          05. 获取会话待办
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName todo
     *
     * @apiParam {Number} dialog_id            会话ID
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
        WebSocketDialog::checkDialog($dialog_id);
        //
        $list = WebSocketDialogMsgTodo::whereDialogId($dialog_id)->whereUserid($user->userid)->whereDoneAt(null)->orderByDesc('id')->take(50)->get();
        return Base::retSuccess("success", $list);
    }

    /**
     * @api {get} api/dialog/top          06. 会话置顶
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
     * @api {get} api/dialog/tel          07. 获取对方联系电话
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
     * @api {get} api/dialog/open/user          08. 打开会话
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
        $data = WebSocketDialog::find($dialog->id)?->formatData($user->userid);
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/dialog/msg/list          09. 获取消息列表
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
            $data['dialog'] = $dialog->formatData($user->userid, true);
            $data['todo'] = $data['dialog']->todo_num > 0 ? WebSocketDialogMsgTodo::whereDialogId($dialog->id)->whereUserid($user->userid)->whereDoneAt(null)->orderByDesc('id')->take(50)->get() : [];
        }
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/dialog/msg/search          10. 搜索消息位置
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
        return Base::retSuccess('success', [
            'data' => $data
        ]);
    }

    /**
     * @api {get} api/dialog/msg/one          11. 获取单条消息
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
     * @api {get} api/dialog/msg/read          12. 已读聊天消息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__read
     *
     * @apiParam {Number} id         消息ID（组）
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
        $ids = Base::explodeInt($id);
        //
        $dialogIds = [];
        WebSocketDialogMsg::whereIn('id', $ids)->chunkById(20, function($list) use ($user, &$dialogIds) {
            /** @var WebSocketDialogMsg $item */
            foreach ($list as $item) {
                $item->readSuccess($user->userid);
                $dialogIds[$item->dialog_id] = $item->dialog_id;
            }
        });
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
            $dialogUser->webSocketDialog->generateUnread($user->userid);
            $data[] = [
                'id' => $dialogUser->webSocketDialog->id,
                'unread' => $dialogUser->webSocketDialog->unread,
                'mention' => $dialogUser->webSocketDialog->mention,
                'user_at' =>  Carbon::parse($dialogUser->updated_at)->toDateTimeString('millisecond'),
                'user_ms' => Carbon::parse($dialogUser->updated_at)->valueOf()
            ];
        }
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/dialog/msg/unread          13. 获取未读消息数据
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
        $dialogUser->webSocketDialog->generateUnread($dialogUser->userid);
        //
        return Base::retSuccess('success', [
            'id' => $dialogUser->webSocketDialog->id,
            'unread' => $dialogUser->webSocketDialog->unread,
            'mention' => $dialogUser->webSocketDialog->mention,
            'user_at' => Carbon::parse($dialogUser->updated_at)->toDateTimeString('millisecond'),
            'user_ms' => Carbon::parse($dialogUser->updated_at)->valueOf()
        ]);
    }

    /**
     * @api {post} api/dialog/msg/stream          14. 通知成员监听消息
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
     * @api {post} api/dialog/msg/sendtext          15. 发送消息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup dialog
     * @apiName msg__sendtext
     *
     * @apiParam {Number} dialog_id         对话ID
     * @apiParam {String} text              消息内容
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
        //
        if (!$user->bot) {
            $chatInformation = Base::settingFind('system', 'chat_information');
            if ($chatInformation == 'required') {
                if (empty($user->getRawOriginal('nickname'))) {
                    return Base::retError('请设置昵称', [], -2);
                }
                if (empty($user->getRawOriginal('tel'))) {
                    return Base::retError('请设置联系电话', [], -3);
                }
            }
        }
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $dialog_ids = trim(Request::input('dialog_ids'));
        $update_id = intval(Request::input('update_id'));
        $update_mark = !($user->bot && in_array(strtolower(trim(Request::input('update_mark'))), ['no', 'false', '0']));
        $reply_id = intval(Request::input('reply_id'));
        $text = trim(Request::input('text'));
        $text_type = strtolower(trim(Request::input('text_type')));
        $silence = in_array(strtolower(trim(Request::input('silence'))), ['yes', 'true', '1']);
        $markdown = in_array($text_type, ['md', 'markdown']);
        //
        $result = [];
        $dialogIds = $dialog_ids ? explode(',', $dialog_ids) : [$dialog_id ?: 0];
        foreach($dialogIds as $dialog_id) {
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
                $result = WebSocketDialogMsg::sendMsg($action, $dialog_id, 'file', $fileData, $user->userid, false, false, $silence);
            }
            //
            $msgData = ['text' => $text];
            if ($markdown) {
                $msgData['type'] = 'md';
            }
            $result = WebSocketDialogMsg::sendMsg($action, $dialog_id, 'text', $msgData, $user->userid, false, false, $silence);
        }
        return $result;
    }

    /**
     * @api {post} api/dialog/msg/sendrecord          16. 发送语音
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
     * @api {post} api/dialog/msg/sendfile          17. 文件上传
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
        $dialogIds = [intval(Request::input('dialog_id'))];
        $replyId = intval(Request::input('reply_id'));
        $imageAttachment = intval(Request::input('image_attachment'));
        $files = Request::file('files');
        $image64 = Request::input('image64');
        $fileName = Request::input('filename');
        return WebSocketDialog::sendMsgFiles($user, $dialogIds, $files, $image64, $fileName, $replyId, $imageAttachment);
    }

    /**
     * @api {post} api/dialog/msg/sendfiles          18. 群发文件上传
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
     * @api {get} api/dialog/msg/sendfileid          19. 通过文件ID发送文件
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
     * @api {post} api/dialog/msg/sendanon          20. 发送匿名消息
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
        return WebSocketDialogMsg::sendMsg(null, $dialog->id, 'text', ['text' => "<p>{$text}</p>"], $botUser->userid, true);
    }

    /**
     * @api {get} api/dialog/msg/readlist          21. 获取消息阅读情况
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
     * @api {get} api/dialog/msg/detail          22. 消息详情
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
     * @api {get} api/dialog/msg/download          23. 文件下载
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
     * @api {get} api/dialog/msg/withdraw          24. 聊天消息撤回
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
     * @api {get} api/dialog/msg/mark          25. 消息标记操作
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
                $builder = WebSocketDialogMsgRead::whereDialogId($dialog_id)->whereUserid($user->userid)->whereReadAt(null);
                if ($after_msg_id > 0) {
                    $builder->where('msg_id', '>=', $after_msg_id);
                }
                $builder->chunkById(100, function ($list) {
                    WebSocketDialogMsgRead::onlyMarkRead($list);
                });
                //
                $dialogUser->webSocketDialog->generateUnread($user->userid);
                $data = [
                    'id' => $dialogUser->webSocketDialog->id,
                    'unread' => $dialogUser->webSocketDialog->unread,
                    'mention' => $dialogUser->webSocketDialog->mention,
                    'mark_unread' => 0,
                ];
                break;

            case 'unread':
                $data = [
                    'id' => $dialogUser->webSocketDialog->id,
                    'mark_unread' => 1,
                ];
                break;

            default:
                return Base::retError("参数错误");
        }
        $dialogUser->mark_unread = $data['mark_unread'];
        $dialogUser->save();
        return Base::retSuccess("success", array_merge($data, [
            'user_at' => Carbon::parse($dialogUser->updated_at)->toDateTimeString('millisecond'),
            'user_ms' => Carbon::parse($dialogUser->updated_at)->valueOf(),
        ]));
    }

    /**
     * @api {get} api/dialog/msg/silence          26. 消息免打扰
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
     * @api {get} api/dialog/msg/forward          27. 转发消息给
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
     * @api {get} api/dialog/msg/emoji          28. emoji回复
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
     * @api {get} api/dialog/msg/tag          29. 标注/取消标注
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
     * @api {get} api/dialog/msg/todo          30. 设待办/取消待办
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
     * @apiParam {Array} userids            会员ID组（type=user有效，格式: [userid1, userid2, userid3]）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function msg__todo()
    {
        $user = User::auth();
        //
        $msg_id = intval(Request::input("msg_id"));
        $type = trim(Request::input("type", "all"));
        $userids = Request::input('userids');
        //
        if ($type === 'user') {
            if (empty($userids)) {
                return Base::retError("选择指定成员");
            }
        } else {
            $userids = [];
        }
        //
        $msg = WebSocketDialogMsg::whereId($msg_id)->first();
        if (empty($msg)) {
            return Base::retError("消息不存在或已被删除");
        }
        WebSocketDialog::checkDialog($msg->dialog_id);
        //
        return $msg->toggleTodoMsg($user->userid, $userids);
    }

    /**
     * @api {get} api/dialog/msg/todolist          31. 获取消息待办情况
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
     * @api {get} api/dialog/msg/done          32. 完成待办
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
            }
        }
        //
        return Base::retSuccess("待办已完成", [
            'add' => $add ?: null
        ]);
    }

    /**
     * @api {get} api/dialog/msg/color          33. 设置颜色
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
     * @api {get} api/dialog/group/add          34. 新增群组
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
        $data = WebSocketDialog::find($dialog->id)?->formatData($user->userid);
        $userids = array_values(array_diff($userids, [$user->userid]));
        $dialog->pushMsg("groupAdd", null, $userids);
        return Base::retSuccess('创建成功', $data);
    }

    /**
     * @api {get} api/dialog/group/edit          35. 修改群组
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
     * @api {get} api/dialog/group/adduser          36. 添加群成员
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
     * @api {get} api/dialog/group/deluser          37. 移出（退出）群成员
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
     * @api {get} api/dialog/group/transfer          38. 转让群组
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
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function group__transfer()
    {
        $user = User::auth();
        //
        $dialog_id = intval(Request::input('dialog_id'));
        $userid = intval(Request::input('userid'));
        $check_owner = trim(Request::input('check_owner', 'yes')) === 'yes';
        //
        if ($check_owner && $userid === $user->userid) {
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
     * @api {get} api/dialog/group/disband          39. 解散群组
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
     * @api {get} api/dialog/group/searchuser          40. 搜索个人群（仅限管理员）
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
     * @api {post} api/dialog/okr/add          41. 创建OKR评论会话
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
     * @api {post} api/dialog/okr/push          42. 推送OKR相关信息
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
        if (Request::input("key") !== env('APP_KEY')) {
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
     * @api {post} api/dialog/msg/wordchain          44. 发送接龙消息
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
        $list = Request::input('list');
        //
        WebSocketDialog::checkDialog($dialog_id);
        $strlen = mb_strlen($text);
        $noimglen = mb_strlen(preg_replace("/<img[^>]*?>/i", "", $text));
        if ($strlen < 1) {
            return Base::retError('内容不能为空');
        }
        if ($noimglen > 200000) {
            return Base::retError('内容最大不能超过200000字');
        }
        //
        $userid = $user->userid;
        if ($uuid) {
            $dialogMsg = WebSocketDialogMsg::whereDialogId($dialog_id)
                ->whereType('word-chain')
                ->orderByDesc('created_at')
                ->where('msg', 'like', "%$uuid%")
                ->value('msg');
            $list = array_reverse(array_merge($dialogMsg['list'] ?? [], $list));
            $list = array_reduce($list, function ($result, $item) {
                $fieldValue = $item['id'];  // 指定字段名
                if (!isset($result[$fieldValue])) {
                    $result[$fieldValue] = $item;
                }
                return $result;
            }, []);
            $list = array_reverse(array_values($list));
        }
        //
        $msgData = [
            'text' => $text,
            'list' => $list,
            'userid' => $userid,
            'uuid' => $uuid ?: Base::generatePassword(36),
        ];
        return WebSocketDialogMsg::sendMsg(null, $dialog_id, 'word-chain', $msgData, $user->userid);
    }

    /**
     * @api {post} api/dialog/msg/vote          45. 发起投票
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
        $userid = $user->userid;
        $result = [];
        if ($type != 'create') {
            if ($type == 'vote' && empty($votes)) {
                return Base::retError('参数错误');
            }
            if (empty($uuid)) {
                return Base::retError('参数错误');
            }
            $dialogMsgs = WebSocketDialogMsg::whereDialogId($dialog_id)
                ->whereType('vote')
                ->orderByDesc('created_at')
                ->where('msg', 'like', "%$uuid%")
                ->get();
            //
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
                        if (in_array($userid, array_column($msgDataVotes, 'userid'))) {
                            return Base::retError('不能重复投票');
                        }
                        $msgDataVotes[] = [
                            'userid' => $userid,
                            'votes' => $votes,
                        ];
                        $msgData['votes'] = $msgDataVotes;
                    }
                    $res = WebSocketDialogMsg::sendMsg($action, $dialog_id, 'vote', $msgData, $user->userid);
                    if (Base::isError($res)) {
                        return $res;
                    }
                    $result[] = $res['data'];
                }
            }
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
                'userid' => $userid,
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
            $result[] = $res['data'];
        }
        return Base::retSuccess('发送成功', $result);
    }

}
