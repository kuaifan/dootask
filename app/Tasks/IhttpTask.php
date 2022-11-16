<?php
namespace App\Tasks;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use App\Module\Base;
use App\Module\Ihttp;

/**
 * Ihttp任务
 * Class IhttpTask
 * @package App\Tasks
 */
class IhttpTask extends AbstractTask
{
    protected $url;
    protected $post;
    protected $extra;
    protected $apiWebsocket;
    protected $apiUserid;
    protected $endPush = [];

    /**
     * IhttpTask constructor.
     * @param $url
     * @param array $post
     * @param array $extra
     */
    public function __construct($url, $post = [], $extra = [])
    {
        parent::__construct(...func_get_args());
        $this->url = $url;
        $this->post = $post;
        $this->extra = $extra;
    }

    /**
     * @param mixed $apiWebsocket
     */
    public function setApiWebsocket($apiWebsocket): void
    {
        $this->apiWebsocket = $apiWebsocket;
    }

    /**
     * @param mixed $apiUserid
     */
    public function setApiUserid($apiUserid): void
    {
        $this->apiUserid = $apiUserid;
    }

    public function start()
    {
        $res = Ihttp::ihttp_request($this->url, $this->post, $this->extra);
        if ($this->apiWebsocket && $this->apiUserid) {
            $data = Base::isSuccess($res) ? Base::json2array($res['data']) : $res;
            $this->endPush[] = [
                'userid' => $this->apiUserid,
                'msg' => [
                    'type' => 'apiWebsocket',
                    'apiWebsocket' => $this->apiWebsocket,
                    'apiSuccess' => Base::isSuccess($res),
                    'data' => $data,
                ]
            ];

        }
    }

    public function end()
    {
        PushTask::push($this->endPush);
    }
}
