<?php

namespace App\Services\WebDav;

use App\Exceptions\ApiException;
use Sabre\DAV\Exception;
use Sabre\DAV\Exception\Conflict;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\InsufficientStorage;
use Sabre\DAV\Exception\NotFound;

class WebDavExceptionMapper
{
    public static function map(ApiException $exception): Exception
    {
        $message = $exception->getMessage();

        if (str_contains($message, '不存在') || str_contains($message, '已被删除')) {
            return new NotFound($message);
        }
        if (
            str_contains($message, '已存在')
            || str_contains($message, '位置错误')
            || str_contains($message, '名称')
            || str_contains($message, '不是文件夹')
            || str_contains($message, '文件夹不能写入')
        ) {
            return new Conflict($message);
        }
        if (str_contains($message, '大小超过限制')) {
            return new WebDavPayloadTooLarge($message);
        }
        if (
            str_contains($message, '最多只能创建')
            || str_contains($message, '保存失败')
            || str_contains($message, '临时文件创建失败')
            || str_contains($message, '读取失败')
        ) {
            return new InsufficientStorage($message);
        }

        return new Forbidden($message);
    }
}
