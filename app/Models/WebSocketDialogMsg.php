<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Module\Base;
use App\Tasks\PushTask;
use App\Tasks\WebSocketDialogMsgTask;
use Carbon\Carbon;
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
 * @property array|mixed $msg 详细消息
 * @property array|mixed $emoji emoji回复
 * @property int|null $read 已阅数量
 * @property int|null $send 发送数量
 * @property int|null $reply_id 回复ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int|mixed $percentage
 * @property-read \App\Models\WebSocketDialogMsg|null $reply_data
 * @property-read \App\Models\WebSocketDialog|null $webSocketDialog
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg newQuery()
 * @method static \Illuminate\Database\Query\Builder|WebSocketDialogMsg onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg query()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereDialogType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereEmoji($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereMsg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereReplyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereSend($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocketDialogMsg whereUserid($value)
 * @method static \Illuminate\Database\Query\Builder|WebSocketDialogMsg withTrashed()
 * @method static \Illuminate\Database\Query\Builder|WebSocketDialogMsg withoutTrashed()
 * @mixin \Eloquent
 */
class WebSocketDialogMsg extends AbstractModel
{
    use SoftDeletes;

    protected $appends = [
        'percentage',
        'reply_data',
    ];

    protected $hidden = [
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
     * 回复消息详情
     * @return WebSocketDialogMsg|null
     */
    public function getReplyDataAttribute()
    {
        if (!isset($this->appendattrs['reply_data'])) {
            $this->appendattrs['reply_data'] = null;
            if ($this->reply_id > 0) {
                $this->appendattrs['reply_data'] = self::find($this->reply_id, ['id', 'userid', 'type', 'msg'])?->cancelAppend() ?: null;
            }
        }
        return $this->appendattrs['reply_data'];
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
        $value = Base::json2array($value);
        if ($this->type === 'file') {
            $value['type'] = in_array($value['ext'], ['jpg', 'jpeg', 'png', 'gif']) ? 'img' : 'file';
            $value['path'] = Base::fillUrl($value['path']);
            $value['thumb'] = Base::fillUrl($value['thumb'] ?: Base::extIcon($value['ext']));
        } else if ($this->type === 'record') {
            $value['path'] = Base::fillUrl($value['path']);
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
        return Base::retSuccess('sucess', $resData);
    }

    /**
     * 转发消息
     * @param $userids
     * @param int $sender       发送的会员ID
     * @return mixed
     */
    public function forwardMsg($userids, $sender)
    {
        return AbstractModel::transaction(function() use ($sender, $userids) {
            $msgs = [];
            foreach ($userids as $userid) {
                if (!User::whereUserid($userid)->exists()) {
                    continue;
                }
                $dialog = WebSocketDialog::checkUserDialog($sender, $userid);
                if ($dialog) {
                    $res = self::sendMsg($dialog->id, 0, $this->type, $this->getOriginal('msg'), $sender);
                    if (Base::isSuccess($res)) {
                        $msgs[] = $res['data'];
                    }
                }
            }
            return Base::retSuccess('转发成功', [
                'msgs' => $msgs
            ]);
        });
    }

    /**
     * 删除消息
     * @return void
     */
    public function deleteMsg()
    {
        $send_dt = Carbon::parse($this->created_at)->addDay();
        if ($send_dt->lt(Carbon::now())) {
            throw new ApiException('已超过24小时，此消息不能撤回');
        }
        AbstractModel::transaction(function() {
            $deleteRead = WebSocketDialogMsgRead::whereMsgId($this->id)->whereNull('read_at')->delete();    // 未阅读记录不需要软删除，直接删除即可
            $this->delete();
            //
            $last_msg = null;
            if ($this->webSocketDialog) {
                $last_msg = WebSocketDialogMsg::whereDialogId($this->dialog_id)->orderByDesc('id')->first();
                $this->webSocketDialog->last_at = $last_msg->created_at;
                $this->webSocketDialog->save();
            }
            //
            $dialog = WebSocketDialog::find($this->dialog_id);
            if ($dialog) {
                $userids = $dialog->dialogUser->pluck('userid')->toArray();
                PushTask::push([
                    'userid' => $userids,
                    'msg' => [
                        'type' => 'dialog',
                        'mode' => 'delete',
                        'data' => [
                            'id' => $this->id,
                            'dialog_id' => $this->dialog_id,
                            'last_msg' => $last_msg,
                            'update_read' => $deleteRead ? 1 : 0
                        ],
                    ]
                ]);
            }
        });
    }

    /**
     * 预览消息
     * @param bool $preserveHtml    保留html格式
     * @return string
     */
    public function previewMsg($preserveHtml = false)
    {
        switch ($this->type) {
            case 'text':
                return $this->previewTextMsg($this->msg['text'], $preserveHtml);
            case 'record':
                return "[语音]";
            case 'meeting':
                return "[会议] ${$this->msg['name']}";
            case 'file':
                if ($this->msg['type'] == 'img') {
                    return "[图片]";
                }
                return "[文件] {$this->msg['name']}";
            default:
                return "[未知的消息]";
        }
    }

    /**
     * 返回文本预览消息
     * @param $text
     * @param bool $preserveHtml    保留html格式
     * @return string|string[]|null
     */
    private function previewTextMsg($text, $preserveHtml = false)
    {
        if (!$text) return '';
        $text = preg_replace("/<img\s+class=\"emoticon\"[^>]*?alt=\"(\S+)\"[^>]*?>/", "[$1]", $text);
        $text = preg_replace("/<img\s+class=\"emoticon\"[^>]*?>/", "[表情]", $text);
        $text = preg_replace("/<img\s+class=\"browse\"[^>]*?>/", "[图片]", $text);
        if ($preserveHtml) {
            return $text;
        } else {
            return strip_tags($text);
        }
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
        // 图片 [:IMAGE:className:width:height:src:alt:]
        preg_match_all("/<img\s+src=\"data:image\/(png|jpg|jpeg|gif);base64,(.*?)\"(.*?)>(<\/img>)*/s", $text, $matchs);
        foreach ($matchs[2] as $key => $base64) {
            $tmpPath = "uploads/chat/" . date("Ym") . "/" . $dialog_id . "/";
            Base::makeDir(public_path($tmpPath));
            $tmpPath .= md5s($base64) . "." . $matchs[1][$key];
            if (file_put_contents(public_path($tmpPath), base64_decode($base64))) {
                $imagesize = getimagesize(public_path($tmpPath));
                if (Base::imgThumb(public_path($tmpPath), public_path($tmpPath) . "_thumb.jpg", 320, 0)) {
                    $tmpPath .= "_thumb.jpg";
                }
                $text = str_replace($matchs[0][$key], "[:IMAGE:browse:{$imagesize[0]}:{$imagesize[1]}:{$tmpPath}::]", $text);
            }
        }
        // 表情图片
        preg_match_all("/<img\s+class=\"emoticon\"(.*?)>/s", $text, $matchs);
        foreach ($matchs[1] as $key => $str) {
            preg_match("/data-asset=\"(.*?)\"/", $str, $matchAsset);
            preg_match("/data-name=\"(.*?)\"/", $str, $matchName);
            if (file_exists(public_path($matchAsset[1]))) {
                $imagesize = getimagesize(public_path($matchAsset[1]));
                $text = str_replace($matchs[0][$key], "[:IMAGE:emoticon:{$imagesize[0]}:{$imagesize[1]}:{$matchAsset[1]}:{$matchName[1]}:]", $text);
            }
        }
        // 其他网络图片
        preg_match_all("/<img[^>]*?src=([\"'])(.*?\.(png|jpg|jpeg|gif))\\1[^>]*?>/is", $text, $matchs);
        foreach ($matchs[2] as $key => $str) {
            $tmpPath = "uploads/chat/" . date("Ym") . "/" . $dialog_id . "/";
            Base::makeDir(public_path($tmpPath));
            $tmpPath .= md5s($str) . "." . $matchs[3][$key];
            if (file_exists(public_path($tmpPath))) {
                $imagesize = getimagesize(public_path($tmpPath));
                if (Base::imgThumb(public_path($tmpPath), public_path($tmpPath) . "_thumb.jpg", 320, 0)) {
                    $tmpPath .= "_thumb.jpg";
                }
                $text = str_replace($matchs[0][$key], "[:IMAGE:browse:{$imagesize[0]}:{$imagesize[1]}:{$tmpPath}::]", $text);
            } else {
                $image = file_get_contents($str);
                if (empty($image)) {
                    $text = str_replace($matchs[0][$key], "[:IMAGE:browse:90:90:images/other/imgerr.jpg::]", $text);
                } else if (file_put_contents(public_path($tmpPath), $image)) {
                    $imagesize = getimagesize(public_path($tmpPath));
                    if (Base::imgThumb(public_path($tmpPath), public_path($tmpPath) . "_thumb.jpg", 320, 0)) {
                        $tmpPath .= "_thumb.jpg";
                    }
                    $text = str_replace($matchs[0][$key], "[:IMAGE:browse:{$imagesize[0]}:{$imagesize[1]}:{$tmpPath}::]", $text);
                }
            }
        }
        // @成员、#任务
        preg_match_all("/<span\s+class=\"mention\"(.*?)>.*?<\/span>.*?<\/span>.*?<\/span>/s", $text, $matchs);
        foreach ($matchs[1] as $key => $str) {
            preg_match("/data-denotation-char=\"(.*?)\"/", $str, $matchChar);
            preg_match("/data-id=\"(.*?)\"/", $str, $matchId);
            preg_match("/data-value=\"(.*?)\"/", $str, $matchValye);
            $text = str_replace($matchs[0][$key], "[:{$matchChar[1]}:{$matchId[1]}:{$matchValye[1]}:]", $text);
        }
        // 处理链接
        preg_match_all("/<a[^>]*?href=([\"'])(.*?)\\1[^>]*?>([^<]*?)<\/a>/is", $text, $matchs);
        foreach ($matchs[2] as $key => $str) {
            $herf = $matchs[2][$key];
            $title = $matchs[3][$key] ?: $herf;
            $text = str_replace($matchs[0][$key], "<a href=\"{$herf}\" target=\"_blank\">{$title}</a>", $text);
        }
        // 过滤标签
        $text = strip_tags($text, '<blockquote> <strong> <pre> <ol> <ul> <li> <em> <p> <s> <u> <a>');
        $text = preg_replace("/\<(blockquote|strong|pre|ol|ul|li|em|p|s|u).*?\>/is", "<$1>", $text);    // 不用去除a标签，上面已经处理过了
        $text = preg_replace("/\[:IMAGE:(.*?):(.*?):(.*?):(.*?):(.*?):\]/i", "<img class=\"$1\" width=\"$2\" height=\"$3\" src=\"{{RemoteURL}}$4\" alt=\"$5\"/>", $text);
        $text = preg_replace("/\[:@:(.*?):(.*?):\]/i", "<span class=\"mention user\" data-id=\"$1\">@$2</span>", $text);
        $text = preg_replace("/\[:#:(.*?):(.*?):\]/i", "<span class=\"mention task\" data-id=\"$1\">#$2</span>", $text);
        return preg_replace("/^(<p><\/p>)+|(<p><\/p>)+$/i", "", $text);
    }

    /**
     * 发送消息
     * @param int $dialog_id    会话ID（即 聊天室ID）
     * @param int $reply_id     回复ID
     * @param string $type      消息类型
     * @param array $msg        发送的消息
     * @param int $sender       发送的会员ID（默认自己，0为系统）
     * @return array
     */
    public static function sendMsg($dialog_id, $reply_id, $type, $msg, $sender = 0)
    {
        $dialogMsg = self::createInstance([
            'dialog_id' => $dialog_id,
            'reply_id' => $reply_id,
            'userid' => $sender ?: User::userid(),
            'type' => $type,
            'msg' => $msg,
            'read' => 0,
        ]);
        AbstractModel::transaction(function () use ($dialogMsg) {
            $dialog = WebSocketDialog::find($dialogMsg->dialog_id);
            if (empty($dialog)) {
                throw new ApiException('获取会话失败');
            }
            $dialog->last_at = Carbon::now();
            $dialog->save();
            $dialogMsg->send = 1;
            $dialogMsg->dialog_type = $dialog->type;
            $dialogMsg->save();
        });
        Task::deliver(new WebSocketDialogMsgTask($dialogMsg->id));
        return Base::retSuccess('发送成功', $dialogMsg);
    }
}
