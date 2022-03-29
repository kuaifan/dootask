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
        // 上传图片
        'api/system/imgupload/',

        // 上传文件
        'api/system/fileupload/',

        // 保存任务优先级
        'api/system/priority/',

        // 保存创建项目列表模板
        'api/system/column/template/',

        // 添加任务
        'api/project/task/add/',

        // 保存工作流
        'api/project/flow/save/',

        // 修改任务
        'api/project/task/update/',

        // 聊天发文本
        'api/dialog/msg/sendtext/',

        // 聊天发文件
        'api/dialog/msg/sendfile/',

        // 保存文件内容
        'api/file/content/save/',

        // 保存文件内容（office）
        'api/file/content/office/',

        // 保存文件内容（上传）
        'api/file/content/upload/',

        // 保存汇报
        'api/report/store/',

        // 发布桌面端
        'desktop/publish/',
    ];
}
