<?php

namespace Tests\Feature;

use App\Module\DashboardTeam;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTeamTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-07-15 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_stats_and_lists_use_the_same_team_scope(): void
    {
        $memberA = $this->createUser('dashboard_member_a');
        $memberB = $this->createUser('dashboard_member_b');
        $outside = $this->createUser('dashboard_outside');
        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Dashboard Team Project',
            'userid' => $memberA,
            'department_owner_view' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $overdue = $this->createTask($projectId, [
            'name' => 'Managed overdue',
            'end_at' => Carbon::now()->subDay(),
            'p_level' => 1,
        ]);
        $this->addOwner($projectId, $overdue, $memberA);

        $noOwnerSoon = $this->createTask($projectId, [
            'name' => 'No owner soon',
            'end_at' => Carbon::now()->addDay(),
            'p_level' => 2,
        ]);

        $outsideTask = $this->createTask($projectId, ['name' => 'Outside owner']);
        $this->addOwner($projectId, $outsideTask, $outside);

        $subtask = $this->createTask($projectId, [
            'name' => 'Subtask',
            'parent_id' => $overdue,
        ]);
        $this->addOwner($projectId, $subtask, $memberA);

        $privateTask = $this->createTask($projectId, [
            'name' => 'Private task',
            'visibility' => 2,
        ]);
        $this->addOwner($projectId, $privateTask, $memberA);

        $weekCompleted = $this->createTask($projectId, [
            'name' => 'Completed this week',
            'complete_at' => Carbon::now()->subDay(),
        ]);
        $this->addOwner($projectId, $weekCompleted, $memberA);

        $lastWeekCompleted = $this->createTask($projectId, [
            'name' => 'Completed last week',
            'complete_at' => Carbon::parse('2026-07-10 10:00:00'),
        ]);
        $this->addOwner($projectId, $lastWeekCompleted, $memberA);

        $context = [
            'viewer_userid' => $memberA,
            'selected_department_ids' => [1],
            'department_ids' => [1],
            'member_userids' => [$memberA, $memberB],
            'member_map' => [
                $memberA => ['userid' => $memberA, 'nickname' => 'Member A', 'userimg' => ''],
                $memberB => ['userid' => $memberB, 'nickname' => 'Member B', 'userimg' => ''],
            ],
            'project_ids' => [$projectId],
            'own_project_id_map' => [$projectId => true],
        ];

        $stats = DashboardTeam::stats($context);
        $this->assertSame(2, $stats['blocks']['uncompleted']);
        $this->assertSame(1, $stats['blocks']['overdue']);
        $this->assertSame(1, $stats['blocks']['due_soon']);
        $this->assertSame(1, $stats['blocks']['no_owner']);
        $this->assertSame(1, $stats['blocks']['week_completed']);
        $this->assertSame(1, $stats['blocks']['last_week_completed']);
        $this->assertSame(2, array_sum(array_column($stats['priority'], 'num')));
        $memberStats = collect($stats['members'])->firstWhere('userid', $memberA);
        $this->assertSame(1, $memberStats['total']);
        $this->assertSame(1, $memberStats['overdue']);
        $this->assertSame(['progress' => 0, 'start' => 1, 'test' => 0], $memberStats['segments']);
        $this->assertSame($memberStats['total'], array_sum($memberStats['segments']));

        $uncompleted = DashboardTeam::tasks($context, ['type' => 'uncompleted', 'member_id' => 0, 'level' => null]);
        $overdueList = DashboardTeam::tasks($context, ['type' => 'overdue', 'member_id' => 0, 'level' => null]);
        $soonList = DashboardTeam::tasks($context, ['type' => 'soon', 'member_id' => 0, 'level' => null]);
        $noOwnerList = DashboardTeam::tasks($context, ['type' => 'noowner', 'member_id' => 0, 'level' => null]);
        $memberList = DashboardTeam::tasks($context, ['type' => '', 'member_id' => $memberA, 'level' => null]);

        $this->assertSame(2, $uncompleted->total());
        $this->assertSame(1, $overdueList->total());
        $this->assertSame(1, $soonList->total());
        $this->assertSame(1, $noOwnerList->total());
        $this->assertSame(1, $memberList->total());
        $this->assertSame($noOwnerSoon, $noOwnerList->items()[0]['id']);

        $newTask = $this->createTask($projectId, ['name' => 'Refresh cache task']);
        $this->addOwner($projectId, $newTask, $memberA);
        $this->assertSame(2, DashboardTeam::stats($context)['blocks']['uncompleted']);
        $this->assertSame(3, DashboardTeam::stats($context, true)['blocks']['uncompleted']);

        $nextTask = $this->createTask($projectId, ['name' => 'Next refresh cache task']);
        $this->addOwner($projectId, $nextTask, $memberA);
        $this->assertSame(4, DashboardTeam::stats($context, true)['blocks']['uncompleted']);
    }

    private function createUser(string $name): int
    {
        return DB::table('users')->insertGetId([
            'email' => $name . '_' . uniqid() . '@test.local',
            'nickname' => $name,
            'password' => md5('123456'),
            'department' => '',
            'bot' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'userid');
    }

    private function createTask(int $projectId, array $data): int
    {
        return DB::table('project_tasks')->insertGetId(array_merge([
            'project_id' => $projectId,
            'parent_id' => 0,
            'name' => 'Dashboard task',
            'visibility' => 1,
            'p_level' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $data));
    }

    private function addOwner(int $projectId, int $taskId, int $userid): void
    {
        DB::table('project_task_users')->insert([
            'project_id' => $projectId,
            'task_id' => $taskId,
            'task_pid' => $taskId,
            'userid' => $userid,
            'owner' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
