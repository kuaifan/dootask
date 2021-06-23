<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Module\Base;
use App\Tasks\IhttpTask;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Request;

class InvokeController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param $method
     * @param string $action
     * @return array|void
     */
    public function __invoke($method, $action = '')
    {
        $app = $method ?: 'main';
        if ($action) {
            $app .= "__" . $action;
        }
        // 接口不存在
        if (!method_exists($this, $app)) {
            $msg = "404 not found (" . str_replace("__", "/", $app) . ").";
            return Base::ajaxError($msg);
        }
        // 使用websocket请求
        $apiWebsocket = Request::header('Api-Websocket');
        if ($apiWebsocket) {
            $userid = User::userid();
            if ($userid > 0) {
                $url = 'http://127.0.0.1:' . env('LARAVELS_LISTEN_PORT') . Request::getRequestUri();
                $task = new IhttpTask($url, Request::post(), [
                    'Content-Type' => Request::header('Content-Type'),
                    'language' => Request::header('language'),
                    'token' => Request::header('token'),
                ]);
                $task->setApiWebsocket($apiWebsocket);
                $task->setApiUserid($userid);
                Task::deliver($task);
                return Base::retSuccess('wait');
            }
        }
        // 正常请求
        $res = $this->__before($method, $action);
        if ($res === true || Base::isSuccess($res)) {
            return $this->$app();
        } else {
            return is_array($res) ? $res : Base::ajaxError($res);
        }
    }

    /**
     * @param $method
     * @param $action
     * @return bool|array|string
     */
    public function __before($method, $action)
    {
        return true;
    }
}
