<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        if (\DB::table('settings')->count() > 0) {
            return;
        }

        \DB::table('settings')->insert(array (
            0 =>
            array (
                'id' => 1,
                'name' => 'system',
                'desc' => '',
                'setting' => '{"reg":"open","login_code":"auto"}',
                'created_at' => '2021-05-31 11:05:06',
                'updated_at' => '2021-06-03 07:27:12',
            ),
            1 =>
            array (
                'id' => 2,
                'name' => 'priority',
                'desc' => '',
                'setting' => '[{"name":"\\u91cd\\u8981\\u4e14\\u7d27\\u6025","color":"#ED4014","days":1,"priority":1},{"name":"\\u91cd\\u8981\\u4e0d\\u7d27\\u6025","color":"#F16B62","days":3,"priority":2},{"name":"\\u7d27\\u6025\\u4e0d\\u91cd\\u8981","color":"#19C919","days":5,"priority":3},{"name":"\\u4e0d\\u91cd\\u8981\\u4e0d\\u7d27\\u6025","color":"#2D8CF0","days":7,"priority":4}]',
                'created_at' => '2021-06-03 08:04:30',
                'updated_at' => '2021-06-24 09:20:26',
            ),
        ));


    }
}
