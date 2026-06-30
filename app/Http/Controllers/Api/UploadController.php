<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Module\Base;
use App\Module\ChunkUpload;
use Request;

/**
 * 分片上传统一入口。
 *
 * 动态路由（routes/web.php）：
 *   api/upload/init     -> init()    启动一个上传会话（含秒传 / 续传命中）
 *   api/upload/chunk    -> chunk()   接收一个分片
 *   api/upload/merge    -> merge()   合并分片并按 scene 入库
 *
 * 小文件（<10MB）不走此接口，前端直接调用各 scene 的老接口（透明降级）。
 */
class UploadController extends AbstractController
{
    /**
     * @api {post} api/upload/init 启动上传会话
     *
     * @apiDescription 提交文件 hash/size/name/scene/scene_params，返回 upload_id 与已收分片列表；
     *  若同用户曾上传过同 hash 文件，直接返回 done=true（秒传）。
     * @apiGroup upload
     * @apiName init
     *
     * @apiParam {String} hash               文件 md5（小写 32 字符）
     * @apiParam {Number} size               文件大小（字节）
     * @apiParam {String} name               原始文件名（含扩展名）
     * @apiParam {String} scene              场景：file_cabinet | dialog_file | image | generic_file
     * @apiParam {Object} [scene_params]     场景参数（如 file_cabinet 需 pid/cover/webkit_relative_path）
     *
     * @apiSuccess {Number} ret
     * @apiSuccess {Object} data 含 done / upload_id / chunk_size / chunk_count / received 或秒传 file
     */
    public function init()
    {
        $user = User::auth();
        $result = ChunkUpload::start($user, [
            'hash' => Request::input('hash', ''),
            'size' => Request::input('size', 0),
            'name' => Request::input('name', ''),
            'scene' => Request::input('scene', ''),
            'scene_params' => Request::input('scene_params', []),
        ]);
        return $result;
    }

    /**
     * @api {post} api/upload/chunk 上传一个分片
     *
     * @apiDescription multipart 请求，blob 字段为分片数据。
     * @apiGroup upload
     * @apiName chunk
     *
     * @apiParam {String} upload_id    init 返回的 upload_id
     * @apiParam {Number} index        分片序号（0-based）
     * @apiParam {File}   blob         分片数据
     *
     * @apiSuccess {Number} ret
     * @apiSuccess {Object} data 含 upload_id 与最新 received[]
     */
    public function chunk()
    {
        $user = User::auth();
        $uploadId = trim(Request::input('upload_id', ''));
        $index = intval(Request::input('index', -1));
        $blob = Request::file('blob');
        if ($uploadId === '') {
            return Base::retError('upload_id 不能为空');
        }
        return ChunkUpload::receive($user, $uploadId, $index, $blob);
    }

    /**
     * @api {post} api/upload/merge 合并分片并入库
     *
     * @apiDescription 全部分片到齐后调用；后端按 scene 路由到对应入库逻辑，返回与该 scene 老接口对齐的数据。
     * @apiGroup upload
     * @apiName merge
     *
     * @apiParam {String} upload_id    init 返回的 upload_id
     *
     * @apiSuccess {Number} ret
     * @apiSuccess {Object} data       scene 入库返回数据
     */
    public function merge()
    {
        $user = User::auth();
        $uploadId = trim(Request::input('upload_id', ''));
        if ($uploadId === '') {
            return Base::retError('upload_id 不能为空');
        }
        try {
            return ChunkUpload::merge($user, $uploadId);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Failed to acquire lock')) {
                return Base::retError('合并繁忙，请稍后再试');
            }
            return Base::retError($e->getMessage());
        }
    }

    /**
     * @api {post} api/upload/cancel 取消上传会话
     *
     * @apiDescription 调用方主动放弃一次分片上传时调用：删除 Redis meta/chunks/hash 索引并清掉分片目录。
     *  会话已过期或归属其他用户时静默成功，避免给前端取消按钮回写"取消失败"。
     * @apiGroup upload
     * @apiName cancel
     *
     * @apiParam {String} upload_id    init 返回的 upload_id
     *
     * @apiSuccess {Number} ret
     */
    public function cancel()
    {
        $user = User::auth();
        $uploadId = trim(Request::input('upload_id', ''));
        if ($uploadId === '') {
            return Base::retError('upload_id 不能为空');
        }
        ChunkUpload::cancelByUser($user, $uploadId);
        return Base::retSuccess('已取消');
    }
}
