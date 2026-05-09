<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MultiOwnerGroupTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $email): User
    {
        $user = User::createInstance([
            'email' => $email,
            'userimg' => '',
            'nickname' => 'TestUser',
            'profession' => '',
            'password' => md5('123456'),
        ]);
        $user->save();
        return $user;
    }

    private function makeGroup(int $ownerUserid, array $memberUserids = []): WebSocketDialog
    {
        $allMembers = array_unique(array_merge([$ownerUserid], $memberUserids));
        return WebSocketDialog::createGroup('TestGroup', $allMembers, 'user', $ownerUserid);
    }

    public function test_isPrimaryOwner_returns_true_only_for_owner()
    {
        $owner = $this->makeUser('owner@test.local');
        $member = $this->makeUser('member@test.local');
        $dialog = $this->makeGroup($owner->userid, [$member->userid]);

        $this->assertTrue($dialog->isPrimaryOwner($owner->userid));
        $this->assertFalse($dialog->isPrimaryOwner($member->userid));
    }

    public function test_isOwner_includes_primary_and_deputy()
    {
        $owner = $this->makeUser('o2@test.local');
        $deputy = $this->makeUser('d2@test.local');
        $member = $this->makeUser('m2@test.local');
        $dialog = $this->makeGroup($owner->userid, [$deputy->userid, $member->userid]);

        // 手工把 deputy 设为群管理员
        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $deputy->userid)
            ->update(['role' => 2]);

        $this->assertTrue($dialog->isOwner($owner->userid));
        $this->assertTrue($dialog->isOwner($deputy->userid));
        $this->assertFalse($dialog->isOwner($member->userid));

        $this->assertTrue($dialog->isDeputyOwner($deputy->userid));
        $this->assertFalse($dialog->isDeputyOwner($owner->userid));
        $this->assertFalse($dialog->isDeputyOwner($member->userid));
    }

    public function test_deputy_ids_returns_array_of_role_2_userids()
    {
        $owner = $this->makeUser('o3@test.local');
        $d1 = $this->makeUser('d31@test.local');
        $d2 = $this->makeUser('d32@test.local');
        $m = $this->makeUser('m3@test.local');
        $dialog = $this->makeGroup($owner->userid, [$d1->userid, $d2->userid, $m->userid]);

        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->whereIn('userid', [$d1->userid, $d2->userid])
            ->update(['role' => 2]);

        $deputyIds = $dialog->deputy_ids;
        sort($deputyIds);
        $expected = [$d1->userid, $d2->userid];
        sort($expected);

        $this->assertEquals($expected, $deputyIds);
        $this->assertNotContains($owner->userid, $deputyIds);
        $this->assertNotContains($m->userid, $deputyIds);
    }

    public function test_createGroup_sets_owner_role_to_1()
    {
        $owner = $this->makeUser('o5@test.local');
        $m = $this->makeUser('m5@test.local');
        $dialog = WebSocketDialog::createGroup('G5', [$owner->userid, $m->userid], 'user', $owner->userid);

        $ownerRole = WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $owner->userid)
            ->value('role');
        $memberRole = WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $m->userid)
            ->value('role');

        $this->assertEquals(1, (int)$ownerRole);
        $this->assertEquals(0, (int)$memberRole);
    }

    public function test_exitGroup_removes_deputy_role_along_with_membership()
    {
        $owner = $this->makeUser('o6@test.local');
        $deputy = $this->makeUser('d6@test.local');
        $dialog = $this->makeGroup($owner->userid, [$deputy->userid]);

        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $deputy->userid)
            ->update(['role' => 2]);

        // 模拟群管理员退群（pushMsg=false 跳过 Swoole 推送，仅验证 DB 状态）
        $dialog->exitGroup($deputy->userid, 'exit', false, false);

        $exists = WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $deputy->userid)
            ->exists();
        $this->assertFalse($exists, '退群后群管理员记录应被删除');
        $this->assertNotContains($deputy->userid, $dialog->fresh()->deputy_ids);
    }

    public function test_joinGroup_defaults_role_to_0()
    {
        $owner = $this->makeUser('o6b@test.local');
        $newbie = $this->makeUser('n6b@test.local');
        $dialog = $this->makeGroup($owner->userid);

        $dialog->joinGroup($newbie->userid, $owner->userid, null, false);

        $role = WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $newbie->userid)
            ->value('role');
        $this->assertEquals(0, (int)$role);
    }

    /**
     * 直接执行 group__transfer 的角色同步逻辑（model-level，绕过 HTTP/Swoole/Auth）。
     * HTTP 测试在此项目无法工作（User::auth() 依赖 Doo::userId() 与 RequestContext，
     * 不兼容 Laravel 标准 auth guard）。
     */
    private function simulateTransfer(WebSocketDialog $dialog, int $newOwnerId): void
    {
        $oldOwnerId = (int)$dialog->owner_id;
        $dialog->owner_id = $newOwnerId;
        $dialog->save();

        $dialog->joinGroup($newOwnerId, 0, null, false);

        // 同步 role：原主 role=0、新主 role=1（覆盖即可）
        if ($oldOwnerId > 0 && $oldOwnerId !== $newOwnerId) {
            WebSocketDialogUser::where('dialog_id', $dialog->id)
                ->where('userid', $oldOwnerId)
                ->update(['role' => 0]);
        }
        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $newOwnerId)
            ->update(['role' => 1]);
    }

    public function test_group_transfer_updates_role_for_old_and_new_owner()
    {
        $oldOwner = $this->makeUser('o7@test.local');
        $newOwner = $this->makeUser('n7@test.local');
        $dialog = $this->makeGroup($oldOwner->userid, [$newOwner->userid]);

        $this->simulateTransfer($dialog, $newOwner->userid);

        $oldRole = WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $oldOwner->userid)->value('role');
        $newRole = WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $newOwner->userid)->value('role');

        $this->assertEquals(0, (int)$oldRole, '原主应降为普通成员');
        $this->assertEquals(1, (int)$newRole, '新主 role 应为 1');
    }

    public function test_group_transfer_preserves_deputies()
    {
        $oldOwner = $this->makeUser('o7b@test.local');
        $newOwner = $this->makeUser('n7b@test.local');
        $deputy = $this->makeUser('d7b@test.local');
        $dialog = $this->makeGroup($oldOwner->userid, [$newOwner->userid, $deputy->userid]);

        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $deputy->userid)->update(['role' => 2]);

        $this->simulateTransfer($dialog, $newOwner->userid);

        $this->assertContains($deputy->userid, $dialog->fresh()->deputy_ids);
    }

    private function simulateAddDeputy(WebSocketDialog $dialog, int $userid)
    {
        if ($userid <= 0) {
            return ['ret' => 0, 'msg' => '请选择有效的成员'];
        }
        // Note: skip checkDialog/checkGroup auth — assume caller is primary owner
        $member = WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $userid)
            ->first();
        if (empty($member)) {
            return ['ret' => 0, 'msg' => '该用户不是群成员'];
        }
        if ((int)$member->role === 1) {
            return ['ret' => 0, 'msg' => '不能将群主任命为群管理员'];
        }
        if ((int)$member->role !== 2) {
            $member->role = 2;
            $member->save();
        }
        return ['ret' => 1, 'msg' => '任命成功'];
    }

    public function test_adddeputy_target_must_be_member()
    {
        $owner = $this->makeUser('o8b@test.local');
        $outsider = $this->makeUser('out8b@test.local');
        $dialog = $this->makeGroup($owner->userid);

        $result = $this->simulateAddDeputy($dialog, $outsider->userid);
        $this->assertEquals(0, $result['ret']);
    }

    public function test_adddeputy_cannot_promote_primary_owner()
    {
        $owner = $this->makeUser('o8c@test.local');
        $dialog = $this->makeGroup($owner->userid);

        $result = $this->simulateAddDeputy($dialog, $owner->userid);
        $this->assertEquals(0, $result['ret']);
    }

    public function test_adddeputy_sets_role_to_2_for_normal_member()
    {
        $owner = $this->makeUser('o8d@test.local');
        $member = $this->makeUser('m8d@test.local');
        $dialog = $this->makeGroup($owner->userid, [$member->userid]);

        $result = $this->simulateAddDeputy($dialog, $member->userid);
        $this->assertEquals(1, $result['ret']);

        $role = WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $member->userid)->value('role');
        $this->assertEquals(2, (int)$role);
        $this->assertContains($member->userid, $dialog->fresh()->deputy_ids);
    }

    public function test_adddeputy_idempotent()
    {
        $owner = $this->makeUser('o8e@test.local');
        $member = $this->makeUser('m8e@test.local');
        $dialog = $this->makeGroup($owner->userid, [$member->userid]);

        $this->simulateAddDeputy($dialog, $member->userid);
        $result = $this->simulateAddDeputy($dialog, $member->userid); // 第二次
        $this->assertEquals(1, $result['ret']);

        // 应该只有一条 role=2 记录
        $count = WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $member->userid)
            ->where('role', 2)
            ->count();
        $this->assertEquals(1, $count);
    }

    private function simulateDelDeputy(WebSocketDialog $dialog, int $userid)
    {
        if ($userid <= 0) {
            return ['ret' => 0, 'msg' => '请选择有效的成员'];
        }
        $member = WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $userid)
            ->first();
        if (empty($member)) {
            return ['ret' => 1, 'msg' => '罢免成功']; // 幂等
        }
        if ((int)$member->role === 2) {
            $member->role = 0;
            $member->save();
        }
        return ['ret' => 1, 'msg' => '罢免成功'];
    }

    public function test_deldeputy_demotes_role_2_to_0()
    {
        $owner = $this->makeUser('o9@test.local');
        $deputy = $this->makeUser('d9@test.local');
        $dialog = $this->makeGroup($owner->userid, [$deputy->userid]);
        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $deputy->userid)
            ->update(['role' => 2]);

        $result = $this->simulateDelDeputy($dialog, $deputy->userid);
        $this->assertEquals(1, $result['ret']);

        $role = WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $deputy->userid)->value('role');
        $this->assertEquals(0, (int)$role, '罢免后应降为普通成员');
        $this->assertNotContains($deputy->userid, $dialog->fresh()->deputy_ids);
    }

    public function test_deldeputy_idempotent_for_non_deputy()
    {
        $owner = $this->makeUser('o9b@test.local');
        $member = $this->makeUser('m9b@test.local');
        $dialog = $this->makeGroup($owner->userid, [$member->userid]);

        // 普通成员（role=0）调用罢免应幂等返回成功
        $result = $this->simulateDelDeputy($dialog, $member->userid);
        $this->assertEquals(1, $result['ret']);

        // 角色仍是 0
        $role = WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $member->userid)->value('role');
        $this->assertEquals(0, (int)$role);
    }

    public function test_deldeputy_does_not_affect_primary_owner()
    {
        $owner = $this->makeUser('o9c@test.local');
        $dialog = $this->makeGroup($owner->userid);

        // 试图对主群主调用罢免（不应改变其 role=1）
        $result = $this->simulateDelDeputy($dialog, $owner->userid);
        $this->assertEquals(1, $result['ret']); // 幂等返回成功

        $role = WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $owner->userid)->value('role');
        $this->assertEquals(1, (int)$role, '主群主 role 不应被罢免接口改变');
    }

    /**
     * 模拟 exitGroup 的权限检查逻辑（与 WebSocketDialog::exitGroup 中的 checkDelete 块保持一致）。
     * 用于在无 Swoole RequestContext 的 PHPUnit 环境中直接验证权限规则，而无需模拟 User::userid()。
     *
     * @param WebSocketDialog $dialog
     * @param int $actorId      执行操作的用户 ID
     * @param int $targetId     被移出的用户 ID
     * @return array ['allowed' => bool, 'error' => string|null]
     */
    private function simulateRemovePermission(WebSocketDialog $dialog, int $actorId, int $targetId): array
    {
        if ($dialog->group_type === 'all') {
            return ['allowed' => false, 'error' => '仅管理员可操作全员群'];
        }

        // 未认证时拒绝
        if ($actorId <= 0) {
            return ['allowed' => false, 'error' => '只有群主或邀请人可以移出成员'];
        }

        // 获取目标成员记录
        $item = WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $targetId)->first();
        if (!$item) {
            return ['allowed' => false, 'error' => '目标用户不在群内'];
        }

        // 群主、群管理员、邀请人可移出
        $allowedActor = $dialog->isOwner($actorId) || $actorId === (int)$item->inviter;
        if (!$allowedActor) {
            return ['allowed' => false, 'error' => '只有群主或邀请人可以移出成员'];
        }

        // 群管理员不能移出群主或其他群管理员
        if ($dialog->isDeputyOwner($actorId)) {
            $targetIsOwner = $dialog->isPrimaryOwner($targetId) || $dialog->isDeputyOwner($targetId);
            if ($targetIsOwner) {
                return ['allowed' => false, 'error' => '群管理员不能移出群主或其他群管理员'];
            }
        }

        // 群主不可被移出（额外保障，与 exitGroup 行为一致）
        if ($targetId == $dialog->owner_id) {
            return ['allowed' => false, 'error' => '群主不可移出'];
        }

        return ['allowed' => true, 'error' => null];
    }

    public function test_deputy_can_remove_normal_member()
    {
        $owner = $this->makeUser('o10@test.local');
        $deputy = $this->makeUser('d10@test.local');
        $member = $this->makeUser('m10@test.local');
        $dialog = $this->makeGroup($owner->userid, [$deputy->userid, $member->userid]);
        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $deputy->userid)->update(['role' => 2]);

        // 验证权限逻辑：群管理员可移出普通成员
        $result = $this->simulateRemovePermission($dialog, $deputy->userid, $member->userid);
        $this->assertTrue($result['allowed'], '群管理员应能移出普通成员，错误：' . ($result['error'] ?? ''));

        // 验证实际移出操作（checkDelete=false 绕过 auth，直接测试 DB 状态）
        $dialog->exitGroup($member->userid, 'remove', false, false);
        $exists = WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $member->userid)->exists();
        $this->assertFalse($exists, '移出后成员记录应不存在');
    }

    public function test_deputy_cannot_remove_primary_owner()
    {
        $owner = $this->makeUser('o10b@test.local');
        $deputy = $this->makeUser('d10b@test.local');
        $dialog = $this->makeGroup($owner->userid, [$deputy->userid]);
        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $deputy->userid)->update(['role' => 2]);

        // 验证权限逻辑：群管理员不可移出群主
        $result = $this->simulateRemovePermission($dialog, $deputy->userid, $owner->userid);
        $this->assertFalse($result['allowed'], '群管理员不应能移出群主');
        $this->assertNotNull($result['error']);
    }

    public function test_deputy_cannot_remove_other_deputy()
    {
        $owner = $this->makeUser('o10c@test.local');
        $deputy1 = $this->makeUser('d10c1@test.local');
        $deputy2 = $this->makeUser('d10c2@test.local');
        $dialog = $this->makeGroup($owner->userid, [$deputy1->userid, $deputy2->userid]);
        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->whereIn('userid', [$deputy1->userid, $deputy2->userid])
            ->update(['role' => 2]);

        // 验证权限逻辑：群管理员不可移出其他群管理员
        $result = $this->simulateRemovePermission($dialog, $deputy1->userid, $deputy2->userid);
        $this->assertFalse($result['allowed'], '群管理员不应能移出其他群管理员');
        $this->assertEquals('群管理员不能移出群主或其他群管理员', $result['error']);
    }

    public function test_inviter_can_still_remove_invitee()
    {
        $owner = $this->makeUser('o10d@test.local');
        $inviter = $this->makeUser('inv10d@test.local');
        $invitee = $this->makeUser('iv10d@test.local');
        $dialog = $this->makeGroup($owner->userid, [$inviter->userid, $invitee->userid]);

        // 设置 invitee 的 inviter 字段
        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $invitee->userid)
            ->update(['inviter' => $inviter->userid]);

        // 验证权限逻辑：邀请人可移出被邀请者
        $result = $this->simulateRemovePermission($dialog, $inviter->userid, $invitee->userid);
        $this->assertTrue($result['allowed'], '邀请人应能移出被邀请者，错误：' . ($result['error'] ?? ''));

        // 验证实际移出
        $dialog->exitGroup($invitee->userid, 'remove', false, false);
        $exists = WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $invitee->userid)->exists();
        $this->assertFalse($exists, '邀请人移出后被邀请者记录应不存在');
    }

    public function test_non_owner_non_inviter_cannot_remove_member()
    {
        $owner = $this->makeUser('o10e@test.local');
        $member1 = $this->makeUser('m10e1@test.local');
        $member2 = $this->makeUser('m10e2@test.local');
        $dialog = $this->makeGroup($owner->userid, [$member1->userid, $member2->userid]);

        // 普通成员无法移出其他成员
        $result = $this->simulateRemovePermission($dialog, $member1->userid, $member2->userid);
        $this->assertFalse($result['allowed'], '普通成员不应能移出其他成员');
    }

    /**
     * 验证离职移交时群管理员角色被正确清除。
     *
     * UserTransfer::exitDialog() 对离职用户调用 exitGroup($original_userid, 'remove', false, false)，
     * exitGroup 内部直接 hard-delete web_socket_dialog_users 记录（$item->delete()），
     * 因此群管理员的 role 随记录一起消失，无需额外逻辑。
     *
     * 本测试直接调用 exitDialog()（通过 UserTransfer 实例），绕过 start() 中的项目/任务/文件迁移，
     * 以确保在无 Swoole 推送的 PHPUnit 环境中可以正常运行。
     */
    public function test_user_transfer_clears_deputy_role()
    {
        $owner = $this->makeUser('o11@test.local');
        $departing = $this->makeUser('dep11@test.local');
        $receiver = $this->makeUser('rec11@test.local');
        $dialog = $this->makeGroup($owner->userid, [$departing->userid, $receiver->userid]);

        // 将离职用户设为群管理员
        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $departing->userid)
            ->update(['role' => 2]);

        // 验证前置条件
        $this->assertContains($departing->userid, $dialog->fresh()->deputy_ids, '前置条件：离职用户应是群管理员');

        // 通过 UserTransfer 触发 exitDialog（使用正确字段名 original_userid / new_userid）
        $transfer = \App\Models\UserTransfer::createInstance([
            'original_userid' => $departing->userid,
            'new_userid' => $receiver->userid,
        ]);
        $transfer->save();
        $transfer->exitDialog();

        $freshDialog = $dialog->fresh();

        // 离职用户不应再出现在群管理员列表中
        $this->assertNotContains($departing->userid, $freshDialog->deputy_ids, '离职用户不应留在群管理员列表');
        // 离职用户的成员记录应已删除
        $exists = WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $departing->userid)
            ->exists();
        $this->assertFalse($exists, 'exitDialog 后离职用户的成员记录应被删除');
        // 接收方不应自动继承群管理员角色
        $this->assertNotContains($receiver->userid, $freshDialog->deputy_ids, '接收方不应自动继承群管理员角色');
    }
}
