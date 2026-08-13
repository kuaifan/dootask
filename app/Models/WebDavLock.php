<?php

namespace App\Models;

class WebDavLock extends AbstractModel
{
    protected $table = 'webdav_locks';

    protected $casts = [
        'timeout_at' => 'datetime',
    ];
}
