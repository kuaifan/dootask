<?php

namespace Tests\Feature;

use App\Models\AbstractModel;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use App\Models\WebSocketDialog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * 项目成员管理接口保护测试
 */
class ProjectMemberProtectionTest extends TestCase
{
    use DatabaseTransactions;

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

    private function makeProject(int $ownerUserid, array $memberUserids = []): Project
    {
        $allMembers = array_unique(array_merge([$ownerUserid], $memberUserids));
        $project = Project::createInstance([
            'name' => 'Test_' . substr(md5(uniqid('', true)), 0, 6),
            'desc' => '',
            'userid' => $ownerUserid,
            'personal' => 0,
        ]);
        $project->save();
        ProjectUser::updateInsert([
            'project_id' => $project->id,
            'userid' => $ownerUserid,
        ], ['owner' => 1]);
        foreach ($allMembers as $uid) {
            if ($uid === $ownerUserid) continue;
            ProjectUser::updateInsert([
                'project_id' => $project->id,
                'userid' => $uid,
            ], ['owner' => 0]);
        }
        $dialog = WebSocketDialog::createGroup('Test_dialog', $allMembers, 'project', $ownerUserid);
        $project->dialog_id = $dialog->id;
        $project->save();
        $project->syncDialogUser();
        return $project->fresh();
    }

    /**
     * 模拟 ProjectController::user() 成员同步接口
     */
    private function simulateMemberSync(Project $project, int $callerUserid, array $userids, ?array $deputyUserids): array
    {
        $callerRow = ProjectUser::where('project_id', $project->id)
            ->where('userid', $callerUserid)->first();
        if (!$callerRow || !in_array((int)$callerRow->owner, [ProjectUser::OWNER_PRIMARY, ProjectUser::OWNER_DEPUTY], true)) {
            throw new \RuntimeException('not owner or deputy');
        }
        $isPrimary = (int)$callerRow->owner === ProjectUser::OWNER_PRIMARY;
        $applyDeputy = $isPrimary && $deputyUserids !== null;

        if (count($userids) > 100) {
            throw new \RuntimeException('over 100 members');
        }

        // 业务闭环：项目必须且只能有一个主负责人，最终成员列表必须包含该负责人
        $primaryOwnerIds = ProjectUser::whereProjectId($project->id)
            ->whereOwner(ProjectUser::OWNER_PRIMARY)
            ->pluck('userid')
            ->map(fn($v) => (int)$v)
            ->toArray();
        if (count($primaryOwnerIds) !== 1) {
            throw new \RuntimeException('项目负责人数据异常，请先修复项目负责人');
        }
        $primaryOwnerId = $primaryOwnerIds[0];
        if (!in_array($primaryOwnerId, $userids, true)) {
            throw new \RuntimeException('项目成员列表必须包含项目负责人');
        }
        // 项目管理员可以管理普通成员，但不能借成员列表移除其他项目管理员
        if (!$isPrimary) {
            $currentDeputyIds = ProjectUser::whereProjectId($project->id)
                ->whereOwner(ProjectUser::OWNER_DEPUTY)
                ->pluck('userid')
                ->map(fn($v) => (int)$v)
                ->toArray();
            if (!empty(array_diff($currentDeputyIds, $userids))) {
                throw new \RuntimeException('项目管理员不能移除项目负责人或项目管理员');
            }
        }

        if ($applyDeputy) {
            $deputyUserids = array_values(array_unique(array_map('intval', $deputyUserids)));
            if (!empty(array_diff($deputyUserids, $userids))) {
                throw new \RuntimeException('deputy must be member');
            }
            if (in_array($project->owner_userid, $deputyUserids, true)) {
                throw new \RuntimeException('primary cannot be deputy');
            }
        }

        return AbstractModel::transaction(function () use ($project, $userids, $applyDeputy, $deputyUserids) {
            $array = [];
            foreach ($userids as $uid) {
                if ($project->joinProject($uid)) {
                    $array[] = $uid;
                }
            }
            $deleteRows = ProjectUser::whereProjectId($project->id)->whereNotIn('userid', $array)->get();
            $deleteUserids = $deleteRows->pluck('userid')->toArray();
            foreach ($deleteRows as $row) {
                $row->exitProject();
            }

            if ($applyDeputy) {
                $current = ProjectUser::whereProjectId($project->id)
                    ->where('owner', ProjectUser::OWNER_DEPUTY)
                    ->pluck('userid')->toArray();
                $toPromote = array_values(array_diff($deputyUserids, $current));
                $toDemote = array_values(array_diff($current, $deputyUserids));
                if (!empty($toPromote)) {
                    ProjectUser::whereProjectId($project->id)
                        ->whereIn('userid', $toPromote)
                        ->where('owner', ProjectUser::OWNER_MEMBER)
                        ->change(['owner' => ProjectUser::OWNER_DEPUTY]);
                }
                if (!empty($toDemote)) {
                    ProjectUser::whereProjectId($project->id)
                        ->whereIn('userid', $toDemote)
                        ->where('owner', ProjectUser::OWNER_DEPUTY)
                        ->change(['owner' => ProjectUser::OWNER_MEMBER]);
                }
            }

            $project->fresh()->syncDialogUser();
            return $deleteUserids;
        });
    }

