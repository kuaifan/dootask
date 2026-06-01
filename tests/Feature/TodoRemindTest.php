<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsgTodo;
use App\Tasks\TodoRemindTask;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * 待办提醒时间测试
 */
class TodoRemindTest extends TestCase
{
    use DatabaseTransactions;

    private function makeTodo(array $attr = []): WebSocketDialogMsgTodo
    {
        $todo = WebSocketDialogMsgTodo::createInstance(array_merge([
            'dialog_id' => 1001,
            'msg_id' => 2001,
            'userid' => 3001,
        ], $attr));
        $todo->save();
        return $todo;
    }

    public function test_remind_columns_persist()
    {
        $todo = $this->makeTodo(['remind_at' => '2026-06-02 09:00:00']);
        $fresh = WebSocketDialogMsgTodo::whereId($todo->id)->first();
        $this->assertEquals('2026-06-02 09:00:00', $fresh->remind_at->format('Y-m-d H:i:s'));
        $this->assertNull($fresh->reminded_at);
    }

    public function test_due_reminders_selects_only_due_unreminded_undone()
    {
        $past = Carbon::now()->subMinutes(5)->format('Y-m-d H:i:s');
        $future = Carbon::now()->addHours(2)->format('Y-m-d H:i:s');

        $due      = $this->makeTodo(['userid' => 1, 'remind_at' => $past]);
        $future1  = $this->makeTodo(['userid' => 2, 'remind_at' => $future]);
        $already  = $this->makeTodo(['userid' => 3, 'remind_at' => $past, 'reminded_at' => Carbon::now()]);
        $done     = $this->makeTodo(['userid' => 4, 'remind_at' => $past, 'done_at' => Carbon::now()]);
        $noRemind = $this->makeTodo(['userid' => 5]);

        $ids = WebSocketDialogMsgTodo::dueReminders()->pluck('id')->toArray();

        $this->assertContains($due->id, $ids);
        $this->assertNotContains($future1->id, $ids);
        $this->assertNotContains($already->id, $ids);
        $this->assertNotContains($done->id, $ids);
        $this->assertNotContains($noRemind->id, $ids);
    }

    public function test_set_todo_remind_sets_and_resets_and_clears()
    {
        // 同一消息两人，预置已提醒状态以验证会被重置
        $a = $this->makeTodo(['msg_id' => 5001, 'userid' => 11, 'reminded_at' => Carbon::now()]);
        $b = $this->makeTodo(['msg_id' => 5001, 'userid' => 12, 'reminded_at' => Carbon::now()]);

        $msg = new \App\Models\WebSocketDialogMsg();
        $msg->id = 5001;

        // 设提醒：写入 remind_at，并把 reminded_at 重置为 null
        $affected = $msg->setTodoRemind([11, 12], '2026-06-05 10:00:00');
        $this->assertSame(2, $affected);
        foreach ([$a, $b] as $row) {
            $fresh = WebSocketDialogMsgTodo::whereId($row->id)->first();
            $this->assertStringStartsWith('2026-06-05 10:00:00', $fresh->remind_at->format('Y-m-d H:i:s'));
            $this->assertNull($fresh->reminded_at, '改时间后应允许再次提醒');
        }

        // 取消提醒：remind_at 置 null
        $msg->setTodoRemind([11], null);
        $this->assertNull(WebSocketDialogMsgTodo::whereId($a->id)->first()->remind_at);
        // 未传 userid 时不动任何行
        $this->assertSame(0, $msg->setTodoRemind([], '2026-06-05 10:00:00'));
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
     * 镜像 msg__todoremind 的权限闸门：
     * 开关 close 且改到「自己以外的人」时，需操作者命中 checkTodoOwnerPermission。
     *
     * 注意：此逻辑为 DialogController::msg__todoremind() 权限逻辑的镜像，
     * 若 msg__todoremind 权限逻辑改动需同步更新此方法。
     */
    private function remindGateAllow(WebSocketDialog $dialog, string $switch, int $sender, array $userids): bool
    {
        if ($switch !== 'close') {
            return true;
        }
        $others = array_diff(array_map('intval', $userids), [$sender]);
        if (!$others) {
            return true; // 只改自己
        }
        return $dialog->checkTodoOwnerPermission($sender);
    }

    public function test_remind_edit_permission_follows_todo_gate()
    {
        $owner = $this->makeUser('r_o@test.local');
        $member = $this->makeUser('r_m@test.local');
        $dialog = WebSocketDialog::createGroup('Test_remind', [$owner->userid, $member->userid], 'user', $owner->userid)->fresh();

        $this->assertTrue($this->remindGateAllow($dialog, 'close', $member->userid, [$member->userid]));   // 改自己→放行
        $this->assertFalse($this->remindGateAllow($dialog, 'close', $member->userid, [$owner->userid]));   // 改他人→拒绝
        $this->assertTrue($this->remindGateAllow($dialog, 'close', $owner->userid, [$member->userid]));    // 群主改他人→放行
        $this->assertTrue($this->remindGateAllow($dialog, 'open', $member->userid, [$owner->userid]));     // open→放行
    }

    public function test_build_remind_text_produces_mention_spans()
    {
        $a = $this->makeUser('rt_a@test.local');
        $b = $this->makeUser('rt_b@test.local');

        $text = TodoRemindTask::buildRemindText([$a->userid, $b->userid]);

        $this->assertStringContainsString("<span class=\"mention user\" data-id=\"{$a->userid}\">@{$a->nickname}</span>", $text);
        $this->assertStringContainsString("<span class=\"mention user\" data-id=\"{$b->userid}\">@{$b->nickname}</span>", $text);
        $this->assertStringContainsString('你有一条待办到提醒时间啦', $text);
    }

    public function test_msg_join_group_extracts_text_mention_from_spans()
    {
        $owner = $this->makeUser('tx_o@test.local');
        $a = $this->makeUser('tx_a@test.local');
        $b = $this->makeUser('tx_b@test.local');
        $dialog = WebSocketDialog::createGroup('Test_text_mention', [$owner->userid, $a->userid, $b->userid], 'user', $owner->userid)->fresh();

        $msg = new \App\Models\WebSocketDialogMsg();
        $msg->dialog_id = $dialog->id;
        $msg->userid = $owner->userid;
        $msg->type = 'text';
        $msg->msg = ['text' => TodoRemindTask::buildRemindText([$a->userid, $b->userid])];

        $result = $msg->msgJoinGroup($dialog);
        $mentions = array_map('intval', $result['mentions']);
        sort($mentions);
        $expected = [$a->userid, $b->userid];
        sort($expected);
        $this->assertEquals($expected, $mentions);
    }
}
