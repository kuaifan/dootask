<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskUser;
use App\Models\ProjectUser;
use App\Models\User;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgTodo;
use App\Models\WebSocketDialogUser;
use App\Module\Base;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * 待办设置/取消权限测试（系统开关 todo_set_permission）
 */
class TodoSetPermissionTest extends TestCase
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

    /** 系统管理员（identity 含 admin） */
    private function makeAdmin(string $email): User
    {
        $user = $this->makeUser($email);
        $user->identity = Base::arrayImplode(['admin']);
        $user->save();
        return $user->fresh();
    }

    /** 普通群（group_type=user），设置群主与群管理员 */
    private function makeUserGroup(int $ownerUserid, array $members, array $deputyUserids = []): WebSocketDialog
    {
        $all = array_values(array_unique(array_merge([$ownerUserid], $members)));
        $dialog = WebSocketDialog::createGroup('Test_group', $all, 'user', $ownerUserid);
        if ($deputyUserids) {
            WebSocketDialogUser::where('dialog_id', $dialog->id)
                ->whereIn('userid', $deputyUserids)
                ->update(['role' => 2]);
        }
        return $dialog->fresh();
    }

    /** 项目 + 项目群（group_type=project），owner=负责人，deputy=项目管理员 */
    private function makeProjectWithDialog(int $ownerUserid, array $members = [], array $deputyUserids = []): Project
    {
        $all = array_values(array_unique(array_merge([$ownerUserid], $members)));
        $project = Project::createInstance([
            'name' => 'Test_' . substr(md5(uniqid('', true)), 0, 6),
            'desc' => '',
            'userid' => $ownerUserid,
            'personal' => 0,
        ]);
        $project->save();
        ProjectUser::updateInsert(['project_id' => $project->id, 'userid' => $ownerUserid], ['owner' => 1]);
        foreach ($all as $uid) {
            if ($uid === $ownerUserid) continue;
            ProjectUser::updateInsert(['project_id' => $project->id, 'userid' => $uid], ['owner' => 0]);
        }
        if ($deputyUserids) {
            ProjectUser::where('project_id', $project->id)
                ->whereIn('userid', $deputyUserids)
                ->update(['owner' => 2]);
        }
        $dialog = WebSocketDialog::createGroup('Test_pdialog', $all, 'project', $ownerUserid);
        $project->dialog_id = $dialog->id;
        $project->save();
        $project->syncDialogUser();
        return $project->fresh();
    }

    public function test_group_owner_and_deputy_allowed_others_not()
    {
        $owner = $this->makeUser('t_g_o@test.local');
        $deputy = $this->makeUser('t_g_d@test.local');
        $member = $this->makeUser('t_g_m@test.local');
        $dialog = $this->makeUserGroup($owner->userid, [$deputy->userid, $member->userid], [$deputy->userid]);

        $this->assertTrue($dialog->checkTodoOwnerPermission($owner->userid));
        $this->assertTrue($dialog->checkTodoOwnerPermission($deputy->userid));
        $this->assertFalse($dialog->checkTodoOwnerPermission($member->userid));
        $this->assertFalse($dialog->checkTodoOwnerPermission(0));
    }

    public function test_project_owner_and_deputy_allowed_member_not()
    {
        $owner = $this->makeUser('t_p_o@test.local');
        $deputy = $this->makeUser('t_p_d@test.local');
        $member = $this->makeUser('t_p_m@test.local');
        $project = $this->makeProjectWithDialog($owner->userid, [$deputy->userid, $member->userid], [$deputy->userid]);
        $dialog = WebSocketDialog::find($project->dialog_id);

        $this->assertTrue($dialog->checkTodoOwnerPermission($owner->userid));
        $this->assertTrue($dialog->checkTodoOwnerPermission($deputy->userid));
        $this->assertFalse($dialog->checkTodoOwnerPermission($member->userid));
    }

    public function test_task_owner_and_task_project_owner_allowed_member_not()
    {
        $projectOwner = $this->makeUser('t_t_po@test.local');
        $taskOwner = $this->makeUser('t_t_to@test.local');
        $member = $this->makeUser('t_t_m@test.local');
        $project = $this->makeProjectWithDialog($projectOwner->userid, [$taskOwner->userid, $member->userid]);

        // 任务群（group_type=task）
        $taskDialog = WebSocketDialog::createGroup(
            'Test_tdialog',
            [$projectOwner->userid, $taskOwner->userid, $member->userid],
            'task',
            $projectOwner->userid
        );
        $task = ProjectTask::createInstance([
            'project_id' => $project->id,
            'parent_id' => 0,
            'name' => 'Test task',
            'dialog_id' => $taskDialog->id,
            'userid' => $projectOwner->userid,
        ]);
        $task->save();
        ProjectTaskUser::createInstance([
            'project_id' => $project->id,
            'task_id' => $task->id,
            'userid' => $taskOwner->userid,
            'owner' => 1,
        ])->save();

        $taskDialog = $taskDialog->fresh();
        $this->assertTrue($taskDialog->checkTodoOwnerPermission($taskOwner->userid), '任务负责人应放行');
        $this->assertTrue($taskDialog->checkTodoOwnerPermission($projectOwner->userid), '任务所属项目负责人应放行');
        $this->assertFalse($taskDialog->checkTodoOwnerPermission($member->userid), '普通成员应拒绝');
    }

    public function test_admin_allowed_in_any_group()
    {
        $admin = $this->makeAdmin('t_admin@test.local');
        $owner = $this->makeUser('t_admin_o@test.local');
        $member = $this->makeUser('t_admin_m@test.local');

        // 普通群：管理员既非群主也非成员，仍放行；普通成员仍拒绝
        $userGroup = $this->makeUserGroup($owner->userid, [$member->userid]);
        $this->assertTrue($userGroup->checkTodoOwnerPermission($admin->userid), '管理员在普通群应放行');
        $this->assertFalse($userGroup->checkTodoOwnerPermission($member->userid), '普通成员仍应拒绝');

        // 项目群：管理员非项目负责人，仍放行
        $project = $this->makeProjectWithDialog($owner->userid, [$member->userid]);
        $pdialog = WebSocketDialog::find($project->dialog_id);
        $this->assertTrue($pdialog->checkTodoOwnerPermission($admin->userid), '管理员在项目群应放行');

        // 全员群（无群主）：管理员放行，普通成员拒绝
        $allGroup = WebSocketDialog::createGroup('Test_all', [$owner->userid, $member->userid], 'all')->fresh();
        $this->assertSame(0, (int)$allGroup->owner_id, '全员群应无群主');
        $this->assertTrue($allGroup->checkTodoOwnerPermission($admin->userid), '管理员在全员群应放行');
        $this->assertFalse($allGroup->checkTodoOwnerPermission($member->userid), '全员群普通成员应拒绝');
    }

    /**
     * 镜像 WebSocketDialogMsg::toggleTodoMsg 内的权限闸门决策。
     * @param string $switch  开关值 open|close
     * @param int    $sender  操作者
     * @param array  $cancel  本次取消待办的用户
     * @param array  $setup   本次新增待办的用户
     * @return bool  true=放行
     */
    private function gateAllow(WebSocketDialog $dialog, string $switch, int $sender, array $cancel, array $setup): bool
    {
        if ($switch !== 'close') {
            return true; // 开关非 close：行为不变，全部放行
        }
        $affected = array_unique(array_merge($cancel, $setup));
        $others = array_diff($affected, [$sender]);
        if (!$others) {
            return true; // 仅影响自己 → 放行
        }
        return $dialog->checkTodoOwnerPermission($sender);
    }

    public function test_gate_open_switch_allows_anyone()
    {
        $owner = $this->makeUser('t_gate_o@test.local');
        $member = $this->makeUser('t_gate_m@test.local');
        $dialog = $this->makeUserGroup($owner->userid, [$member->userid]);

        // 普通成员给他人设待办，开关 open → 放行
        $this->assertTrue($this->gateAllow($dialog, 'open', $member->userid, [], [$owner->userid, $member->userid]));
    }

    public function test_gate_close_self_only_allowed()
    {
        $owner = $this->makeUser('t_gate2_o@test.local');
        $member = $this->makeUser('t_gate2_m@test.local');
        $dialog = $this->makeUserGroup($owner->userid, [$member->userid]);

        // 普通成员仅给/取消自己的待办 → 放行
        $this->assertTrue($this->gateAllow($dialog, 'close', $member->userid, [], [$member->userid]));
        $this->assertTrue($this->gateAllow($dialog, 'close', $member->userid, [$member->userid], []));
    }

    public function test_gate_close_member_to_others_blocked()
    {
        $owner = $this->makeUser('t_gate3_o@test.local');
        $member = $this->makeUser('t_gate3_m@test.local');
        $dialog = $this->makeUserGroup($owner->userid, [$member->userid]);

        // 普通成员给他人设待办，开关 close → 拒绝
        $this->assertFalse($this->gateAllow($dialog, 'close', $member->userid, [], [$owner->userid]));
        // 普通成员取消他人待办 → 拒绝
        $this->assertFalse($this->gateAllow($dialog, 'close', $member->userid, [$owner->userid], []));
    }

    public function test_gate_close_owner_to_others_allowed()
    {
        $owner = $this->makeUser('t_gate4_o@test.local');
        $member = $this->makeUser('t_gate4_m@test.local');
        $dialog = $this->makeUserGroup($owner->userid, [$member->userid]);

        // 群主给他人设待办，开关 close → 放行
        $this->assertTrue($this->gateAllow($dialog, 'close', $owner->userid, [], [$member->userid]));
    }

    /**
     * 真实调用 toggleTodoMsg，覆盖闸门「被拦截路径」：
     * 开关 close 下，普通成员给他人设待办应被提前 retError 拦截，
     * 不会执行后续的 sendMsg（无 WebSocket 副作用）。
     */
    public function test_toggle_todo_msg_blocks_member_to_others_when_close()
    {
        $owner = $this->makeUser('t_toggle_o@test.local');
        $member = $this->makeUser('t_toggle_m@test.local');
        $dialog = $this->makeUserGroup($owner->userid, [$member->userid]);

        // 系统开关设为 close（合并写入，避免清空其它 system 设置）
        $setting = Base::setting('system', ['todo_set_permission' => 'close'], true);
        $this->assertSame('close', $setting['todo_set_permission']);
        $this->assertSame('close', Base::settingFind('system', 'todo_set_permission'));

        // 群主发一条真实文本消息（type 必须是 text，否则被 in_array 提前拒绝）
        $msg = WebSocketDialogMsg::createInstance([
            'dialog_id' => $dialog->id,
            'dialog_type' => $dialog->type,
            'userid' => $owner->userid,
            'type' => 'text',
            'msg' => ['text' => 'hello todo'],
            'read' => 0,
            'send' => 1,
        ]);
        $msg->save();

        // 普通成员给群主（他人）设待办 → 被闸门拦截
        $res = $msg->toggleTodoMsg($member->userid, [$owner->userid]);

        $this->assertTrue(Base::isError($res), '被拦截路径应返回错误响应');
        $this->assertSame(0, $res['ret']);
        $this->assertStringContainsString('仅群主、项目/任务负责人或系统管理员可设置或取消他人待办', $res['msg']);

        // 被拦截后不应写入任何待办记录
        $this->assertSame(0, WebSocketDialogMsgTodo::whereMsgId($msg->id)->count());
    }
}
