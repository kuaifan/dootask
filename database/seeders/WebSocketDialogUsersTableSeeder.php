<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WebSocketDialogUsersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        if (\DB::table('web_socket_dialog_users')->count() > 0) {
            return;
        }

        \DB::table('web_socket_dialog_users')->insert(array (
            0 =>
            array (
                'id' => 1,
                'dialog_id' => 1,
                'userid' => 1,
                'last_at' => seeders_at('2021-07-01 10:46:37'),
                'created_at' => seeders_at('2021-07-01 10:46:37'),
                'updated_at' => seeders_at('2021-07-01 10:46:37'),
            ),
            1 =>
            array (
                'id' => 2,
                'dialog_id' => 1,
                'userid' => 2,
                'last_at' => seeders_at('2021-07-01 10:46:37'),
                'created_at' => seeders_at('2021-07-01 10:46:37'),
                'updated_at' => seeders_at('2021-07-01 10:46:37'),
            ),
            2 =>
            array (
                'id' => 3,
                'dialog_id' => 2,
                'userid' => 1,
                'last_at' => seeders_at('2021-07-01 10:46:47'),
                'created_at' => seeders_at('2021-07-01 10:46:47'),
                'updated_at' => seeders_at('2021-07-01 10:46:47'),
            ),
            3 =>
            array (
                'id' => 4,
                'dialog_id' => 3,
                'userid' => 1,
                'last_at' => seeders_at('2021-07-01 10:46:47'),
                'created_at' => seeders_at('2021-07-01 10:46:47'),
                'updated_at' => seeders_at('2021-07-01 10:46:47'),
            ),
            4 =>
            array (
                'id' => 5,
                'dialog_id' => 4,
                'userid' => 1,
                'last_at' => seeders_at('2021-07-01 10:47:45'),
                'created_at' => seeders_at('2021-07-01 10:47:45'),
                'updated_at' => seeders_at('2021-07-01 10:47:45'),
            ),
            5 =>
            array (
                'id' => 6,
                'dialog_id' => 5,
                'userid' => 1,
                'last_at' => seeders_at('2021-07-01 10:47:45'),
                'created_at' => seeders_at('2021-07-01 10:47:45'),
                'updated_at' => seeders_at('2021-07-01 10:47:45'),
            ),
            6 =>
            array (
                'id' => 7,
                'dialog_id' => 6,
                'userid' => 1,
                'last_at' => seeders_at('2021-07-01 11:02:57'),
                'created_at' => seeders_at('2021-07-01 11:02:57'),
                'updated_at' => seeders_at('2021-07-01 11:02:57'),
            ),
            7 =>
            array (
                'id' => 8,
                'dialog_id' => 7,
                'userid' => 1,
                'last_at' => seeders_at('2021-07-01 11:02:57'),
                'created_at' => seeders_at('2021-07-01 11:02:57'),
                'updated_at' => seeders_at('2021-07-01 11:02:57'),
            ),
            8 =>
            array (
                'id' => 9,
                'dialog_id' => 8,
                'userid' => 1,
                'last_at' => seeders_at('2021-07-01 11:43:01'),
                'created_at' => seeders_at('2021-07-01 11:43:01'),
                'updated_at' => seeders_at('2021-07-01 11:43:01'),
            ),
            9 =>
            array (
                'id' => 10,
                'dialog_id' => 9,
                'userid' => 1,
                'last_at' => seeders_at('2021-07-01 11:43:01'),
                'created_at' => seeders_at('2021-07-01 11:43:01'),
                'updated_at' => seeders_at('2021-07-01 11:43:01'),
            ),
            10 =>
            array (
                'id' => 11,
                'dialog_id' => 10,
                'userid' => 1,
                'last_at' => seeders_at('2021-07-01 15:33:23'),
                'created_at' => seeders_at('2021-07-01 15:33:23'),
                'updated_at' => seeders_at('2021-07-01 15:33:23'),
            ),
            11 =>
            array (
                'id' => 12,
                'dialog_id' => 11,
                'userid' => 1,
                'last_at' => seeders_at('2021-07-01 15:33:23'),
                'created_at' => seeders_at('2021-07-01 15:33:23'),
                'updated_at' => seeders_at('2021-07-01 15:33:23'),
            ),
            12 =>
            array (
                'id' => 13,
                'dialog_id' => 12,
                'userid' => 1,
                'last_at' => seeders_at('2021-07-01 15:37:06'),
                'created_at' => seeders_at('2021-07-01 15:37:06'),
                'updated_at' => seeders_at('2021-07-01 15:37:06'),
            ),
            13 =>
            array (
                'id' => 14,
                'dialog_id' => 13,
                'userid' => 1,
                'last_at' => seeders_at('2021-07-01 15:37:06'),
                'created_at' => seeders_at('2021-07-01 15:37:06'),
                'updated_at' => seeders_at('2021-07-01 15:37:06'),
            ),
            14 =>
            array (
                'id' => 15,
                'dialog_id' => 14,
                'userid' => 1,
                'last_at' => seeders_at('2021-07-01 16:15:28'),
                'created_at' => seeders_at('2021-07-01 16:15:28'),
                'updated_at' => seeders_at('2021-07-01 16:15:28'),
            ),
            15 =>
            array (
                'id' => 16,
                'dialog_id' => 15,
                'userid' => 1,
                'last_at' => seeders_at('2021-07-01 16:15:28'),
                'created_at' => seeders_at('2021-07-01 16:15:28'),
                'updated_at' => seeders_at('2021-07-01 16:15:28'),
            ),
            16 =>
            array (
                'id' => 17,
                'dialog_id' => 7,
                'userid' => 2,
                'last_at' => seeders_at('2021-07-01 16:22:42'),
                'created_at' => seeders_at('2021-07-01 16:22:42'),
                'updated_at' => seeders_at('2021-07-01 16:22:42'),
            ),
            17 =>
            array (
                'id' => 18,
                'dialog_id' => 5,
                'userid' => 2,
                'last_at' => seeders_at('2021-07-01 16:23:15'),
                'created_at' => seeders_at('2021-07-01 16:23:15'),
                'updated_at' => seeders_at('2021-07-01 16:23:15'),
            ),
            18 =>
            array (
                'id' => 19,
                'dialog_id' => 15,
                'userid' => 2,
                'last_at' => seeders_at('2021-07-01 16:23:40'),
                'created_at' => seeders_at('2021-07-01 16:23:40'),
                'updated_at' => seeders_at('2021-07-01 16:23:40'),
            ),
            19 =>
            array (
                'id' => 20,
                'dialog_id' => 11,
                'userid' => 2,
                'last_at' => seeders_at('2021-07-01 16:29:38'),
                'created_at' => seeders_at('2021-07-01 16:29:38'),
                'updated_at' => seeders_at('2021-07-01 16:29:38'),
            ),
            20 =>
            array (
                'id' => 21,
                'dialog_id' => 16,
                'userid' => 2,
                'last_at' => seeders_at('2021-07-01 16:56:05'),
                'created_at' => seeders_at('2021-07-01 16:56:05'),
                'updated_at' => seeders_at('2021-07-01 16:56:05'),
            ),
            21 =>
            array (
                'id' => 22,
                'dialog_id' => 16,
                'userid' => 1,
                'last_at' => seeders_at('2021-07-01 16:56:05'),
                'created_at' => seeders_at('2021-07-01 16:56:05'),
                'updated_at' => seeders_at('2021-07-01 16:56:05'),
            ),
            22 =>
            array (
                'id' => 23,
                'dialog_id' => 17,
                'userid' => 2,
                'last_at' => seeders_at('2021-07-01 16:56:35'),
                'created_at' => seeders_at('2021-07-01 16:56:35'),
                'updated_at' => seeders_at('2021-07-01 16:56:35'),
            ),
            23 =>
            array (
                'id' => 24,
                'dialog_id' => 17,
                'userid' => 1,
                'last_at' => seeders_at('2021-07-01 16:56:35'),
                'created_at' => seeders_at('2021-07-01 16:56:35'),
                'updated_at' => seeders_at('2021-07-01 16:56:35'),
            ),
            24 =>
            array (
                'id' => 25,
                'dialog_id' => 18,
                'userid' => 2,
                'last_at' => seeders_at('2021-07-01 16:56:57'),
                'created_at' => seeders_at('2021-07-01 16:56:57'),
                'updated_at' => seeders_at('2021-07-01 16:56:57'),
            ),
            25 =>
            array (
                'id' => 26,
                'dialog_id' => 18,
                'userid' => 1,
                'last_at' => seeders_at('2021-07-01 16:56:57'),
                'created_at' => seeders_at('2021-07-01 16:56:57'),
                'updated_at' => seeders_at('2021-07-01 16:56:57'),
            ),
        ));


    }
}
