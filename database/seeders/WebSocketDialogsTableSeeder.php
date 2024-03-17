<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogUser;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class WebSocketDialogsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        if (\DB::table('web_socket_dialogs')->count() > 0) {
            return;
        }

        \DB::table('web_socket_dialogs')->insert(array (
            0 =>
            array (
                'id' => 1,
                'type' => 'user',
                'group_type' => '',
                'name' => '',
                'created_at' => seeders_at('2021-07-01 10:46:37'),
                'updated_at' => seeders_at('2021-07-01 17:12:52'),
                'deleted_at' => NULL,
            ),
            1 =>
            array (
                'id' => 2,
                'type' => 'group',
                'group_type' => 'project',
                'name' => '',
                'created_at' => seeders_at('2021-07-01 10:46:47'),
                'updated_at' => seeders_at('2021-07-01 17:31:03'),
                'deleted_at' => seeders_at('2021-07-01 17:31:03'),
            ),
            2 =>
            array (
                'id' => 3,
                'type' => 'group',
                'group_type' => 'project',
                'name' => '',
                'created_at' => seeders_at('2021-07-01 10:46:47'),
                'updated_at' => seeders_at('2021-07-01 10:46:55'),
                'deleted_at' => seeders_at('2021-07-01 10:46:55'),
            ),
            3 =>
            array (
                'id' => 4,
                'type' => 'group',
                'group_type' => 'project',
                'name' => '',
                'created_at' => seeders_at('2021-07-01 10:47:45'),
                'updated_at' => seeders_at('2021-07-01 17:30:05'),
                'deleted_at' => seeders_at('2021-07-01 17:30:05'),
            ),
            4 =>
            array (
                'id' => 5,
                'type' => 'group',
                'group_type' => 'project',
                'name' => '',
                'created_at' => seeders_at('2021-07-01 10:47:45'),
                'updated_at' => seeders_at('2021-07-01 16:31:58'),
                'deleted_at' => NULL,
            ),
            5 =>
            array (
                'id' => 6,
                'type' => 'group',
                'group_type' => 'project',
                'name' => '',
                'created_at' => seeders_at('2021-07-01 11:02:57'),
                'updated_at' => seeders_at('2021-07-01 17:31:28'),
                'deleted_at' => seeders_at('2021-07-01 17:31:28'),
            ),
            6 =>
            array (
                'id' => 7,
                'type' => 'group',
                'group_type' => 'project',
                'name' => '',
                'created_at' => seeders_at('2021-07-01 11:02:57'),
                'updated_at' => seeders_at('2021-07-01 16:32:50'),
                'deleted_at' => NULL,
            ),
            7 =>
            array (
                'id' => 8,
                'type' => 'group',
                'group_type' => 'project',
                'name' => '',
                'created_at' => seeders_at('2021-07-01 11:43:01'),
                'updated_at' => seeders_at('2021-07-01 17:31:28'),
                'deleted_at' => seeders_at('2021-07-01 17:31:28'),
            ),
            8 =>
            array (
                'id' => 9,
                'type' => 'group',
                'group_type' => 'project',
                'name' => '',
                'created_at' => seeders_at('2021-07-01 11:43:01'),
                'updated_at' => seeders_at('2021-07-01 14:02:57'),
                'deleted_at' => NULL,
            ),
            9 =>
            array (
                'id' => 10,
                'type' => 'group',
                'group_type' => 'project',
                'name' => '',
                'created_at' => seeders_at('2021-07-01 15:33:23'),
                'updated_at' => seeders_at('2021-07-01 17:31:28'),
                'deleted_at' => seeders_at('2021-07-01 17:31:28'),
            ),
            10 =>
            array (
                'id' => 11,
                'type' => 'group',
                'group_type' => 'project',
                'name' => '',
                'created_at' => seeders_at('2021-07-01 15:33:23'),
                'updated_at' => seeders_at('2021-07-01 16:31:40'),
                'deleted_at' => NULL,
            ),
            11 =>
            array (
                'id' => 12,
                'type' => 'group',
                'group_type' => 'project',
                'name' => '',
                'created_at' => seeders_at('2021-07-01 15:37:06'),
                'updated_at' => seeders_at('2021-07-01 17:31:28'),
                'deleted_at' => seeders_at('2021-07-01 17:31:28'),
            ),
            12 =>
            array (
                'id' => 13,
                'type' => 'group',
                'group_type' => 'project',
                'name' => '',
                'created_at' => seeders_at('2021-07-01 15:37:06'),
                'updated_at' => seeders_at('2021-07-01 15:44:09'),
                'deleted_at' => NULL,
            ),
            13 =>
            array (
                'id' => 14,
                'type' => 'group',
                'group_type' => 'project',
                'name' => '',
                'created_at' => seeders_at('2021-07-01 16:15:28'),
                'updated_at' => seeders_at('2021-07-01 17:31:28'),
                'deleted_at' => seeders_at('2021-07-01 17:31:28'),
            ),
            14 =>
            array (
                'id' => 15,
                'type' => 'group',
                'group_type' => 'project',
                'name' => '',
                'created_at' => seeders_at('2021-07-01 16:15:28'),
                'updated_at' => seeders_at('2021-07-01 16:37:59'),
                'deleted_at' => NULL,
            ),
            15 =>
            array (
                'id' => 16,
                'type' => 'group',
                'group_type' => 'task',
                'name' => '',
                'created_at' => seeders_at('2021-07-01 16:56:05'),
                'updated_at' => seeders_at('2021-07-01 16:58:02'),
                'deleted_at' => NULL,
            ),
            16 =>
            array (
                'id' => 17,
                'type' => 'group',
                'group_type' => 'task',
                'name' => '',
                'created_at' => seeders_at('2021-07-01 16:56:35'),
                'updated_at' => seeders_at('2021-07-01 16:57:58'),
                'deleted_at' => NULL,
            ),
            17 =>
            array (
                'id' => 18,
                'type' => 'group',
                'group_type' => 'task',
                'name' => '',
                'created_at' => seeders_at('2021-07-01 16:56:57'),
                'updated_at' => seeders_at('2021-07-01 16:57:52'),
                'deleted_at' => NULL,
            ),
        ));

        $botUser = User::botGetOrCreate('bot-manager');
        if ($botUser) {
            $dialog = WebSocketDialog::checkUserDialog($botUser, 1);
            if ($dialog) {
                WebSocketDialogUser::whereDialogId($dialog->id)->change(['last_at' => Carbon::now()->subSecond()]);
            }
        }
        User::botGetOrCreate('ai-openai');
        User::botGetOrCreate('ai-claude');

        $userids = User::whereBot(0)->whereNull('disable_at')->pluck('userid')->toArray();
        WebSocketDialog::createGroup("全体成员 All members", $userids, 'all');
    }
}
