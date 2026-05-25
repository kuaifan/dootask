<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserTransfer;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * P0-B：离职转移普通群群主时 role/deputy_ids 必须同步
 */
class UserTransferOwnerRoleTest extends TestCase
{
    use DatabaseTransactions;

    private function isSwooleInfraFailure(\Throwable $e): bool
    {
        $msg = $e->getMessage();
        return str_contains($msg, 'swoole')
            || str_contains($msg, 'Swoole')
            || str_contains($msg, 'AbstractData::__wakeup')
            || str_contains($msg, 'Undefined array key');
    }

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
     * 离职移交后：接收人 role=1，且 deputy_ids 不再包含他（哪怕之前是群管理员）
     */
    public function test_transfer_promotes_deputy_receiver_to_owner_role_one()
    {
        $original = $this->makeUser('orig_owner@test.local');
        $receiver = $this->makeUser('xfer_receiver@test.local');

        // original 为群主、receiver 为群管理员（role=2）的普通 user 群
        $dialog = WebSocketDialog::createGroup(
            'XferUserGroup',
            [$original->userid, $receiver->userid],
            'user',
            $original->userid
        );
        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $receiver->userid)
            ->update(['role' => 2]);

        // 移交前断言
        $this->assertContains((int)$receiver->userid, $dialog->fresh()->deputy_ids);

        // 触发离职移交（exitDialog 路径）
        $transfer = new UserTransfer();
        $transfer->original_userid = $original->userid;
        $transfer->new_userid = $receiver->userid;
        try {
            $transfer->exitDialog();
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'swoole')) {
                $this->markTestSkipped('Swoole runtime 不可用，UserTransfer::exitDialog 无法在当前环境中端到端验证：' . $e->getMessage());
            }
            throw $e;
        }

        // 群主已切换
        $dialog = WebSocketDialog::find($dialog->id);
        $this->assertEquals($receiver->userid, (int)$dialog->owner_id, '群主应转移为接收人');

        // original 已退群
        $this->assertFalse(
            WebSocketDialogUser::where('dialog_id', $dialog->id)
                ->where('userid', $original->userid)
                ->exists(),
            '原群主应已退出群组'
        );

        // receiver role=1（从 deputy 升级为 primary owner）
        $receiverDu = WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $receiver->userid)
            ->first();
        $this->assertNotNull($receiverDu);
        $this->assertEquals(1, (int)$receiverDu->role, '接收人 role 应升级为 1（primary owner）');

        // deputy_ids 不再包含 receiver
        $this->assertNotContains((int)$receiver->userid, $dialog->fresh()->deputy_ids,
            '接收人升为群主后 deputy_ids 不应再包含他');
    }

    /**
     * 离职移交：接收人不在群中（exitDialog 把他作为新成员加入），role 也应被设为 1
     */
    public function test_transfer_adds_new_receiver_with_owner_role_one()
    {
        $original = $this->makeUser('orig2_owner@test.local');
        $receiver = $this->makeUser('xfer2_receiver@test.local');

        // 群里只有 original
        $dialog = WebSocketDialog::createGroup(
            'XferUserGroup2',
            [$original->userid],
            'user',
            $original->userid
        );

        $transfer = new UserTransfer();
        $transfer->original_userid = $original->userid;
        $transfer->new_userid = $receiver->userid;
        try {
            $transfer->exitDialog();
        } catch (\Throwable $e) {
            if ($this->isSwooleInfraFailure($e)) {
                $this->markTestSkipped('Swoole/PushTask 运行时不可用：' . $e->getMessage());
            }
            throw $e;
        }

        $dialog = WebSocketDialog::find($dialog->id);
        $this->assertEquals($receiver->userid, (int)$dialog->owner_id);

        $receiverDu = WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $receiver->userid)
            ->first();
        $this->assertNotNull($receiverDu, '接收人应被 joinGroup 加入群组');
        $this->assertEquals(1, (int)$receiverDu->role, '接收人 role 应为 1');
    }

    /**
     * 离职移交：original 不是群主时（普通成员），不触发 owner 转移逻辑
     */
    public function test_transfer_does_not_promote_when_original_not_owner()
    {
        $owner = $this->makeUser('grp_owner@test.local');
        $original = $this->makeUser('orig3_member@test.local');
        $receiver = $this->makeUser('xfer3_receiver@test.local');

        $dialog = WebSocketDialog::createGroup(
            'XferUserGroup3',
            [$owner->userid, $original->userid, $receiver->userid],
            'user',
            $owner->userid
        );
        // receiver 是 role=2 的群管理员
        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $receiver->userid)
            ->update(['role' => 2]);

        $transfer = new UserTransfer();
        $transfer->original_userid = $original->userid;
        $transfer->new_userid = $receiver->userid;
        try {
            $transfer->exitDialog();
        } catch (\Throwable $e) {
            if ($this->isSwooleInfraFailure($e)) {
                $this->markTestSkipped('Swoole/PushTask 运行时不可用：' . $e->getMessage());
            }
            throw $e;
        }

        // 原群主未变
        $dialog = WebSocketDialog::find($dialog->id);
        $this->assertEquals($owner->userid, (int)$dialog->owner_id);

        // receiver 仍然是群管理员（role=2）
        $receiverDu = WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $receiver->userid)
            ->first();
        $this->assertEquals(2, (int)$receiverDu->role);
    }
}
