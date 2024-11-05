<?php

namespace App\Tasks;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use App\Models\User;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgRead;
use App\Module\Base;
use App\Module\Doo;
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
        // 判断是否有Request方法，兼容go协程请求
        $this->ignoreFd = $ignoreFd;
        $this->client = [];
        if (method_exists(request(), "header")) {
            $this->ignoreFd = $ignoreFd === null ? Request::header('fd') : $ignoreFd;
            $this->client = [
                'version' => Base::headerOrInput('version'),
                'language' => Base::headerOrInput('language'),
                'platform' => Base::headerOrInput('platform'),
            ];
        }
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

        // 将会话以外的成员加入会话内
        $msgJoinGroupResult = $msg->msgJoinGroup($dialog);
        $updateds = $msgJoinGroupResult['updateds'];
        $silences = $msgJoinGroupResult['silences'];
        $userids = $msgJoinGroupResult['userids'];
        $mentions = $msgJoinGroupResult['mentions'];

        // 推送目标①：会话成员/群成员
        $array = [];
        foreach ($userids AS $userid) {
            $silence = $this->silence || $silences[$userid];
            $updated = $updateds[$userid] ?? $msg->created_at;
            if ($userid == $msg->userid) {
                $array[$userid] = [
                    'userid' => $userid,
                    'mention' => 0,
                    'silence' => $silence,
                    'dot' => 0,
                    'updated' => $updated,
                ];
            } else {
                $mention = array_intersect([0, $userid], $mentions) ? 1 : 0;
                $silence = $mention ? false : $silence;
                $dot = $msg->type === 'record' ? 1 : 0;
                WebSocketDialogMsgRead::createInstance([
                    'dialog_id' => $msg->dialog_id,
                    'msg_id' => $msg->id,
                    'userid' => $userid,
                    'mention' => $mention,
                    'silence' => $silence,
                    'dot' => $dot,
                ])->saveOrIgnore();
                $array[$userid] = [
                    'userid' => $userid,
                    'mention' => $mention,
                    'silence' => $silence,
                    'dot' => $dot,
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
        // 没有接收人时通知发送人已读
        if ($msg->send === 0) {
            PushTask::push([
                'userid' => $msg->userid,
                'msg' => [
                    'type' => 'dialog',
                    'mode' => 'readed',
                    'data' => [
                        'id' => $msg->id,
                        'read' => $msg->read,
                        'percentage' => $msg->percentage,
                    ],
                ]
            ]);
        }
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
                        'dot' => $item['dot'],
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
                $langs = User::select(['userid', 'lang'])
                    ->whereIn('userid', $umengUserid)
                    ->get()
                    ->groupBy('lang')
                    ->map(function($group) {
                        return $group->pluck('userid');
                    });
                foreach ($langs as $lang => $uids) {
                    Doo::setLanguage($lang);
                    $umengMsg = [
                        'title' => $umengTitle,
                        'body' => WebSocketDialogMsg::previewMsg($msg),
                        'description' => "MID:{$msg->id}",
                        'seconds' => 3600,
                        'badge' => 1,
                    ];
                    $this->endArray[] = new PushUmengMsg($uids->toArray(), $umengMsg);
                }
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
