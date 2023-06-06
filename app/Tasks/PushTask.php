<?php
namespace App\Tasks;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use App\Models\WebSocket;
use App\Models\WebSocketTmpMsg;
use App\Module\Base;
use App\Module\Doo;
use Cache;
use Carbon\Carbon;
use Hhxsv5\LaravelS\Swoole\Task\Task;

/**
 * 发送消息任务
 * Class PushTask
 * @package App\Tasks
 */
class PushTask extends AbstractTask
{
    protected $params;
    protected $retryOffline = true;
    protected $endPush = [];

    /**
     * PushTask constructor.
     * @param array|string $params
     */
    public function __construct($params = [],  $retryOffline = true)
    {
        parent::__construct(...func_get_args());
        $this->params = $params;
        $this->retryOffline = $retryOffline;
    }

    public function start()
    {
        if (is_string($this->params)) {
            // 推送缓存
            if (Base::leftExists($this->params, "PUSH::")) {
                $params = Cache::pull($this->params);
                if (is_array($params) && $params['fd']) {
                    $this->params = [$params];
                }
            }
            // 根据会员ID推送离线时收到的消息
            elseif (Base::leftExists($this->params, "RETRY::")) {
                $this->sendTmpMsgForUserid(intval(Base::leftDelete($this->params, "RETRY::")));
            }
        }
        is_array($this->params) && self::push($this->params, $this->retryOffline);
    }

    public function end()
    {
        self::push($this->endPush);
    }

    /**
     * 根据会员ID推送离线时收到的消息
     * @param $userid
     */
    private function sendTmpMsgForUserid($userid)
    {
        if (empty($userid)) {
            return;
        }
        WebSocketTmpMsg::whereCreateId($userid)
            ->whereSend(0)
            ->where('created_at', '>', Carbon::now()->subMinute())  // 1分钟内添加的数据
            ->orderBy('id')
            ->chunk(100, function($list) use ($userid) {
                foreach ($list as $item) {
                    $this->endPush[] = [
                        'tmpMsgId' => $item->id,
                        'userid' => $userid,
                        'msg' => Base::json2array($item->msg),
                    ];
                }
            });
    }

    /**
     * 记录离线消息，等上线后重新发送
     * @param array $userFail
     * @param array $msg
     */
    private static function addTmpMsg(array $userFail, array $msg)
    {
        foreach ($userFail as $uid) {
            $msgString = Base::array2json($msg);
            $inArray = [
                'md5' => md5($uid . '-' . $msgString),
                'msg' => $msgString,
                'send' => 0,
                'create_id' => $uid,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            if (!WebSocketTmpMsg::whereMd5($inArray['md5'])->exists()) {
                WebSocketTmpMsg::insertOrIgnore($inArray);
            }
        }
    }

    /**
     * 推送消息
     * @param array $lists 消息列表
     * @param bool $retryOffline 如果会员不在线，等上线后继续发送
     * @param string $key 延迟推送key依据，留空立即推送（延迟推送时发给同一人同一种消息类型只发送最新的一条）
     * @param int $delay 延迟推送时间，默认：1秒（$key填写时有效）
     */
    public static function push(array $lists, $retryOffline = true, $key = null, $delay = 1)
    {
        if (empty($lists)) {
            return;
        }
        if (!Base::isTwoArray($lists)) {
            $lists = [$lists];
        }
        $swoole = app('swoole');
        foreach ($lists AS $item) {
            if (!is_array($item) || empty($item)) {
                continue;
            }
            $userid = $item['userid'];
            $fd = $item['fd'];
            $ignoreFd = $item['ignoreFd'];
            $msg = $item['msg'];
            $tmpMsgId = intval($item['tmpMsgId']);
            if (!is_array($msg)) {
                continue;
            }
            $type = $msg['type'];
            if (empty($type)) {
                continue;
            }
            // 发送对象
            $offlineUser = [];
            $array = [];
            if ($fd) {
                if (is_array($fd)) {
                    $array = array_merge($array, $fd);
                } else {
                    $array[] = $fd;
                }
            }
            if ($userid) {
                if (!is_array($userid)) {
                    $userid = [$userid];
                }
                foreach ($userid as $uid) {
                    $row = WebSocket::select(['fd'])->whereUserid($uid)->pluck('fd');
                    if ($row->isNotEmpty()) {
                        $array = array_merge($array, $row->toArray());
                    } else {
                        $offlineUser[] = $uid;
                    }
                }
            }
            if ($ignoreFd) {
                $ignoreFd = is_array($ignoreFd) ? $ignoreFd : [$ignoreFd];
            }
            // 开始发送
            foreach ($array as $fid) {
                if ($ignoreFd) {
                    if (in_array($fid, $ignoreFd)) continue;
                }
                if ($key) {
                    $key = "PUSH::" . $fid . ":" . $type . ":" . $key;
                    Cache::put($key, [
                        'fd' => $fid,
                        'ignoreFd' => $ignoreFd,
                        'msg' => $msg,
                    ]);
                    $task = new PushTask($key, $retryOffline);
                    $task->delay($delay);
                    Task::deliver($task);
                } else {
                    try {
                        $swoole->push($fid, self::pushMsgFormat($fid, $msg));
                        if ($tmpMsgId > 0) {
                            WebSocketTmpMsg::whereId($tmpMsgId)->update(['send' => 1]);
                        }
                    } catch (\Throwable) {
                        // 发送失败
                    }
                }
            }
            // 记录不在线的
            if ($retryOffline && $tmpMsgId == 0) {
                $offlineUser = array_values(array_unique($offlineUser));
                self::addTmpMsg($offlineUser, $msg);
            }
        }
    }

    /**
     * 格式化推送消息
     * @param $fid
     * @param $msg
     * @return string
     */
    private static function pushMsgFormat($fid, $msg)
    {
        $encrypt = Base::json2array(Cache::get("User::encrypt:" . $fid));
        if ($encrypt['type'] == 'pgp') {
            if (is_array($msg) && $msg['type'] == 'dialog') {
                // 仅加密对话消息
                $msg = [
                    'type' => 'encrypt',
                    'encrypted' => Doo::pgpEncryptApi($msg, $encrypt['key']),
                ];
            }
        }
        return Base::array2json($msg);
    }
}
