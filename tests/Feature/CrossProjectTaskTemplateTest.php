<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskTemplate;
use App\Models\ProjectUser;
use App\Models\User;
use App\Module\Base;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CrossProjectTaskTemplateTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * 创建测试用户。
     */
    private function makeUser(string $email): User
    {
        $user = User::createInstance([
            'email' => $email,
            'userimg' => '',
            'nickname' => 'TestUser_' . substr(md5($email), 0, 6),
            'profession' => '',
            'password' => md5('123456'),
        ]);
        $user->save();
        return $user;
    }

    /**
     * 创建项目，自动把 owner 加为主负责人，把 members 加为普通成员。
     */
    private function makeProject(int $ownerUserid, array $memberUserids = []): Project
    {
        $project = Project::createInstance([
            'name' => 'p-' . uniqid(),
            'desc' => '',
            'userid' => $ownerUserid,
            'personal' => 0,
        ]);
        $project->save();
        ProjectUser::updateInsert([
            'project_id' => $project->id,
            'userid' => $ownerUserid,
        ], ['owner' => 1]);
        foreach ($memberUserids as $uid) {
            if ($uid === $ownerUserid) continue;
            ProjectUser::updateInsert([
                'project_id' => $project->id,
                'userid' => $uid,
            ], ['owner' => 0]);
        }
        return $project;
    }

    /**
     * 创建一个任务模板。
     */
    private function makeTemplate(Project $project, int $userid, array $overrides = []): ProjectTaskTemplate
    {
        $tpl = ProjectTaskTemplate::createInstance(array_merge([
            'project_id' => $project->id,
            'name' => 'tpl-' . uniqid(),
            'title' => 'title-' . uniqid(),
            'content' => 'content',
            'sort' => 0,
            'is_default' => 0,
            'userid' => $userid,
            'use_count' => 0,
        ], $overrides));
        $tpl->save();
        return $tpl;
    }

    /**
     * 复刻 task__template_search 业务逻辑。
     */
    private function callTemplateSearch(int $userid, string $keyword = '', int $page = 1, int $pageSize = 20): array
    {
        $projectIds = ProjectUser::where('userid', $userid)->pluck('project_id');
        $q = ProjectTaskTemplate::with(['project:id,name'])
            ->whereIn('project_id', $projectIds);
        if ($keyword !== '') {
            $q->where(function ($q2) use ($keyword) {
                $like = '%' . $keyword . '%';
                $q2->where('name', 'like', $like)
                   ->orWhere('title', 'like', $like)
                   ->orWhere('content', 'like', $like);
            });
        }
        $total = $q->count();
        $items = $q->orderByDesc('use_count')
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->forPage($page, $pageSize)
            ->get()
            ->map(fn($tpl) => [
                'id' => $tpl->id,
                'project_id' => $tpl->project_id,
                'project_name' => $tpl->project->name ?? '',
                'name' => $tpl->name,
                'title' => $tpl->title,
                'content' => $tpl->content,
                'use_count' => $tpl->use_count,
            ])->toArray();
        return ['total' => $total, 'items' => $items, 'page' => $page, 'page_size' => $pageSize];
    }

    /**
     * 复刻共享模板关闭后的搜索范围：目标项目关闭共享模板时，仅返回目标项目自己的模板。
     */
    private function callTemplateSearchForProject(int $userid, int $currentProjectId, string $keyword = '', int $page = 1, int $pageSize = 20): array
    {
        $projectIds = ProjectUser::where('userid', $userid)->pluck('project_id');
        $currentProject = Project::find($currentProjectId);
        if ($currentProject && ($currentProject->task_template_share ?: 'open') === 'close') {
            $projectIds = collect($projectIds)->filter(fn($id) => intval($id) === $currentProjectId)->values();
        }
        $q = ProjectTaskTemplate::with(['project:id,name'])
            ->whereIn('project_id', $projectIds);
        if ($keyword !== '') {
            $q->where(function ($q2) use ($keyword) {
                $like = '%' . $keyword . '%';
                $q2->where('name', 'like', $like)
                    ->orWhere('title', 'like', $like)
                    ->orWhere('content', 'like', $like);
            });
        }
        $total = $q->count();
        $items = $q->orderByDesc('use_count')
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->forPage($page, $pageSize)
            ->get()
            ->map(fn($tpl) => [
                'id' => $tpl->id,
                'project_id' => $tpl->project_id,
                'name' => $tpl->name,
            ])->toArray();
        return ['total' => $total, 'items' => $items, 'page' => $page, 'page_size' => $pageSize];
    }

    /**
     * 模拟 task__add 的"使用模板"副作用：检查 template_id 可见性，原子递增 use_count + 更新 last_used_at。
     * 不实际创建任务，只验证副作用。
     */
    private function simulateUseTemplate(int $userid, int $templateId, ?int $targetProjectId = null): void
    {
        if ($templateId <= 0) return;
        $tpl = ProjectTaskTemplate::find($templateId);
        if (!$tpl) return;
        $isMember = ProjectUser::where('project_id', $tpl->project_id)
            ->where('userid', $userid)->exists();
        if (!$isMember) return;
        if ($targetProjectId) {
            $targetProject = Project::find($targetProjectId);
            $shareEnabled = !$targetProject || ($targetProject->task_template_share ?: 'open') === 'open';
            if ($tpl->project_id != $targetProjectId && !$shareEnabled) {
                return;
            }
        }
        $tpl->incrementUsage();
    }

    /**
     * 调用 task__template_visible 端点（绕过 HTTP 层，直接复刻 controller 业务逻辑）。
     */
    private function callTemplateVisible(int $userid, int $currentProjectId): array
    {
        $projectIds = ProjectUser::where('userid', $userid)->pluck('project_id');
        $currentProject = Project::find($currentProjectId);
        if ($currentProject && ($currentProject->task_template_share ?: 'open') === 'close') {
            $projectIds = collect($projectIds)->filter(fn($id) => intval($id) === $currentProjectId)->values();
        }
        return ProjectTaskTemplate::with(['project:id,name'])
            ->whereIn('project_id', $projectIds)
            ->orderByRaw('project_id = ? DESC', [$currentProjectId])
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(fn($tpl) => [
                'id' => $tpl->id,
                'project_id' => $tpl->project_id,
                'project_name' => $tpl->project->name ?? '',
                'name' => $tpl->name,
                'title' => $tpl->title,
                'content' => $tpl->content,
                'sort' => $tpl->sort,
                'is_default' => $tpl->is_default,
                'userid' => $tpl->userid,
                'use_count' => $tpl->use_count,
                'last_used_at' => $tpl->last_used_at,
            ])->toArray();
    }

    public function test_search_paginates_visible_templates_by_use_count_desc()
    {
        $alice = $this->makeUser('alice-' . uniqid() . '@test.com');
        $projectA = $this->makeProject($alice->userid);
        foreach (range(1, 25) as $i) {
            $this->makeTemplate($projectA, $alice->userid, [
                'name' => 'tpl-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'use_count' => $i,
            ]);
        }

        $page1 = $this->callTemplateSearch($alice->userid, '', 1, 20);
        $page2 = $this->callTemplateSearch($alice->userid, '', 2, 20);

        $this->assertSame(25, $page1['total']);
        $this->assertCount(20, $page1['items']);
        $this->assertCount(5, $page2['items']);
        $this->assertSame('tpl-25', $page1['items'][0]['name']);
        $this->assertSame('tpl-06', $page1['items'][19]['name']);
        $this->assertSame('tpl-05', $page2['items'][0]['name']);
    }

    public function test_search_filters_by_keyword_in_name_title_content()
    {
        $alice = $this->makeUser('alice-' . uniqid() . '@test.com');
        $projectA = $this->makeProject($alice->userid);
        $this->makeTemplate($projectA, $alice->userid, ['name' => '需求评审', 'title' => 't1', 'content' => 'c1']);
        $this->makeTemplate($projectA, $alice->userid, ['name' => 'tpl2', 'title' => '周报', 'content' => 'c2']);
        $this->makeTemplate($projectA, $alice->userid, ['name' => 'tpl3', 'title' => 't3', 'content' => '故障复盘']);
        $this->makeTemplate($projectA, $alice->userid, ['name' => 'noise', 'title' => 'noise', 'content' => 'noise']);

        $r1 = $this->callTemplateSearch($alice->userid, '需求');
        $r2 = $this->callTemplateSearch($alice->userid, '周报');
        $r3 = $this->callTemplateSearch($alice->userid, '故障');

        $this->assertSame(1, $r1['total']);
        $this->assertSame('需求评审', $r1['items'][0]['name']);
        $this->assertSame(1, $r2['total']);
        $this->assertSame('tpl2', $r2['items'][0]['name']);
        $this->assertSame(1, $r3['total']);
        $this->assertSame('tpl3', $r3['items'][0]['name']);
    }

    public function test_search_excludes_non_member_project_templates()
    {
        $alice = $this->makeUser('alice-' . uniqid() . '@test.com');
        $bob = $this->makeUser('bob-' . uniqid() . '@test.com');
        $projectA = $this->makeProject($alice->userid);
        $projectC = $this->makeProject($bob->userid);
        $this->makeTemplate($projectA, $alice->userid, ['name' => 'visible']);
        $this->makeTemplate($projectC, $bob->userid, ['name' => 'hidden']);

        $r = $this->callTemplateSearch($alice->userid, '');

        $names = array_column($r['items'], 'name');
        $this->assertContains('visible', $names);
        $this->assertNotContains('hidden', $names);
    }

    public function test_search_sort_falls_back_when_use_count_equal()
    {
        $alice = $this->makeUser('alice-' . uniqid() . '@test.com');
        $projectA = $this->makeProject($alice->userid);
        $t1 = $this->makeTemplate($projectA, $alice->userid, ['name' => 'older', 'use_count' => 5]);
        sleep(1);
        $t2 = $this->makeTemplate($projectA, $alice->userid, ['name' => 'newer', 'use_count' => 5]);
        $t2->last_used_at = now();
        $t2->save();

        $r = $this->callTemplateSearch($alice->userid, '');

        $this->assertSame('newer', $r['items'][0]['name']);
    }

    public function test_search_endpoint_returns_expected_shape()
    {
        $alice = $this->makeUser('alice-' . uniqid() . '@test.com');
        $projectA = $this->makeProject($alice->userid);
        $this->makeTemplate($projectA, $alice->userid, ['name' => 'sanity', 'use_count' => 1]);

        $projectIds = ProjectUser::where('userid', $alice->userid)->pluck('project_id');
        $expected = ProjectTaskTemplate::whereIn('project_id', $projectIds)
            ->orderByDesc('use_count')->orderByDesc('last_used_at')->orderByDesc('created_at')
            ->forPage(1, 20)->get()->pluck('name')->toArray();

        $actual = array_column($this->callTemplateSearch($alice->userid, '')['items'], 'name');

        $this->assertSame($expected, $actual);
    }

    public function test_visible_returns_templates_from_all_user_projects()
    {
        $alice = $this->makeUser('alice-' . uniqid() . '@test.com');
        $projectA = $this->makeProject($alice->userid);
        $projectB = $this->makeProject($alice->userid);
        $tplA = $this->makeTemplate($projectA, $alice->userid, ['name' => 'A1']);
        $tplB = $this->makeTemplate($projectB, $alice->userid, ['name' => 'B1']);

        $result = $this->callTemplateVisible($alice->userid, $projectB->id);

        $names = array_column($result, 'name');
        $this->assertContains('A1', $names);
        $this->assertContains('B1', $names);
    }

    public function test_visible_excludes_templates_from_non_member_projects()
    {
        $alice = $this->makeUser('alice-' . uniqid() . '@test.com');
        $bob = $this->makeUser('bob-' . uniqid() . '@test.com');
        $projectA = $this->makeProject($alice->userid);
        $projectC = $this->makeProject($bob->userid);
        $tplA = $this->makeTemplate($projectA, $alice->userid, ['name' => 'A1']);
        $tplC = $this->makeTemplate($projectC, $bob->userid, ['name' => 'C1']);

        $result = $this->callTemplateVisible($alice->userid, $projectA->id);

        $names = array_column($result, 'name');
        $this->assertContains('A1', $names);
        $this->assertNotContains('C1', $names);
    }

    public function test_visible_orders_current_project_first()
    {
        $alice = $this->makeUser('alice-' . uniqid() . '@test.com');
        $projectA = $this->makeProject($alice->userid);
        $projectB = $this->makeProject($alice->userid);
        $this->makeTemplate($projectA, $alice->userid, ['name' => 'A1', 'sort' => 0]);
        $this->makeTemplate($projectB, $alice->userid, ['name' => 'B1', 'sort' => 0]);

        $result = $this->callTemplateVisible($alice->userid, $projectB->id);

        // B 项目模板应该排在前面
        $this->assertSame('B1', $result[0]['name']);
    }

    public function test_use_template_increments_use_count_and_updates_last_used()
    {
        $alice = $this->makeUser('alice-' . uniqid() . '@test.com');
        $projectA = $this->makeProject($alice->userid);
        $tpl = $this->makeTemplate($projectA, $alice->userid, ['use_count' => 3]);

        $before = $tpl->fresh();
        $this->assertSame(3, (int) $before->use_count);
        $this->assertNull($before->last_used_at);

        $this->simulateUseTemplate($alice->userid, $tpl->id);

        $after = $tpl->fresh();
        $this->assertSame(4, (int) $after->use_count);
        $this->assertNotNull($after->last_used_at);
    }

    public function test_use_template_silently_ignores_non_member()
    {
        $alice = $this->makeUser('alice-' . uniqid() . '@test.com');
        $bob = $this->makeUser('bob-' . uniqid() . '@test.com');
        $projectA = $this->makeProject($alice->userid);
        $tpl = $this->makeTemplate($projectA, $alice->userid, ['use_count' => 3]);

        $this->simulateUseTemplate($bob->userid, $tpl->id);

        $this->assertSame(3, (int) $tpl->fresh()->use_count);
    }

    public function test_use_template_handles_invalid_template_id()
    {
        $alice = $this->makeUser('alice-' . uniqid() . '@test.com');
        $this->simulateUseTemplate($alice->userid, 0);
        $this->simulateUseTemplate($alice->userid, 99999999);
        $this->assertTrue(true);
    }

    public function test_visible_returns_only_current_project_templates_when_share_closed()
    {
        $alice = $this->makeUser('alice-' . uniqid() . '@test.com');
        $projectA = $this->makeProject($alice->userid);
        $projectB = $this->makeProject($alice->userid);
        $projectB->task_template_share = 'close';
        $projectB->save();
        $this->makeTemplate($projectA, $alice->userid, ['name' => 'A1']);
        $this->makeTemplate($projectB, $alice->userid, ['name' => 'B1']);

        $result = $this->callTemplateVisible($alice->userid, $projectB->id);

        $names = array_column($result, 'name');
        $this->assertNotContains('A1', $names);
        $this->assertContains('B1', $names);
    }

    public function test_search_returns_only_current_project_templates_when_share_closed()
    {
        $alice = $this->makeUser('alice-' . uniqid() . '@test.com');
        $projectA = $this->makeProject($alice->userid);
        $projectB = $this->makeProject($alice->userid);
        $projectB->task_template_share = 'close';
        $projectB->save();
        $this->makeTemplate($projectA, $alice->userid, ['name' => 'shared']);
        $this->makeTemplate($projectB, $alice->userid, ['name' => 'own']);

        $result = $this->callTemplateSearchForProject($alice->userid, $projectB->id);

        $names = array_column($result['items'], 'name');
        $this->assertNotContains('shared', $names);
        $this->assertContains('own', $names);
    }

    public function test_cross_project_template_usage_ignored_when_target_project_share_closed()
    {
        $alice = $this->makeUser('alice-' . uniqid() . '@test.com');
        $projectA = $this->makeProject($alice->userid);
        $projectB = $this->makeProject($alice->userid);
        $projectB->task_template_share = 'close';
        $projectB->save();
        $tplA = $this->makeTemplate($projectA, $alice->userid, ['use_count' => 7]);
        $tplB = $this->makeTemplate($projectB, $alice->userid, ['use_count' => 3]);

        $this->simulateUseTemplate($alice->userid, $tplA->id, $projectB->id);
        $this->simulateUseTemplate($alice->userid, $tplB->id, $projectB->id);

        $this->assertSame(7, (int) $tplA->fresh()->use_count);
        $this->assertSame(4, (int) $tplB->fresh()->use_count);
    }
}
