<?php

namespace App\Http\Controllers\Api;

use Request;
use App\Models\File;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Models\UserTag;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Module\Base;
use App\Module\Apps;
use App\Module\Manticore\ManticoreFile;
use App\Module\Manticore\ManticoreUser;
use App\Module\Manticore\ManticoreProject;
use App\Module\Manticore\ManticoreTask;
use App\Module\Manticore\ManticoreMsg;

/**
 * @apiDefine search
 *
 * 智能搜索
 */
class SearchController extends AbstractController
{
    /**
     * @api {get} api/search/contact 搜索联系人
     *
     * @apiDescription 需要token身份，优先使用 Manticore Search，未安装则使用 MySQL 搜索
     * @apiVersion 1.0.0
     * @apiGroup search
     * @apiName contact
     *
     * @apiParam {String} key                  搜索关键词
     * @apiParam {String} [search_type]        搜索类型（text/vector/hybrid，默认：hybrid，仅 Manticore 有效）
     * @apiParam {Number} [take]               获取数量（默认：20，最大：50）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function contact()
    {
        User::auth();

        $key = trim(Request::input('key'));
        $searchType = Request::input('search_type', 'hybrid');
        $take = Base::getPaginate(50, 20, 'take');

        if (empty($key)) {
            return Base::retSuccess('success', []);
        }

        // 优先使用 Manticore 搜索
        if (Apps::isInstalled('search')) {
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
                        // 标签直接从 Manticore 搜索结果获取（空格分隔的字符串转数组）
                        $tagsStr = $item['tags'] ?? '';
                        $searchTags = !empty($tagsStr) ? preg_split('/\s+/', trim($tagsStr)) : [];

                        $item = array_merge($userData->toArray(), [
                            'relevance' => $item['relevance'] ?? 0,
                            'introduction_preview' => $item['introduction_preview'] ?? null,
                            'search_tags' => $searchTags,
                        ]);
                    }
                }
            }
        } else {
            // MySQL 回退搜索
            $results = $this->searchContactByMysql($key, $take);
        }

        return Base::retSuccess('success', $results);
    }

    /**
     * MySQL 回退搜索联系人
     *
     * @param string $key 搜索关键词
     * @param int $take 获取数量
     * @return array
     */
    private function searchContactByMysql(string $key, int $take): array
    {
        $users = User::select(User::$basicField)
            ->where('bot', 0)
            ->whereNull('disable_at')
            ->searchByKeyword($key)
            ->orderByDesc('line_at')
            ->take($take)
            ->get();

        // 获取用户标签
        $userids = $users->pluck('userid')->toArray();
        $userTags = $this->getUserTagsMap($userids);

        return $users->map(function ($user) use ($userTags) {
            return array_merge($user->toArray(), [
                'relevance' => 0,
                'introduction_preview' => null,
                'search_tags' => $userTags[$user->userid] ?? [],
            ]);
        })->toArray();
    }

    /**
     * @api {get} api/search/project 搜索项目
     *
     * @apiDescription 需要token身份，优先使用 Manticore Search，未安装则使用 MySQL 搜索
     * @apiVersion 1.0.0
     * @apiGroup search
     * @apiName project
     *
     * @apiParam {String} key                  搜索关键词
     * @apiParam {String} [search_type]        搜索类型（text/vector/hybrid，默认：hybrid，仅 Manticore 有效）
     * @apiParam {Number} [take]               获取数量（默认：20，最大：50）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function project()
    {
        $user = User::auth();

        $key = trim(Request::input('key'));
        $searchType = Request::input('search_type', 'hybrid');
        $take = Base::getPaginate(50, 20, 'take');

        if (empty($key)) {
            return Base::retSuccess('success', []);
        }

        // 优先使用 Manticore 搜索
        if (Apps::isInstalled('search')) {
            $results = ManticoreProject::search($user->userid, $key, $searchType, $take);

            // 补充项目完整信息
            $projectIds = array_column($results, 'project_id');
            if (!empty($projectIds)) {
                $projects = Project::whereIn('id', $projectIds)
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
        } else {
            // MySQL 回退搜索
            $results = $this->searchProjectByMysql($user->userid, $key, $take);
        }

        return Base::retSuccess('success', $results);
    }

    /**
     * MySQL 回退搜索项目
     *
     * @param int $userid 用户ID
     * @param string $key 搜索关键词
     * @param int $take 获取数量
     * @return array
     */
    private function searchProjectByMysql(int $userid, string $key, int $take): array
    {
        $projects = Project::authData()
            ->whereNull('projects.archived_at')
            ->searchByKeyword($key)
            ->orderByDesc('projects.id')
            ->take($take)
            ->get();

        return $projects->map(function ($project) {
            $array = $project->toArray();
            $array['relevance'] = 0;
            $array['desc_preview'] = null;
            return $array;
        })->toArray();
    }

