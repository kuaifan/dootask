<?php

namespace App\Http\Controllers;

use App\Module\Base;
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
        //
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
