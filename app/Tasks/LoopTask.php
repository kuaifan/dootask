<?php

namespace App\Tasks;

use App\Exceptions\ApiException;
use App\Models\AbstractModel;
use App\Module\ProjectTaskHandoffRecord;
use App\Models\ProjectFlow;
use App\Models\ProjectFlowItem;
use App\Models\ProjectTask;
use App\Models\ProjectTaskUser;
use Carbon\Carbon;

/**
 * 任务重复周期
 */
class LoopTask extends AbstractTask
{
    public function __construct()
    {
        parent::__construct();
    }

    public function start()
    {
        // 只处理本轮开始前已存在的任务，本轮新生成的任务不会在同一轮里被再次扫到
        $maxId = (int)ProjectTask::withTrashed()->max('id');
        ProjectTask::where('id', '<=', $maxId)->whereBetween('loop_at', [
            Carbon::now()->subMinutes(10),
            Carbon::now()
        ])->whereHas('project', function ($query) {
            // 仅处理未删除、未归档项目的任务（Project 软删除由全局作用域排除）
            $query->whereNull('archived_at');
        })->chunkById(100, function ($list) {
            /** @var ProjectTask $item */
            foreach ($list as $item) {
                if ($item->parent_id > 0) {
                    // 如果是子任务则不处理
                    continue;
                }
                try {
                    $this->createLoopTask($item);
                } catch (\Throwable $e) {
                    $item->addLog("生成重复任务失败：" . $e->getMessage(), [], $item->userid);
                }
            }
        });
    }

    /**
     * 按周期生成新任务并关闭原任务周期
     * 复制、刷新周期、关闭旧周期在同一事务内，任一步失败整体回滚，不会留下 loop_at 仍在窗口内的残留副本
     * @param ProjectTask $item
     */
    private function createLoopTask(ProjectTask $item)
    {
        AbstractModel::transaction(function () use ($item) {
            // 锁定并复核原任务：已被并发或上一轮处理（loop_at 已变化/清空）则跳过
            $source = ProjectTask::whereKey($item->id)->lockForUpdate()->first();
            if (!$source
                || $source->parent_id > 0
                || !$source->loop_at
                || Carbon::parse($source->loop_at)->toDateTimeString() !== Carbon::parse($item->loop_at)->toDateTimeString()) {
                return;
            }
            $task = $source->copyTask();
            ProjectTaskHandoffRecord::track($task, 'recurring', function () use ($task, $source) {
                // 工作流
                $projectFlow = ProjectFlow::whereProjectId($task->project_id)->orderByDesc('id')->first();
                if ($projectFlow) {
                    $projectFlowItem = ProjectFlowItem::whereFlowId($projectFlow->id)->orderBy('sort')->get();
                    // 赋一个开始状态
                    foreach ($projectFlowItem as $flowItem) {
                        if ($flowItem->status == 'start') {
                            $task->flow_item_id = $flowItem->id;
                            $task->flow_item_name = $flowItem->status . "|" . $flowItem->name . "|" . $flowItem->color;
                            if ($flowItem->userids) {
                                $userids = array_values(array_unique($flowItem->userids));
                                foreach ($userids as $uid) {
                                    ProjectTaskUser::updateInsert([
                                        'task_id' => $task->id,
                                        'userid' => $uid,
                                    ], [
                                        'project_id' => $task->project_id,
                                        'task_pid' => $task->id,
                                        'owner' => 1,
                                    ]);
                                }
                            }
                            break;
                        }
                    }
                }
                // 新任务时间、周期
                if ($task->start_at) {
                    $diffSecond = (int)Carbon::parse($task->start_at)->diffInSeconds(Carbon::parse($task->end_at), true);
                    $task->start_at = Carbon::parse($task->loop_at);
                    $task->end_at = $task->start_at->clone()->addSeconds($diffSecond);
                }
                // 处理子任务
                $source->copySubTasks($task, [
                    'reset_complete' => true,
                    'sync_time' => true,
                ]);
                // 刷新新任务的下个周期，失败则抛出使整体回滚（否则副本会带着旧 loop_at 被再次复制）
                if (!$task->refreshLoop(true)) {
                    throw new ApiException('重复周期设置无效');
                }
                $task->addLog("创建任务来自周期任务ID：{$source->id}", [], $task->userid);
            });
            // 清空旧周期
            $source->loop = '';
            $source->loop_at = null;
            $source->save();
            $source->addLog("已创建新的周期任务ID：{$task->id}，此任务关闭周期", [], $task->userid);
        });
    }

    public function end()
    {

    }
}
