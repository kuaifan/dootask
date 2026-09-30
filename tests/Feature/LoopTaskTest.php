<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\ProjectLog;
use App\Models\ProjectTask;
use App\Models\User;
use App\Tasks\LoopTask;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * LoopTask：周期任务到点生成新任务
 *
 * 回归：LoopTask 曾先提交副本、再刷新周期；刷新失败（Carbon 3 的 addDays 收到字符串）时
 * 副本与原任务的 loop_at 都留在 10 分钟窗口内，每分钟翻倍并被同一轮 chunkById 继续扫到。
 */
class LoopTaskTest extends TestCase
{
    use DatabaseTransactions;

    private Project $project;
    private ProjectColumn $column;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::createInstance([
            'email' => 'looptask_' . uniqid() . '@example.com',
            'userimg' => '',
            'nickname' => 'LoopTaskUser',
            'profession' => '',
            'password' => md5('123456'),
        ]);
        $this->user->save();
        $this->project = Project::createInstance([
            'name' => 'looptask-' . uniqid(),
            'desc' => '',
            'userid' => $this->user->userid,
            'personal' => 0,
        ]);
        $this->project->save();
        $this->column = ProjectColumn::createInstance([
            'project_id' => $this->project->id,
            'name' => 'Default',
        ]);
        $this->column->save();
    }

    /**
     * 创建一个顶级周期任务
     * @param mixed $loop 重复周期（与库里一致，数字周期是字符串）
     * @param Carbon $loopAt 下个周期时间
     */
    private function makeLoopTask($loop, Carbon $loopAt, array $overrides = []): ProjectTask
    {
        $task = ProjectTask::createInstance(array_merge([
            'project_id' => $this->project->id,
            'column_id' => $this->column->id,
            'parent_id' => 0,
            'name' => 'loop-' . uniqid(),
            'userid' => $this->user->userid,
            'start_at' => $loopAt->clone(),
            'end_at' => $loopAt->clone()->addHour(),
            'loop' => (string)$loop,
            'loop_at' => $loopAt->clone(),
        ], $overrides));
        $task->save();
        return $task;
    }

    private function runLoopTask(): void
    {
        (new LoopTask())->start();
    }

    private function projectTasks()
    {
        return ProjectTask::whereProjectId($this->project->id)->whereParentId(0)->orderBy('id')->get();
    }

    private function failureLogs(int $taskId)
    {
        return ProjectLog::whereTaskId($taskId)->where('detail', 'like', '生成重复任务失败%')->get();
    }

    public function test_custom_days_loop_creates_exactly_one_copy_and_closes_source(): void
    {
        $due = Carbon::now()->subMinute();
        $source = $this->makeLoopTask('90', $due);

        $this->runLoopTask();

        $tasks = $this->projectTasks();
        $this->assertCount(2, $tasks, '到点只应生成 1 个新任务');
        $source = $source->fresh();
        $this->assertSame('', (string)$source->loop, '原任务周期应被关闭');
        $this->assertNull($source->loop_at);

        $copy = $tasks->last();
        $this->assertNotSame($source->id, $copy->id);
        $this->assertSame('90', (string)$copy->loop, '新任务继承周期');
        $this->assertTrue(Carbon::parse($copy->loop_at)->gt(Carbon::now()->addDays(88)), '新任务的下个周期应在约 90 天后');
        $this->assertSame($due->toDateTimeString(), Carbon::parse($copy->start_at)->toDateTimeString(), '新任务开始时间等于原任务的 loop_at');
        $this->assertSame(3600, (int)Carbon::parse($copy->start_at)->diffInSeconds(Carbon::parse($copy->end_at)), '新任务时长与原任务一致');
    }

    public function test_second_run_does_not_create_more_copies(): void
    {
        $this->makeLoopTask('90', Carbon::now()->subMinute());

        $this->runLoopTask();
        $this->runLoopTask();
        $this->runLoopTask();

        $this->assertCount(2, $this->projectTasks());
    }

    /**
     * 历史脏数据（如 loop='0'）：刷新周期失败时必须整体回滚，不能留下 loop_at 仍在窗口内的副本
     */
    public function test_refresh_failure_rolls_back_without_leaving_copies(): void
    {
        $source = $this->makeLoopTask('0', Carbon::now()->subMinute());

        $this->runLoopTask();

        $this->assertCount(1, $this->projectTasks(), '失败不应留下任何副本');
        $this->assertCount(1, $this->failureLogs($source->id), '应记录一条失败日志');
        $this->assertNotNull($source->fresh()->loop_at, '失败时原任务周期保持不变，窗口内可重试');
    }

    public function test_repeated_failures_never_snowball(): void
    {
        $source = $this->makeLoopTask('0', Carbon::now()->subMinute());

        for ($i = 0; $i < 5; $i++) {
            $this->runLoopTask();
        }

        $this->assertCount(1, $this->projectTasks());
        $this->assertCount(5, $this->failureLogs($source->id), '每轮只对原任务失败一次');
    }

    public function test_failure_rolls_back_subtasks_and_members_too(): void
    {
        $source = $this->makeLoopTask('0', Carbon::now()->subMinute());
        $this->makeLoopTask('day', Carbon::now()->subMinute(), [
            'parent_id' => $source->id,
            'loop' => '',
            'loop_at' => null,
        ]);

        $before = ProjectTask::whereProjectId($this->project->id)->count();
        $this->runLoopTask();

        $this->assertSame($before, ProjectTask::whereProjectId($this->project->id)->count(), '副本及其子任务都应回滚');
    }

    public function test_due_time_outside_window_is_ignored(): void
    {
        $this->makeLoopTask('day', Carbon::now()->subMinutes(30));
        $this->makeLoopTask('day', Carbon::now()->addMinutes(30));

        $this->runLoopTask();

        $this->assertCount(2, $this->projectTasks());
    }

    public function test_archived_project_is_skipped(): void
    {
        $this->project->archived_at = Carbon::now();
        $this->project->save();
        $this->makeLoopTask('day', Carbon::now()->subMinute());

        $this->runLoopTask();

        $this->assertCount(1, $this->projectTasks());
    }

    public function test_subtask_is_not_processed(): void
    {
        $parent = $this->makeLoopTask('never', Carbon::now()->subDay(), ['loop' => '', 'loop_at' => null]);
        $sub = $this->makeLoopTask('day', Carbon::now()->subMinute(), ['parent_id' => $parent->id]);

        $this->runLoopTask();

        $this->assertSame(0, ProjectTask::whereParentId($parent->id)->where('id', '!=', $sub->id)->count());
        $this->assertCount(1, $this->projectTasks());
    }

    public function test_preset_loop_creates_one_copy_with_next_cycle(): void
    {
        $this->makeLoopTask('day', Carbon::now()->subMinute());

        $this->runLoopTask();

        $tasks = $this->projectTasks();
        $this->assertCount(2, $tasks);
        $this->assertTrue(Carbon::parse($tasks->last()->loop_at)->gt(Carbon::now()));
    }
}