    /**
     * @api {get} api/search/task 搜索任务
     *
     * @apiDescription 需要token身份，优先使用 Manticore Search，未安装则使用 MySQL 搜索
     * @apiVersion 1.0.0
     * @apiGroup search
     * @apiName task
     *
     * @apiParam {String} key                  搜索关键词
     * @apiParam {String} [search_type]        搜索类型（text/vector/hybrid，默认：hybrid，仅 Manticore 有效）
     * @apiParam {Number} [take]               获取数量（默认：20，最大：50）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function task()
    {
        $user = User::auth();

        $key = trim(Request::input('key'));
        $searchType = Request::input('search_type', 'hybrid');
        $take = Base::getPaginate(50, 20, 'take');

        if (empty($key)) {
            return Base::retSuccess('success', []);
        }

        // 优先使用 Manticore 搜索
        if (Apps::isInstalled('search')) {
            $results = ManticoreTask::search($user->userid, $key, $searchType, $take);

            // 补充任务完整信息
            $taskIds = array_column($results, 'task_id');
            if (!empty($taskIds)) {
                $tasks = ProjectTask::with(['taskUser', 'taskTag'])
                    ->whereIn('id', $taskIds)
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
        } else {
            // MySQL 回退搜索
            $results = $this->searchTaskByMysql($user->userid, $key, $take);
        }

        return Base::retSuccess('success', $results);
    }

    /**
     * MySQL 回退搜索任务
     *
     * @param int $userid 用户ID
     * @param string $key 搜索关键词
     * @param int $take 获取数量
     * @return array
     */
    private function searchTaskByMysql(int $userid, string $key, int $take): array
    {
        $tasks = ProjectTask::with(['taskUser', 'taskTag'])
            ->whereIn('project_tasks.project_id', function ($query) use ($userid) {
                $query->select('project_id')
                    ->from('project_users')
                    ->where('userid', $userid);
            })
            ->whereNull('project_tasks.archived_at')
            ->whereNull('project_tasks.deleted_at')
            ->searchByKeyword($key)
            ->orderByDesc('project_tasks.id')
            ->take($take)
            ->get();

        return $tasks->map(function ($task) {
            $array = $task->toArray();
            $array['relevance'] = 0;
            $array['desc_preview'] = null;
            $array['content_preview'] = null;
            return $array;
        })->toArray();
    }

