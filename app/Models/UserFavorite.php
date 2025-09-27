<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\File;

/**
 * App\Models\UserFavorite
 *
 * @property int $id
 * @property int|null $userid 用户ID
 * @property string|null $favoritable_type 收藏类型(比如：task/project/file/message)
 * @property int|null $favoritable_id 收藏对象ID
 * @property string $remark 收藏备注
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $favoritable
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|UserFavorite newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserFavorite newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserFavorite query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|UserFavorite whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserFavorite whereFavoritableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserFavorite whereFavoritableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserFavorite whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserFavorite whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserFavorite whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserFavorite whereUserid($value)
 * @mixin \Eloquent
 */
class UserFavorite extends AbstractModel
{
    const TYPE_TASK = 'task';
    const TYPE_PROJECT = 'project';
    const TYPE_FILE = 'file';
    const TYPE_MESSAGE = 'message';

    protected $fillable = [
        'userid',
        'favoritable_type',
        'favoritable_id',
        'remark',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userid', 'userid');
    }

    /**
     * 多态关联
     */
    public function favoritable()
    {
        return $this->morphTo();
    }

    /**
     * 切换收藏状态
     * @param int $userid 用户ID
     * @param string $type 收藏类型
     * @param int $id 收藏对象ID
     * @return array ['favorited' => bool, 'action' => 'added'|'removed']
     */
    public static function toggleFavorite($userid, $type, $id)
    {
        $favorite = self::whereUserid($userid)
            ->whereFavoritableType($type)
            ->whereFavoritableId($id)
            ->first();

        if ($favorite) {
            // 取消收藏
            $favorite->delete();
            return ['favorited' => false, 'action' => 'removed', 'remark' => ''];
        }

        // 添加收藏
        $favorite = self::create([
            'userid' => $userid,
            'favoritable_type' => $type,
            'favoritable_id' => $id,
        ]);

        return ['favorited' => true, 'action' => 'added', 'remark' => $favorite->remark ?? ''];
    }

    /**
     * 更新收藏备注
     * @param int $userid
     * @param string $type
     * @param int $id
     * @param string $remark
     * @return static|null
     */
    public static function updateRemark($userid, $type, $id, $remark)
    {
        $favorite = self::whereUserid($userid)
            ->whereFavoritableType($type)
            ->whereFavoritableId($id)
            ->first();

        if (!$favorite) {
            return null;
        }

        $favorite->remark = $remark;
        $favorite->save();

        return $favorite;
    }

    /**
     * 检查是否已收藏
     * @param int $userid 用户ID
     * @param string $type 收藏类型
     * @param int $id 收藏对象ID
     * @return bool
     */
    public static function isFavorited($userid, $type, $id)
    {
        return self::whereUserid($userid)
            ->whereFavoritableType($type)
            ->whereFavoritableId($id)
            ->exists();
    }

