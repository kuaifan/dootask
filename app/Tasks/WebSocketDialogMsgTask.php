<?php

namespace App\Tasks;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use App\Models\User;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgRead;
use App\Module\Base;
use Carbon\Carbon;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Request;


/**
 * 推送会话消息
 * Class WebSocketDialogMsgTask
 * @package App\Tasks
 */
class WebSocketDialogMsgTask extends AbstractTask
{
    protected $id;
    protected $ignoreFd;
    protected $msgNotExistRetry = false;    // 推送失败后重试
    protected $silence = false;             // 静默推送（前端不通知、App不推送，如果会话设置了免打扰则强制静默）
    protected $client = [];                 // 客户端信息（版本、语言、平台）
    protected $endPush = [];
    protected $endArray = [];

    /**
     * WebSocketDialogMsgTask constructor.
     * @param int $id         消息ID
     * @param mixed $ignoreFd
     */
    public function __construct($id, $ignoreFd = null)
    {
        parent::__construct(...func_get_args());
        $this->id = $id;
        $this->ignoreFd = $ignoreFd === null ? Request::header('fd') : $ignoreFd;
        $this->client = [
            'version' => Base::headerOrInput('version'),
            'language' => Base::headerOrInput('language'),
            'platform' => Base::headerOrInput('platform'),
        ];
    }

    /**
     * @param $ignoreFd
     */
    public function setIgnoreFd($ignoreFd)
    {
        $this->ignoreFd = $ignoreFd;
    }

    /**
     * @param bool $msgNotExistRetry
     */
    public function setMsgNotExistRetry(bool $msgNotExistRetry): void
    {
        $this->msgNotExistRetry = $msgNotExistRetry;
    }

    /**
     * @param bool $silence
     */
    public function setSilence(bool $silence): void
    {
        $this->silence = $silence;
    }

    public function start()
    {
        global $_A;
        $_A = [
            '__fill_url_remote_url' => true,
        ];

        //
        $msg = WebSocketDialogMsg::find($this->id);
        if (empty($msg)) {
            if ($this->msgNotExistRetry) {
                $task = new WebSocketDialogMsgTask($this->id, $this->ignoreFd || '');
                $task->delay(1);
                $this->endArray[] = $task;
            }
            return;
        }
        $dialog = WebSocketDialog::find($msg->dialog_id);
        if (empty($dialog)) {
            return;
        }
        $updateds = [];
        $silences = [];
        foreach ($dialog->dialogUser as $dialogUser) {
            $updateds[$dialogUser->userid] = $dialogUser->updated_at;
            $silences[$dialogUser->userid] = $dialogUser->silence;
        }
        $userids = array_keys($silences);

        // 提及会员
        $mentions = [];
        if ($msg->type === 'text') {
            preg_match_all("/<span class=\"mention user\" data-id=\"(\d+)\">/", $msg->msg['text'], $matchs);
            if ($matchs) {
                $mentions = array_values(array_filter(array_unique($matchs[1])));
            }
        }

        // 将会话以外的成员加入会话内
        $diffids = array_values(array_diff($mentions, $userids));
        if ($diffids) {
            // 仅(群聊)且(是群主或没有群主)才可以@成员以外的人
            if ($dialog->type === 'group' && in_array($dialog->owner_id, [0, $msg->userid])) {
                $dialog->joinGroup($diffids, $msg->userid);
                $dialog->pushMsg("groupJoin", null, $diffids);
                $userids = array_values(array_unique(array_merge($mentions, $userids)));
            }
        }

        // 推送目标①：会话成员/群成员
        $array = [];
        foreach ($userids AS $userid) {
            $silence = $this->silence || $silences[$userid];
            $updated = $updateds[$userid] ?? $msg->created_at;
            if ($userid == $msg->userid) {
                $array[$userid] = [
                    'userid' => $userid,
                    'mention' => false,
                    'silence' => $silence,
                    'updated' => $updated,
                ];
            } else {
                $mention = array_intersect([0, $userid], $mentions) ? 1 : 0;
                $silence = $mention ? false : $silence;
                WebSocketDialogMsgRead::createInstance([
                    'dialog_id' => $msg->dialog_id,
                    'msg_id' => $msg->id,
                    'userid' => $userid,
                    'mention' => $mention,
                    'silence' => $silence,
                ])->saveOrIgnore();
                $array[$userid] = [
                    'userid' => $userid,
                    'mention' => $mention,
                    'silence' => $silence,
                    'updated' => $updated,
                ];
                // 机器人收到消处理
                $botUser = User::whereUserid($userid)->whereBot(1)->first();
                if ($botUser) {
                    $this->endArray[] = new BotReceiveMsgTask($botUser->userid, $msg->id, $mention, $this->client);
                }
            }
        }
        // 更新已发送数量
        $msg->send = WebSocketDialogMsgRead::whereMsgId($msg->id)->count();
        $msg->save();
        // 开始推送消息
        $umengUserid = [];
        foreach ($array as $item) {
            $this->endPush[] = [
                'userid' => $item['userid'],
                'ignoreFd' => $this->ignoreFd,
                'msg' => [
                    'type' => 'dialog',
                    'mode' => 'add',
                    'silence' => $item['silence'] ? 1 : 0,
                    'data' => array_merge($msg->toArray(), [
                        'mention' => $item['mention'],
                        'user_at' => Carbon::parse($item['updated'])->toDateTimeString('millisecond'),
                        'user_ms' => Carbon::parse($item['updated'])->valueOf(),
                    ]),
                ]
            ];
            if ($item['userid'] != $msg->userid && !$item['silence'] && !$this->silence) {
                $umengUserid[] = $item['userid'];
            }
        }
        // umeng推送app
        if ($umengUserid) {
            $setting = Base::setting('appPushSetting');
            if ($setting['push'] === 'open') {
                $umengTitle = User::userid2nickname($msg->userid);
                if ($dialog->type == 'group') {
                    $umengTitle = "{$dialog->getGroupName()} ($umengTitle)";
                }
                $this->endArray[] = new PushUmengMsg($umengUserid, [
                    'title' => $umengTitle,
                    'body' => $msg->previewMsg(),
                    'description' => "MID:{$msg->id}",
                    'seconds' => 3600,
                    'badge' => 1,
                ]);
            }
        }

        // 推送目标②：正在打开这个任务会话的会员
        if ($dialog->type == 'group' && $dialog->group_type == 'task') {
            $list = User::whereTaskDialogId($dialog->id)->pluck('userid')->toArray();
            if ($list) {
                $array = [];
                foreach ($list as $item) {
                    if (!in_array($item, $userids)) {
                        $array[] = $item;
                    }
                }
                if ($array) {
                    $this->endPush[] = [
                        'userid' => $array,
                        'ignoreFd' => $this->ignoreFd,
                        'msg' => [
                            'type' => 'dialog',
                            'mode' => 'chat',
                            'silence' => $this->silence ? 1 : 0,
                            'data' => array_merge($msg->toArray(), [
                                'user_at' => Carbon::parse($msg->created_at)->toDateTimeString('millisecond'),
                                'user_ms' => Carbon::parse($msg->created_at)->valueOf(),
                            ]),
                        ]
                    ];
                }
            }
        }
    }

    public function end()
    {
        foreach ($this->endArray as $task) {
            Task::deliver($task);
        }
        PushTask::push($this->endPush);
    }
}
