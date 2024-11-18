<?php

namespace App\Services;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use App\Models\User;
use App\Models\WebSocket;
use App\Module\Base;
use App\Module\Doo;
use App\Module\Table\OnlineData;
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
     * WebSocketService constructor.
     */
    public function __construct()
    {

    }

    /**
     * 连接建立时触发
     * @param Server $server
     * @param Request $request
     * @return void
     */
    public function onOpen(Server $server, Request $request)
    {
        $fd = $request->fd;
        $get = Base::newTrim($request->get);
        Cache::forget("User::encrypt:" . $fd);
        switch ($get['action']) {
            /**
             * 网页访问
             */
            case 'web':
                {
                    Doo::load($get['token'], $get['language']);
                    //
                    $count = 0;
                    $userid = Doo::userId();
                    if ($userid > 0 && !Doo::userExpired()) {
                        $count = User::whereUserid($userid)->whereEmail(Doo::userEmail())->whereEncrypt(Doo::userEncrypt())->count();
                    }
                    if ($count) {
                        // 用户正常
                        $server->push($fd, Base::array2json([
                            'type' => 'open',
                            'data' => [
                                'fd' => $fd,
                                'ud' => $userid,
                            ],
                        ]));
                        $this->userOn($fd, $userid);
                    } else {
                        // 用户不存在
                        $server->push($fd, Base::array2json([
                            'type' => 'error',
                            'data' => [
                                'error' => 'No member'
                            ],
                        ]));
                        $server->close($fd);
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
     * @return void
     */
    public function onMessage(Server $server, Frame $frame)
    {
        $msg = Base::json2array($frame->data);
        $type = $msg['type'];                       // 消息类型
        $data = $msg['data'];                       // 消息详情
        $msgId = $msg['msgId'] ?? $msg['msg_id'];   // 消息ID（用于回调）

        // 处理消息
        $reData = [];
        switch ($type) {
            // 收到回执
            case 'receipt':
                return;

            // 访问状态
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

            // 加密参数
            case 'encrypt':
                if ($data['type'] === 'pgp') {
                    $data['key'] = Doo::pgpPublicFormat($data['key']);
                }
                Cache::put("User::encrypt:" . $frame->fd, Base::array2json($data), Carbon::now()->addDay());
                return;
        }

        // 返回消息
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
     * @return void
     */
    public function onClose(Server $server, $fd, $reactorId)
    {
        $this->userOff($fd);
    }

    /** ****************************************************************************** */
    /** ****************************************************************************** */
    /** ****************************************************************************** */

    /**
     * 用户上线
     * @param $fd
     * @param $userid
     * @return void
     */
    private function userOn($fd, $userid)
    {
        WebSocket::updateInsert([
            'key' => md5($fd . '@' . $userid)
        ], [
            'fd' => $fd,
            'userid' => $userid,
        ]);
        OnlineData::online($userid);
    }

    /**
     * 用户下线
     * @param $fd
     * @return void
     */
    private function userOff($fd)
    {
        $paths = [];
        WebSocket::whereFd($fd)->chunk(10, function($list) use (&$paths) {
            /** @var WebSocket $item */
            foreach ($list as $item) {
                $item->delete();
                if ($item->userid) {
                    OnlineData::offline($item->userid);
                }
                if ($item->path && str_starts_with($item->path, "/single/file/")) {
                    $paths[$item->path] = $item->path;
                }
            }
        });
        foreach ($paths as $path) {
            $this->pushPath($path);
        }
    }

    /**
     * 通知相同访问路径的用户
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
