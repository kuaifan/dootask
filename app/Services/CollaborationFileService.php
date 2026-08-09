<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use App\Models\UserDepartment;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgAttachment;
use App\Models\WebSocketDialogMsgAttachmentBackfill;
use App\Models\WebSocketDialogUser;
use App\Module\Base;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class CollaborationFileService
{
    private const CURSOR_SOURCE_MESSAGES = 'messages';
    private const CURSOR_SOURCE_ATTACHMENTS = 'attachments';

    private const SCOPES = ['all', 'conversation', 'project'];
    private const CONVERSATION_TYPES = ['all', 'private', 'group'];
    private const PROJECT_SOURCES = ['all', 'project_chat', 'task'];
    private const FILE_TYPES = ['all', 'document', 'sheet', 'slide', 'image', 'video', 'archive', 'other'];

    private const FILE_TYPE_EXTENSIONS = [
        'document' => ['doc', 'docx', 'dot', 'dotx', 'odt', 'ott', 'pdf', 'rtf', 'txt', 'md'],
        'sheet' => ['csv', 'ods', 'ots', 'tsv', 'xls', 'xlsm', 'xlsx', 'xlt', 'xltx'],
        'slide' => ['odp', 'otp', 'pot', 'potx', 'pps', 'ppsx', 'ppt', 'pptx'],
        'image' => ['bmp', 'gif', 'jpeg', 'jpg', 'png', 'svg', 'tif', 'tiff', 'webp'],
        'video' => ['3gp', 'avi', 'flv', 'mkv', 'mov', 'mp4', 'mpeg', 'mpg', 'rm', 'wmv'],
        'archive' => ['7z', 'gz', 'rar', 'tar', 'tgz', 'zip'],
    ];

    private const PREVIEW_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'webp', 'png', 'gif', 'bmp'];

    public static function normalizeParams(array $params): array
    {
        $scope = trim((string)($params['scope'] ?? 'all'));
        $conversationType = trim((string)($params['conversation_type'] ?? 'all'));
        $projectSource = trim((string)($params['project_source'] ?? 'all'));
        $fileType = trim((string)($params['file_type'] ?? 'all'));

        if (!in_array($scope, self::SCOPES, true)
            || !in_array($conversationType, self::CONVERSATION_TYPES, true)
            || !in_array($projectSource, self::PROJECT_SOURCES, true)
            || !in_array($fileType, self::FILE_TYPES, true)) {
            throw new ApiException('参数错误');
        }

        $normalized = [
            'scope' => $scope,
            'conversation_type' => $conversationType,
            'project_id' => max(0, intval($params['project_id'] ?? 0)),
            'project_source' => $projectSource,
            'file_type' => $fileType,
            'sender_id' => max(0, intval($params['sender_id'] ?? 0)),
            'key' => mb_substr(trim((string)($params['key'] ?? '')), 0, 100),
            'cursor' => mb_substr(trim((string)($params['cursor'] ?? '')), 0, 100),
            'take' => min(100, max(1, intval($params['take'] ?? 50))),
        ];

        return $normalized;
    }

    public static function lists(User $user, array $params): array
    {
        $params = self::normalizeParams($params);
        $departmentView = UserDepartment::ownerViewContext($user, true);

        if ($params['scope'] === 'project' && $params['project_id'] > 0) {
            if (!Project::whereKey($params['project_id'])->exists()) {
                throw new ApiException('项目不存在或已被删除');
            }
            if (!self::canAccessProject($params['project_id'], intval($user->userid), $departmentView)) {
                throw new ApiException('无权限访问此文件');
            }
        }

        $cursorSource = self::cursorSource($params['cursor']);
        if ($cursorSource === self::CURSOR_SOURCE_MESSAGES) {
            return self::listsFromMessages($user, $params, $departmentView);
        }
        if ($cursorSource === self::CURSOR_SOURCE_ATTACHMENTS || self::attachmentIndexReady()) {
            return self::listsFromAttachments($user, $params, $departmentView);
        }

        return self::listsFromMessages($user, $params, $departmentView);
    }

    private static function listsFromMessages(User $user, array $params, array $departmentView): array
    {
        $query = WebSocketDialogMsg::query()
            ->select([
                'web_socket_dialog_msgs.*',
                'dialogs.type as source_dialog_type',
                'dialogs.group_type as source_group_type',
                'dialogs.name as source_dialog_name',
                'project_chat.id as project_chat_id',
                'project_chat.name as project_chat_name',
                'project_task.id as source_task_id',
                'project_task.name as source_task_name',
                'project_task.complete_at as source_task_complete_at',
                'project_task.archived_at as source_task_archived_at',
                'task_project.id as task_project_id',
                'task_project.name as task_project_name',
            ])
            ->join('web_socket_dialogs as dialogs', 'dialogs.id', '=', 'web_socket_dialog_msgs.dialog_id')
            ->leftJoin('projects as project_chat', function ($join) {
                $join->on('project_chat.dialog_id', '=', 'dialogs.id')
                    ->whereNull('project_chat.deleted_at');
            })
            ->leftJoin('project_tasks as project_task', function ($join) {
                $join->on('project_task.dialog_id', '=', 'dialogs.id')
                    ->whereNull('project_task.deleted_at');
            })
            ->leftJoin('projects as task_project', function ($join) {
                $join->on('task_project.id', '=', 'project_task.project_id')
                    ->whereNull('task_project.deleted_at');
            })
            ->whereNull('dialogs.deleted_at')
            ->where('web_socket_dialog_msgs.type', 'file');

        self::applyAccess($query, $user, $departmentView);
        self::applyScope($query, $params);
        self::applyFilters($query, $params, false);

        $cursor = self::messageCursor($params['cursor']);
        if ($cursor > 0) {
            $query->where('web_socket_dialog_msgs.id', '<', $cursor);
        }

        $rows = $query
            ->orderByDesc('web_socket_dialog_msgs.id')
            ->take($params['take'] + 1)
            ->get();

        $hasMore = $rows->count() > $params['take'];
        if ($hasMore) {
            $rows->pop();
        }

        $userIds = $rows->pluck('userid')->map(fn($id) => intval($id))->filter()->unique()->values();
        $users = User::select(User::$basicField)->whereIn('userid', $userIds)->get()->keyBy('userid');
        $privateNames = self::privateDialogNames($rows, intval($user->userid));

        $list = $rows->map(function (WebSocketDialogMsg $row) use ($users, $privateNames, $user) {
            return self::formatRow($row, $users->get($row->userid), $privateNames, $user);
        })->values()->toArray();

        return [
            'list' => $list,
            'next_cursor' => $hasMore && $rows->isNotEmpty() ? 'm:' . intval($rows->last()->id) : 0,
            'has_more' => $hasMore,
        ];
    }

    private static function listsFromAttachments(User $user, array $params, array $departmentView): array
    {
        $query = WebSocketDialogMsgAttachment::query()
            ->select([
                'web_socket_dialog_msg_attachments.*',
                'messages.userid as sender_id',
                'messages.key as message_key',
                'messages.created_at as message_created_at',
                'dialogs.type as source_dialog_type',
                'dialogs.group_type as source_group_type',
                'dialogs.name as source_dialog_name',
                'project_chat.id as project_chat_id',
                'project_chat.name as project_chat_name',
                'project_task.id as source_task_id',
                'project_task.name as source_task_name',
                'project_task.complete_at as source_task_complete_at',
                'project_task.archived_at as source_task_archived_at',
                'task_project.id as task_project_id',
                'task_project.name as task_project_name',
            ])
            ->join('web_socket_dialog_msgs as messages', 'messages.id', '=', 'web_socket_dialog_msg_attachments.msg_id')
            ->join('web_socket_dialogs as dialogs', 'dialogs.id', '=', 'messages.dialog_id')
            ->leftJoin('projects as project_chat', function ($join) {
                $join->on('project_chat.dialog_id', '=', 'dialogs.id')
                    ->whereNull('project_chat.deleted_at');
            })
            ->leftJoin('project_tasks as project_task', function ($join) {
                $join->on('project_task.dialog_id', '=', 'dialogs.id')
                    ->whereNull('project_task.deleted_at');
            })
            ->leftJoin('projects as task_project', function ($join) {
                $join->on('task_project.id', '=', 'project_task.project_id')
                    ->whereNull('task_project.deleted_at');
            })
            ->whereNull('messages.deleted_at')
            ->whereNull('dialogs.deleted_at');

        self::applyAccess($query, $user, $departmentView);
        self::applyScope($query, $params);
        self::applyFilters($query, $params, true);
        self::applyAttachmentCursor($query, $params['cursor']);

        $rows = $query
            ->orderBy('web_socket_dialog_msg_attachments.cursor_msg_id')
            ->orderBy('web_socket_dialog_msg_attachments.position')
            ->orderBy('web_socket_dialog_msg_attachments.id')
            ->take($params['take'] + 1)
            ->get();

        $hasMore = $rows->count() > $params['take'];
        if ($hasMore) {
            $rows->pop();
        }

        $userIds = $rows->pluck('sender_id')->map(fn($id) => intval($id))->filter()->unique()->values();
        $users = User::select(User::$basicField)->whereIn('userid', $userIds)->get()->keyBy('userid');
        $privateNames = self::privateDialogNames($rows, intval($user->userid));

        $list = $rows->map(function (WebSocketDialogMsgAttachment $row) use ($users, $privateNames, $user) {
            return self::formatAttachmentRow($row, $users->get($row->sender_id), $privateNames, $user);
        })->values()->toArray();

        return [
            'list' => $list,
            'next_cursor' => $hasMore && $rows->isNotEmpty() ? self::attachmentCursor($rows->last()) : 0,
            'has_more' => $hasMore,
        ];
    }

    public static function authorizeMessage(WebSocketDialogMsg $message, User $user): void
    {
        $dialog = WebSocketDialog::whereId($message->dialog_id)->first();
        if (empty($dialog)) {
            throw new ApiException('对话不存在或已被删除');
        }

        $departmentView = UserDepartment::ownerViewContext($user, true);
        if ($dialog->group_type === 'project') {
            $projectId = intval(Project::whereDialogId($dialog->id)->value('id'));
            if ($projectId <= 0 || !self::canAccessProject($projectId, intval($user->userid), $departmentView)) {
                throw new ApiException('无权限访问此文件');
            }
            return;
        }

        if ($dialog->group_type === 'task') {
            $taskQuery = DB::table('project_tasks')
                ->whereNull('project_tasks.deleted_at')
                ->where('project_tasks.dialog_id', $dialog->id);
            self::applyTaskPermission($taskQuery, 'project_tasks', intval($user->userid), $departmentView);
            if (!$taskQuery->exists()) {
                throw new ApiException('无权限访问此文件');
            }
            return;
        }

        if (!WebSocketDialogUser::whereDialogId($dialog->id)->whereUserid($user->userid)->exists()) {
            throw new ApiException('无权限访问此文件');
        }
    }

    public static function authorizeAttachment(WebSocketDialogMsgAttachment $attachment, User $user): WebSocketDialogMsg
    {
        $message = WebSocketDialogMsg::whereId($attachment->msg_id)->first();
        if (!$message || intval($message->dialog_id) !== intval($attachment->dialog_id)) {
            throw new ApiException('文件不存在或已被删除');
        }
        self::authorizeMessage($message, $user);
        return $message;
    }

    public static function resolveLocalAttachmentPath(string $path): ?string
    {
        $urlPath = parse_url(trim($path), PHP_URL_PATH);
        if (!is_string($urlPath) || $urlPath === '') {
            return null;
        }

        $relativePath = rawurldecode($urlPath);
        if (str_contains($relativePath, "\0")) {
            return null;
        }
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        if (!str_starts_with($relativePath, 'uploads/')) {
            return null;
        }

        $uploadsRoot = realpath(public_path('uploads'));
        $filePath = realpath(public_path($relativePath));
        if ($uploadsRoot === false || $filePath === false || !is_file($filePath)) {
            return null;
        }

        $uploadsPrefix = rtrim($uploadsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        return str_starts_with($filePath, $uploadsPrefix) ? $filePath : null;
    }

    private static function applyAccess(Builder $query, User $user, array $departmentView): void
    {
        $userid = intval($user->userid);
        $query->where(function (Builder $access) use ($userid, $departmentView) {
            $access->where(function (Builder $conversation) use ($userid) {
                self::applyConversationType($conversation);
                $conversation->whereExists(function (QueryBuilder $member) use ($userid) {
                    $member->selectRaw('1')
                        ->from('web_socket_dialog_users as access_dialog_user')
                        ->whereColumn('access_dialog_user.dialog_id', 'dialogs.id')
                        ->where('access_dialog_user.userid', $userid);
                });
            })->orWhere(function (Builder $project) use ($userid, $departmentView) {
                $project->where('dialogs.group_type', 'project')
                    ->whereNotNull('project_chat.id');
                self::applyProjectPermission($project, 'project_chat.id', $userid, $departmentView);
            })->orWhere(function (Builder $task) use ($userid, $departmentView) {
                $task->where('dialogs.group_type', 'task')
                    ->whereNotNull('project_task.id');
                self::applyTaskPermission($task, 'project_task', $userid, $departmentView);
            });
        });
    }

    private static function applyScope(Builder $query, array $params): void
    {
        if ($params['scope'] === 'conversation') {
            self::applyConversationType($query);
            if ($params['conversation_type'] === 'private') {
                $query->where('dialogs.type', 'user');
            } elseif ($params['conversation_type'] === 'group') {
                $query->where('dialogs.type', 'group')
                    ->whereNotIn('dialogs.group_type', ['project', 'task']);
            }
            return;
        }

        if ($params['scope'] === 'project') {
            $projectId = $params['project_id'];
            $query->where(function (Builder $scope) use ($projectId, $params) {
                if ($params['project_source'] !== 'task') {
                    $scope->where(function (Builder $projectChat) use ($projectId) {
                        $projectChat->where('dialogs.group_type', 'project')
                            ->whereNotNull('project_chat.id')
                            ->whereNull('project_chat.archived_at');
                        if ($projectId > 0) {
                            $projectChat->where('project_chat.id', $projectId);
                        }
                    });
                }
                if ($params['project_source'] !== 'project_chat') {
                    $method = $params['project_source'] === 'task' ? 'where' : 'orWhere';
                    $scope->{$method}(function (Builder $task) use ($projectId) {
                        $task->where('dialogs.group_type', 'task')
                            ->whereNotNull('task_project.id')
                            ->whereNull('task_project.archived_at');
                        if ($projectId > 0) {
                            $task->where('task_project.id', $projectId);
                        }
                    });
                }
            });
        }
    }

    private static function applyFilters(Builder $query, array $params, bool $indexed): void
    {
        $messageTable = $indexed ? 'messages' : 'web_socket_dialog_msgs';
        if ($params['sender_id'] > 0) {
            $query->where("{$messageTable}.userid", $params['sender_id']);
        }

        if ($params['file_type'] !== 'all') {
            $extension = $indexed
                ? 'web_socket_dialog_msg_attachments.ext'
                : DB::raw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(" . DB::getTablePrefix() . "web_socket_dialog_msgs.msg, '$.ext')))");
            if ($params['file_type'] === 'other') {
                $known = array_values(array_unique(array_merge(...array_values(self::FILE_TYPE_EXTENSIONS))));
                $query->whereNotIn($extension, $known);
            } else {
                $query->whereIn($extension, self::FILE_TYPE_EXTENSIONS[$params['file_type']]);
            }
        }

        if ($params['key'] !== '') {
            $key = '%' . addcslashes($params['key'], '%_\\') . '%';
            $query->where(function (Builder $search) use ($key, $indexed, $messageTable) {
                $search->where($indexed ? 'web_socket_dialog_msg_attachments.name' : "{$messageTable}.key", 'like', $key);
                if ($indexed) {
                    $search->orWhere("{$messageTable}.key", 'like', $key);
                }
                $search
                    ->orWhere('dialogs.name', 'like', $key)
                    ->orWhere('project_chat.name', 'like', $key)
                    ->orWhere('project_task.name', 'like', $key)
                    ->orWhere('task_project.name', 'like', $key)
                    ->orWhereExists(function (QueryBuilder $sender) use ($key, $messageTable) {
                        $sender->selectRaw('1')
                            ->from('users as search_sender')
                            ->whereColumn('search_sender.userid', "{$messageTable}.userid")
                            ->where('search_sender.nickname', 'like', $key);
                    })->orWhereExists(function (QueryBuilder $privateUser) use ($key) {
                        $privateUser->selectRaw('1')
                            ->from('web_socket_dialog_users as search_dialog_user')
                            ->join('users as search_private_user', 'search_private_user.userid', '=', 'search_dialog_user.userid')
                            ->whereColumn('search_dialog_user.dialog_id', 'dialogs.id')
                            ->where('search_private_user.nickname', 'like', $key);
                    });
            });
        }
    }

    private static function attachmentIndexReady(): bool
    {
        try {
            return WebSocketDialogMsgAttachmentBackfill::whereStatus(
                WebSocketDialogMsgAttachmentBackfill::STATUS_COMPLETED
            )->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    private static function cursorSource(string $cursor): ?string
    {
        if ($cursor === '' || $cursor === '0') {
            return null;
        }
        if (preg_match('/^m:\d+$/', $cursor) || ctype_digit($cursor)) {
            return self::CURSOR_SOURCE_MESSAGES;
        }
        if (preg_match('/^a:\d+:\d+:\d+$/', $cursor)
            || preg_match('/^\d+:\d+:\d+$/', $cursor)) {
            return self::CURSOR_SOURCE_ATTACHMENTS;
        }
        throw new ApiException('参数错误');
    }

    private static function messageCursor(string $cursor): int
    {
        if ($cursor === '' || $cursor === '0') {
            return 0;
        }
        if (preg_match('/^m:(\d+)$/', $cursor, $matches)) {
            return intval($matches[1]);
        }
        if (ctype_digit($cursor)) {
            return intval($cursor);
        }
        throw new ApiException('参数错误');
    }

    private static function applyAttachmentCursor(Builder $query, string $cursor): void
    {
        if ($cursor === '' || $cursor === '0') {
            return;
        }

        if (preg_match('/^a:(\d+):(\d+):(\d+)$/', $cursor, $matches)
            || preg_match('/^(\d+):(\d+):(\d+)$/', $cursor, $matches)) {
            $msgId = intval($matches[1]);
            $position = intval($matches[2]);
            $attachmentId = intval($matches[3]);
        } else {
            throw new ApiException('参数错误');
        }

        $query->where(function (Builder $page) use ($msgId, $position, $attachmentId) {
            $page->where('web_socket_dialog_msg_attachments.cursor_msg_id', '>', -$msgId)
                ->orWhere(function (Builder $sameMessage) use ($msgId, $position, $attachmentId) {
                    $sameMessage->where('web_socket_dialog_msg_attachments.cursor_msg_id', -$msgId)
                        ->where(function (Builder $afterAttachment) use ($position, $attachmentId) {
                            $afterAttachment->where('web_socket_dialog_msg_attachments.position', '>', $position)
                                ->orWhere(function (Builder $samePosition) use ($position, $attachmentId) {
                                    $samePosition->where('web_socket_dialog_msg_attachments.position', $position)
                                        ->where('web_socket_dialog_msg_attachments.id', '>', $attachmentId);
                                });
                        });
                });
        });
    }

    private static function attachmentCursor(WebSocketDialogMsgAttachment $attachment): string
    {
        return implode(':', [
            'a',
            intval($attachment->msg_id),
            intval($attachment->position),
            intval($attachment->id),
        ]);
    }

    private static function applyConversationType(Builder $query): void
    {
        $query->where(function (Builder $type) {
            $type->where('dialogs.type', 'user')
                ->orWhere(function (Builder $group) {
                    $group->where('dialogs.type', 'group')
                        ->whereNotIn('dialogs.group_type', ['project', 'task']);
                });
        });
    }

    private static function applyProjectPermission(Builder|QueryBuilder $query, string $projectColumn, int $userid, array $departmentView): void
    {
        $query->where(function ($permission) use ($projectColumn, $userid, $departmentView) {
            $permission->whereExists(function (QueryBuilder $member) use ($projectColumn, $userid) {
                $member->selectRaw('1')
                    ->from('project_users as access_project_user')
                    ->whereColumn('access_project_user.project_id', $projectColumn)
                    ->where('access_project_user.userid', $userid);
            });
            if (!empty($departmentView['project_ids'])) {
                $permission->orWhereIn($projectColumn, $departmentView['project_ids']);
            }
        });
    }

    private static function applyTaskPermission(Builder|QueryBuilder $query, string $taskAlias, int $userid, array $departmentView): void
    {
        $query->where(function ($permission) use ($taskAlias, $userid, $departmentView) {
            $permission->where(function ($projectVisible) use ($taskAlias, $userid, $departmentView) {
                $projectVisible->where("{$taskAlias}.visibility", 1);
                self::applyProjectPermission($projectVisible, "{$taskAlias}.project_id", $userid, $departmentView);
            })->orWhereExists(function (QueryBuilder $projectOwner) use ($taskAlias, $userid) {
                $projectOwner->selectRaw('1')
                    ->from('project_users as access_task_project_owner')
                    ->whereColumn('access_task_project_owner.project_id', "{$taskAlias}.project_id")
                    ->where('access_task_project_owner.userid', $userid)
                    ->whereIn('access_task_project_owner.owner', [ProjectUser::OWNER_PRIMARY, ProjectUser::OWNER_DEPUTY]);
            })->orWhereExists(function (QueryBuilder $taskUser) use ($taskAlias, $userid) {
                $taskUser->selectRaw('1')
                    ->from('project_task_users as access_task_user')
                    ->whereColumn('access_task_user.task_id', "{$taskAlias}.id")
                    ->where('access_task_user.userid', $userid);
            })->orWhereExists(function (QueryBuilder $visibleUser) use ($taskAlias, $userid) {
                $visibleUser->selectRaw('1')
                    ->from('project_task_visibility_users as access_task_visible_user')
                    ->whereColumn('access_task_visible_user.task_id', "{$taskAlias}.id")
                    ->where('access_task_visible_user.userid', $userid);
            })->orWhereExists(function (QueryBuilder $parentVisibleUser) use ($taskAlias, $userid) {
                $parentVisibleUser->selectRaw('1')
                    ->from('project_task_visibility_users as access_parent_task_visible_user')
                    ->whereColumn('access_parent_task_visible_user.task_id', "{$taskAlias}.parent_id")
                    ->where('access_parent_task_visible_user.userid', $userid);
            });
        });
    }

    private static function canAccessProject(int $projectId, int $userid, array $departmentView): bool
    {
        if (isset($departmentView['project_id_map'][$projectId])) {
            return true;
        }
        return DB::table('project_users')->where('project_id', $projectId)->where('userid', $userid)->exists();
    }

    private static function privateDialogNames($rows, int $userid): array
    {
        $dialogIds = $rows->filter(fn($row) => $row->source_dialog_type === 'user')
            ->pluck('dialog_id')->map(fn($id) => intval($id))->unique()->values();
        if ($dialogIds->isEmpty()) {
            return [];
        }

        $names = [];
        $members = DB::table('web_socket_dialog_users as dialog_user')
            ->join('users', 'users.userid', '=', 'dialog_user.userid')
            ->whereIn('dialog_user.dialog_id', $dialogIds)
            ->where('dialog_user.userid', '!=', $userid)
            ->select(['dialog_user.dialog_id', 'users.nickname'])
            ->get();
        foreach ($members as $member) {
            $names[intval($member->dialog_id)] = $member->nickname;
        }
        return $names;
    }

    private static function formatRow(WebSocketDialogMsg $row, ?User $sender, array $privateNames, User $currentUser): array
    {
        $file = Base::json2array($row->getRawOriginal('msg'));
        $ext = strtolower((string)($file['ext'] ?? pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION)));
        $imageUrl = '';
        if (in_array($ext, self::PREVIEW_IMAGE_EXTENSIONS, true)) {
            $imageUrl = Base::fillUrl(($file['thumb'] ?? '') ?: ($file['path'] ?? ''));
        }
        $sourceType = 'group';
        $sourceName = $row->source_dialog_name;
        $projectId = 0;
        $projectName = '';
        $taskId = 0;
        $taskName = '';

        if ($row->source_dialog_type === 'user') {
            $sourceType = 'private';
            $sourceName = $privateNames[intval($row->dialog_id)] ?? $currentUser->nickname;
        } elseif ($row->source_group_type === 'project') {
            $sourceType = 'project_chat';
            $sourceName = $row->project_chat_name ?: $row->source_dialog_name;
            $projectId = intval($row->project_chat_id);
            $projectName = (string)$row->project_chat_name;
        } elseif ($row->source_group_type === 'task') {
            $sourceType = 'task';
            $sourceName = $row->source_task_name ?: $row->source_dialog_name;
            $projectId = intval($row->task_project_id);
            $projectName = (string)$row->task_project_name;
            $taskId = intval($row->source_task_id);
            $taskName = (string)$row->source_task_name;
        }

        return [
            'attachment_id' => 0,
            'attachment_source' => WebSocketDialogMsgAttachment::SOURCE_FILE_MESSAGE,
            'attachment_position' => 0,
            'generated_name' => false,
            'msg_id' => intval($row->id),
            'dialog_id' => intval($row->dialog_id),
            'name' => (string)($file['name'] ?? ''),
            'ext' => $ext,
            'size' => intval($file['size'] ?? 0),
            'thumb' => Base::fillUrl($file['thumb'] ?? Base::extIcon($ext)),
            'image_url' => $imageUrl,
            'width' => intval($file['width'] ?? -1),
            'height' => intval($file['height'] ?? -1),
            'file_type' => self::fileType($ext),
            'source_type' => $sourceType,
            'source_name' => (string)$sourceName,
            'project_id' => $projectId,
            'project_name' => $projectName,
            'task_id' => $taskId,
            'task_name' => $taskName,
            'task_status' => $taskId > 0 ? ($row->source_task_archived_at ? 'archived' : ($row->source_task_complete_at ? 'completed' : 'active')) : '',
            'sender' => $sender ? $sender->toArray() : ['userid' => intval($row->userid)],
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }

    private static function formatAttachmentRow(
        WebSocketDialogMsgAttachment $row,
        ?User $sender,
        array $privateNames,
        User $currentUser
    ): array {
        $ext = strtolower((string)$row->ext);
        $name = trim((string)$row->name);
        $generatedName = $name === '';
        if ($name === '') {
            $path = (string)(parse_url((string)$row->path, PHP_URL_PATH) ?: $row->path);
            $name = basename($path) ?: "image-{$row->msg_id}-" . (intval($row->position) + 1) . ($ext ? ".{$ext}" : '');
        }

        $imageUrl = '';
        if ($row->kind === WebSocketDialogMsgAttachment::KIND_IMAGE) {
            $imageUrl = Base::fillUrl($row->thumb ?: $row->path);
        }

        $sourceType = 'group';
        $sourceName = $row->source_dialog_name;
        $projectId = 0;
        $projectName = '';
        $taskId = 0;
        $taskName = '';

        if ($row->source_dialog_type === 'user') {
            $sourceType = 'private';
            $sourceName = $privateNames[intval($row->dialog_id)] ?? $currentUser->nickname;
        } elseif ($row->source_group_type === 'project') {
            $sourceType = 'project_chat';
            $sourceName = $row->project_chat_name ?: $row->source_dialog_name;
            $projectId = intval($row->project_chat_id);
            $projectName = (string)$row->project_chat_name;
        } elseif ($row->source_group_type === 'task') {
            $sourceType = 'task';
            $sourceName = $row->source_task_name ?: $row->source_dialog_name;
            $projectId = intval($row->task_project_id);
            $projectName = (string)$row->task_project_name;
            $taskId = intval($row->source_task_id);
            $taskName = (string)$row->source_task_name;
        }

        return [
            'attachment_id' => intval($row->id),
            'attachment_source' => (string)$row->source_type,
            'attachment_position' => intval($row->position),
            'generated_name' => $generatedName,
            'msg_id' => intval($row->msg_id),
            'dialog_id' => intval($row->dialog_id),
            'name' => $name,
            'ext' => $ext,
            'size' => intval($row->size),
            'thumb' => Base::fillUrl($row->thumb ?: Base::extIcon($ext)),
            'image_url' => $imageUrl,
            'width' => intval($row->width),
            'height' => intval($row->height),
            'file_type' => self::fileType($ext),
            'source_type' => $sourceType,
            'source_name' => (string)$sourceName,
            'project_id' => $projectId,
            'project_name' => $projectName,
            'task_id' => $taskId,
            'task_name' => $taskName,
            'task_status' => $taskId > 0 ? ($row->source_task_archived_at ? 'archived' : ($row->source_task_complete_at ? 'completed' : 'active')) : '',
            'sender' => $sender ? $sender->toArray() : ['userid' => intval($row->sender_id)],
            'created_at' => (string)$row->message_created_at,
        ];
    }

    private static function fileType(string $ext): string
    {
        foreach (self::FILE_TYPE_EXTENSIONS as $type => $extensions) {
            if (in_array($ext, $extensions, true)) {
                return $type;
            }
        }
        return 'other';
    }
}
