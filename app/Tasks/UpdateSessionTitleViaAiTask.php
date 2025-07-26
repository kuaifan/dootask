<?php

namespace App\Tasks;

use App\Models\WebSocketDialogSession;
use App\Module\AI;
use App\Module\Base;

/**
 * 通过AI接口更新对话标题
 */
class UpdateSessionTitleViaAiTask extends AbstractTask
{
    protected $sessionId;
    protected $msgText;

    public function __construct($sessionId, $msgText)
    {
        parent::__construct();
        $this->sessionId = $sessionId;
        $this->msgText = $msgText;
    }

    public function start()
    {
        if (empty($this->sessionId) || empty($this->msgText)) {
            return;
        }

        $session = WebSocketDialogSession::whereId($this->sessionId)->first();
        if (!$session) {
            return;
        }

        $result = AI::generateTitle($this->msgText);
        if (Base::isError($result)) {
            return;
        }

        $newTitle = $result['data']['title'];
        if ($newTitle && $newTitle != $session->title) {
            $session->title = Base::cutStr($newTitle, 100);
            $session->save();
        }
    }

    public function end()
    {

    }
}
