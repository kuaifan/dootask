<?php

namespace Tests\Feature;

use App\Models\AbstractModel;
use App\Models\User;
use App\Models\UserDepartment;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MultiOwnerDepartmentTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // 部门测试涉及 User::save()（更新 department 字段），会触发 UserObserver→Apps::dispatchUserHook→Ihttp 外部 HTTP 调用。
        // 该 HTTP 调用在测试环境下会因 parse_url 没有 query 段触发 PHP 警告（被 Laravel 错误处理器升级为 ErrorException）。
        // 测试这层不关心 hook 行为，直接清空 User 观察者避免触发。
        User::flushEventListeners();

        // PHP 8 在加载 App\Module\Table\AbstractData 时会发 E_WARNING（__wakeup/__clone 私有），
        // 被 Laravel HandleExceptions 升级为 ErrorException 中断 addDeputy 等流程。
        // 通过在静默错误处理下提前 class_exists 触发一次加载，使后续路径不再触发。
        if (!class_exists(\App\Module\Table\OnlineData::class, false)) {
            $prev = set_error_handler(static function () { return true; });
            try {
                class_exists(\App\Module\Table\OnlineData::class);
            } finally {
                set_error_handler($prev);
            }
        }

        // saveDepartment 内部会触发 WebSocketDialog::pushMsg → Task::deliver → app('swoole')->task()，
        // 测试环境无 Swoole 运行时；绑定一个最小 stub 让 Task::deliver 安全降级，仅验证 DB 状态。
        // OnlineData::live 也会读取 swoole 的 onlineDataTable，提供一个 fake table（get 始终返回 0）。
        if (!app()->bound('swoole')) {
            $fakeTable = new class {
                public function get($key) { return 0; }
                public function set($key, $value) { return true; }
                public function del($key) { return true; }
                public function exist($key) { return false; }
                public function incr($key, $col, $incrBy = 1) { return 1; }
                public function decr($key, $col, $decrBy = 1) { return 0; }
            };
            app()->instance('swoole', new class($fakeTable) {
                public $worker_id = 0;
                public $taskworker = false;
                public $setting = ['worker_num' => 1];
                public $onlineDataTable;
                public $globalDataTable;
                public function __construct($fakeTable) {
                    $this->onlineDataTable = $fakeTable;
                    $this->globalDataTable = $fakeTable;
                }
                public function task($task) { return false; }
                public function sendMessage($task, $workerId) { return false; }
            });
        }
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
     * 创建测试部门，自动创建关联部门群。
     */
    private function makeDepartment(int $ownerUserid, string $name = null): UserDepartment
    {
        $name = $name ?? 'Dept_' . substr(md5(uniqid('', true)), 0, 6);
        $dept = UserDepartment::createInstance();
        $dept->saveDepartment([
            'name' => $name,
            'parent_id' => 0,
            'owner_userid' => $ownerUserid,
        ]);
        return $dept->fresh();
    }

    /**
     * 模拟 department__adddeputy（绕过 User::auth('admin') / HTTP）。
     * 直接调用 UserDepartment::addDeputy，确保 simulate 与真实接口一致。
     * 注：addDeputy 方法在 Task 5 才实现；本 simulate 在 Task 5 之前调用会报错。
     */
    private function simulateAddDeputy(UserDepartment $dept, int $userid): void
    {
        $dept->addDeputy($userid);
    }

    private function simulateDelDeputy(UserDepartment $dept, int $userid): void
    {
        $dept->delDeputy($userid);
    }

    public function test_setup_works()
    {
        $owner = $this->makeUser('d1_owner@test.local');
        $dept = $this->makeDepartment($owner->userid);

        $this->assertEquals($owner->userid, $dept->owner_userid);
        $this->assertNotEmpty($dept->dialog_id);
    }

    public function test_helpers_and_deputy_userids_accessor()
    {
        $owner = $this->makeUser('d3_o@test.local');
        $deputy = $this->makeUser('d3_d@test.local');
        $member = $this->makeUser('d3_m@test.local');
        $dept = $this->makeDepartment($owner->userid);

        // 手动插入部门管理员记录（addDeputy 在 Task 5 才实现）
        DB::table('user_department_owners')->insert([
            'department_id' => $dept->id,
            'userid' => $deputy->userid,
        ]);

        $dept = $dept->fresh();
        $this->assertTrue($dept->isPrimaryOwner($owner->userid));
        $this->assertFalse($dept->isPrimaryOwner($deputy->userid));
        $this->assertFalse($dept->isPrimaryOwner($member->userid));

        $this->assertFalse($dept->isDeputyOwner($owner->userid));
        $this->assertTrue($dept->isDeputyOwner($deputy->userid));
        $this->assertFalse($dept->isDeputyOwner($member->userid));

        $this->assertTrue($dept->isOwner($owner->userid));
        $this->assertTrue($dept->isOwner($deputy->userid));
        $this->assertFalse($dept->isOwner($member->userid));

        $deputyIds = $dept->deputy_userids;
        $this->assertEquals([$deputy->userid], $deputyIds);

        // 序列化后 API 响应应包含 deputy_userids
        $arr = $dept->toArray();
        $this->assertArrayHasKey('deputy_userids', $arr);
        $this->assertEquals([$deputy->userid], $arr['deputy_userids']);
    }

    public function test_saveDepartment_owner_change_syncs_dialog_role()
    {
        $oldOwner = $this->makeUser('d4_old@test.local');
        $newOwner = $this->makeUser('d4_new@test.local');
        $dept = $this->makeDepartment($oldOwner->userid);

        // 手动加 newOwner 入群（saveDepartment 之前他不在群里）
        // joinGroup($userid, $inviter, $important=null, $pushMsg=true) — pushMsg=false 跳过 Swoole
        $dialog = WebSocketDialog::find($dept->dialog_id);
        $dialog->joinGroup($newOwner->userid, 0, null, false);

        // 转让主负责人
        $dept->saveDepartment([
            'name' => $dept->name,
            'parent_id' => $dept->parent_id,
            'owner_userid' => $newOwner->userid,
        ]);

        $oldRole = WebSocketDialogUser::where('dialog_id', $dept->dialog_id)
            ->where('userid', $oldOwner->userid)->value('role');
        $newRole = WebSocketDialogUser::where('dialog_id', $dept->dialog_id)
            ->where('userid', $newOwner->userid)->value('role');

        $this->assertEquals(0, (int)$oldRole, '原主应降为普通成员');
        $this->assertEquals(1, (int)$newRole, '新主 role 应为 1');
    }

    public function test_saveDepartment_owner_change_preserves_deputies()
    {
        $oldOwner = $this->makeUser('d4b_old@test.local');
        $newOwner = $this->makeUser('d4b_new@test.local');
        $deputy = $this->makeUser('d4b_dep@test.local');
        $dept = $this->makeDepartment($oldOwner->userid);

        // 加 deputy 入群 + 部门管理员记录 + role=2（pushMsg=false 跳过 Swoole）
        $dialog = WebSocketDialog::find($dept->dialog_id);
        $dialog->joinGroup($deputy->userid, 0, null, false);
        DB::table('user_department_owners')->insert([
            'department_id' => $dept->id,
            'userid' => $deputy->userid,
        ]);
        WebSocketDialogUser::where('dialog_id', $dialog->id)
            ->where('userid', $deputy->userid)->update(['role' => 2]);

        // 加 newOwner 入群
        $dialog->joinGroup($newOwner->userid, 0, null, false);

        // 转让
        $dept->saveDepartment([
            'name' => $dept->name,
            'parent_id' => $dept->parent_id,
            'owner_userid' => $newOwner->userid,
        ]);

        // 部门管理员表保留
        $this->assertContains($deputy->userid, $dept->fresh()->deputy_userids);
        // 部门管理员 role 保留
        $depRole = WebSocketDialogUser::where('dialog_id', $dept->dialog_id)
            ->where('userid', $deputy->userid)->value('role');
        $this->assertEquals(2, (int)$depRole);
    }

    public function test_addDeputy_creates_owner_record_and_joins_group_as_deputy()
    {
        $owner = $this->makeUser('d5_o@test.local');
        $deputy = $this->makeUser('d5_d@test.local');
        $dept = $this->makeDepartment($owner->userid);

        $this->simulateAddDeputy($dept, $deputy->userid);

        $dept = $dept->fresh();
        $this->assertContains($deputy->userid, $dept->deputy_userids);
        // 部门管理员已入群
        $exists = WebSocketDialogUser::where('dialog_id', $dept->dialog_id)
            ->where('userid', $deputy->userid)->exists();
        $this->assertTrue($exists);
        // 部门管理员 role=2
        $role = WebSocketDialogUser::where('dialog_id', $dept->dialog_id)
            ->where('userid', $deputy->userid)->value('role');
        $this->assertEquals(2, (int)$role);
    }

    public function test_addDeputy_idempotent()
    {
        $owner = $this->makeUser('d5b_o@test.local');
        $deputy = $this->makeUser('d5b_d@test.local');
        $dept = $this->makeDepartment($owner->userid);

        $this->simulateAddDeputy($dept, $deputy->userid);
        $this->simulateAddDeputy($dept, $deputy->userid);

        $count = DB::table('user_department_owners')
            ->where('department_id', $dept->id)
            ->where('userid', $deputy->userid)->count();
        $this->assertEquals(1, $count);
    }

    public function test_addDeputy_rejects_primary_owner()
    {
        $owner = $this->makeUser('d5c_o@test.local');
        $dept = $this->makeDepartment($owner->userid);

        $this->expectException(\App\Exceptions\ApiException::class);
        $this->simulateAddDeputy($dept, $owner->userid);
    }

    public function test_addDeputy_rejects_nonexistent_user()
    {
        $owner = $this->makeUser('d5d_o@test.local');
        $dept = $this->makeDepartment($owner->userid);

        $this->expectException(\App\Exceptions\ApiException::class);
        $this->simulateAddDeputy($dept, 99999);
    }

    public function test_delDeputy_removes_owner_record_and_exits_department_group()
    {
        $owner = $this->makeUser('d6_o@test.local');
        $deputy = $this->makeUser('d6_d@test.local');
        $dept = $this->makeDepartment($owner->userid);
        $this->simulateAddDeputy($dept, $deputy->userid);

        // 任命后部门管理员应该入 users.department 并加入部门群
        $this->assertContains($dept->id, User::find($deputy->userid)->department, '任命部门管理员后应加入 users.department');
        $this->assertTrue(
            WebSocketDialogUser::where('dialog_id', $dept->dialog_id)->where('userid', $deputy->userid)->exists(),
            '任命部门管理员后应加入部门群'
        );

        $this->simulateDelDeputy($dept, $deputy->userid);

        $dept = $dept->fresh();
        $this->assertNotContains($deputy->userid, $dept->deputy_userids);
        // 罢免后从 users.department 移除（与负责人"离开部门"对齐）
        $this->assertNotContains($dept->id, User::find($deputy->userid)->department, '罢免部门管理员后应从 users.department 移除');
        // 退出部门群（成员关系=群关系一致）
        $exists = WebSocketDialogUser::where('dialog_id', $dept->dialog_id)
            ->where('userid', $deputy->userid)->exists();
        $this->assertFalse($exists, '罢免部门管理员后应退出部门群（成员关系=群关系）');
    }

    public function test_delDeputy_idempotent_for_non_deputy()
    {
        $owner = $this->makeUser('d6b_o@test.local');
        $member = $this->makeUser('d6b_m@test.local');
        $dept = $this->makeDepartment($owner->userid);

        // member 不是部门管理员，调 delDeputy 不应抛错
        $this->simulateDelDeputy($dept, $member->userid);
        $this->assertTrue(true);
    }

    public function test_deleteDepartment_cleans_deputy_records()
    {
        $owner = $this->makeUser('d7_o@test.local');
        $deputy = $this->makeUser('d7_d@test.local');
        $dept = $this->makeDepartment($owner->userid);
        $this->simulateAddDeputy($dept, $deputy->userid);
        $deptId = $dept->id;

        $dept->deleteDepartment();

        $count = DB::table('user_department_owners')->where('department_id', $deptId)->count();
        $this->assertEquals(0, $count);
    }

    public function test_user_transfer_clears_departing_deputy_role()
    {
        $owner = $this->makeUser('d7b_o@test.local');
        $departing = $this->makeUser('d7b_dep@test.local');
        $receiver = $this->makeUser('d7b_rec@test.local');
        $dept = $this->makeDepartment($owner->userid);
        $this->simulateAddDeputy($dept, $departing->userid);

        UserDepartment::transfer($departing->userid, $receiver->userid);

        // 离职的部门管理员记录已删
        $this->assertNotContains($departing->userid, $dept->fresh()->deputy_userids);
        // receiver 没有继承部门管理员身份
        $this->assertNotContains($receiver->userid, $dept->fresh()->deputy_userids);
    }

    public function test_user_transfer_inherits_departing_primary()
    {
        // 部门负责人转让仍要把负责人身份传给接收人（保留现有行为）
        $departing = $this->makeUser('d7c_dep@test.local');
        $receiver = $this->makeUser('d7c_rec@test.local');
        $dept = $this->makeDepartment($departing->userid);

        UserDepartment::transfer($departing->userid, $receiver->userid);

        $this->assertEquals($receiver->userid, $dept->fresh()->owner_userid);
    }

    public function test_deleteDepartment_recursively_cleans_child_deputies()
    {
        // 父部门 + 子部门各有部门管理员，删父部门时部门管理员记录应级联清理
        $owner = $this->makeUser('d7d_o@test.local');
        $deputyParent = $this->makeUser('d7d_dp@test.local');
        $deputyChild = $this->makeUser('d7d_dc@test.local');

        $parent = $this->makeDepartment($owner->userid, 'ParentDept');
        $this->simulateAddDeputy($parent, $deputyParent->userid);

        $child = UserDepartment::createInstance();
        $child->saveDepartment([
            'name' => 'ChildDept',
            'parent_id' => $parent->id,
            'owner_userid' => $owner->userid,
        ]);
        $child = $child->fresh();
        $this->simulateAddDeputy($child, $deputyChild->userid);

        $parentId = $parent->id;
        $childId = $child->id;
        $parent->deleteDepartment(); // 递归删子部门 + 清各自部门管理员

        $this->assertEquals(0, DB::table('user_department_owners')->where('department_id', $parentId)->count());
        $this->assertEquals(0, DB::table('user_department_owners')->where('department_id', $childId)->count());
    }
}
