<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WebSocketDialogMsgReadsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        if (\DB::table('web_socket_dialog_msg_reads')->count() > 0) {
            return;
        }

        \DB::table('web_socket_dialog_msg_reads')->insert(array (
            0 =>
            array (
                'id' => 1,
                'dialog_id' => 1,
                'msg_id' => 5,
                'userid' => 1,
                'after' => 0,
                'read_at' => seeders_at('2021-07-01 16:21:28'),
            ),
            1 =>
            array (
                'id' => 2,
                'dialog_id' => 5,
                'msg_id' => 6,
                'userid' => 2,
                'after' => 0,
                'read_at' => seeders_at('2021-07-01 16:25:43'),
            ),
            2 =>
            array (
                'id' => 3,
                'dialog_id' => 5,
                'msg_id' => 7,
                'userid' => 1,
                'after' => 0,
                'read_at' => seeders_at('2021-07-01 16:27:44'),
            ),
            3 =>
            array (
                'id' => 4,
                'dialog_id' => 7,
                'msg_id' => 2,
                'userid' => 2,
                'after' => 1,
                'read_at' => seeders_at('2021-07-01 16:26:03'),
            ),
            4 =>
            array (
                'id' => 5,
                'dialog_id' => 7,
                'msg_id' => 8,
                'userid' => 1,
                'after' => 0,
                'read_at' => seeders_at('2021-07-01 16:27:21'),
            ),
            5 =>
            array (
                'id' => 6,
                'dialog_id' => 7,
                'msg_id' => 9,
                'userid' => 1,
                'after' => 0,
                'read_at' => seeders_at('2021-07-01 16:27:21'),
            ),
            6 =>
            array (
                'id' => 7,
                'dialog_id' => 7,
                'msg_id' => 10,
                'userid' => 1,
                'after' => 0,
                'read_at' => seeders_at('2021-07-01 16:27:21'),
            ),
            7 =>
            array (
                'id' => 8,
                'dialog_id' => 7,
                'msg_id' => 11,
                'userid' => 2,
                'after' => 0,
                'read_at' => seeders_at('2021-07-01 16:32:00'),
            ),
            8 =>
            array (
                'id' => 9,
                'dialog_id' => 5,
                'msg_id' => 12,
                'userid' => 2,
                'after' => 0,
                'read_at' => seeders_at('2021-07-01 16:31:43'),
            ),
            9 =>
            array (
                'id' => 10,
                'dialog_id' => 1,
                'msg_id' => 13,
                'userid' => 2,
                'after' => 0,
                'read_at' => seeders_at('2021-07-01 16:31:43'),
            ),
            10 =>
            array (
                'id' => 11,
                'dialog_id' => 11,
                'msg_id' => 14,
                'userid' => 2,
                'after' => 0,
                'read_at' => seeders_at('2021-07-01 16:31:37'),
            ),
            11 =>
            array (
                'id' => 12,
                'dialog_id' => 11,
                'msg_id' => 4,
                'userid' => 2,
                'after' => 1,
                'read_at' => seeders_at('2021-07-01 16:31:37'),
            ),
            12 =>
            array (
                'id' => 13,
                'dialog_id' => 11,
                'msg_id' => 15,
                'userid' => 1,
                'after' => 0,
                'read_at' => NULL,
            ),
            13 =>
            array (
                'id' => 14,
                'dialog_id' => 5,
                'msg_id' => 16,
                'userid' => 1,
                'after' => 0,
                'read_at' => NULL,
            ),
            14 =>
            array (
                'id' => 15,
                'dialog_id' => 7,
                'msg_id' => 17,
                'userid' => 1,
                'after' => 0,
                'read_at' => NULL,
            ),
            15 =>
            array (
                'id' => 16,
                'dialog_id' => 7,
                'msg_id' => 18,
                'userid' => 1,
                'after' => 0,
                'read_at' => NULL,
            ),
            16 =>
            array (
                'id' => 17,
                'dialog_id' => 7,
                'msg_id' => 19,
                'userid' => 1,
                'after' => 0,
                'read_at' => NULL,
            ),
            17 =>
            array (
                'id' => 18,
                'dialog_id' => 15,
                'msg_id' => 20,
                'userid' => 2,
                'after' => 0,
                'read_at' => seeders_at('2021-07-01 16:37:34'),
            ),
            18 =>
            array (
                'id' => 19,
                'dialog_id' => 15,
                'msg_id' => 21,
                'userid' => 1,
                'after' => 0,
                'read_at' => seeders_at('2021-07-01 17:12:23'),
            ),
            19 =>
            array (
                'id' => 20,
                'dialog_id' => 16,
                'msg_id' => 22,
                'userid' => 2,
                'after' => 0,
                'read_at' => seeders_at('2021-07-01 16:58:00'),
            ),
            20 =>
            array (
                'id' => 21,
                'dialog_id' => 17,
                'msg_id' => 23,
                'userid' => 2,
                'after' => 0,
                'read_at' => seeders_at('2021-07-01 16:57:54'),
            ),
            21 =>
            array (
                'id' => 22,
                'dialog_id' => 18,
                'msg_id' => 24,
                'userid' => 2,
                'after' => 0,
                'read_at' => seeders_at('2021-07-01 16:57:46'),
            ),
            22 =>
            array (
                'id' => 23,
                'dialog_id' => 18,
                'msg_id' => 25,
                'userid' => 1,
                'after' => 0,
                'read_at' => seeders_at('2021-07-01 17:12:20'),
            ),
            23 =>
            array (
                'id' => 24,
                'dialog_id' => 17,
                'msg_id' => 26,
                'userid' => 1,
                'after' => 0,
                'read_at' => seeders_at('2021-07-01 16:59:43'),
            ),
            24 =>
            array (
                'id' => 25,
                'dialog_id' => 16,
                'msg_id' => 27,
                'userid' => 1,
                'after' => 0,
                'read_at' => seeders_at('2021-07-01 16:59:42'),
            ),
            25 =>
            array (
                'id' => 26,
                'dialog_id' => 1,
                'msg_id' => 28,
                'userid' => 2,
                'after' => 0,
                'read_at' => NULL,
            ),
        ));


    }
}
