<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProjectUsersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        if (\DB::table('project_users')->count() > 0) {
            return;
        }

        \DB::table('project_users')->insert(array (
            0 =>
            array (
                'id' => 1,
                'project_id' => 1,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 10:46:47'),
                'updated_at' => seeders_at('2021-07-01 10:46:47'),
            ),
            1 =>
            array (
                'id' => 2,
                'project_id' => 2,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 10:47:45'),
                'updated_at' => seeders_at('2021-07-01 10:47:45'),
            ),
            2 =>
            array (
                'id' => 3,
                'project_id' => 3,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:02:57'),
                'updated_at' => seeders_at('2021-07-01 11:02:57'),
            ),
            3 =>
            array (
                'id' => 4,
                'project_id' => 4,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:43:01'),
                'updated_at' => seeders_at('2021-07-01 11:43:01'),
            ),
            4 =>
            array (
                'id' => 5,
                'project_id' => 5,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 15:33:23'),
                'updated_at' => seeders_at('2021-07-01 15:33:23'),
            ),
            5 =>
            array (
                'id' => 6,
                'project_id' => 6,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 15:37:06'),
                'updated_at' => seeders_at('2021-07-01 15:37:06'),
            ),
            6 =>
            array (
                'id' => 7,
                'project_id' => 7,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 16:15:28'),
                'updated_at' => seeders_at('2021-07-01 16:15:28'),
            ),
            7 =>
            array (
                'id' => 8,
                'project_id' => 3,
                'userid' => 2,
                'owner' => 0,
                'created_at' => seeders_at('2021-07-01 16:22:42'),
                'updated_at' => seeders_at('2021-07-01 16:22:42'),
            ),
            8 =>
            array (
                'id' => 9,
                'project_id' => 2,
                'userid' => 2,
                'owner' => 0,
                'created_at' => seeders_at('2021-07-01 16:23:15'),
                'updated_at' => seeders_at('2021-07-01 16:23:15'),
            ),
            9 =>
            array (
                'id' => 10,
                'project_id' => 7,
                'userid' => 2,
                'owner' => 0,
                'created_at' => seeders_at('2021-07-01 16:23:40'),
                'updated_at' => seeders_at('2021-07-01 16:23:40'),
            ),
            10 =>
            array (
                'id' => 11,
                'project_id' => 5,
                'userid' => 2,
                'owner' => 0,
                'created_at' => seeders_at('2021-07-01 16:29:38'),
                'updated_at' => seeders_at('2021-07-01 16:29:38'),
            ),
        ));


    }
}
