<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        if (\DB::table('users')->count() > 0) {
            return;
        }

        \DB::table('users')->insert(array (
            0 =>
            array (
                'userid' => 1,
                'identity' => ',admin,',
                'az' => 'A',
                'email' => 'admin@dootask.com',
                'nickname' => '',
                'profession' => '管理员',
                'userimg' => '',
                'encrypt' => 'AJnoOb',
                'password' => '7d996ac317f1b9db564750ef3b8790fc',
                'changepass' => 0,
                'login_num' => 73,
                'last_ip' => '127.0.0.1',
                'last_at' => seeders_at('2021-07-01 16:58:16'),
                'line_ip' => '127.0.0.1',
                'line_at' => seeders_at('2021-07-01 17:43:48'),
                'task_dialog_id' => 18,
                'email_verity' => 1,
                'created_ip' => '',
                'disable_at' => null,
                'created_at' => seeders_at('2021-07-01 11:01:14'),
                'updated_at' => seeders_at('2021-07-01 17:43:48'),
            ),
            1 =>
            array (
                'userid' => 2,
                'identity' => '',
                'az' => 'z',
                'email' => 'test@dootask.com',
                'nickname' => '',
                'profession' => '测试员',
                'userimg' => '',
                'encrypt' => '18cZzh',
                'password' => '7eedd4cbf70da996d21f641bcc6cb412',
                'changepass' => 0,
                'login_num' => 63,
                'last_ip' => '127.0.0.1',
                'last_at' => seeders_at('2021-07-01 16:57:40'),
                'line_ip' => '127.0.0.1',
                'line_at' => seeders_at('2021-07-01 16:57:40'),
                'task_dialog_id' => 16,
                'email_verity' => 1,
                'created_ip' => '',
                'disable_at' => null,
                'created_at' => seeders_at('2021-07-01 11:01:14'),
                'updated_at' => seeders_at('2021-07-01 16:58:00'),
            ),
        ));


    }
}