    /**
     * @api {get} api/search/file 搜索文件
     *
     * @apiDescription 需要token身份，优先使用 Manticore Search，未安装则使用 MySQL 搜索
     * @apiVersion 1.0.0
     * @apiGroup search
     * @apiName file
     *
     * @apiParam {String} key                  搜索关键词
     * @apiParam {String} [search_type]        搜索类型（text/vector/hybrid，默认：hybrid，仅 Manticore 有效）
     * @apiParam {Number} [take]               获取数量（默认：20，最大：50）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function file()
    {
        $user = User::auth();

        $key = trim(Request::input('key'));
        $searchType = Request::input('search_type', 'hybrid');
        $take = Base::getPaginate(50, 20, 'take');

        if (empty($key)) {
            return Base::retSuccess('success', []);
        }

        // 优先使用 Manticore 搜索
        if (Apps::isInstalled('search')) {
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
        } else {
            // MySQL 回退搜索
            $results = $this->searchFileByMysql($user->userid, $key, $take);
            return Base::retSuccess('success', $results);
        }
    }

    /**
     * MySQL 回退搜索文件
     *
     * @param int $userid 用户ID
     * @param string $key 搜索关键词
     * @param int $take 获取数量
     * @return array
     */
    private function searchFileByMysql(int $userid, string $key, int $take): array
    {
        $results = [];

        // 搜索用户自己的文件
        $ownFiles = File::where('userid', $userid)
            ->searchByKeyword($key)
            ->take($take)
            ->get();

        foreach ($ownFiles as $file) {
            $results[] = array_merge($file->toArray(), [
                'relevance' => 0,
                'content_preview' => null,
            ]);
        }

        // 搜索共享给用户的文件
        $remaining = $take - count($results);
        if ($remaining > 0) {
            $sharedFiles = File::sharedToUser($userid)
                ->searchByKeyword($key)
                ->take($remaining)
                ->get();

            foreach ($sharedFiles as $file) {
                $temp = $file->toArray();
                if ($file->pshare === $file->id) {
                    $temp['pid'] = 0;
                }
                $temp['relevance'] = 0;
                $temp['content_preview'] = null;
                $results[] = $temp;
            }
        }

        return $results;
    }

    /**
     * @api {get} api/search/message 搜索消息
     *
     * @apiDescription 需要token身份，优先使用 Manticore Search，未安装则使用 MySQL 搜索
     * @apiVersion 1.0.0
     * @apiGroup search
     * @apiName message
     *
     * @apiParam {String} key                  搜索关键词
     * @apiParam {String} [search_type]        搜索类型（text/vector/hybrid，默认：hybrid，仅 Manticore 有效）
     * @apiParam {Number} [take]               获取数量（默认：20，最大：50）
     * @apiParam {String} [mode]               返回模式（message/position/dialog，默认：message）
     * - message: 返回消息详细信息
     * - position: 只返回消息ID
     * - dialog: 返回对话级数据
     * @apiParam {Number} [dialog_id]          对话ID（筛选指定对话内的消息）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function message()
    {
        $user = User::auth();

        $key = trim(Request::input('key'));
        $searchType = Request::input('search_type', 'hybrid');
        $take = Base::getPaginate(50, 20, 'take');
        $mode = Request::input('mode', 'message');
        $dialogId = intval(Request::input('dialog_id', 0));

        // 验证 mode 参数
        if (!in_array($mode, ['message', 'position', 'dialog'])) {
            $mode = 'message';
        }

        if (empty($key)) {
            return Base::retSuccess('success', []);
        }

        // 如果指定了 dialog_id，需要验证用户有权限访问该对话
        if ($dialogId > 0) {
            WebSocketDialog::checkDialog($dialogId);
        }

        // 优先使用 Manticore 搜索
        if (Apps::isInstalled('search')) {
            $results = ManticoreMsg::search($user->userid, $key, $searchType, 0, $take, $dialogId);
        } else {
            // MySQL 回退搜索
            $results = $this->searchMessageByMysql($user->userid, $key, $take, $dialogId);
        }

        // 根据 mode 返回不同格式的数据
        return $this->formatMessageResults($results, $mode, $user->userid);
    }

    /**
     * MySQL 回退搜索消息
     *
     * @param int $userid 用户ID
     * @param string $key 搜索关键词
     * @param int $take 获取数量
     * @param int $dialogId 对话ID（0表示不限制）
     * @return array
     */
    private function searchMessageByMysql(int $userid, string $key, int $take, int $dialogId = 0): array
    {
        $builder = WebSocketDialogMsg::select([
                'id as msg_id',
                'dialog_id',
                'userid',
                'type',
                'msg',
                'created_at',
            ])
            ->accessibleByUser($userid)
            ->where('bot', 0)
            ->searchByKeyword($key);

        if ($dialogId > 0) {
            $builder->where('dialog_id', $dialogId);
        }

        $items = $builder->orderByDesc('id')
            ->limit($take)
            ->get();

        return $items->map(function ($item) {
            return [
                'msg_id' => $item->msg_id,
                'dialog_id' => $item->dialog_id,
                'userid' => $item->userid,
                'type' => $item->type,
                'msg' => $item->msg,
                'created_at' => $item->created_at,
                'relevance' => 0,
                'content_preview' => null,
            ];
        })->toArray();
    }

