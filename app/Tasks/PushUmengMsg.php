<?php
namespace App\Tasks;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use App\Models\UmengAlias;
use App\Module\Base;

/**
 * 推送友盟消息
 */
class PushUmengMsg extends AbstractTask
{
    protected $userid = 0;
    protected $array = [];

    /**
     * @param array|int $userid
     * @param array $array
     */
    public function __construct($userid, $array = [])
    {
        parent::__construct(...func_get_args());
        $this->userid = $userid;
        $this->array = is_array($array) ? $array : [];
    }

    public function start()
    {
        if (empty($this->userid) || empty($this->array)) {
            return;
        }
        $setting = Base::setting('appPushSetting');
        if ($setting['push'] !== 'open') {
            return;
        }
        UmengAlias::pushMsgToUserid($this->userid, $this->array);
    }

    public function end()
    {

    }
}
