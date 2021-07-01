<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WebSocketsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        if (\DB::table('web_sockets')->count() > 0) {
            return;
        }

        \DB::table('web_sockets')->insert(array (
            0 =>
            array (
                'id' => 707,
                'key' => '1d274f8a181854b719040773b874558f',
                'fd' => '118',
                'userid' => 1,
                'created_at' => seeders_at('2021-07-01 18:03:05'),
                'updated_at' => seeders_at('2021-07-01 18:03:05'),
            ),
            1 =>
            array (
                'id' => 708,
                'key' => '7fdff81ae562ac8e037b4a0103becbb3',
                'fd' => '119',
                'userid' => 1,
                'created_at' => seeders_at('2021-07-01 18:03:13'),
                'updated_at' => seeders_at('2021-07-01 18:03:13'),
            ),
            2 =>
            array (
                'id' => 709,
                'key' => '26823fece8755d2b60cf8f406e4a4ed9',
                'fd' => '120',
                'userid' => 1,
                'created_at' => seeders_at('2021-07-01 18:03:47'),
                'updated_at' => seeders_at('2021-07-01 18:03:47'),
            ),
        ));


    }
}
