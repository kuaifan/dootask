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
     * @param array $params
     */
    public function __construct($params = [])
    {
        $this->params = $params;
    }

    public function start()
    {
        if (is_string($this->params)) {
            $key = $this->params;
            $params = Cache::pull($key);
            if (is_array($params) && $params['fd']) {
                $this->params = [$params];
            }
        }
        is_array($this->params) && self::push($this->params);
    }

    /**
     * 记录发送失败的消息，等上线后重新发送
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
     * 根据会员ID重试发送失败的消息
     * @param $userid
     */
    public static function resendTmpMsgForUserid($userid)
    {
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
     * @param bool $addFail 失败后是否保存到临时表，等上线后继续发送
     */
    public static function push(array $lists, $key = '', $delay = 1, $addFail = false)
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
            $userFail = [];
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
                        $userFail[] = $uid;
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
                        $userFail[] = WebSocket::whereFd($fid)->value('userid');
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
            // 记录发送失败的
            if ($addFail) {
                $userFail = array_values(array_unique($userFail));
                $tmp_msg_id == 0 && self::addTmpMsg($userFail, $msg);
            }
        }
    }

    /**
     * 推送消息（出错后保存临时表，上线后尝试重新发送）
     * @param array $lists 消息列表
     */
    public static function pushR(array $lists)
    {
        self::push($lists, '', 1, true);
    }
}
