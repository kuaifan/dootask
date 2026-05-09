<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * 群成员移出权限保护测试
 */
class DialogRemovePermissionTest extends TestCase
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

    /**
     * 检查移出权限（模拟 exitGroup 中的权限检查逻辑）
     */
    private function checkRemovePermission(WebSocketDialog $dialog, int $actorUserid, int $targetUserid): void
    {
        $member = WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $targetUserid)
            ->first();

        if (!$member) {
            throw new \RuntimeException('target not member');
        }

        $actor = $actorUserid;
        if ($actor <= 0) {
            throw new \RuntimeException('只有群主或邀请人可以移出成员');
        }

        // 目标是群主或群管理员时的保护
        $targetIsPrimaryOwner = $dialog->isPrimaryOwner($targetUserid);
        $targetIsDeputyOwner = $dialog->isDeputyOwner($targetUserid);

        if ($targetIsPrimaryOwner || $targetIsDeputyOwner) {
            // 普通邀请人不能移出群主或群管理员
            $actorIsPrimaryOwner = $dialog->isPrimaryOwner($actor);
            $actorIsDeputyOwner = $dialog->isDeputyOwner($actor);

            if (!$actorIsPrimaryOwner && !$actorIsDeputyOwner) {
                throw new \RuntimeException('普通成员不能移出群主或群管理员');
            }

            // 群管理员不能移出群主或其他群管理员
            if ($actorIsDeputyOwner && !$actorIsPrimaryOwner) {
                throw new \RuntimeException('群管理员不能移出群主或其他群管理员');
            }
        }

        // 普通成员：群主、群管理员、邀请人可移出
        $allowedActor = $dialog->isOwner($actor) || $actor === (int)$member->inviter;
        if (!$allowedActor) {
            throw new \RuntimeException('只有群主、群管理员或邀请人可以移出成员');
        }
    }

    /**
     * 执行移出（权限检查通过后）
     */
    private function simulateRemove(WebSocketDialog $dialog, int $actorUserid, int $targetUserid): void
    {
        $this->checkRemovePermission($dialog, $actorUserid, $targetUserid);

        // 执行移出
        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $targetUserid)
            ->delete();
    }

    /**
     * 测试：普通邀请人不能移出群主
     */
    public function test_inviter_cannot_remove_primary_owner()
    {
        $owner = $this->makeUser('owner@test.local');
        $inviter = $this->makeUser('inviter@test.local');
        $dialog = WebSocketDialog::createGroup('TestGroup', [$owner->userid, $inviter->userid], 'user', $owner->userid);

        // inviter 邀请了 owner（模拟场景）
        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $owner->userid)
            ->update(['inviter' => $inviter->userid]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('普通成员不能移出群主或群管理员');

        $this->simulateRemove($dialog, $inviter->userid, $owner->userid);
    }

    /**
     * 测试：普通邀请人不能移出群管理员
     */
    public function test_inviter_cannot_remove_deputy_owner()
    {
        $owner = $this->makeUser('owner2@test.local');
        $deputy = $this->makeUser('deputy2@test.local');
        $inviter = $this->makeUser('inviter2@test.local');
        $dialog = WebSocketDialog::createGroup('TestGroup2', [$owner->userid, $deputy->userid, $inviter->userid], 'user', $owner->userid);

        // 设置群管理员
        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $deputy->userid)
            ->update(['role' => 2]);

        // inviter 邀请了 deputy（模拟场景）
        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $deputy->userid)
            ->update(['inviter' => $inviter->userid]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('普通成员不能移出群主或群管理员');

        $this->simulateRemove($dialog, $inviter->userid, $deputy->userid);
    }

    /**
     * 测试：群管理员不能移出群主
     */
    public function test_deputy_cannot_remove_primary_owner()
    {
        $owner = $this->makeUser('owner3@test.local');
        $deputy = $this->makeUser('deputy3@test.local');
        $dialog = WebSocketDialog::createGroup('TestGroup3', [$owner->userid, $deputy->userid], 'user', $owner->userid);

        // 设置群管理员
        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $deputy->userid)
            ->update(['role' => 2]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('群管理员不能移出群主或其他群管理员');

        $this->simulateRemove($dialog, $deputy->userid, $owner->userid);
    }

    /**
     * 测试：群管理员不能移出其他群管理员
     */
    public function test_deputy_cannot_remove_other_deputy()
    {
        $owner = $this->makeUser('owner4@test.local');
        $deputy1 = $this->makeUser('deputy4_1@test.local');
        $deputy2 = $this->makeUser('deputy4_2@test.local');
        $dialog = WebSocketDialog::createGroup('TestGroup4', [$owner->userid, $deputy1->userid, $deputy2->userid], 'user', $owner->userid);

        // 设置两个群管理员
        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->whereIn('userid', [$deputy1->userid, $deputy2->userid])
            ->update(['role' => 2]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('群管理员不能移出群主或其他群管理员');

        $this->simulateRemove($dialog, $deputy1->userid, $deputy2->userid);
    }

    /**
     * 测试：群管理员可以移出普通成员
     */
    public function test_deputy_can_remove_regular_member()
    {
        $owner = $this->makeUser('owner5@test.local');
        $deputy = $this->makeUser('deputy5@test.local');
        $member = $this->makeUser('member5@test.local');
        $dialog = WebSocketDialog::createGroup('TestGroup5', [$owner->userid, $deputy->userid, $member->userid], 'user', $owner->userid);

        // 设置群管理员
        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $deputy->userid)
            ->update(['role' => 2]);

        // 应该成功
        $this->simulateRemove($dialog, $deputy->userid, $member->userid);

        $this->assertFalse(WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $member->userid)->exists());
    }

    /**
     * 测试：群主可以移出群管理员
     */
    public function test_primary_owner_can_remove_deputy()
    {
        $owner = $this->makeUser('owner6@test.local');
        $deputy = $this->makeUser('deputy6@test.local');
        $dialog = WebSocketDialog::createGroup('TestGroup6', [$owner->userid, $deputy->userid], 'user', $owner->userid);

        // 设置群管理员
        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $deputy->userid)
            ->update(['role' => 2]);

        // 应该成功
        $this->simulateRemove($dialog, $owner->userid, $deputy->userid);

        $this->assertFalse(WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $deputy->userid)->exists());
    }

    /**
     * 测试：普通邀请人可以移出自己邀请的普通成员
     */
    public function test_inviter_can_remove_invited_regular_member()
    {
        $owner = $this->makeUser('owner7@test.local');
        $inviter = $this->makeUser('inviter7@test.local');
        $invited = $this->makeUser('invited7@test.local');
        $dialog = WebSocketDialog::createGroup('TestGroup7', [$owner->userid, $inviter->userid, $invited->userid], 'user', $owner->userid);

        // inviter 邀请了 invited
        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $invited->userid)
            ->update(['inviter' => $inviter->userid]);

        // 应该成功
        $this->simulateRemove($dialog, $inviter->userid, $invited->userid);

        $this->assertFalse(WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $invited->userid)->exists());
    }

    /**
     * 测试：非邀请人的普通成员不能移出其他普通成员
     */
    public function test_regular_member_cannot_remove_other_member()
    {
        $owner = $this->makeUser('owner8@test.local');
        $member1 = $this->makeUser('member8_1@test.local');
        $member2 = $this->makeUser('member8_2@test.local');
        $dialog = WebSocketDialog::createGroup('TestGroup8', [$owner->userid, $member1->userid, $member2->userid], 'user', $owner->userid);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('只有群主、群管理员或邀请人可以移出成员');

        $this->simulateRemove($dialog, $member1->userid, $member2->userid);
    }

    /**
     * P2：DB::table/stdClass 渠道的会话数据也应稳定包含 deputy_ids
     */
    public function test_synthesize_data_from_db_row_includes_deputy_ids()
    {
        $owner = $this->makeUser('owner9@test.local');
        $deputy = $this->makeUser('deputy9@test.local');
        $member = $this->makeUser('member9@test.local');
        $dialog = WebSocketDialog::createGroup('TestGroup9', [$owner->userid, $deputy->userid, $member->userid], 'user', $owner->userid);

        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $deputy->userid)
            ->update(['role' => 2]);

        // 模拟 getDialogList/searchDialog/getDialogBeyond 的 DB::table stdClass 数据源
        $row = \DB::table('web_socket_dialog_users as u')
            ->select(['d.*', 'u.top_at', 'u.last_at', 'u.mark_unread', 'u.silence', 'u.hide', 'u.color', 'u.updated_at as user_at'])
            ->join('web_socket_dialogs as d', 'u.dialog_id', '=', 'd.id')
            ->where('u.dialog_id', $dialog->id)
            ->where('u.userid', $owner->userid)
            ->first();

        $data = WebSocketDialog::synthesizeData($row, $owner->userid);

        $this->assertArrayHasKey('deputy_ids', $data);
        $this->assertContains((int)$deputy->userid, $data['deputy_ids']);
        $this->assertNotContains((int)$owner->userid, $data['deputy_ids']);
        $this->assertNotContains((int)$member->userid, $data['deputy_ids']);
    }

}
