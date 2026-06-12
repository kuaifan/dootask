<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserDepartment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserImportParseTest extends TestCase
{
    use DatabaseTransactions;

    public function test_parse_skips_header_and_empty_rows()
    {
        $sheet = [
            ['邮箱', '昵称', '初始密码'],            // 表头，应跳过
            ['a@test.local', '张三', 'Abc123456'],
            ['', '', ''],                            // 空行，应跳过
            ['b@test.local', '李四', 'Xyz123456'],
        ];

        $rows = User::parseImportRows($sheet);

        $this->assertCount(2, $rows);
        $this->assertSame('a@test.local', $rows[0]['email']);
        $this->assertSame('张三', $rows[0]['nickname']);
        $this->assertSame('Abc123456', $rows[0]['password']);
        $this->assertSame(2, $rows[0]['line']);
        $this->assertSame(4, $rows[1]['line']);
    }

    public function test_parse_trims_cells()
    {
        $sheet = [
            ['邮箱', '昵称', '初始密码'],
            ['  a@test.local  ', '  张三 ', ' Abc123456 '],
        ];

        $rows = User::parseImportRows($sheet);

        $this->assertSame('a@test.local', $rows[0]['email']);
        $this->assertSame('张三', $rows[0]['nickname']);
        $this->assertSame('Abc123456', $rows[0]['password']);
    }

    public function test_validate_passes_for_valid_row()
    {
        $row = ['email' => 'ok@test.local', 'nickname' => '张三', 'password' => 'Abc123456'];
        $this->assertNull(User::validateImportRow($row));
    }

    public function test_validate_requires_all_fields()
    {
        $this->assertSame('邮箱、昵称、初始密码均为必填', User::validateImportRow(['email' => '', 'nickname' => '张三', 'password' => 'Abc123456']));
        $this->assertSame('邮箱、昵称、初始密码均为必填', User::validateImportRow(['email' => 'a@test.local', 'nickname' => '', 'password' => 'Abc123456']));
        $this->assertSame('邮箱、昵称、初始密码均为必填', User::validateImportRow(['email' => 'a@test.local', 'nickname' => '张三', 'password' => '']));
    }

    public function test_validate_rejects_bad_email()
    {
        $this->assertSame('邮箱格式不正确', User::validateImportRow(['email' => 'not-an-email', 'nickname' => '张三', 'password' => 'Abc123456']));
    }

    public function test_validate_rejects_bad_nickname_length()
    {
        $this->assertSame('昵称需为2-20个字', User::validateImportRow(['email' => 'a@test.local', 'nickname' => '王', 'password' => 'Abc123456']));
        $this->assertSame('昵称需为2-20个字', User::validateImportRow(['email' => 'a@test.local', 'nickname' => str_repeat('字', 21), 'password' => 'Abc123456']));
    }

    public function test_validate_rejects_short_password()
    {
        $this->assertNotNull(User::validateImportRow(['email' => 'a@test.local', 'nickname' => '张三', 'password' => '123']));
    }

    public function test_assert_valid_profession_passes_for_empty_and_normal()
    {
        // 空职位允许（可选字段），2/20 字边界与正常值允许；不抛异常即通过
        User::assertValidProfession('');
        User::assertValidProfession('工程');                 // 恰好 2 字
        User::assertValidProfession('工程师');
        User::assertValidProfession(str_repeat('字', 20));   // 恰好 20 字
        $this->assertTrue(true);
    }

    public function test_assert_valid_profession_rejects_too_short()
    {
        $this->expectException(\App\Exceptions\ApiException::class);
        $this->expectExceptionMessage('职位/职称不可以少于2个字');
        User::assertValidProfession('A');
    }

    public function test_assert_valid_profession_rejects_too_long()
    {
        $this->expectException(\App\Exceptions\ApiException::class);
        $this->expectExceptionMessage('职位/职称最多只能设置20个字');
        User::assertValidProfession(str_repeat('字', 21));
    }

    public function test_assert_valid_departments_normalizes_ids()
    {
        // 空/非数组 → 返回空数组
        $this->assertSame([], User::assertValidDepartments([]));
        $this->assertSame([], User::assertValidDepartments('not-array'));
        // 去重 + 转 int + 过滤非正数（存在性校验会查库，需用真实部门 ID）
        $deptA = UserDepartment::createInstance(['name' => 'ImportParseDeptA_' . uniqid()]);
        $deptA->save();
        $deptB = UserDepartment::createInstance(['name' => 'ImportParseDeptB_' . uniqid()]);
        $deptB->save();
        $a = $deptA->id;
        $b = $deptB->id;
        $this->assertSame([$a, $b], User::assertValidDepartments([(string)$a, $a, $b, 0, -1]));
    }

    public function test_assert_valid_departments_rejects_over_limit()
    {
        // 超过 10 个（count 校验在查库之前）→ 抛异常
        $this->expectException(\App\Exceptions\ApiException::class);
        $this->expectExceptionMessage('最多只可加入10个部门');
        User::assertValidDepartments(range(1, 11));
    }

    public function test_parse_reads_profession_column()
    {
        $sheet = [
            ['邮箱', '昵称', '初始密码', '职位'],
            ['a@test.local', '张三', 'Abc123456', '工程师'],
            ['b@test.local', '李四', 'Xyz123456'], // 无职位列 → profession 为空
        ];
        $rows = User::parseImportRows($sheet);
        $this->assertCount(2, $rows);
        $this->assertSame('工程师', $rows[0]['profession']);
        $this->assertSame('', $rows[1]['profession']);
    }

    public function test_validate_passes_for_empty_profession()
    {
        $row = ['email' => 'a@test.local', 'nickname' => '张三', 'password' => 'Abc123456', 'profession' => ''];
        $this->assertNull(User::validateImportRow($row));
    }

    public function test_validate_rejects_bad_profession()
    {
        $row = ['email' => 'a@test.local', 'nickname' => '张三', 'password' => 'Abc123456', 'profession' => 'A'];
        $this->assertSame('职位/职称不可以少于2个字', User::validateImportRow($row));
    }
}
