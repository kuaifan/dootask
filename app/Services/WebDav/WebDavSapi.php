<?php

namespace App\Services\WebDav;

use Sabre\HTTP\ResponseInterface;
use Sabre\HTTP\Sapi;

class WebDavSapi extends Sapi
{
    public static function sendResponse(ResponseInterface $response)
    {
        // Symfony/Laravel owns the actual response emission.
    }
}