    /**
     * 获取用户收藏列表
     * @param int $userid 用户ID
     * @param string|null $type 收藏类型过滤
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return array
     */
    public static function getUserFavorites($userid, $type = null, $page = 1, $pageSize = 20)
    {
        $query = self::whereUserid($userid)->orderByDesc('created_at');

        if ($type) {
            $query->whereFavoritableType($type);
        }

        $favorites = $query->paginate($pageSize, ['*'], 'page', $page);

        $data = [
            'tasks' => [],
            'projects' => [],
            'files' => [],
            'messages' => []
        ];

        // 分组收集ID
        $taskIds = [];
        $projectIds = [];
        $fileIds = [];
        $messageIds = [];

        foreach ($favorites->items() as $favorite) {
            switch ($favorite->favoritable_type) {
                case self::TYPE_TASK:
                    $taskIds[] = $favorite->favoritable_id;
                    break;
                case self::TYPE_PROJECT:
                    $projectIds[] = $favorite->favoritable_id;
                    break;
                case self::TYPE_FILE:
                    $fileIds[] = $favorite->favoritable_id;
                    break;
                case self::TYPE_MESSAGE:
                    $messageIds[] = $favorite->favoritable_id;
                    break;
            }
        }

        // 批量查询具体数据
        if (!empty($taskIds)) {
            $tasks = ProjectTask::select([
                'project_tasks.id', 
                'project_tasks.name', 
                'project_tasks.project_id', 
                'project_tasks.complete_at', 
                'project_tasks.created_at',
                'project_tasks.flow_item_id',
                'project_tasks.flow_item_name',
                'projects.name as project_name'
            ])
            ->leftJoin('projects', 'project_tasks.project_id', '=', 'projects.id')
            ->whereIn('project_tasks.id', $taskIds)
            ->get()
            ->keyBy('id');
            
            foreach ($favorites->items() as $favorite) {
                if ($favorite->favoritable_type === self::TYPE_TASK && isset($tasks[$favorite->favoritable_id])) {
                    $task = $tasks[$favorite->favoritable_id];
                    
                    // 解析 flow_item_name 字段（格式：status|name|color）
                    $flowItemParts = explode('|', $task->flow_item_name ?: '');
                    $flowItemStatus = $flowItemParts[0] ?? '';
                    $flowItemName = $flowItemParts[1] ?? $task->flow_item_name;
                    $flowItemColor = $flowItemParts[2] ?? '';
                    
                    $data['tasks'][] = [
                        'id' => $task->id,
                        'name' => $task->name,
                        'project_id' => $task->project_id,
                        'project_name' => $task->project_name,
                        'complete_at' => $task->complete_at,
                        'flow_item_id' => $task->flow_item_id,
                        'flow_item_name' => $flowItemName,
                        'flow_item_status' => $flowItemStatus,
                        'flow_item_color' => $flowItemColor,
                        'favorited_at' => Carbon::parse($favorite->created_at)->format('Y-m-d H:i:s'),
                        'remark' => $favorite->remark,
                    ];
                }
            }
        }

        if (!empty($projectIds)) {
            $projects = Project::select([
                'id', 'name', 'desc', 'archived_at', 'created_at'
            ])->whereIn('id', $projectIds)->get()->keyBy('id');
            
            foreach ($favorites->items() as $favorite) {
                if ($favorite->favoritable_type === self::TYPE_PROJECT && isset($projects[$favorite->favoritable_id])) {
                    $project = $projects[$favorite->favoritable_id];
                    $data['projects'][] = [
                        'id' => $project->id,
                        'name' => $project->name,
                        'desc' => $project->desc,
                        'archived_at' => $project->archived_at,
                        'favorited_at' => Carbon::parse($favorite->created_at)->format('Y-m-d H:i:s'),
                        'remark' => $favorite->remark,
                    ];
                }
            }
        }

        if (!empty($fileIds)) {
            $files = File::select([
                'id', 'name', 'ext', 'size', 'pid', 'created_at'
            ])->whereIn('id', $fileIds)->get()->keyBy('id');
            
            foreach ($favorites->items() as $favorite) {
                if ($favorite->favoritable_type === self::TYPE_FILE && isset($files[$favorite->favoritable_id])) {
                    $file = $files[$favorite->favoritable_id];
                    $fileData = File::handleImageUrl(array_merge(
                        $file->only(['id', 'ext']),
                        [
                            'name' => $file->name,
                            'size' => $file->size,
                            'pid' => $file->pid,
                        ]
                    ));
                    $data['files'][] = [
                        'id' => $file->id,
                        'name' => $file->name,
                        'ext' => $file->ext,
                        'size' => $file->size,
                        'pid' => $file->pid,
                        'image_url' => $fileData['image_url'] ?? null,
                        'image_width' => $fileData['image_width'] ?? null,
                        'image_height' => $fileData['image_height'] ?? null,
                        'favorited_at' => Carbon::parse($favorite->created_at)->format('Y-m-d H:i:s'),
                        'remark' => $favorite->remark,
                    ];
                }
            }
        }

        if (!empty($messageIds)) {
            $messages = WebSocketDialogMsg::select([
                'id', 'dialog_id', 'userid', 'type', 'msg', 'created_at'
            ])->whereIn('id', $messageIds)->get()->keyBy('id');
            
            foreach ($favorites->items() as $favorite) {
                if ($favorite->favoritable_type === self::TYPE_MESSAGE && isset($messages[$favorite->favoritable_id])) {
                    $message = $messages[$favorite->favoritable_id];
                    
                    // 使用 previewMsg 获取消息预览文本
                    $previewText = '';
                    if ($message->msg && is_array($message->msg)) {
                        $previewText = WebSocketDialogMsg::previewMsg($message);
                    }
                    
                    // 如果没有预览文本，使用消息类型作为标题
                    if (empty($previewText)) {
                        $previewText = '[' . ucfirst($message->type) . ']';
                    }
                    
                    $data['messages'][] = [
                        'id' => $message->id,
                        'name' => $previewText,
                        'dialog_id' => $message->dialog_id,
                        'userid' => $message->userid,
                        'type' => $message->type,
                        'favorited_at' => Carbon::parse($favorite->created_at)->format('Y-m-d H:i:s'),
                        'remark' => $favorite->remark,
                    ];
                }
            }
        }

        return [
            'data' => $data,
            'total' => $favorites->total(),
            'current_page' => $favorites->currentPage(),
            'per_page' => $favorites->perPage(),
            'last_page' => $favorites->lastPage(),
        ];
    }

    /**
     * 清理用户收藏
     * @param int $userid 用户ID
     * @param string|null $type 收藏类型，null表示全部类型
     * @return int 删除的记录数
     */
    public static function cleanUserFavorites($userid, $type = null)
    {
        $query = self::whereUserid($userid);
        
        if ($type) {
            $query->whereFavoritableType($type);
        }
        
        return $query->delete();
    }
}
