<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WebSocketDialogMsgsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        if (\DB::table('web_socket_dialog_msgs')->count() > 0) {
            return;
        }

        \DB::table('web_socket_dialog_msgs')->insert(array (
            0 =>
            array (
                'id' => 1,
                'dialog_id' => 9,
                'userid' => 1,
                'type' => 'text',
                'msg' => '{"text":"\\u5c06\\u6536\\u96c6\\u7bb1\\u7684\\u4e8b\\u52a1\\u8fdb\\u884c\\u5224\\u65ad"}',
                'read' => 0,
                'send' => 0,
                'created_at' => seeders_at('2021-07-01 14:02:57'),
                'updated_at' => seeders_at('2021-07-01 14:02:57'),
            ),
            1 =>
            array (
                'id' => 2,
                'dialog_id' => 7,
                'userid' => 1,
                'type' => 'text',
                'msg' => '{"text":"\\u8fd9\\u4e2a\\u6536\\u85cf\\u771f\\u5fc3\\u4e0d\\u9519\\u3002\\u4ee5\\u540e\\u53ef\\u4ee5\\u4e0d\\u7528\\u518d\\u53bb\\u81ea\\u5df1\\u627e\\u4e86\\u3002"}',
                'read' => 1,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 15:43:24'),
                'updated_at' => seeders_at('2021-07-01 16:26:03'),
            ),
            2 =>
            array (
                'id' => 3,
                'dialog_id' => 13,
                'userid' => 1,
                'type' => 'text',
                'msg' => '{"text":"\\u5c06\\u5c0f\\u7ec4\\u8fd9\\u534a\\u5e74\\u7684\\u5de5\\u4f5c\\u76ee\\u6807\\u62c6\\u89e3\\uff0c\\u4ee5\\u4fbf\\u5de5\\u4f5c\\u66f4\\u80fd\\u6709\\u5e8f\\u8fdb\\u884c"}',
                'read' => 0,
                'send' => 0,
                'created_at' => seeders_at('2021-07-01 15:44:09'),
                'updated_at' => seeders_at('2021-07-01 15:44:09'),
            ),
            3 =>
            array (
                'id' => 4,
                'dialog_id' => 11,
                'userid' => 1,
                'type' => 'text',
                'msg' => '{"text":"\\u5404\\u6709\\u5404\\u7684\\u9700\\u6c42\\uff0c\\u4e00\\u76ee\\u4e86\\u7136\\uff0c\\u4e0d\\u9519\\uff01"}',
                'read' => 1,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 15:44:29'),
                'updated_at' => seeders_at('2021-07-01 16:31:37'),
            ),
            4 =>
            array (
                'id' => 5,
                'dialog_id' => 1,
                'userid' => 2,
                'type' => 'text',
                'msg' => '{"text":"\\u5df2\\u6536\\u5230\\u4fe1\\u606f\\uff01"}',
                'read' => 1,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:12:28'),
                'updated_at' => seeders_at('2021-07-01 16:21:28'),
            ),
            5 =>
            array (
                'id' => 6,
                'dialog_id' => 5,
                'userid' => 1,
                'type' => 'text',
                'msg' => '{"text":"test\\uff0c\\u6ce8\\u610f\\u770b\\u4e00\\u4e0b\\u4f60\\u7684\\u4efb\\u52a1\\u3002"}',
                'read' => 1,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:25:06'),
                'updated_at' => seeders_at('2021-07-01 16:25:43'),
            ),
            6 =>
            array (
                'id' => 7,
                'dialog_id' => 5,
                'userid' => 2,
                'type' => 'text',
                'msg' => '{"text":"\\u597d\\u7684\\uff01\\u4e0d\\u597d\\u610f\\u601d\\uff0c\\u521a\\u521a\\u5728\\u5fd9\\uff0c\\u6ca1\\u6ce8\\u610f\\u770b\\u6d88\\u606f\\u3002"}',
                'read' => 1,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:26:01'),
                'updated_at' => seeders_at('2021-07-01 16:27:44'),
            ),
            7 =>
            array (
                'id' => 8,
                'dialog_id' => 7,
                'userid' => 2,
                'type' => 'text',
                'msg' => '{"text":"\\u54c8\\u54c8"}',
                'read' => 1,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:26:34'),
                'updated_at' => seeders_at('2021-07-01 16:27:21'),
            ),
            8 =>
            array (
                'id' => 9,
                'dialog_id' => 7,
                'userid' => 2,
                'type' => 'text',
                'msg' => '{"text":"\\u4e0d\\u9519\\u4e0d\\u9519\\u3002\\u771f\\u7684\\u628a\\u5bb6\\u5e95\\u90fd\\u642c\\u51fa\\u6765\\u4e86\\u5440"}',
                'read' => 1,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:26:35'),
                'updated_at' => seeders_at('2021-07-01 16:27:21'),
            ),
            9 =>
            array (
                'id' => 10,
                'dialog_id' => 7,
                'userid' => 2,
                'type' => 'text',
                'msg' => '{"text":"\\u6211\\u4eec\\u4e5f\\u4e00\\u8d77\\u628a\\u81ea\\u5df1\\u7684\\u6536\\u85cf\\u90fd\\u8d21\\u732e\\u51fa\\u6765\\u5427"}',
                'read' => 1,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:26:59'),
                'updated_at' => seeders_at('2021-07-01 16:27:21'),
            ),
            10 =>
            array (
                'id' => 11,
                'dialog_id' => 7,
                'userid' => 1,
                'type' => 'text',
                'msg' => '{"text":"\\u5bf9\\u7684\\u3002\\u7fa4\\u4f17\\u7684\\u529b\\u91cf\\u6700\\u5f3a\\u5927\\u4e86\\ud83d\\udc4d\\ud83c\\udffb"}',
                'read' => 1,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:27:40'),
                'updated_at' => seeders_at('2021-07-01 16:32:00'),
            ),
            11 =>
            array (
                'id' => 12,
                'dialog_id' => 5,
                'userid' => 1,
                'type' => 'text',
                'msg' => '{"text":"\\ud83d\\ude1c\\ud83d\\ude1c\\ud83e\\udd1c"}',
                'read' => 1,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:28:24'),
                'updated_at' => seeders_at('2021-07-01 16:31:43'),
            ),
            12 =>
            array (
                'id' => 13,
                'dialog_id' => 1,
                'userid' => 1,
                'type' => 'text',
                'msg' => '{"text":"OK"}',
                'read' => 1,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:29:04'),
                'updated_at' => seeders_at('2021-07-01 16:31:43'),
            ),
            13 =>
            array (
                'id' => 14,
                'dialog_id' => 11,
                'userid' => 1,
                'type' => 'text',
                'msg' => '{"text":"test\\uff0c\\u6ce8\\u610f\\u8ddf\\u8fdb\\u9879\\u76ee\\u8fdb\\u5ea6\\uff01"}',
                'read' => 1,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:30:54'),
                'updated_at' => seeders_at('2021-07-01 16:31:37'),
            ),
            14 =>
            array (
                'id' => 15,
                'dialog_id' => 11,
                'userid' => 2,
                'type' => 'text',
                'msg' => '{"text":"OK"}',
                'read' => 0,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:31:40'),
                'updated_at' => seeders_at('2021-07-01 16:31:40'),
            ),
            15 =>
            array (
                'id' => 16,
                'dialog_id' => 5,
                'userid' => 2,
                'type' => 'text',
                'msg' => '{"text":"\\u5927\\u5bb6\\u4e00\\u8d77\\u52aa\\u529b\\u5440\\uff01\\uff01\\uff01\\uff01\\uff01\\uff01"}',
                'read' => 0,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:31:58'),
                'updated_at' => seeders_at('2021-07-01 16:31:58'),
            ),
            16 =>
            array (
                'id' => 17,
                'dialog_id' => 7,
                'userid' => 2,
                'type' => 'text',
                'msg' => '{"text":"\\u54c8\\u54c8\\u2026\\u2026"}',
                'read' => 0,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:32:08'),
                'updated_at' => seeders_at('2021-07-01 16:32:08'),
            ),
            17 =>
            array (
                'id' => 18,
                'dialog_id' => 7,
                'userid' => 2,
                'type' => 'text',
                'msg' => '{"text":"\\u77ac\\u95f4\\u611f\\u89c9\\u81ea\\u5df1\\u68d2\\u68d2\\u54d2"}',
                'read' => 0,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:32:24'),
                'updated_at' => seeders_at('2021-07-01 16:32:24'),
            ),
            18 =>
            array (
                'id' => 19,
                'dialog_id' => 7,
                'userid' => 2,
                'type' => 'text',
                'msg' => '{"text":"\\ud83d\\ude4c\\ud83d\\ude4c\\ud83d\\ude4c\\ud83d\\ude4c\\u270a\\u270a\\u270a\\u270a"}',
                'read' => 0,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:32:50'),
                'updated_at' => seeders_at('2021-07-01 16:32:50'),
            ),
            19 =>
            array (
                'id' => 20,
                'dialog_id' => 15,
                'userid' => 1,
                'type' => 'text',
                'msg' => '{"text":"\\u5927\\u5bb6\\u518d\\u52a0\\u628a\\u6cb9\\uff0c\\u4e89\\u53d6\\u65e9\\u65e5\\u5b8c\\u6210\\u4efb\\u52a1\\u3002"}',
                'read' => 1,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:36:31'),
                'updated_at' => seeders_at('2021-07-01 16:37:34'),
            ),
            20 =>
            array (
                'id' => 21,
                'dialog_id' => 15,
                'userid' => 2,
                'type' => 'text',
                'msg' => '{"text":"\\ud83e\\udd14\\ud83e\\udd14\\ud83e\\udd14\\ud83e\\udd14\\ud83e\\udd14\\ud83e\\udd14"}',
                'read' => 1,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:37:59'),
                'updated_at' => seeders_at('2021-07-01 17:12:23'),
            ),
            21 =>
            array (
                'id' => 22,
                'dialog_id' => 16,
                'userid' => 1,
                'type' => 'text',
                'msg' => '{"text":"\\u5e0c\\u671b\\u80fd\\u52a0\\u628a\\u5c3d\\u628a\\u8fd9\\u4e9b\\u95ee\\u9898\\u90fd\\u89e3\\u51b3\\u4e86"}',
                'read' => 1,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:56:05'),
                'updated_at' => seeders_at('2021-07-01 16:58:00'),
            ),
            22 =>
            array (
                'id' => 23,
                'dialog_id' => 17,
                'userid' => 1,
                'type' => 'text',
                'msg' => '{"text":"\\u521d\\u7a3f\\u5927\\u6982\\u4ec0\\u4e48\\u65f6\\u5019\\u53ef\\u4ee5\\u51fa\\u6765\\u5462\\uff1f"}',
                'read' => 1,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:56:35'),
                'updated_at' => seeders_at('2021-07-01 16:57:54'),
            ),
            23 =>
            array (
                'id' => 24,
                'dialog_id' => 18,
                'userid' => 1,
                'type' => 'text',
                'msg' => '{"text":"\\u8fd9\\u4e2a\\u7f51\\u7ad9\\u7684\\u8d44\\u6e90\\u771f\\u5fc3\\u4e0d\\u9519"}',
                'read' => 1,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:56:57'),
                'updated_at' => seeders_at('2021-07-01 16:57:46'),
            ),
            24 =>
            array (
                'id' => 25,
                'dialog_id' => 18,
                'userid' => 2,
                'type' => 'text',
                'msg' => '{"text":"\\u6211\\u4e5f\\u662f\\u8fd9\\u4e48\\u8ba4\\u4e3a\\u7684"}',
                'read' => 1,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:57:52'),
                'updated_at' => seeders_at('2021-07-01 17:12:20'),
            ),
            25 =>
            array (
                'id' => 26,
                'dialog_id' => 17,
                'userid' => 2,
                'type' => 'text',
                'msg' => '{"text":"\\u660e\\u5929"}',
                'read' => 1,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:57:58'),
                'updated_at' => seeders_at('2021-07-01 16:59:43'),
            ),
            26 =>
            array (
                'id' => 27,
                'dialog_id' => 16,
                'userid' => 2,
                'type' => 'text',
                'msg' => '{"text":"\\u597d\\u7684"}',
                'read' => 1,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 16:58:02'),
                'updated_at' => seeders_at('2021-07-01 16:59:42'),
            ),
            27 =>
            array (
                'id' => 28,
                'dialog_id' => 1,
                'userid' => 1,
                'type' => 'text',
                'msg' => '{"text":"\\u5173\\u6ce8\\u4e00\\u4e0b\\u6d88\\u606f\\u54c8"}',
                'read' => 0,
                'send' => 1,
                'created_at' => seeders_at('2021-07-01 17:12:52'),
                'updated_at' => seeders_at('2021-07-01 17:12:52'),
            ),
        ));


    }
}
