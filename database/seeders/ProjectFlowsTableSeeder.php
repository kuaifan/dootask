<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProjectFlowsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        if (\DB::table('project_flows')->count() > 0) {
            return;
        }

        \DB::table('project_flows')->insert(array (
            0 =>
            array (
                'id' => 1,
                'project_id' => 2,
                'name' => 'Default',
                'created_at' => '2022-01-15 23:43:15',
                'updated_at' => '2022-01-15 23:43:15',
            ),
            1 =>
            array (
                'id' => 2,
                'project_id' => 3,
                'name' => 'Default',
                'created_at' => '2022-01-15 23:43:23',
                'updated_at' => '2022-01-15 23:43:23',
            ),
            2 =>
            array (
                'id' => 3,
                'project_id' => 4,
                'name' => 'Default',
                'created_at' => '2022-01-15 23:43:28',
                'updated_at' => '2022-01-15 23:43:28',
            ),
            3 =>
            array (
                'id' => 4,
                'project_id' => 5,
                'name' => 'Default',
                'created_at' => '2022-01-15 23:43:34',
                'updated_at' => '2022-01-15 23:43:34',
            ),
            4 =>
            array (
                'id' => 5,
                'project_id' => 6,
                'name' => 'Default',
                'created_at' => '2022-01-15 23:43:40',
                'updated_at' => '2022-01-15 23:43:40',
            ),
            5 =>
            array (
                'id' => 6,
                'project_id' => 7,
                'name' => 'Default',
                'created_at' => '2022-01-15 23:43:45',
                'updated_at' => '2022-01-15 23:43:45',
            ),
        ));


    }
}
