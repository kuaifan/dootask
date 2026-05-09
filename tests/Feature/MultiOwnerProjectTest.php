<?php

namespace Tests\Feature;

use App\Models\AbstractModel;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MultiOwnerProjectTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * 创建用户。密码用 md5（pre_users.password 是 VARCHAR(50)，bcrypt 会被截断）。
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
     * 创建测试项目，附带 N 个成员；自动创建关联项目群。
     */
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
     * 模拟 ProjectController::transfer (after Task 5 changes).
     * 关键：必须 syncDialogUser 同步 role，不要手写 update。
     */
    private function simulateTransfer(Project $project, int $newOwnerUserid): void
    {
        AbstractModel::transaction(function () use ($project, $newOwnerUserid) {
            ProjectUser::whereProjectId($project->id)
                ->whereOwner(1)
                ->change(['owner' => 0]);
            ProjectUser::updateInsert([
                'project_id' => $project->id,
                'userid' => $newOwnerUserid,
            ], ['owner' => 1]);
            if ($project->dialog_id > 0) {
                $dialog = WebSocketDialog::find($project->dialog_id);
                if ($dialog) {
                    $dialog->owner_id = $newOwnerUserid;
                    $dialog->save();
                }
            }
            $project->fresh()->syncDialogUser();
        });
    }

    private function simulateAddDeputy(Project $project, int $userid): void
    {
        $member = ProjectUser::where('project_id', $project->id)
            ->where('userid', $userid)->first();
        if (!$member) throw new \RuntimeException('not member');
        if ((int)$member->owner === 1) throw new \RuntimeException('cannot deputy primary');
        if ((int)$member->owner !== 2) {
            AbstractModel::transaction(function () use ($project, $member) {
                $member->owner = 2;
                $member->save();
                $project->fresh()->syncDialogUser();
            });
        }
    }

    private function simulateDelDeputy(Project $project, int $userid): void
    {
        $member = ProjectUser::where('project_id', $project->id)
            ->where('userid', $userid)->first();
        if (!$member) return;
        if ((int)$member->owner === 2) {
            AbstractModel::transaction(function () use ($project, $member) {
                $member->owner = 0;
                $member->save();
                $project->fresh()->syncDialogUser();
            });
        }
    }

    /**
     * 模拟合并后的 ProjectController::user() 端点：同步成员 + 项目管理员。
     *
     * @param Project $project          项目实例
     * @param int     $callerUserid     调用方 userid（用于权限判断）
     * @param int[]   $userids          最终成员完整列表（必须包含项目负责人）
     * @param int[]|null $deputyUserids 最终项目管理员完整列表；null 表示不设置（沿用既有项目管理员）
     * @return int[]                    被移除的成员 userids
     */
    private function simulateMemberSync(Project $project, int $callerUserid, array $userids, ?array $deputyUserids): array
    {
        // 鉴权：调用方必须是项目负责人或项目管理员
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

    public function test_setup_works()
    {
        $owner = $this->makeUser('p1_owner@test.local');
        $member = $this->makeUser('p1_m@test.local');
        $project = $this->makeProject($owner->userid, [$member->userid]);

        $this->assertEquals($owner->userid, $project->owner_userid);
        $this->assertNotEmpty($project->dialog_id);
    }

    public function test_projectuser_constants_and_helpers()
    {
        $owner = $this->makeUser('p2_o@test.local');
        $deputy = $this->makeUser('p2_d@test.local');
        $member = $this->makeUser('p2_m@test.local');
        $project = $this->makeProject($owner->userid, [$deputy->userid, $member->userid]);

        ProjectUser::where('project_id', $project->id)
            ->where('userid', $deputy->userid)->update(['owner' => 2]);

        $ownerRow = ProjectUser::where('project_id', $project->id)->where('userid', $owner->userid)->first();
        $deputyRow = ProjectUser::where('project_id', $project->id)->where('userid', $deputy->userid)->first();
        $memberRow = ProjectUser::where('project_id', $project->id)->where('userid', $member->userid)->first();

        $this->assertTrue($ownerRow->isPrimaryOwner());
        $this->assertFalse($ownerRow->isDeputyOwner());
        $this->assertTrue($ownerRow->isOwner());

        $this->assertFalse($deputyRow->isPrimaryOwner());
        $this->assertTrue($deputyRow->isDeputyOwner());
        $this->assertTrue($deputyRow->isOwner());

        $this->assertFalse($memberRow->isPrimaryOwner());
        $this->assertFalse($memberRow->isDeputyOwner());
        $this->assertFalse($memberRow->isOwner());
    }

    public function test_project_helpers_and_deputy_userids()
    {
        $owner = $this->makeUser('p3_o@test.local');
        $d1 = $this->makeUser('p3_d1@test.local');
        $d2 = $this->makeUser('p3_d2@test.local');
        $member = $this->makeUser('p3_m@test.local');
        $project = $this->makeProject($owner->userid, [$d1->userid, $d2->userid, $member->userid]);

        ProjectUser::where('project_id', $project->id)
            ->whereIn('userid', [$d1->userid, $d2->userid])
            ->update(['owner' => 2]);

        $project = $project->fresh();

        $this->assertTrue($project->isPrimaryOwner($owner->userid));
        $this->assertFalse($project->isPrimaryOwner($d1->userid));
        $this->assertFalse($project->isPrimaryOwner($member->userid));

        $this->assertFalse($project->isDeputyOwner($owner->userid));
        $this->assertTrue($project->isDeputyOwner($d1->userid));
        $this->assertTrue($project->isDeputyOwner($d2->userid));
        $this->assertFalse($project->isDeputyOwner($member->userid));

        $this->assertTrue($project->isOwner($owner->userid));
        $this->assertTrue($project->isOwner($d1->userid));
        $this->assertFalse($project->isOwner($member->userid));

        // deputy_userids 排序无关，比较成 set
        $deputyIds = $project->deputy_userids;
        sort($deputyIds);
        $expected = [$d1->userid, $d2->userid];
        sort($expected);
        $this->assertEquals($expected, $deputyIds);

        // 序列化后 API 响应里应包含 deputy_userids
        $arr = $project->toArray();
        $this->assertArrayHasKey('deputy_userids', $arr);
    }

    public function test_syncDialogUser_syncs_role_from_owner()
    {
        $owner = $this->makeUser('p4_o@test.local');
        $deputy = $this->makeUser('p4_d@test.local');
        $member = $this->makeUser('p4_m@test.local');
        $project = $this->makeProject($owner->userid, [$deputy->userid, $member->userid]);

        ProjectUser::where('project_id', $project->id)
            ->where('userid', $deputy->userid)->update(['owner' => 2]);

        $project->fresh()->syncDialogUser();

        $dialog = WebSocketDialog::find($project->dialog_id);
        $this->assertEquals(1, (int)WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $owner->userid)->value('role'));
        $this->assertEquals(2, (int)WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $deputy->userid)->value('role'));
        $this->assertEquals(0, (int)WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $member->userid)->value('role'));
    }

    public function test_syncDialogUser_clears_role_for_demoted_user()
    {
        $owner = $this->makeUser('p4b_o@test.local');
        $deputy = $this->makeUser('p4b_d@test.local');
        $project = $this->makeProject($owner->userid, [$deputy->userid]);

        ProjectUser::where('project_id', $project->id)
            ->where('userid', $deputy->userid)->update(['owner' => 2]);
        $project->fresh()->syncDialogUser();

        ProjectUser::where('project_id', $project->id)
            ->where('userid', $deputy->userid)->update(['owner' => 0]);
        $project->fresh()->syncDialogUser();

        $this->assertEquals(0, (int)WebSocketDialogUser::where('dialog_id', $project->dialog_id)
            ->where('userid', $deputy->userid)->value('role'));
    }

    public function test_transfer_updates_dialog_owner_and_role()
    {
        $oldOwner = $this->makeUser('p5_old@test.local');
        $newOwner = $this->makeUser('p5_new@test.local');
        $project = $this->makeProject($oldOwner->userid, [$newOwner->userid]);

        $this->simulateTransfer($project, $newOwner->userid);

        $project = $project->fresh();
        $this->assertEquals($newOwner->userid, $project->owner_userid);
        $dialog = WebSocketDialog::find($project->dialog_id);
        $this->assertEquals($newOwner->userid, (int)$dialog->owner_id);
        $this->assertEquals(1, (int)WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $newOwner->userid)->value('role'));
        $this->assertEquals(0, (int)WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $oldOwner->userid)->value('role'));
    }

    public function test_transfer_preserves_deputies()
    {
        $oldOwner = $this->makeUser('p5b_old@test.local');
        $newOwner = $this->makeUser('p5b_new@test.local');
        $deputy = $this->makeUser('p5b_d@test.local');
        $project = $this->makeProject($oldOwner->userid, [$newOwner->userid, $deputy->userid]);
        ProjectUser::where('project_id', $project->id)
            ->where('userid', $deputy->userid)->update(['owner' => 2]);
        $project->fresh()->syncDialogUser(); // sync deputy role=2 first

        $this->simulateTransfer($project, $newOwner->userid);

        $project = $project->fresh();
        $this->assertContains($deputy->userid, $project->deputy_userids);
        $this->assertEquals(2, (int)WebSocketDialogUser::where('dialog_id', $project->dialog_id)
            ->where('userid', $deputy->userid)->value('role'));
    }

    public function test_transfer_demotes_deputy_when_promoted_to_primary()
    {
        $oldOwner = $this->makeUser('p5c_old@test.local');
        $deputy = $this->makeUser('p5c_d@test.local');
        $project = $this->makeProject($oldOwner->userid, [$deputy->userid]);
        ProjectUser::where('project_id', $project->id)
            ->where('userid', $deputy->userid)->update(['owner' => 2]);
        $project->fresh()->syncDialogUser();

        $this->simulateTransfer($project, $deputy->userid);

        $project = $project->fresh();
        $this->assertEquals($deputy->userid, $project->owner_userid);
        $this->assertNotContains($deputy->userid, $project->deputy_userids);
        $row = ProjectUser::where('project_id', $project->id)->where('userid', $deputy->userid)->first();
        $this->assertEquals(1, (int)$row->owner);
    }

    public function test_adddeputy_marks_owner_2_and_syncs_dialog_role()
    {
        $owner = $this->makeUser('p6_o@test.local');
        $member = $this->makeUser('p6_m@test.local');
        $project = $this->makeProject($owner->userid, [$member->userid]);

        $this->simulateAddDeputy($project, $member->userid);

        $row = ProjectUser::where('project_id', $project->id)->where('userid', $member->userid)->first();
        $this->assertEquals(2, (int)$row->owner);
        $this->assertEquals(2, (int)WebSocketDialogUser::where('dialog_id', $project->dialog_id)
            ->where('userid', $member->userid)->value('role'));
        $this->assertContains($member->userid, $project->fresh()->deputy_userids);
    }

    public function test_adddeputy_idempotent()
    {
        $owner = $this->makeUser('p6b_o@test.local');
        $member = $this->makeUser('p6b_m@test.local');
        $project = $this->makeProject($owner->userid, [$member->userid]);

        $this->simulateAddDeputy($project, $member->userid);
        $this->simulateAddDeputy($project, $member->userid); // 第二次不应报错

        $count = ProjectUser::where('project_id', $project->id)
            ->where('userid', $member->userid)->count();
        $this->assertEquals(1, $count);
    }

    public function test_adddeputy_rejects_primary_owner()
    {
        $owner = $this->makeUser('p6c_o@test.local');
        $project = $this->makeProject($owner->userid);

        $this->expectException(\RuntimeException::class);
        $this->simulateAddDeputy($project, $owner->userid);
    }

    public function test_adddeputy_rejects_non_member()
    {
        $owner = $this->makeUser('p6d_o@test.local');
        $outsider = $this->makeUser('p6d_x@test.local');
        $project = $this->makeProject($owner->userid);

        $this->expectException(\RuntimeException::class);
        $this->simulateAddDeputy($project, $outsider->userid);
    }

    public function test_deldeputy_demotes_to_member_and_clears_dialog_role()
    {
        $owner = $this->makeUser('p7_o@test.local');
        $deputy = $this->makeUser('p7_d@test.local');
        $project = $this->makeProject($owner->userid, [$deputy->userid]);
        $this->simulateAddDeputy($project, $deputy->userid);

        $this->simulateDelDeputy($project, $deputy->userid);

        $row = ProjectUser::where('project_id', $project->id)->where('userid', $deputy->userid)->first();
        $this->assertEquals(0, (int)$row->owner);
        $this->assertEquals(0, (int)WebSocketDialogUser::where('dialog_id', $project->dialog_id)
            ->where('userid', $deputy->userid)->value('role'));
    }

    public function test_deldeputy_idempotent_for_non_deputy()
    {
        $owner = $this->makeUser('p7b_o@test.local');
        $member = $this->makeUser('p7b_m@test.local');
        $project = $this->makeProject($owner->userid, [$member->userid]);

        $this->simulateDelDeputy($project, $member->userid);
        $this->assertTrue(true); // 没抛 = 通过
    }

    public function test_owner_userids_query_includes_primary_and_deputy()
    {
        $owner = $this->makeUser('p8_o@test.local');
        $deputy = $this->makeUser('p8_d@test.local');
        $member = $this->makeUser('p8_m@test.local');
        $project = $this->makeProject($owner->userid, [$deputy->userid, $member->userid]);
        ProjectUser::where('project_id', $project->id)
            ->where('userid', $deputy->userid)->update(['owner' => 2]);

        $ownerUserids = ProjectUser::whereProjectId($project->id)
            ->whereIn('owner', [1, 2])
            ->pluck('userid')->map(fn($v) => (int)$v)->toArray();
        sort($ownerUserids);
        $expected = [$owner->userid, $deputy->userid];
        sort($expected);
        $this->assertEquals($expected, $ownerUserids);
    }

    public function test_userProject_primary_mode_distinguishes_primary_from_deputy()
    {
        $owner = $this->makeUser('p8c_o@test.local');
        $deputy = $this->makeUser('p8c_d@test.local');
        $project = $this->makeProject($owner->userid, [$deputy->userid]);
        ProjectUser::where('project_id', $project->id)
            ->where('userid', $deputy->userid)->update(['owner' => 2]);

        $row = ProjectUser::where('project_id', $project->id)
            ->where('userid', $deputy->userid)->first();
        $this->assertEquals(2, (int)$row->owner);
        $this->assertTrue((int)$row->owner !== 1, 'deputy must not be primary');
        $this->assertTrue((bool)$row->owner, 'deputy is truthy (passes mustOwner=true)');
    }

    public function test_user_transfer_clears_departing_deputy_role()
    {
        $owner = $this->makeUser('p9_o@test.local');
        $departing = $this->makeUser('p9_dep@test.local');
        $receiver = $this->makeUser('p9_rec@test.local');
        $project = $this->makeProject($owner->userid, [$departing->userid, $receiver->userid]);
        ProjectUser::where('project_id', $project->id)
            ->where('userid', $departing->userid)->update(['owner' => 2]);

        \App\Models\ProjectUser::transfer($departing->userid, $receiver->userid);

        // 离职的项目管理员已不在 project_users
        $this->assertFalse(
            ProjectUser::where('project_id', $project->id)
                ->where('userid', $departing->userid)->exists()
        );
        // receiver 没有继承项目管理员身份
        $row = ProjectUser::where('project_id', $project->id)->where('userid', $receiver->userid)->first();
        $this->assertNotEquals(2, (int)$row->owner);
        $this->assertNotContains($receiver->userid, $project->fresh()->deputy_userids);
    }

    public function test_user_transfer_inherits_departing_primary_role()
    {
        $departing = $this->makeUser('p9b_dep@test.local');
        $receiver = $this->makeUser('p9b_rec@test.local');
        $project = $this->makeProject($departing->userid, [$receiver->userid]);

        \App\Models\ProjectUser::transfer($departing->userid, $receiver->userid);

        $this->assertEquals($receiver->userid, $project->fresh()->owner_userid);
    }

    public function test_user_transfer_promotes_existing_member_when_inherits_primary()
    {
        // 离职=主, receiver=普通成员 → receiver 应升为主
        $departing = $this->makeUser('p9c_dep@test.local');
        $receiver = $this->makeUser('p9c_rec@test.local');
        $project = $this->makeProject($departing->userid, [$receiver->userid]);

        \App\Models\ProjectUser::transfer($departing->userid, $receiver->userid);

        $project = $project->fresh();
        $this->assertEquals($receiver->userid, $project->owner_userid);
        $row = ProjectUser::where('project_id', $project->id)->where('userid', $receiver->userid)->first();
        $this->assertEquals(1, (int)$row->owner);
    }

    public function test_user_transfer_keeps_receiver_primary_when_departing_is_member()
    {
        // receiver=主, departing=普通成员 → receiver 保持主
        $receiver = $this->makeUser('p9d_rec@test.local');
        $departing = $this->makeUser('p9d_dep@test.local');
        $project = $this->makeProject($receiver->userid, [$departing->userid]);

        \App\Models\ProjectUser::transfer($departing->userid, $receiver->userid);

        $row = ProjectUser::where('project_id', $project->id)->where('userid', $receiver->userid)->first();
        $this->assertEquals(1, (int)$row->owner);
    }

    public function test_member_sync_promotes_existing_member_to_deputy()
    {
        $owner = $this->makeUser('m1_o@test.local');
        $member = $this->makeUser('m1_m@test.local');
        $project = $this->makeProject($owner->userid, [$member->userid]);

        $this->simulateMemberSync(
            $project,
            $owner->userid,
            [$owner->userid, $member->userid],
            [$member->userid]
        );

        $row = ProjectUser::where('project_id', $project->id)->where('userid', $member->userid)->first();
        $this->assertEquals(ProjectUser::OWNER_DEPUTY, (int)$row->owner);
        $this->assertContains($member->userid, $project->fresh()->deputy_userids);
        $this->assertEquals(2, (int)WebSocketDialogUser::where('dialog_id', $project->dialog_id)
            ->where('userid', $member->userid)->value('role'));
    }

    public function test_member_sync_demotes_deputy_kept_as_member()
    {
        $owner = $this->makeUser('m2_o@test.local');
        $deputy = $this->makeUser('m2_d@test.local');
        $project = $this->makeProject($owner->userid, [$deputy->userid]);
        $this->simulateAddDeputy($project, $deputy->userid);

        $this->simulateMemberSync(
            $project,
            $owner->userid,
            [$owner->userid, $deputy->userid],
            []
        );

        $row = ProjectUser::where('project_id', $project->id)->where('userid', $deputy->userid)->first();
        $this->assertEquals(ProjectUser::OWNER_MEMBER, (int)$row->owner);
        $this->assertNotContains($deputy->userid, $project->fresh()->deputy_userids);
    }

    public function test_member_sync_removes_deputy_from_project_in_one_call()
    {
        $owner = $this->makeUser('m3_o@test.local');
        $deputy = $this->makeUser('m3_d@test.local');
        $project = $this->makeProject($owner->userid, [$deputy->userid]);
        $this->simulateAddDeputy($project, $deputy->userid);

        $deleted = $this->simulateMemberSync(
            $project,
            $owner->userid,
            [$owner->userid],
            []
        );

        $this->assertContains($deputy->userid, $deleted);
        $row = ProjectUser::where('project_id', $project->id)->where('userid', $deputy->userid)->first();
        $this->assertNull($row);
    }

    public function test_member_sync_rejects_deputy_not_in_member_list()
    {
        $owner = $this->makeUser('m4_o@test.local');
        $outsider = $this->makeUser('m4_x@test.local');
        $project = $this->makeProject($owner->userid);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('deputy must be member');

        $this->simulateMemberSync(
            $project,
            $owner->userid,
            [$owner->userid],
            [$outsider->userid]
        );
    }

    public function test_member_sync_rejects_primary_in_deputy_list()
    {
        $owner = $this->makeUser('m5_o@test.local');
        $project = $this->makeProject($owner->userid);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('primary cannot be deputy');

        $this->simulateMemberSync(
            $project,
            $owner->userid,
            [$owner->userid],
            [$owner->userid]
        );
    }

    public function test_member_sync_ignores_deputy_field_when_caller_is_deputy()
    {
        $owner = $this->makeUser('m6_o@test.local');
        $deputy = $this->makeUser('m6_d@test.local');
        $member = $this->makeUser('m6_m@test.local');
        $project = $this->makeProject($owner->userid, [$deputy->userid, $member->userid]);
        $this->simulateAddDeputy($project, $deputy->userid);

        $this->simulateMemberSync(
            $project,
            $deputy->userid,
            [$owner->userid, $deputy->userid, $member->userid],
            [$member->userid]
        );

        $memberRow = ProjectUser::where('project_id', $project->id)->where('userid', $member->userid)->first();
        $this->assertEquals(ProjectUser::OWNER_MEMBER, (int)$memberRow->owner);
        $deputyRow = ProjectUser::where('project_id', $project->id)->where('userid', $deputy->userid)->first();
        $this->assertEquals(ProjectUser::OWNER_DEPUTY, (int)$deputyRow->owner);
    }
}
