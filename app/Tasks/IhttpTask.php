<?php
namespace App\Tasks;

@error_reporting(E_ALL & ~E_NOTICE);

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

    /**
     * IhttpTask constructor.
     * @param $url
     * @param array $post
     * @param array $extra
     */
    public function __construct($url, $post = [], $extra = [])
    {
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
            $body = Base::isSuccess($res) ? Base::json2array($res['data']) : $res;
            PushTask::push([
                'userid' => $this->apiUserid,
                'msg' => [
                    'messageType' => 'apiWebsocket',
                    'apiWebsocket' => $this->apiWebsocket,
                    'apiSuccess' => Base::isSuccess($res),
                    'body' => $body,
                ]
            ]);
        }
    }
}
