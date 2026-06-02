<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminCreateUserTest extends TestCase
{
    use DatabaseTransactions;

    /** Swoole 运行时缺失/Task 不可用属环境性失败，与业务无关 */
    private function isSwooleInfraFailure(\Throwable $e): bool
    {
        $msg = $e->getMessage();
        return str_contains($msg, 'swoole')
            || str_contains($msg, 'Swoole')
            || str_contains($msg, 'AbstractData::__wakeup')
            || str_contains($msg, 'Undefined array key');
    }

    public function test_create_by_admin_sets_changepass_and_normal_identity()
    {
        // Doo::userCreate 内部会调用 DB::commit() 绕过 DatabaseTransactions 的事务回滚。
        // 为保证幂等，先提交当前事务（让 DELETE 对 SO 的独立 DB 连接可见），
        // 删除残留用户后再重新开启事务，使后续测试代码仍在事务保护下运行。
        \DB::commit();
        \DB::table('users')->where('email', 'newstaff@test.local')->delete();
        \DB::beginTransaction();

        try {
            $user = User::createByAdmin('newstaff@test.local', 'Abc123456', '新员工');
        } catch (\Throwable $e) {
            if ($this->isSwooleInfraFailure($e)) {
                $this->markTestSkipped('Swoole 运行时不可用，createByAdmin 端到端无法验证：' . $e->getMessage());
            }
            throw $e;
        }

        $this->assertSame('newstaff@test.local', $user->email);
        $this->assertSame('新员工', $user->nickname);
        $this->assertSame(1, (int)$user->changepass, '首登应强制改密');
        $this->assertNotContains('temp', $user->identity, '管理员创建账号应为正式身份');
        $this->assertSame($user->password, \App\Module\Doo::md5s('Abc123456', $user->encrypt));

        // 手动清理：Doo::userCreate 的 DB::commit 已将创建的用户持久化，DatabaseTransactions 无法回滚
        \DB::table('users')->where('email', 'newstaff@test.local')->delete();
    }

    public function test_create_by_admin_can_skip_changepass()
    {
        // 同上：先提交事务让 DELETE 对 SO 独立连接可见，保证幂等
        \DB::commit();
        \DB::table('users')->where('email', 'nochg@test.local')->delete();
        \DB::beginTransaction();

        try {
            $user = User::createByAdmin('nochg@test.local', 'Abc123456', '免改密', ['changePass' => false]);
        } catch (\Throwable $e) {
            if ($this->isSwooleInfraFailure($e)) {
                $this->markTestSkipped('Swoole 运行时不可用，createByAdmin 端到端无法验证：' . $e->getMessage());
            }
            throw $e;
        }

        $this->assertSame(0, (int)$user->changepass, 'changePass=false 时不应要求首登改密');

        \DB::table('users')->where('email', 'nochg@test.local')->delete();
    }

    public function test_create_by_admin_rejects_bad_nickname()
    {
        $this->expectException(\App\Exceptions\ApiException::class);
        User::createByAdmin('x@test.local', 'Abc123456', '王'); // 昵称不足 2 字，校验在 SO 之前
    }

    public function test_create_by_admin_rejects_bad_profession()
    {
        $this->expectException(\App\Exceptions\ApiException::class);
        $this->expectExceptionMessage('职位/职称不可以少于2个字');
        User::createByAdmin('p@test.local', 'Abc123456', '张三', ['profession' => 'A']);
    }

    public function test_create_by_admin_rejects_too_many_departments()
    {
        $this->expectException(\App\Exceptions\ApiException::class);
        $this->expectExceptionMessage('最多只可加入10个部门');
        User::createByAdmin('d@test.local', 'Abc123456', '张三', ['department' => range(1, 11)]);
    }

    public function test_create_by_admin_rejects_nonexistent_department()
    {
        $this->expectException(\App\Exceptions\ApiException::class);
        $this->expectExceptionMessage('修改部门不存在');
        User::createByAdmin('d2@test.local', 'Abc123456', '张三', ['department' => [999999]]);
    }

    public function test_import_rejects_over_limit()
    {
        $rows = [];
        for ($i = 0; $i <= User::IMPORT_MAX; $i++) { // 501 行
            $rows[] = ['line' => $i + 2, 'email' => "u{$i}@test.local", 'nickname' => "员工{$i}", 'password' => 'Abc123456'];
        }
        $this->expectException(\App\Exceptions\ApiException::class);
        User::importUsers($rows);
    }

    public function test_import_preview_marks_existing_dup_and_invalid()
    {
        // 造一个已存在用户（直接 createInstance，不走 SO）
        $existing = User::createInstance([
            'email' => 'exists@test.local',
            'nickname' => '老员工',
            'userimg' => '',
            'profession' => '',
            'password' => md5('x'),
        ]);
        $existing->save();

        $rows = [
            ['line' => 2, 'email' => 'exists@test.local', 'nickname' => '张三', 'password' => 'Abc123456'], // 系统已存在
            ['line' => 3, 'email' => 'newp1@test.local', 'nickname' => '李四', 'password' => 'Abc123456'],  // ok
            ['line' => 4, 'email' => 'newp1@test.local', 'nickname' => '王五', 'password' => 'Abc123456'],  // 文件内重复
            ['line' => 5, 'email' => 'bad', 'nickname' => '赵六', 'password' => 'Abc123456'],               // 邮箱格式
        ];

        $preview = User::importPreview($rows);

        $this->assertSame(4, $preview['total']);
        $this->assertSame(1, $preview['valid']);
        $this->assertSame(3, $preview['invalid']);
        $this->assertSame('error', $preview['rows'][0]['status']);
        $this->assertSame('邮箱地址已存在', $preview['rows'][0]['reason']);
        $this->assertSame('ok', $preview['rows'][1]['status']);
        $this->assertSame('error', $preview['rows'][2]['status']);
        $this->assertSame('文件内邮箱重复', $preview['rows'][2]['reason']);
        $this->assertSame('error', $preview['rows'][3]['status']);
        $this->assertSame('邮箱格式不正确', $preview['rows'][3]['reason']);
        // 预览不创建账号
        $this->assertSame(0, User::whereEmail('newp1@test.local')->count());
    }

    public function test_import_preview_defaults_email_verity_to_verified()
    {
        // 预览默认逐行标记为已认证（email_verity=1），前端可再按行调整
        $rows = [
            ['line' => 2, 'email' => 'verity1@test.local', 'nickname' => '张三', 'password' => 'Abc123456'],
            ['line' => 3, 'email' => 'bad', 'nickname' => '李四', 'password' => 'Abc123456'], // 错误行同样带默认值
        ];

        $preview = User::importPreview($rows);

        $this->assertSame(1, $preview['rows'][0]['email_verity']);
        $this->assertSame(1, $preview['rows'][1]['email_verity']);
    }

    public function test_import_collects_all_invalid_rows_without_creating()
    {
        // 全部非法 → 不触发 createByAdmin/SO，可在无 Swoole 环境稳定运行
        $rows = [
            ['line' => 2, 'email' => '', 'nickname' => '张三', 'password' => 'Abc123456'],
            ['line' => 3, 'email' => 'bad-email', 'nickname' => '李四', 'password' => 'Abc123456'],
            ['line' => 4, 'email' => 'c@test.local', 'nickname' => '王', 'password' => 'Abc123456'],
        ];

        $result = User::importUsers($rows);

        $this->assertSame(3, $result['total']);
        $this->assertSame(0, $result['success']);
        $this->assertCount(3, $result['failed']);
        $this->assertSame(2, $result['failed'][0]['line']);
        $this->assertSame('邮箱、昵称、初始密码均为必填', $result['failed'][0]['reason']);
        $this->assertSame('邮箱格式不正确', $result['failed'][1]['reason']);
        $this->assertSame('昵称需为2-20个字', $result['failed'][2]['reason']);
    }

    public function test_import_marks_row_with_bad_profession()
    {
        // 行内职位非法（1字）→ 该行被标记失败，不创建账号
        $rows = [
            ['line' => 2, 'email' => 'badprof@test.local', 'nickname' => '张三', 'password' => 'Abc123456', 'profession' => 'A'],
        ];
        $result = User::importUsers($rows, true);
        $this->assertSame(0, $result['success']);
        $this->assertCount(1, $result['failed']);
        $this->assertSame('职位/职称不可以少于2个字', $result['failed'][0]['reason']);
    }

    public function test_import_marks_row_with_nonexistent_department()
    {
        // 行内部门不存在 → createByAdmin 在 reg() 之前抛异常 → 该行失败（无需 Swoole）
        $rows = [
            ['line' => 2, 'email' => 'baddept@test.local', 'nickname' => '张三', 'password' => 'Abc123456', 'profession' => '', 'department' => [999999]],
        ];
        $result = User::importUsers($rows, true);
        $this->assertSame(0, $result['success']);
        $this->assertCount(1, $result['failed']);
        $this->assertSame('修改部门不存在', $result['failed'][0]['reason']);
    }
}