    /**
     * 测试：项目负责人不能从成员列表中移除自己
     */
    public function test_primary_owner_cannot_remove_self()
    {
        $owner = $this->makeUser('owner@test.local');
        $member = $this->makeUser('member@test.local');
        $project = $this->makeProject($owner->userid, [$member->userid]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('项目成员列表必须包含项目负责人');

        // 负责人尝试移除自己
        $this->simulateMemberSync($project, $owner->userid, [$member->userid], null);
    }

    /**
     * 测试：项目管理员不能通过成员管理接口移除项目负责人
     */
    public function test_deputy_cannot_remove_primary_owner()
    {
        $owner = $this->makeUser('owner2@test.local');
        $deputy = $this->makeUser('deputy2@test.local');
        $member = $this->makeUser('member2@test.local');
        $project = $this->makeProject($owner->userid, [$deputy->userid, $member->userid]);

        // 任命项目管理员
        ProjectUser::where('project_id', $project->id)
            ->where('userid', $deputy->userid)
            ->update(['owner' => ProjectUser::OWNER_DEPUTY]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('项目成员列表必须包含项目负责人');

        // 项目管理员尝试移除负责人
        $this->simulateMemberSync($project, $deputy->userid, [$deputy->userid, $member->userid], null);
    }

    /**
     * 测试：项目负责人可以正常管理普通成员
     */
    public function test_primary_owner_can_manage_regular_members()
    {
        $owner = $this->makeUser('owner3@test.local');
        $member1 = $this->makeUser('member3_1@test.local');
        $member2 = $this->makeUser('member3_2@test.local');
        $project = $this->makeProject($owner->userid, [$member1->userid, $member2->userid]);

        // 移除 member2
        $deleted = $this->simulateMemberSync($project, $owner->userid, [$owner->userid, $member1->userid], []);

        $this->assertContains($member2->userid, $deleted);
        $this->assertFalse(ProjectUser::where('project_id', $project->id)
            ->where('userid', $member2->userid)->exists());
    }

    /**
     * 测试：项目管理员可以管理普通成员，但不能移除负责人
     */
    public function test_deputy_can_manage_regular_members_but_not_owner()
    {
        $owner = $this->makeUser('owner4@test.local');
        $deputy = $this->makeUser('deputy4@test.local');
        $member = $this->makeUser('member4@test.local');
        $project = $this->makeProject($owner->userid, [$deputy->userid, $member->userid]);

        ProjectUser::where('project_id', $project->id)
            ->where('userid', $deputy->userid)
            ->update(['owner' => ProjectUser::OWNER_DEPUTY]);

        // 项目管理员移除普通成员（应该成功）
        $deleted = $this->simulateMemberSync($project, $deputy->userid, [$owner->userid, $deputy->userid], null);

        $this->assertContains($member->userid, $deleted);
    }

    /**
     * 测试：项目管理员不能移除其他项目管理员（不能借成员管理绕过罢免权限）
     */
    public function test_deputy_cannot_remove_other_deputy()
    {
        $owner = $this->makeUser('owner4b@test.local');
        $deputy1 = $this->makeUser('deputy4b_1@test.local');
        $deputy2 = $this->makeUser('deputy4b_2@test.local');
        $member = $this->makeUser('member4b@test.local');
        $project = $this->makeProject($owner->userid, [$deputy1->userid, $deputy2->userid, $member->userid]);

        ProjectUser::where('project_id', $project->id)
            ->whereIn('userid', [$deputy1->userid, $deputy2->userid])
            ->update(['owner' => ProjectUser::OWNER_DEPUTY]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('项目管理员不能移除项目负责人或项目管理员');

        // deputy1 尝试移除 deputy2
        $this->simulateMemberSync($project, $deputy1->userid, [$owner->userid, $deputy1->userid, $member->userid], null);
    }

    /**
     * 测试：空成员列表被拒绝（因为必须包含负责人）
     */
    public function test_empty_member_list_rejected()
    {
        $owner = $this->makeUser('owner5@test.local');
        $project = $this->makeProject($owner->userid);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('项目成员列表必须包含项目负责人');

        $this->simulateMemberSync($project, $owner->userid, [], []);
    }
}
