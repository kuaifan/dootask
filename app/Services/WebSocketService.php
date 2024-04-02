<?php

namespace App\Services;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use App\Models\User;
use App\Models\WebSocket;
use App\Models\WebSocketDialogMsg;
use App\Module\Base;
use App\Module\Doo;
use App\Tasks\LineTask;
use App\Tasks\PushTask;
use Cache;
use Carbon\Carbon;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

/**
 * @see https://wiki.swoole.com/#/start/start_ws_server
 */
class WebSocketService implements WebSocketHandlerInterface
{
    /**
     * 声明没有参数的构造函数
     * WebSocketService constructor.
     */
    public function __construct()
    {

    }

    /**
     * 连接建立时触发
     * @param Server $server
     * @param Request $request
     */
    public function onOpen(Server $server, Request $request)
    {
        $fd = $request->fd;
        $get = Base::newTrim($request->get);
        $action = $get['action'];
        Cache::forget("User::encrypt:" . $fd);
        switch ($action) {
            /**
             * 网页访问
             */
            case 'web':
                {
                    Doo::load($get['token'], $get['language']);
                    //
                    if (Doo::userId() > 0
                        && !Doo::userExpired()
                        && $user = User::whereUserid(Doo::userId())->whereEmail(Doo::userEmail())->whereEncrypt(Doo::userEncrypt())->first()) {
                        // 保存用户
                        $this->saveUser($fd, $user->userid);
                        // 发送open事件
                        $server->push($fd, Base::array2json([
                            'type' => 'open',
                            'data' => [
                                'fd' => $fd,
                                'ud' => $user->userid,
                            ],
                        ]));
                        // 通知上线
                        Task::deliver(new LineTask($user->userid, true));
                        // 推送离线时收到的消息
                        Task::deliver(new PushTask("RETRY::" . $user->userid));
                    } else {
                        // 用户不存在
                        $server->push($fd, Base::array2json([
                            'type' => 'error',
                            'data' => [
                                'error' => 'No member'
                            ],
                        ]));
                        $server->close($fd);
                        $this->deleteUser($fd);
                    }
                }
                break;

            default:
                break;
        }
    }

    /**
     * 收到消息时触发
     * @param Server $server
     * @param Frame $frame
     */
    public function onMessage(Server $server, Frame $frame)
    {
        $msg = Base::json2array($frame->data);
        $type = $msg['type'];       // 消息类型
        $msgId = $msg['msgId'];     // 消息ID（用于回调）
        $data = $msg['data'];       // 消息详情
        //
        $reData = [];
        switch ($type) {
            /**
             * 收到回执
             */
            case 'receipt':
                return;

            /**
             * 已阅消息
             */
            case 'readMsg':
                $ids = is_array($data['id']) ? $data['id'] : [$data['id']];
                $userid = $this->getUserid($frame->fd);
                WebSocketDialogMsg::whereIn('id', $ids)->chunkById(20, function($list) use ($userid) {
                    /** @var WebSocketDialogMsg $item */
                    foreach ($list as $item) {
                        $item->readSuccess($userid);
                    }
                });
                return;

            /**
             * 访问状态
             */
            case 'path':
                $row = WebSocket::whereFd($frame->fd)->first();
                if ($row) {
                    $pathNew = $data['path'];
                    $pathOld = $row->path;
                    $row->path = $pathNew;
                    $row->save();
                    if (preg_match("/^\/single\/file\/\d+$/", $pathOld)) {
                        $this->pushPath($pathOld);
                    }
                    if (preg_match("/^\/single\/file\/\d+$/", $pathNew)) {
                        $this->pushPath($pathNew);
                    }
                }
                return;

            /**
             * 加密参数
             */
            case 'encrypt':
                if ($data['type'] === 'pgp') {
                    $data['key'] = Doo::pgpPublicFormat($data['key']);
                }
                Cache::put("User::encrypt:" . $frame->fd, Base::array2json($data), Carbon::now()->addDay());
                return;
        }
        //
        if ($msgId) {
            PushTask::push([
                'fd' => $frame->fd,
                'msg' => [
                    'type' => 'receipt',
                    'msgId' => $msgId,
                    'data' => $reData,
                ]
            ]);
        }
    }

    /**
     * 关闭连接时触发
     * @param Server $server
     * @param $fd
     * @param $reactorId
     * @throws \Exception
     */
    public function onClose(Server $server, $fd, $reactorId)
    {
        $userid = $this->getUserid($fd);
        if($userid){
            Task::deliver(new LineTask($userid, false));  // 通知离线
            $this->deleteUser($fd);
        }
    }

    /** ****************************************************************************** */
    /** ****************************************************************************** */
    /** ****************************************************************************** */

    /**
     * 保存用户
     * @param $fd
     * @param $userid
     */
    private function saveUser($fd, $userid)
    {
        Cache::put("User::fd:" . $fd, "on", Carbon::now()->addDay());
        Cache::put("User::online:" . $userid, "on", Carbon::now()->addDay());
        //
        WebSocket::updateInsert([
            'key' => md5($fd . '@' . $userid)
        ], [
            'fd' => $fd,
            'userid' => $userid,
        ]);
    }

    /**
     * 清除用户
     * @param $fd
     */
    private function deleteUser($fd)
    {
        Cache::forget("User::fd:" . $fd);
        //
        $array = [];
        WebSocket::whereFd($fd)->chunk(10, function($list) use (&$array) {
            /** @var WebSocket $item */
            foreach ($list as $item) {
                $item->delete();
                if ($item->userid) {
                    // 离线时更新会员最后在线时间
                    User::whereUserid($item->userid)->update([
                        'line_at' => Carbon::now()
                    ]);
                    Cache::forget("User::online:" . $item->userid);
                }
                if ($item->path && str_starts_with($item->path, "/single/file/")) {
                    $array[$item->path] = $item->path;
                }
            }
        });
        foreach ($array as $path) {
            $this->pushPath($path);
        }
    }

    /**
     * 根据fd获取会员ID
     * @param $fd
     * @return int
     */
    private function getUserid($fd)
    {
        return intval(WebSocket::whereFd($fd)->value('userid'));
    }

    /**
     * 发送给相同访问状态的会员
     * @param $path
     */
    private function pushPath($path)
    {
        $array = WebSocket::wherePath($path)->pluck('userid')->toArray();
        if ($array) {
            $userids = array_values(array_filter(array_unique($array)));
            $params = [
                'userid' => $userids,
                'msg' => [
                    'type' => 'path',
                    'data' => [
                        'path' => $path,
                        'userids' => $userids
                    ],
                ]
            ];
            $task = new PushTask($params, false);
            Task::deliver($task);
        }
    }
}
