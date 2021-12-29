<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FileUsersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        if (\DB::table('file_users')->count() > 0) {
            return;
        }

        \DB::table('file_users')->insert(array (
            0 =>
            array (
                'id' => 1,
                'file_id' => 1,
                'userid' => 0,
                'permission' => 1,
                'created_at' => seeders_at('2021-07-01 14:03:29'),
                'updated_at' => seeders_at('2021-07-01 14:03:29'),
            ),
            1 =>
            array (
                'id' => 2,
                'file_id' => 11,
                'userid' => 0,
                'permission' => 1,
                'created_at' => seeders_at('2021-07-01 14:03:29'),
                'updated_at' => seeders_at('2021-07-01 14:03:29'),
            ),
            2 =>
            array (
                'id' => 3,
                'file_id' => 12,
                'userid' => 0,
                'permission' => 1,
                'created_at' => seeders_at('2021-07-01 14:03:29'),
                'updated_at' => seeders_at('2021-07-01 14:03:29'),
            ),
            3 =>
            array (
                'id' => 4,
                'file_id' => 13,
                'userid' => 0,
                'permission' => 1,
                'created_at' => seeders_at('2021-07-01 14:03:29'),
                'updated_at' => seeders_at('2021-07-01 14:03:29'),
            ),
            4 =>
            array (
                'id' => 5,
                'file_id' => 15,
                'userid' => 0,
                'permission' => 1,
                'created_at' => seeders_at('2021-07-01 14:03:29'),
                'updated_at' => seeders_at('2021-07-01 14:03:29'),
            ),
            5 =>
            array (
                'id' => 6,
                'file_id' => 16,
                'userid' => 0,
                'permission' => 1,
                'created_at' => seeders_at('2021-07-01 14:03:29'),
                'updated_at' => seeders_at('2021-07-01 14:03:29'),
            ),
        ));


    }
}