    /**
     * 格式化消息搜索结果
     *
     * @param array $results 搜索结果
     * @param string $mode 返回模式
     * @param int $userid 用户ID
     * @return \Illuminate\Http\JsonResponse
     */
    private function formatMessageResults(array $results, string $mode, int $userid)
    {
        switch ($mode) {
            case 'position':
                // 只返回消息ID
                $data = array_column($results, 'msg_id');
                return Base::retSuccess('success', compact('data'));

            case 'dialog':
                // 返回对话级数据
                $list = [];
                $seenDialogs = [];
                foreach ($results as $item) {
                    $dialogIdFromResult = $item['dialog_id'];
                    // 每个对话只返回一次
                    if (isset($seenDialogs[$dialogIdFromResult])) {
                        continue;
                    }
                    $seenDialogs[$dialogIdFromResult] = true;

                    if ($dialog = WebSocketDialog::find($dialogIdFromResult)) {
                        $dialogData = array_merge($dialog->toArray(), [
                            'search_msg_id' => $item['msg_id'],
                        ]);
                        $list[] = WebSocketDialog::synthesizeData($dialogData, $userid);
                    }
                }
                return Base::retSuccess('success', ['data' => $list]);

            case 'message':
            default:
                // 返回消息详细信息（默认行为）
                $msgIds = array_column($results, 'msg_id');
                if (!empty($msgIds)) {
                    $msgs = WebSocketDialogMsg::whereIn('id', $msgIds)
                        ->with(['user' => function ($query) {
                            $query->select(User::$basicField);
                        }])
                        ->get()
                        ->keyBy('id');

                    // 创建结果映射以保持原始顺序和额外字段
                    $resultsMap = [];
                    foreach ($results as $item) {
                        $resultsMap[$item['msg_id']] = $item;
                    }

                    $formattedResults = [];
                    foreach ($msgIds as $msgId) {
                        $msgData = $msgs->get($msgId);
                        $originalItem = $resultsMap[$msgId] ?? [];
                        if ($msgData) {
                            $formattedResults[] = [
                                'id' => $msgData->id,
                                'msg_id' => $msgData->id,
                                'dialog_id' => $msgData->dialog_id,
                                'userid' => $msgData->userid,
                                'type' => $msgData->type,
                                'msg' => $msgData->msg,
                                'created_at' => $msgData->created_at,
                                'user' => $msgData->user,
                                'relevance' => $originalItem['relevance'] ?? 0,
                                'content_preview' => $originalItem['content_preview'] ?? null,
                            ];
                        }
                    }
                    return Base::retSuccess('success', $formattedResults);
                }

                return Base::retSuccess('success', []);
        }
    }

    /**
     * 批量获取用户标签映射
     *
     * @param array $userids 用户ID数组
     * @return array 用户ID => 标签名称数组的映射
     */
    private function getUserTagsMap(array $userids): array
    {
        if (empty($userids)) {
            return [];
        }

        // 获取所有用户的标签（带认可数）
        $tags = UserTag::whereIn('user_id', $userids)
            ->withCount('recognitions')
            ->get();

        // 按用户分组，每个用户取 Top 10 标签
        $result = [];
        foreach ($userids as $userid) {
            $result[$userid] = [];
        }

        $userTags = $tags->groupBy('user_id');
        foreach ($userTags as $userid => $tagCollection) {
            $result[$userid] = $tagCollection
                ->sortByDesc('recognitions_count')
                ->take(10)
                ->pluck('name')
                ->values()
                ->toArray();
        }

        return $result;
    }
}
