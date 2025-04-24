<?php

namespace App\Tasks;

use App\Models\WebSocketDialogSession;
use App\Module\Base;
use App\Module\Extranet;

/**
 * 通过AI接口更新对话标题
 */
class UpdateSessionTitleViaAiTask extends AbstractTask
{
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

        $res = Extranet::openAIGenerateTitle($this->msgText);
        if (Base::isError($res)) {
            return;
        }

        $newTitle = $res['data'];
        if ($newTitle && $newTitle != $session->title) {
            $session->title = Base::cutStr($newTitle, 100);
            $session->save();
        }
    }

    public function end()
    {

    }
}
