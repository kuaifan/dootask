<?php

namespace App\Models;

class ProjectTaskHandoff extends AbstractModel
{
    protected $table = 'project_task_handoffs';

    protected $casts = [
        'record' => 'array',
    ];
}
