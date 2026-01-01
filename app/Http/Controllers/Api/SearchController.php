<?php

namespace App\Http\Controllers\Api;

use Request;
use App\Models\File;
use App\Models\User;
use App\Module\Base;
use App\Module\Apps;
use App\Module\Manticore\ManticoreFile;
use App\Module\Manticore\ManticoreUser;
use App\Module\Manticore\ManticoreProject;
use App\Module\Manticore\ManticoreTask;

/**
 * @apiDefine search
 *
 * 智能搜索
 */
class SearchController extends AbstractController
{
    /**
     * @api {get} api/search/contact          AI 搜索联系人
     *
     * @apiDescription 需要token身份，需要安装 Manticore Search 应用
     * @apiVersion 1.0.0
     * @apiGroup search
     * @apiName contact
     *
     * @apiParam {String} key                  搜索关键词
     * @apiParam {String} [search_type]        搜索类型（text/vector/hybrid，默认：hybrid）
     * @apiParam {Number} [take]               获取数量（默认：20，最大：50）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function contact()
    {
        User::auth();

        if (!Apps::isInstalled('manticore')) {
            return Base::retError('Manticore Search 应用未安装');
        }

        $key = trim(Request::input('key'));
        $searchType = Request::input('search_type', 'hybrid');
        $take = min(50, max(1, intval(Request::input('take', 20))));

        if (empty($key)) {
            return Base::retSuccess('success', []);
        }

        $results = ManticoreUser::search($key, $searchType, $take);

        // 补充用户完整信息
        $userids = array_column($results, 'userid');
        if (!empty($userids)) {
            $users = User::whereIn('userid', $userids)
                ->select(User::$basicField)
                ->get()
                ->keyBy('userid');

            foreach ($results as &$item) {
                $userData = $users->get($item['userid']);
                if ($userData) {
                    $item = array_merge($userData->toArray(), [
                        'relevance' => $item['relevance'] ?? 0,
                        'introduction_preview' => $item['introduction_preview'] ?? null,
                    ]);
                }
            }
        }

        return Base::retSuccess('success', $results);
    }

    /**
     * @api {get} api/search/project          AI 搜索项目
     *
     * @apiDescription 需要token身份，需要安装 Manticore Search 应用
     * @apiVersion 1.0.0
     * @apiGroup search
     * @apiName project
     *
     * @apiParam {String} key                  搜索关键词
     * @apiParam {String} [search_type]        搜索类型（text/vector/hybrid，默认：hybrid）
     * @apiParam {Number} [take]               获取数量（默认：20，最大：50）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function project()
    {
        $user = User::auth();

        if (!Apps::isInstalled('manticore')) {
            return Base::retError('Manticore Search 应用未安装');
        }

        $key = trim(Request::input('key'));
        $searchType = Request::input('search_type', 'hybrid');
        $take = min(50, max(1, intval(Request::input('take', 20))));

        if (empty($key)) {
            return Base::retSuccess('success', []);
        }

        $results = ManticoreProject::search($user->userid, $key, $searchType, $take);

        // 补充项目完整信息
        $projectIds = array_column($results, 'project_id');
        if (!empty($projectIds)) {
            $projects = \App\Models\Project::whereIn('id', $projectIds)
                ->get()
                ->keyBy('id');

            foreach ($results as &$item) {
                $projectData = $projects->get($item['project_id']);
                if ($projectData) {
                    $item = array_merge($projectData->toArray(), [
                        'relevance' => $item['relevance'] ?? 0,
                        'desc_preview' => $item['desc_preview'] ?? null,
                    ]);
                }
            }
        }

        return Base::retSuccess('success', $results);
    }

    /**
     * @api {get} api/search/task             AI 搜索任务
     *
     * @apiDescription 需要token身份，需要安装 Manticore Search 应用
     * @apiVersion 1.0.0
     * @apiGroup search
     * @apiName task
     *
     * @apiParam {String} key                  搜索关键词
     * @apiParam {String} [search_type]        搜索类型（text/vector/hybrid，默认：hybrid）
     * @apiParam {Number} [take]               获取数量（默认：20，最大：50）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task()
    {
        $user = User::auth();

        if (!Apps::isInstalled('manticore')) {
            return Base::retError('Manticore Search 应用未安装');
        }

        $key = trim(Request::input('key'));
        $searchType = Request::input('search_type', 'hybrid');
        $take = min(50, max(1, intval(Request::input('take', 20))));

        if (empty($key)) {
            return Base::retSuccess('success', []);
        }

        $results = ManticoreTask::search($user->userid, $key, $searchType, $take);

        // 补充任务完整信息
        $taskIds = array_column($results, 'task_id');
        if (!empty($taskIds)) {
            $tasks = \App\Models\ProjectTask::whereIn('id', $taskIds)
                ->get()
                ->keyBy('id');

            foreach ($results as &$item) {
                $taskData = $tasks->get($item['task_id']);
                if ($taskData) {
                    $item = array_merge($taskData->toArray(), [
                        'relevance' => $item['relevance'] ?? 0,
                        'desc_preview' => $item['desc_preview'] ?? null,
                        'content_preview' => $item['content_preview'] ?? null,
                    ]);
                }
            }
        }

        return Base::retSuccess('success', $results);
    }

    /**
     * @api {get} api/search/file              AI 搜索文件
     *
     * @apiDescription 需要token身份，需要安装 Manticore Search 应用
     * @apiVersion 1.0.0
     * @apiGroup search
     * @apiName file
     *
     * @apiParam {String} key                  搜索关键词
     * @apiParam {String} [search_type]        搜索类型（text/vector/hybrid，默认：hybrid）
     * @apiParam {Number} [take]               获取数量（默认：20，最大：50）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function file()
    {
        $user = User::auth();

        if (!Apps::isInstalled('manticore')) {
            return Base::retError('Manticore Search 应用未安装');
        }

        $key = trim(Request::input('key'));
        $searchType = Request::input('search_type', 'hybrid');
        $take = min(50, max(1, intval(Request::input('take', 20))));

        if (empty($key)) {
            return Base::retSuccess('success', []);
        }

        $results = ManticoreFile::search($user->userid, $key, $searchType, 0, $take);

        // 补充文件完整信息
        $fileIds = array_column($results, 'file_id');
        if (!empty($fileIds)) {
            $files = File::whereIn('id', $fileIds)
                ->get()
                ->keyBy('id');

            $formattedResults = [];
            foreach ($results as $item) {
                $fileData = $files->get($item['file_id']);
                if ($fileData) {
                    $formattedResults[] = array_merge($fileData->toArray(), [
                        'relevance' => $item['relevance'] ?? 0,
                        'content_preview' => $item['content_preview'] ?? null,
                    ]);
                }
            }
            return Base::retSuccess('success', $formattedResults);
        }

        return Base::retSuccess('success', []);
    }
}

