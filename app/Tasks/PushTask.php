<?php
namespace App\Tasks;

@error_reporting(E_ALL & ~E_NOTICE);

use App\Models\WebSocket;
use App\Models\WebSocketTmpMsg;
use App\Module\Base;
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

    /**
     * PushTask constructor.
     * @param array|string $params
     */
    public function __construct($params = [])
    {
        $this->params = $params;
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
                self::sendTmpMsgForUserid(intval(Base::leftDelete($this->params, "RETRY::")));
            }
        }
        is_array($this->params) && self::push($this->params);
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
     * 根据会员ID推送离线时收到的消息
     * @param $userid
     */
    private static function sendTmpMsgForUserid($userid)
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
                    self::push([
                        'tmp_msg_id' => $item->id,
                        'userid' => $userid,
                        'msg' => Base::json2array($item->msg),
                    ]);
                }
            });
    }

    /**
     * 推送消息
     * @param array $lists 消息列表
     * @param string|int $key 延迟推送key依据，留空立即推送（延迟推送时发给同一人同一种消息类型只发送最新的一条）
     * @param int $delay 延迟推送时间，默认：1秒（$key填写时有效）
     * @param bool $retryOffline 如果会员不在线，等上线后继续发送
     */
    public static function push(array $lists, $key = '', $delay = 1, $retryOffline = true)
    {
        if (!is_array($lists) || empty($lists)) {
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
            $msg = $item['msg'];
            $tmp_msg_id = intval($item['tmp_msg_id']);
            if (!is_array($msg)) {
                continue;
            }
            $type = $msg['type'];
            if (empty($type)) {
                continue;
            }
            // 发送对象
            $offline_user = [];
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
                        $offline_user[] = $uid;
                    }
                }
            }
            // 开始发送
            foreach ($array as $fid) {
                if (empty($key)) {
                    try {
                        $swoole->push($fid, Base::array2json($msg));
                        $tmp_msg_id > 0 && WebSocketTmpMsg::whereId($tmp_msg_id)->update(['send' => 1]);
                    } catch (\Exception $e) {

                    }
                } else {
                    $key =  "PUSH::" . $fid . ":" . $type . ":" . $key;
                    Cache::put($key, [
                        'fd' => $fid,
                        'msg' => $msg,
                    ]);
                    $task = new PushTask($key);
                    $task->delay($delay);
                    Task::deliver($task);
                }
            }
            // 记录不在线的
            if ($retryOffline && $tmp_msg_id == 0) {
                $offline_user = array_values(array_unique($offline_user));
                self::addTmpMsg($offline_user, $msg);
            }
        }
    }

    /**
     * 推送消息（仅推送当前在线的）
     * @param array $lists 消息列表
     */
    public static function pushO(array $lists, $key = '', $delay = 1)
    {
        self::push($lists, $key, $delay, false);
    }
}
