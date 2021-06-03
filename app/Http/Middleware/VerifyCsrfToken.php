<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //上传图片
        'api/system/imgupload/',

        //上传文件
        'api/system/fileupload/',

        // 添加任务
        'api/project/task/add/',
    ];
}
