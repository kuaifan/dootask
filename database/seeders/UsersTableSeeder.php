<?php

namespace Database\Seeders;

use App\Models\User;
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


        if (User::count() > 0) {
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
                'login_num' => 64,
                'last_ip' => '10.22.22.1',
                'last_at' => '2021-06-25 18:50:13',
                'line_ip' => '10.22.22.1',
                'line_at' => '2021-06-25 18:50:13',
                'task_dialog_id' => 28,
                'created_ip' => '',
                'created_at' => '2021-06-02 11:01:14',
                'updated_at' => '2021-06-25 18:50:28',
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
                'login_num' => 58,
                'last_ip' => '10.22.22.1',
                'last_at' => '2021-06-25 18:50:51',
                'line_ip' => '10.22.22.1',
                'line_at' => '2021-06-25 18:50:51',
                'task_dialog_id' => 28,
                'created_ip' => '',
                'created_at' => '2021-06-02 11:01:14',
                'updated_at' => '2021-06-25 18:51:06',
            ),
        ));


    }
}
