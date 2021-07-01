<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WebSocketTmpMsgsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        if (\DB::table('web_socket_tmp_msgs')->count() > 0) {
            return;
        }

        \DB::table('web_socket_tmp_msgs')->insert(array (
            0 =>
            array (
                'id' => 17,
                'md5' => '4144a68494b6e7f331e2aca92351c3c5',
                'msg' => '{"type":"dialog","mode":"update","data":{"id":25,"dialog_id":18,"userid":2,"type":"text","msg":{"text":"\\u6211\\u4e5f\\u662f\\u8fd9\\u4e48\\u8ba4\\u4e3a\\u7684"},"read":1,"send":1,"created_at":"2021-07-01 16:57:52","percentage":100}}',
                'send' => 0,
                'create_id' => 2,
                'created_at' => '2021-07-01 17:12:20',
                'updated_at' => '2021-07-01 17:12:20',
            ),
            1 =>
            array (
                'id' => 18,
                'md5' => '9804e72c2a7010a6928d602e04cf27e1',
                'msg' => '{"type":"dialog","mode":"update","data":{"id":21,"dialog_id":15,"userid":2,"type":"text","msg":{"text":"\\ud83e\\udd14\\ud83e\\udd14\\ud83e\\udd14\\ud83e\\udd14\\ud83e\\udd14\\ud83e\\udd14"},"read":1,"send":1,"created_at":"2021-07-01 16:37:59","percentage":100}}',
                'send' => 0,
                'create_id' => 2,
                'created_at' => '2021-07-01 17:12:23',
                'updated_at' => '2021-07-01 17:12:23',
            ),
            2 =>
            array (
                'id' => 19,
                'md5' => 'c24dffddf5cff9cf1b264db75822f213',
                'msg' => '{"type":"dialog","mode":"add","data":{"id":28,"dialog_id":1,"userid":1,"type":"text","msg":{"text":"\\u5173\\u6ce8\\u4e00\\u4e0b\\u6d88\\u606f\\u54c8"},"read":0,"send":1,"created_at":"2021-07-01 17:12:52","percentage":0}}',
                'send' => 0,
                'create_id' => 2,
                'created_at' => '2021-07-01 17:12:52',
                'updated_at' => '2021-07-01 17:12:52',
            ),
        ));


    }
}
