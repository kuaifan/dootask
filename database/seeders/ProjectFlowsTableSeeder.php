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
                'created_at' => seeders_at('2021-07-01 12:27:12'),
                'updated_at' => seeders_at('2021-07-01 12:27:12'),
            ),
            1 =>
            array (
                'id' => 2,
                'project_id' => 3,
                'name' => 'Default',
                'created_at' => seeders_at('2021-07-01 12:27:12'),
                'updated_at' => seeders_at('2021-07-01 12:27:12'),
            ),
            2 =>
            array (
                'id' => 3,
                'project_id' => 4,
                'name' => 'Default',
                'created_at' => seeders_at('2021-07-01 12:27:12'),
                'updated_at' => seeders_at('2021-07-01 12:27:12'),
            ),
            3 =>
            array (
                'id' => 4,
                'project_id' => 5,
                'name' => 'Default',
                'created_at' => seeders_at('2021-07-01 12:27:12'),
                'updated_at' => seeders_at('2021-07-01 12:27:12'),
            ),
            4 =>
            array (
                'id' => 5,
                'project_id' => 6,
                'name' => 'Default',
                'created_at' => seeders_at('2021-07-01 12:27:12'),
                'updated_at' => seeders_at('2021-07-01 12:27:12'),
            ),
            5 =>
            array (
                'id' => 6,
                'project_id' => 7,
                'name' => 'Default',
                'created_at' => seeders_at('2021-07-01 12:27:12'),
                'updated_at' => seeders_at('2021-07-01 12:27:12'),
            ),
        ));


    }
}
