<?php

namespace App\Models;

use Carbon\Carbon;
use App\Module\Base;
use App\Module\Doo;
use App\Module\Image;
use App\Tasks\PushTask;
use App\Exceptions\ApiException;
use App\Tasks\WebSocketDialogMsgTask;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\WebSocketDialogMsg
 *
 * @property int $id
 * @property int|null $dialog_id 对话ID
 * @property string|null $dialog_type 对话类型
 * @property int|null $userid 发送会员ID
 * @property string|null $type 消息类型
 * @property string|null $mtype 消息类型（用于搜索）
 * @property array|mixed $msg 详细消息
 * @property array|mixed $emoji emoji回复
 * @property string|null $key 搜索关键词
 * @property int|null $read 已阅数量
 * @property int|null $send 发送数量
 * @property int|null $tag 标注会员ID
 * @property int|null $todo 设为待办会员ID
 * @property int|null $link 是否存在链接
 * @property int|null $modify 是否编辑
 * @property int|null $reply_num 有多少条回复
 * @property int|null $reply_id 回复ID
 * @property int|null $forward_id 转发ID
 * @property int|null $forward_num 被转发多少次
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int|mixed $percentage
 * @property-read \App\Models\WebSocketDialog|null $webSocketDialog
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereDialogType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereEmoji($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereForwardId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereForwardNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereModify($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereMsg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereMtype($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereReplyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereReplyNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereSend($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereTag($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereTodo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg withoutTrashed()
 * @mixin \Eloquent
 */
class WebSocketDialogMsg extends AbstractModel
{
    use SoftDeletes;

    protected $appends = [
        'percentage',
    ];

    protected $hidden = [
        'key',
        'updated_at',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function webSocketDialog(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(WebSocketDialog::class, 'id', 'dialog_id');
    }

    /**
     * 阅读占比
     * @return int|mixed
     */
    public function getPercentageAttribute()
    {
        if (!isset($this->appendattrs['percentage'])) {
            $this->generatePercentage();
        }
        return $this->appendattrs['percentage'];
    }

    /**
     * 消息格式化
     * @param $value
     * @return array|mixed
     */
    public function getMsgAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        $value = $this->formatDataMsg($this->type, $value);
        if (isset($value['reply_data'])) {
            $value['reply_data']['msg'] = $this->formatDataMsg($value['reply_data']['type'], $value['reply_data']['msg']);
        }
        return $value;
    }

    /**
     * emoji回复格式化
     * @param $value
     * @return array|mixed
     */
    public function getEmojiAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        return Base::json2array($value);
    }

    /**
     * 处理消息数据
     * @param $type
     * @param $msg
     * @return mixed
     */
    private function formatDataMsg($type, $msg)
    {
        if (!is_array($msg)) {
            $msg = Base::json2array($msg);
        }
        switch ($type) {
            case 'file':
                $msg['type'] = in_array($msg['ext'], ['jpg', 'jpeg', 'webp', 'png', 'gif']) ? 'img' : 'file';
                $msg['path'] = Base::fillUrl($msg['path']);
                $msg['thumb'] = Base::fillUrl($msg['thumb'] ?: Base::extIcon($msg['ext']));
                break;

            case 'record':
                $msg['path'] = Base::fillUrl($msg['path']);
                $textUserid = is_array($msg['text_userid']) ? $msg['text_userid'] : [];
                if (isset($msg['text_userid'])) {
                    unset($msg['text_userid']);
                }
                if ($msg['text'] && !in_array(Doo::userId(), $textUserid)) {
                    $msg['text'] = "";
                }
                break;

            case 'location':
                $msg['thumb'] = Base::fillUrl($msg['thumb'] ?: "images/other/location.jpg");
                break;

            case 'template':
                if ($msg['data']['thumb']) {
                    $msg['data']['thumb']['url'] = Base::fillUrl($msg['data']['thumb']['url']);
                }
                break;
        }
        return $msg;
    }

    /**
     * 获取占比
     * @param bool|int $increment 是否新增阅读数
     * @return int
     */
    public function generatePercentage($increment = false) {
        if ($increment) {
            $this->increment('read', is_bool($increment) ? 1 : $increment);
        }
        if ($this->read > $this->send || empty($this->send)) {
            return $this->appendattrs['percentage'] = 100;
        } else {
            return $this->appendattrs['percentage'] = intval($this->read / $this->send * 100);
        }
    }

    /**
     * 标记已送达 同时 告诉发送人已送达
     * @param $userid
     * @return bool
     */
    public function readSuccess($userid)
    {
        if (empty($userid)) {
            return false;
        }
        self::transaction(function() use ($userid) {
            $msgRead = WebSocketDialogMsgRead::whereMsgId($this->id)->whereUserid($userid)->lockForUpdate()->first();
            if (empty($msgRead)) {
                $msgRead = WebSocketDialogMsgRead::createInstance([
                    'dialog_id' => $this->dialog_id,
                    'msg_id' => $this->id,
                    'userid' => $userid,
                    'after' => 1,
                ]);
                if ($msgRead->saveOrIgnore()) {
                    $this->send = WebSocketDialogMsgRead::whereMsgId($this->id)->count();
                    $this->save();
                } else {
                    return;
                }
            }
            if (!$msgRead->read_at) {
                $msgRead->read_at = Carbon::now();
                $msgRead->save();
                $this->generatePercentage(true);
                PushTask::push([
                    'userid' => $this->userid,
                    'msg' => [
                        'type' => 'dialog',
                        'mode' => 'readed',
                        'data' => [
                            'id' => $this->id,
                            'read' => $this->read,
                            'percentage' => $this->percentage,
                        ],
                    ]
                ]);
            }
        });
        return true;
    }

    /**
     * emoji回复
     * @param $symbol
     * @param int $sender       发送的会员ID
     * @return mixed
     */
    public function emojiMsg($symbol, $sender)
    {
        $exist = false;
        $array = $this->emoji;
        foreach ($array as $index => &$item) {
            if ($item['symbol'] === $symbol) {
                if (in_array($sender, $item['userids'])) {
                    // 已存在 去除
                    $item['userids'] = array_values(array_diff($item['userids'], [$sender]));
                    if (empty($item['userids'])) {
                        unset($array[$index]);
                        $array = array_values($array);
                    }
                } else {
                    // 未存在 添加
                    array_unshift($item['userids'], $sender);
                }
                $exist = true;
                break;
            }
        }
        if (!$exist) {
            array_unshift($array, [
                'symbol' => $symbol,
                'userids' => [$sender]
            ]);
        }
        //
        $this->emoji = Base::array2json($array);
        $this->save();
        $resData = [
            'id' => $this->id,
            'emoji' => $array,
        ];
        //
        $dialog = WebSocketDialog::find($this->dialog_id);
        $dialog?->pushMsg('update', $resData);
        //
        return Base::retSuccess('success', $resData);
    }

    /**
     * 标注、取消标注
     * @param int $sender       标注的会员ID
     * @return mixed
     */
    public function toggleTagMsg($sender)
    {
        if (in_array($this->type, ['tag', 'todo', 'notice'])) {
            return Base::retError('此消息不支持标注');
        }
        $before = $this->tag;
        $this->tag = $before ? 0 : $sender;
        $this->save();
        $resData = [
            'id' => $this->id,
            'tag' => $this->tag,
        ];
        //
        $data = [
            'update' => $resData
        ];
        $res = self::sendMsg(null, $this->dialog_id, 'tag', [
            'action' => $this->tag ? 'add' : 'remove',
            'data' => [
                'id' => $this->id,
                'type' => $this->type,
                'msg' => $this->quoteTextMsg(),
            ]
        ], $sender);
        if (Base::isSuccess($res)) {
            $data['add'] = $res['data'];
            $dialog = WebSocketDialog::find($this->dialog_id);
            $dialog->pushMsg('update', $resData);
        } else {
            $this->tag = $before;
            $this->save();
        }
        //
        return Base::retSuccess($this->tag ? '标注成功' : '取消成功', $data);
    }

    /**
     * 设待办、取消待办
     * @param int $sender       设待办的会员ID
     * @param array $userids    设置给指定会员
     * @return mixed
     */
    public function toggleTodoMsg($sender, $userids = [])
    {
        if (in_array($this->type, ['tag', 'todo', 'notice'])) {
            return Base::retError('此消息不支持设待办');
        }
        $current = WebSocketDialogMsgTodo::whereMsgId($this->id)->pluck('userid')->toArray();
        $cancel = array_diff($current, $userids);
        $setup = array_diff($userids, $current);
        //
        $this->todo = $setup || count($current) > count($cancel) ? $sender : 0;
        $this->save();
        $upData = [
            'id' => $this->id,
            'todo' => $this->todo,
            'dialog_id' => $this->dialog_id,
        ];
        $dialog = WebSocketDialog::find($this->dialog_id);
        $dialog->pushMsg('update', $upData);
        //
        $retData = [
            'add' => [],
            'update' => $upData
        ];
        if ($cancel) {
            $res = self::sendMsg(null, $this->dialog_id, 'todo', [
                'action' => 'remove',
                'data' => [
                    'id' => $this->id,
                    'type' => $this->type,
                    'msg' => $this->quoteTextMsg(),
                    'userids' => implode(",", $cancel),
                ]
            ], $sender);
            if (Base::isSuccess($res)) {
                $retData['add'][] = $res['data'];
                WebSocketDialogMsgTodo::whereMsgId($this->id)->whereIn('userid', $cancel)->delete();
            }
        }
        if ($setup) {
            $res = self::sendMsg(null, $this->dialog_id, 'todo', [
                'action' => 'add',
                'data' => [
                    'id' => $this->id,
                    'type' => $this->type,
                    'msg' => $this->quoteTextMsg(),
                    'userids' => implode(",", $setup),
                ]
            ], $sender);
            if (Base::isSuccess($res)) {
                $retData['add'][] = $res['data'];
                $useridList = $dialog->dialogUser->pluck('userid')->toArray();
                foreach ($setup as $userid) {
                    if (!in_array($userid, $useridList)) {
                        continue;
                    }
                    WebSocketDialogMsgTodo::createInstance([
                        'dialog_id' => $this->dialog_id,
                        'msg_id' => $this->id,
                        'userid' => $userid,
                    ])->saveOrIgnore();
                }
            }
        }
        //
        return Base::retSuccess($this->todo ? '设置成功' : '取消成功', $retData);
    }

    /**
     * 转发消息
     * @param array|int $dialogids
     * @param array|int $userids
     * @param User $user                发送的会员
     * @param int $showSource           是否显示原发送者信息
     * @param string $leaveMessage      转发留言
     * @return mixed
     */
    public function forwardMsg($dialogids, $userids, $user, $showSource = 1, $leaveMessage = '')
    {
        return AbstractModel::transaction(function () use ($dialogids, $user, $userids, $showSource, $leaveMessage) {
            $msgData = Base::json2array($this->getRawOriginal('msg'));
            $forwardData = is_array($msgData['forward_data']) ? $msgData['forward_data'] : [];
            $forwardId = $forwardData['id'] ?: $this->id;
            $forwardUserid = $forwardData['userid'] ?: $this->userid;
            if ($forwardData['show'] === 0) {
                // 如果上一条消息不显示原发送者信息，则转发的消息原始数据为当前消息
                $forwardId = $this->id;
                $forwardUserid = $this->userid;
            }
            $msgData['forward_data'] = [
                'id' => $forwardId,                 // 转发的消息ID（原始）
                'userid' => $forwardUserid,         // 转发的消息会员ID（原始）
                'parent_id' => $this->id,           // 转发的消息ID
                'parent_userid' => $this->userid,   // 转发的消息会员ID
                'show' => $showSource,              // 是否显示原发送者信息
            ];
            $msgs = [];
            $already = [];
            if ($dialogids) {
                if (!is_array($dialogids)) {
                    $dialogids = [$dialogids];
                }
                foreach ($dialogids as $dialogid) {
                    $res = self::sendMsg('forward-' . $forwardId, $dialogid, $this->type, $msgData, $user->userid);
                    if (Base::isSuccess($res)) {
                        $msgs[] = $res['data'];
                        $already[] = $dialogid;
                    }
                    if ($leaveMessage) {
                        $res = self::sendMsg(null, $dialogid, 'text', ['text' => $leaveMessage], $user->userid);
                        if (Base::isSuccess($res)) {
                            $msgs[] = $res['data'];
                        }
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
                        $res = self::sendMsg('forward-' . $forwardId, $dialog->id, $this->type, $msgData, $user->userid);
                        if (Base::isSuccess($res)) {
                            $msgs[] = $res['data'];
                        }
                        if ($leaveMessage) {
                            $res = self::sendMsg(null, $dialog->id, 'text', ['text' => $leaveMessage], $user->userid);
                            if (Base::isSuccess($res)) {
                                $msgs[] = $res['data'];
                            }
                        }
                    }
                }
            }
            if (count($msgs) > 0) {
                $this->increment('forward_num', count($msgs));
            }
            return Base::retSuccess('转发成功', [
                'msgs' => $msgs
            ]);
        });
    }

    /**
     * 删除消息
     * @param array|int $ids
     * @return void
     */
    public static function deleteMsgs($ids) {
        $ids = Base::arrayRetainInt(is_array($ids) ? $ids : [$ids], true);
        AbstractModel::transaction(function() use ($ids) {
            $dialogIds = WebSocketDialogMsg::select('dialog_id')->whereIn("id", $ids)->distinct()->get()->pluck('dialog_id');
            $replyIds = WebSocketDialogMsg::select('reply_id')->whereIn("id", $ids)->distinct()->get()->pluck('reply_id');
            //
            WebSocketDialogMsgRead::whereIn('msg_id', $ids)->whereNull('read_at')->delete();    // 未阅读记录不需要软删除，直接删除即可
            WebSocketDialogMsgTodo::whereIn('msg_id', $ids)->delete();
            self::whereIn('id', $ids)->delete();
            //
            foreach ($dialogIds as $dialogId) {
                WebSocketDialogUser::updateMsgLastAt($dialogId);
            }
            foreach ($replyIds as $id) {
                self::whereId($id)->update(['reply_num' => self::whereReplyId($id)->count()]);
            }
        });
    }

    /**
     * 撤回消息
     * @return void
     */
    public function withdrawMsg()
    {
        $send_dt = Carbon::parse($this->created_at)->addDay();
        if ($send_dt->lt(Carbon::now())) {
            throw new ApiException('已超过24小时，此消息不能撤回');
        }
        AbstractModel::transaction(function() {
            $deleteRead = WebSocketDialogMsgRead::whereMsgId($this->id)->whereNull('read_at')->delete();    // 未阅读记录不需要软删除，直接删除即可
            $this->delete();
            //
            if ($this->reply_id > 0) {
                self::whereId($this->reply_id)->decrement('reply_num');
            }
            //
            $dialogData = $this->webSocketDialog;
            if ($dialogData) {
                foreach ($dialogData->dialogUser as $dialogUser) {
                    $dialogUser->updated_at = Carbon::now();
                    $dialogUser->save();
                }
                $userids = $dialogData->dialogUser->pluck('userid')->toArray();
                PushTask::push([
                    'userid' => $userids,
                    'msg' => [
                        'type' => 'dialog',
                        'mode' => 'delete',
                        'data' => [
                            'id' => $this->id,
                            'dialog_id' => $this->dialog_id,
                            'last_msg' => WebSocketDialogUser::updateMsgLastAt($this->dialog_id),
                            'update_read' => $deleteRead ? 1 : 0
                        ],
                    ]
                ]);
            }
            //
            WebSocketDialogMsgTodo::whereMsgId($this->id)->delete();
        });
    }

    /**
     * 预览消息
     * @param WebSocketDialogMsg|array $data    消息数据
     * @param bool $preserveHtml                保留html格式
     * @return string
     */
    public static function previewMsg($data, $preserveHtml = false)
    {
        if ($data instanceof WebSocketDialogMsg) {
            $data = [
                'type' => $data->type,
                'msg' => $data->msg,
            ];
        }
        if (!is_array($data)) {
            return '';
        }

        switch ($data['type']) {
            case 'text':
                return self::previewTextMsg($data['msg'], $preserveHtml);

            case 'vote':
                $action = Doo::translate("投票");
                return "[{$action}] " . self::previewTextMsg($data['msg'], $preserveHtml);

            case 'word-chain':
                $action = Doo::translate("接龙");
                return "[{$action}] " . self::previewTextMsg($data['msg'], $preserveHtml);

            case 'record':
                $action = Doo::translate("语音");
                return "[{$action}]";

            case 'location':
                $action = Doo::translate("位置");
                return "[{$action}] " . Base::cutStr($data['msg']['title'], 50);

            case 'meeting':
                $action = Doo::translate("会议");
                return "[{$action}] " . Base::cutStr($data['msg']['name'], 50);

            case 'file':
                return self::previewFileMsg($data['msg']);

            case 'tag':
                $action = Doo::translate($data['msg']['action'] === 'remove' ? '取消标注' : '标注');
                return "[{$action}] " . self::previewMsg($data['msg']['data']);

            case 'top':
                $action = Doo::translate($data['msg']['action'] === 'remove' ? '取消置顶' : '置顶');
                return "[{$action}] " . self::previewMsg($data['msg']['data']);

            case 'todo':
                $action = Doo::translate($data['msg']['action'] === 'remove' ? '取消待办' : ($data['msg']['action'] === 'done' ? '完成' : '设待办'));
                return "[{$action}] " . self::previewMsg($data['msg']['data']);

            case 'notice':
                return Base::cutStr(Doo::translate($data['msg']['notice']), 50);

            case 'template':
                return self::previewTemplateMsg($data['msg']);

            case 'preview':
                return $data['msg']['preview'];

            default:
                $action = Doo::translate("未知的消息");
                return "[{$action}]";
        }
    }

    /**
     * 返回文本预览消息
     * @param array $msgData
     * @param bool $preserveHtml    保留html格式
     * @return string|string[]|null
     */
    private static function previewTextMsg($msgData, $preserveHtml = false)
    {
        $text = $msgData['text'] ?? '';
        if (!$text) return '';
        if ($msgData['type'] === 'md') {
            $text = Base::markdown2html($text);
        }
        $text = preg_replace("/<img\s+class=\"emoticon\"[^>]*?alt=\"(\S+)\"[^>]*?>/", "[$1]", $text);
        $text = preg_replace("/<img\s+class=\"emoticon\"[^>]*?>/", "[" . Doo::translate('动画表情') . "]", $text);
        $text = preg_replace("/<img\s+class=\"browse\"[^>]*?>/", "[" . Doo::translate('图片') . "]", $text);
        if (!$preserveHtml) {
            $text = str_replace("</p><p>", "</p> <p>", $text);
            $text = strip_tags($text);
            $text = str_replace(["&nbsp;", "&amp;", "&lt;", "&gt;"], [" ", "&", "<", ">"], $text);
            $text = preg_replace("/\s+/", " ", $text);
            $text = Base::cutStr($text, 50);
        }
        return $text;
    }

    /**
     * 预览文件消息
     * @param $msg
     * @return string
     */
    private static function previewFileMsg($msg)
    {
        if ($msg['type'] == 'img') {
            $action = Doo::translate("图片");
            return "[{$action}]";
        } elseif ($msg['ext'] == 'mp4') {
            $action = Doo::translate("视频");
            return "[{$action}]";
        }
        $action = Doo::translate("文件");
        return "[{$action}] " . Base::cutStr($msg['name'], 50);
    }

    /**
     * 预览模板消息
     * @param $msg
     * @return string
     */
    private static function previewTemplateMsg($msg)
    {
        if (!empty($msg['title_raw'])) {
            return $msg['title_raw'];
        }
        if ($msg['type'] === 'task_list' && count($msg['list']) === 1) {
            return Doo::translate($msg['title']) . ": " . Base::cutStr($msg['list'][0]['name'], 50);
        }
        if (!empty($msg['title'])) {
            return Doo::translate($msg['title']);
        }
        if ($msg['type'] === 'content' && is_string($msg['content']) && $msg['content'] !== '') {
            return Base::cutStr(Doo::translate($msg['content']), 50);
        }
        return Doo::translate('未知的消息');
    }

    /**
     * 生成关键词并保存
     * @param string $key
     * @return void
     */
    public function generateKeyAndSave($key = ''): void
    {
        if (empty($key)) {
            $key = '';
            switch ($this->type) {
                case 'text':
                    if (!preg_match("/<span[^>]*?data-quick-key=([\"'])(.*?)\\1[^>]*?>/is", $this->msg['text'])) {
                        $key = strip_tags($this->msg['text']);
                    }
                    break;

                case 'vote':
                case 'word-chain':
                    $key = strip_tags($this->msg['text']);
                    break;

                case 'file':
                    $key = $this->msg['name'];
                    $key = preg_replace("/^(image|\d+)\.(png|jpg|jpeg|webp|gif)$/i", "", $key);
                    $key = preg_replace("/^LongText-(.*?)/i", "", $key);
                    break;

                case 'meeting':
                    $key = $this->msg['name'];
                    break;
            }
        }
        $key = str_replace(["&quot;", "&amp;", "&lt;", "&gt;"], "", $key);
        $key = str_replace(["\r", "\n", "\t", "&nbsp;"], " ", $key);
        $key = preg_replace("/^\/[A-Za-z]+/", " ", $key);
        $key = preg_replace("/\s+/", " ", $key);
        $this->key = trim($key);
        $this->save();
    }

    /**
     * 返回引用消息（如果是文本只取预览）
     * @return array|mixed
     */
    public function quoteTextMsg()
    {
        $msg = $this->msg;
        if ($this->type === 'text') {
            $msg['text'] = self::previewTextMsg($msg);
        }
        return $msg;
    }

    /**
     * 处理文本消息内容，用于发送前
     * @param $text
     * @param $dialog_id
     * @return mixed|string|string[]
     */
    public static function formatMsg($text, $dialog_id)
    {
        @ini_set("pcre.backtrack_limit", 999999999);
        // 基础处理
        $text = preg_replace("/<(\/[a-zA-Z]+)\s*>/s", "<$1>", $text);
        // 图片 [:IMAGE:className:width:height:src:alt:]
        preg_match_all("/<img\s+src=\"data:image\/(png|jpg|jpeg|webp|gif);base64,(.*?)\"(.*?)>(<\/img>)*/s", $text, $matchs);
        foreach ($matchs[2] as $key => $base64) {
            $imagePath = "uploads/chat/" . date("Ym") . "/" . $dialog_id . "/";
            Base::makeDir(public_path($imagePath));
            $imagePath .= md5s($base64) . "." . $matchs[1][$key];
            if (Base::saveContentImage(public_path($imagePath), base64_decode($base64), 90)) {
                $imageSize = getimagesize(public_path($imagePath));
                if ($extension = Image::thumbImage(public_path($imagePath), public_path($imagePath) . "_thumb.{*}", 320, 0, 80)) {
                    $imagePath .= "_thumb.{$extension}";
                }
                $text = str_replace($matchs[0][$key], "[:IMAGE:browse:{$imageSize[0]}:{$imageSize[1]}:{$imagePath}::]", $text);
            }
        }
        // 表情图片
        preg_match_all("/<img\s+class=\"emoticon\"(.*?)>/s", $text, $matchs);
        foreach ($matchs[1] as $key => $str) {
            preg_match("/data-asset=\"(.*?)\"/", $str, $matchAsset);
            preg_match("/data-name=\"(.*?)\"/", $str, $matchName);
            $imageSize = null;
            $imagePath = "";
            $imageName = "";
            if ($matchAsset[1] === "emosearch") {
                preg_match("/src=\"(.*?)\"/", $str, $matchSrc);
                if ($matchSrc) {
                    $srcMd5 = md5($matchSrc[1]);
                    $imagePath = "uploads/emosearch/" . substr($srcMd5, 0, 2) . "/" . substr($srcMd5, 32 - 2) . "/";
                    Base::makeDir(public_path($imagePath));
                    $imagePath .= md5s($matchSrc[1]);
                    if (file_exists(public_path($imagePath))) {
                        $imageSize = getimagesize(public_path($imagePath));
                    } else {
                        $image = file_get_contents($matchSrc[1]);
                        if ($image && file_put_contents(public_path($imagePath), $image)) {
                            $imageSize = getimagesize(public_path($imagePath));
                            // 添加后缀
                            if ($imageSize && !str_contains($imagePath, '.')) {
                                preg_match("/^image\/(png|jpg|jpeg|webp|gif)$/", $imageSize['mime'], $matchMine);
                                if ($matchMine) {
                                    $imageNewPath = $imagePath . "." . $matchMine[1];
                                    if (rename(public_path($imagePath), public_path($imageNewPath))) {
                                        $imagePath = $imageNewPath;
                                    }
                                }
                            }
                        }
                    }
                }
            } elseif (file_exists(public_path($matchAsset[1]))) {
                $imagePath = $matchAsset[1];
                $imageName = $matchName[1];
                $imageSize = getimagesize(public_path($matchAsset[1]));
            }
            if ($imageSize) {
                $text = str_replace($matchs[0][$key], "[:IMAGE:emoticon:{$imageSize[0]}:{$imageSize[1]}:{$imagePath}:{$imageName}:]", $text);
            } else {
                $text = str_replace($matchs[0][$key], "[:IMAGE:browse:90:90:images/other/imgerr.jpg::]", $text);
            }
        }
        // 其他网络图片
        $imageSaveLocal = Base::settingFind("system", "image_save_local");
        preg_match_all("/<img[^>]*?src=([\"'])(.*?(png|jpg|jpeg|webp|gif).*?)\\1[^>]*?>/is", $text, $matchs);
        foreach ($matchs[2] as $key => $str) {
            if ($imageSaveLocal === 'close') {
                $imageSize = getimagesize($str);
                if ($imageSize === false) {
                    $imageSize = ["auto", "auto"];
                }
                $imagePath = "base64-" . base64_encode($str);
                $text = str_replace($matchs[0][$key], "[:IMAGE:browse:{$imageSize[0]}:{$imageSize[1]}:{$imagePath}::]", $text);
                continue;
            }
            if (str_starts_with($str, "{{RemoteURL}}")) {
                $imagePath = Base::leftDelete($str, "{{RemoteURL}}");
                $imagePath = Base::thumbRestore($imagePath);
            } else {
                $imagePath = "uploads/chat/" . date("Ym") . "/" . $dialog_id . "/";
                Base::makeDir(public_path($imagePath));
                $imagePath .= md5s($str) . "." . $matchs[3][$key];
            }
            if (file_exists(public_path($imagePath))) {
                $imageSize = getimagesize(public_path($imagePath));
                if ($extension = Image::thumbImage(public_path($imagePath), public_path($imagePath) . "_thumb.{*}", 320, 0, 80)) {
                    $imagePath .= "_thumb.{$extension}";
                }
                $text = str_replace($matchs[0][$key], "[:IMAGE:browse:{$imageSize[0]}:{$imageSize[1]}:{$imagePath}::]", $text);
            } else {
                $image = file_get_contents($str);
                if (empty($image)) {
                    $text = str_replace($matchs[0][$key], "[:IMAGE:browse:90:90:images/other/imgerr.jpg::]", $text);
                } else if (Base::saveContentImage(public_path($imagePath), $image, 90)) {
                    $imageSize = getimagesize(public_path($imagePath));
                    if ($extension = Image::thumbImage(public_path($imagePath), public_path($imagePath) . "_thumb.{*}", 320, 0, 80)) {
                        $imagePath .= "_thumb.{$extension}";
                    }
                    $text = str_replace($matchs[0][$key], "[:IMAGE:browse:{$imageSize[0]}:{$imageSize[1]}:{$imagePath}::]", $text);
                }
            }
        }
        // @成员、#任务、~文件
        preg_match_all("/<span\s+class=\"mention\"(.*?)>.*?<\/span>.*?<\/span>.*?<\/span>/s", $text, $matchs);
        foreach ($matchs[1] as $key => $str) {
            preg_match("/data-denotation-char=\"(.*?)\"/", $str, $matchChar);
            preg_match("/data-id=\"(.*?)\"/", $str, $matchId);
            preg_match("/data-value=\"(.*?)\"/s", $str, $matchValye);
            $keyId = $matchId[1];
            if ($matchChar[1] === "~") {
                if (Base::isNumber($keyId)) {
                    $file = File::permissionFind($keyId, User::auth());
                    if ($file->type == 'folder') {
                        throw new ApiException('文件夹不支持分享');
                    }
                    $fileLink = $file->getShareLink(User::userid());
                    $keyId = $fileLink['code'];
                } else {
                    preg_match("/\/single\/file\/(.*?)$/i", $keyId, $match);
                    if ($match && strlen($match[1]) >= 8) {
                        $keyId = $match[1];
                    } else {
                        throw new ApiException('文件分享错误');
                    }
                }
            }
            $text = str_replace($matchs[0][$key], "[:{$matchChar[1]}:{$keyId}:{$matchValye[1]}:]", $text);
        }
        // 处理快捷消息
        preg_match_all("/<span[^>]*?data-quick-key=([\"'])(.*?)\\1[^>]*?>(.*?)<\/span>/is", $text, $matchs);
        foreach ($matchs[0] as $key => $str) {
            $quickKey = $matchs[2][$key];
            $quickLabel = $matchs[3][$key];
            if ($quickKey && $quickLabel) {
                $quickKey = str_replace(":", "", $quickKey);
                $quickLabel = str_replace(":", "", $quickLabel);
                $text = str_replace($str, "[:QUICK:{$quickKey}:{$quickLabel}:]", $text);
            }
        }
        // 处理 li 标签
        preg_match_all("/<li[^>]*?>/i", $text, $matchs);
        foreach ($matchs[0] as $str) {
            if (preg_match("/data-list=['\"](bullet|ordered|checked|unchecked)['\"]/i", $str, $match)) {
                $text = str_replace($str, '<li data-list="' . $match[1] . '">', $text);
            } else {
                $text = str_replace($str, '<li>', $text);
            }
        }
        // 处理链接标签
        preg_match_all("/<a[^>]*?href=([\"'])(.*?)\\1[^>]*?>(.*?)<\/a>/is", $text, $matchs);
        foreach ($matchs[0] as $key => $str) {
            $herf = $matchs[2][$key];
            $title = $matchs[3][$key] ?: $herf;
            preg_match("/\/single\/file\/(.*?)$/i", strip_tags($title), $match);
            if ($match && strlen($match[1]) >= 8) {
                $file = File::select(['files.id', 'files.name', 'files.ext'])->join('file_links as L', 'files.id', '=', 'L.file_id')->where('L.code', $match[1])->first();
                if ($file && $file->name) {
                    $name = $file->ext ? "{$file->name}.{$file->ext}" : $file->name;
                    $text = str_replace($str, "[:~:{$match[1]}:{$name}:]", $text);
                    continue;
                }
            }
            $herf = base64_encode($herf);
            $title = base64_encode($title);
            $text = str_replace($str, "[:LINK:{$herf}:{$title}:]", $text);
        }
        // 文件分享链接
        preg_match_all("/(https*:\/\/)((\w|=|\?|\.|\/|&|-|:|\+|%|;|#|@|,|!)+)/i", $text, $matchs);
        if ($matchs) {
            foreach ($matchs[0] as $str) {
                preg_match("/\/single\/file\/(.*?)$/i", $str, $match);
                if ($match && strlen($match[1]) >= 8) {
                    $file = File::select(['files.id', 'files.name', 'files.ext'])->join('file_links as L', 'files.id', '=', 'L.file_id')->where('L.code', $match[1])->first();
                    if ($file && $file->name) {
                        $name = $file->ext ? "{$file->name}.{$file->ext}" : $file->name;
                        $text = str_replace($str, "[:~:{$match[1]}:{$name}:]", $text);
                    }
                }
            }
        }
        // 过滤标签
        $text = strip_tags($text, '<blockquote> <strong> <pre> <ol> <ul> <li> <em> <p> <s> <u> <a>');
        $text = preg_replace_callback("/\<(blockquote|strong|pre|ol|ul|em|p|s|u)(.*?)\>/is", function (array $match) {    // 不用去除 li 和 a 标签，上面已经处理过了
            preg_match("/<[^>]*?style=([\"'])(.*?)\\1[^>]*?>/is", $match[0], $matchs);
            $attach = '';
            if ($matchs) {
                $styleArray = explode(';', $matchs[2]);
                $validStyles = array_filter($styleArray, function ($styleItem) {
                    return preg_match('/\s*(?:color|font-size|background-color|font-weight|font-family|text-decoration|font-style)\s*:/i', $styleItem); // 只保留指定样式
                });
                if ($validStyles) {
                    $attach = ' style="' . implode(';', $validStyles) . '"';
                }
            }
            return "<{$match[1]}{$attach}>";
        }, $text);
        $text = preg_replace_callback("/\[:LINK:(.*?):(.*?):\]/i", function (array $match) {
            return "<a href=\"" . base64_decode($match[1]) . "\" target=\"_blank\">" . base64_decode($match[2]) . "</a>";
        }, $text);
        $text = preg_replace_callback("/\[:IMAGE:(.*?):(.*?):(.*?):(.*?):(.*?):\]/i", function (array $match) {
            $wh = $match[2] === 'auto' ? "" : " width=\"{$match[2]}\" height=\"{$match[3]}\"";
            $src = str_starts_with($match[4], "base64-") ? base64_decode(substr($match[4], 7)) : "{{RemoteURL}}{$match[4]}";
            return "<img class=\"{$match[1]}\"{$wh} src=\"{$src}\" alt=\"{$match[5]}\"/>";
        }, $text);
        $text = preg_replace("/\[:@:(.*?):(.*?):\]/i", "<span class=\"mention user\" data-id=\"$1\">@$2</span>", $text);
        $text = preg_replace("/\[:#:(.*?):(.*?):\]/is", "<span class=\"mention task\" data-id=\"$1\">#$2</span>", $text);
        $text = preg_replace("/\[:~:(.*?):(.*?):\]/i", "<a class=\"mention file\" href=\"{{RemoteURL}}single/file/$1\" target=\"_blank\">~$2</a>", $text);
        $text = preg_replace("/\[:QUICK:(.*?):(.*?):\]/i", "<span data-quick-key=\"$1\">$2</span>", $text);
        return preg_replace("/^(<p><\/p>)+|(<p><\/p>)+$/i", "", $text);
    }

    /**
     * 发送消息、修改消息
     * @param string $action            动作
     * - null：发送消息
     * - reply-98：回复消息ID=98
     * - update-99：更新消息ID=99（标记修改）
     * - change-99：更新消息ID=99（不标记修改）
     * - forward-99：转发消息ID=99
     * @param int $dialog_id            会话ID（即 聊天室ID）
     * @param string $type              消息类型
     * @param array $msg                发送的消息
     * @param int|null $sender          发送的会员ID（默认自己，0为系统）
     * @param bool $push_self           推送-是否推给自己
     * @param bool $push_retry          推送-失败后重试1次（有时候在事务里执行，数据还没生成时会出现找不到消息的情况）
     * @param bool|null $push_silence   推送-静默
     * - type = [text|file|record|meeting]  默认为：false
     * @param string|null $search_key   搜索关键词（用于搜索，留空则自动生成）
     * @return array
     */
    public static function sendMsg($action, $dialog_id, $type, $msg, $sender = null, $push_self = false, $push_retry = false, $push_silence = null, $search_key = null)
    {
        $link = 0;
        $mtype = $type;
        if ($type === 'text') {
            if (str_contains($msg['text'], '<a ') || preg_match("/https*:\/\//", $msg['text'])) {
                $link = 1;
            }
            if (str_contains($msg['text'], '<img ')) {
                $mtype = str_contains($msg['text'], '"emoticon"') ? 'emoticon' : 'image';
            }
            preg_match_all("/@([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,6})/i", $msg['text'], $matchs);
            foreach($matchs[0] as $key => $item) {
                $aiUser = User::whereEmail($matchs[1][$key])->whereDisableAt(null)->first();
                if ($aiUser) {
                    $msg['text'] = str_replace($item, "<span class=\"mention user\" data-id=\"{$aiUser->userid}\">@{$aiUser->nickname}</span>", $msg['text']);
                }
            }
        } elseif ($type === 'file') {
            if (in_array($msg['ext'], ['jpg', 'jpeg', 'webp', 'png', 'gif'])) {
                $mtype = 'image';
            }
        } elseif ($type === 'location') {
            if (preg_match('/^https*:\/\//', $msg['thumb'])) {
                $thumb = file_get_contents($msg['thumb']);
                if (empty($thumb)) {
                    throw new ApiException('获取地图快照失败');
                }
                $fileUrl = "uploads/chat/" . date("Ym") . "/" . $dialog_id . "/" . md5s($msg['thumb']) . ".jpg";
                $filePath = public_path($fileUrl);
                Base::makeDir(dirname($filePath));
                if (!Base::saveContentImage($filePath, $thumb, 90)) {
                    throw new ApiException('保存地图快照失败');
                }
                $imageSize = getimagesize($filePath);
                if ($imageSize[0] < 20 || $imageSize[1] < 20) {
                    throw new ApiException('地图快照尺寸太小');
                }
                $msg['thumb_original'] = $msg['thumb'];
                $msg['thumb'] = $fileUrl;
                $msg['width'] = $imageSize[0];
                $msg['height'] = $imageSize[1];
            }
        }
        if ($push_silence === null) {
            $push_silence = !in_array($type, ["text", "file", "record", "meeting"]);
        }
        //
        $update_id = intval(preg_match("/^update-(\d+)$/", $action, $match) ? $match[1] : 0);
        $change_id = intval(preg_match("/^change-(\d+)$/", $action, $match) ? $match[1] : 0);
        $reply_id = intval(preg_match("/^reply-(\d+)$/", $action, $match) ? $match[1] : 0);
        $forward_id = intval(preg_match("/^forward-(\d+)$/", $action, $match) ? $match[1] : 0);
        $sender = $sender === null ? User::userid() : $sender;
        //
        $dialog = WebSocketDialog::find($dialog_id);
        if (empty($dialog)) {
            throw new ApiException('获取会话失败');
        }
        if ($sender > 0) {
            $dialog->checkMute($sender);
        }
        //
        $modify = 1;
        if ($change_id) {
            $modify = 0;
            $update_id = $change_id;
        }
        if ($update_id) {
            // 修改
            $dialogMsg = self::whereId($update_id)->whereDialogId($dialog_id)->first();
            if (empty($dialogMsg)) {
                throw new ApiException('消息不存在');
            }
            $oldMsg = Base::json2array($dialogMsg->getRawOriginal('msg'));
            if ($dialogMsg->type === 'vote') {
                if ($dialogMsg->userid != $sender) {
                    $msg = [
                        'votes' => $msg['votes'],
                    ];
                }
            } else {
                if (!in_array($dialogMsg->type, ['text', 'template'])) {
                    throw new ApiException('此消息不支持此操作');
                }
                if ($dialogMsg->userid != $sender) {
                    throw new ApiException('仅支持修改自己的消息');
                }
            }
            //
            $updateData = [
                'mtype' => $mtype,
                'link' => $link,
                'msg' => array_merge($oldMsg, $msg),
                'modify' => $modify,
            ];
            $dialogMsg->updateInstance($updateData);
            $dialogMsg->generateKeyAndSave($search_key);
            //
            WebSocketDialogUser::whereDialogId($dialog->id)->whereUserid($sender)->whereHide(1)->change([
                'hide' => 0,    // 修改消息时，显示会话（仅自己）
                'updated_at' => Carbon::now()->toDateTimeString('millisecond'),
            ]);
            //
            $dialogMsg->msgJoinGroup($dialog);
            //
            $dialog->pushMsg('update', array_merge($updateData, [
                'id' => $dialogMsg->id
            ]));
            //
            return Base::retSuccess('修改成功', $dialogMsg);
        } else {
            // 发送
            if ($reply_id) {
                // 回复
                $replyRow = self::whereId($reply_id)->whereDialogId($dialog_id)->first();
                if (empty($replyRow)) {
                    throw new ApiException('回复的消息不存在');
                }
                $replyMsg = Base::json2array($replyRow->getRawOriginal('msg'));
                unset($replyMsg['reply_data']);
                $msg['reply_data'] = [
                    'id' => $replyRow->id,
                    'userid' => $replyRow->userid,
                    'type' => $replyRow->type,
                    'msg' => $replyMsg,
                ];
                $replyRow->increment('reply_num');
            }
            //
            $dialogMsg = self::createInstance([
                'dialog_id' => $dialog_id,
                'dialog_type' => $dialog->type,
                'reply_id' => $reply_id,
                'forward_id' => $forward_id,
                'userid' => $sender,
                'type' => $type,
                'mtype' => $mtype,
                'link' => $link,
                'msg' => $msg,
                'read' => 0,
            ]);
            AbstractModel::transaction(function () use ($search_key, $dialogMsg) {
                $dialogMsg->send = 1;
                $dialogMsg->generateKeyAndSave($search_key);
                //
                if ($dialogMsg->type === 'meeting') {
                    MeetingMsg::createInstance([
                        'meetingid' => $dialogMsg->msg['meetingid'],
                        'dialog_id' => $dialogMsg->dialog_id,
                        'msg_id' => $dialogMsg->id,
                    ])->save();
                }
                //
                WebSocketDialogUser::whereDialogId($dialogMsg->dialog_id)->change([
                    'hide' => 0,    // 有新消息时，显示会话（会话内所有会员）
                    'last_at' => Carbon::now(),
                    'updated_at' => Carbon::now()->toDateTimeString('millisecond'),
                ]);
            });
            //
            $task = new WebSocketDialogMsgTask($dialogMsg->id);
            if ($push_self) {
                $task->setIgnoreFd(null);
            }
            if ($push_retry) {
                $task->setMsgNotExistRetry(true);
            }
            if ($push_silence) {
                $task->setSilence($push_silence);
            }
            Task::deliver($task);
            //
            return Base::retSuccess('发送成功', $dialogMsg);
        }
    }

    /**
     * 将被@的人加入群
     * @param WebSocketDialog $dialog 对话
     * @return array
     */
    public function msgJoinGroup(WebSocketDialog $dialog)
    {
        $updateds = [];
        $silences = [];
        foreach ($dialog->dialogUser as $dialogUser) {
            $updateds[$dialogUser->userid] = $dialogUser->updated_at;
            $silences[$dialogUser->userid] = $dialogUser->silence;
        }
        $userids = array_keys($silences);

        // 提及会员
        $mentions = [];
        if ($this->type === 'text') {
            preg_match_all("/<span class=\"mention user\" data-id=\"(\d+)\">/", $this->msg['text'], $matchs);
            if ($matchs) {
                $mentions = array_values(array_filter(array_unique($matchs[1])));
            }
        }

        // 将会话以外的成员加入会话内
        $diffids = array_values(array_diff($mentions, $userids));
        if ($diffids) {
            // 仅(群聊)且(是群主或没有群主)才可以@成员以外的人
            if ($dialog->type === 'group' && in_array($dialog->owner_id, [0, $this->userid])) {
                $dialog->joinGroup($diffids, $this->userid);
                $dialog->pushMsg("groupJoin", null, $diffids);
                $userids = array_values(array_unique(array_merge($mentions, $userids)));
            }
        }

        return compact('updateds', 'silences', 'userids', 'mentions');
    }
}
