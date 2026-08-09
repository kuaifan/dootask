<?php

namespace App\Tasks;

use App\Services\MessageAttachmentBackfillService;

class MessageAttachmentBackfillTask extends AbstractTask
{
    public function __construct()
    {
        parent::__construct();
    }

    public function start()
    {
        MessageAttachmentBackfillService::processAutomatic();
    }

    public function end()
    {
    }
}
