<?php

namespace App\Services\WebDav;

use Sabre\DAV\Exception;

class WebDavPayloadTooLarge extends Exception
{
    public function getHTTPCode()
    {
        return 413;
    }
}
