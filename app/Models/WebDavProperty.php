<?php

namespace App\Models;

class WebDavProperty extends AbstractModel
{
    protected $table = 'webdav_properties';

    protected $casts = [
        'value_type' => 'integer',
    ];
}
