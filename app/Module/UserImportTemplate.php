<?php

namespace App\Module;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserImportTemplate implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            ['employee@example.com', '张三', 'Abc123456', '工程师'],
        ];
    }

    public function headings(): array
    {
        return ['邮箱(必填)', '昵称(必填,2-20字)', '初始密码(必填,6-32位)', '职位(选填,2-20字)'];
    }
}
