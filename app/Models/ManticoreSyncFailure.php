<?php

namespace App\Models;

/**
 * Manticore 同步失败记录
 *
 * @property int $id
 * @property string $data_type 数据类型: msg/file/task/project/user
 * @property int $data_id 数据ID
 * @property string $action 操作类型: sync/delete
 * @property string|null $error_message 错误信息
 * @property int $retry_count 重试次数
 * @property \Carbon\Carbon|null $last_retry_at 最后重试时间
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ManticoreSyncFailure extends AbstractModel
{
    protected $table = 'manticore_sync_failures';

    protected $fillable = [
        'data_type',
        'data_id',
        'action',
        'error_message',
        'retry_count',
        'last_retry_at',
    ];

    protected $dates = [
        'last_retry_at',
        'created_at',
        'updated_at',
    ];

    /**
     * 记录同步失败
     *
     * @param string $dataType 数据类型
     * @param int $dataId 数据ID
     * @param string $action 操作类型 sync/delete
     * @param string $errorMessage 错误信息
     */
    public static function recordFailure(string $dataType, int $dataId, string $action, string $errorMessage = ''): void
    {
        self::updateOrCreate(
            [
                'data_type' => $dataType,
                'data_id' => $dataId,
                'action' => $action,
            ],
            [
                'error_message' => mb_substr($errorMessage, 0, 500),
                'retry_count' => \DB::raw('retry_count + 1'),
                'last_retry_at' => now(),
            ]
        );
    }

    /**
     * 删除成功记录
     *
     * @param string $dataType 数据类型
     * @param int $dataId 数据ID
     * @param string $action 操作类型
     */
    public static function removeSuccess(string $dataType, int $dataId, string $action): void
    {
        self::where('data_type', $dataType)
            ->where('data_id', $dataId)
            ->where('action', $action)
            ->delete();
    }

    /**
     * 获取待重试的记录
     * 根据重试次数决定间隔：1次=1分钟，2次=5分钟，3次=15分钟，4次+=30分钟
     *
     * @param int $limit 数量限制
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getPendingRetries(int $limit = 100)
    {
        return self::where(function ($query) {
            $query->whereNull('last_retry_at')
                ->orWhere(function ($q) {
                    // 根据重试次数决定间隔
                    $q->where(function ($sub) {
                        // 重试1次：等待1分钟
                        $sub->where('retry_count', 1)
                            ->where('last_retry_at', '<', now()->subMinutes(1));
                    })->orWhere(function ($sub) {
                        // 重试2次：等待5分钟
                        $sub->where('retry_count', 2)
                            ->where('last_retry_at', '<', now()->subMinutes(5));
                    })->orWhere(function ($sub) {
                        // 重试3次：等待15分钟
                        $sub->where('retry_count', 3)
                            ->where('last_retry_at', '<', now()->subMinutes(15));
                    })->orWhere(function ($sub) {
                        // 重试4次以上：等待30分钟
                        $sub->where('retry_count', '>=', 4)
                            ->where('last_retry_at', '<', now()->subMinutes(30));
                    });
                });
        })
        ->orderBy('last_retry_at')
        ->limit($limit)
        ->get();
    }

    /**
     * 获取统计信息
     *
     * @return array
     */
    public static function getStats(): array
    {
        return [
            'total' => self::count(),
            'by_type' => self::selectRaw('data_type, COUNT(*) as count')
                ->groupBy('data_type')
                ->pluck('count', 'data_type')
                ->toArray(),
            'by_action' => self::selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->pluck('count', 'action')
                ->toArray(),
        ];
    }
}
