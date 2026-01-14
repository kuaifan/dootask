<?php

namespace App\Tasks;

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
        ProjectTask::whereBetween('loop_at', [
            Carbon::now()->subMinutes(10),
            Carbon::now()
        ])->chunkById(100, function ($list) {
            /** @var ProjectTask $item */
            foreach ($list as $item) {
                if ($item->parent_id > 0) {
                    // 如果是子任务则不处理
                    continue;
                }
                try {
                    $task = $item->copyTask();
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
                        $diffSecond = Carbon::parse($task->start_at)->diffInSeconds(Carbon::parse($task->end_at), true);
                        $task->start_at = Carbon::parse($task->loop_at);
                        $task->end_at = $task->start_at->clone()->addSeconds($diffSecond);
                    }
                    // 处理子任务
                    $item->copySubTasks($task, [
                        'reset_complete' => true,
                        'sync_time' => true,
                    ]);
                    //
                    $task->refreshLoop(true);
                    $task->addLog("创建任务来自周期任务ID：{$item->id}", [], $task->userid);
                    // 清空旧周期
                    $item->loop = '';
                    $item->loop_at = null;
                    $item->save();
                    $item->addLog("已创建新的周期任务ID：{$task->id}，此任务关闭周期", [], $task->userid);
                } catch (\Throwable $e) {
                    $item->addLog("生成重复任务失败：" . $e->getMessage(), [], $item->userid);
                }
            }
        });
    }

    public function end()
    {

    }
}
