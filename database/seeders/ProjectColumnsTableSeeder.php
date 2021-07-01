<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProjectColumnsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        if (\DB::table('project_columns')->count() > 0) {
            return;
        }

        \DB::table('project_columns')->insert(array (
            0 =>
            array (
                'id' => 1,
                'project_id' => 1,
                'name' => 'Default',
                'color' => '',
                'sort' => 0,
                'created_at' => seeders_at('2021-07-01 10:46:47'),
                'updated_at' => seeders_at('2021-07-01 10:46:55'),
                'deleted_at' => seeders_at('2021-07-01 10:46:55'),
            ),
            1 =>
            array (
                'id' => 2,
                'project_id' => 2,
                'name' => '🛒 预备项目',
                'color' => '',
                'sort' => 0,
                'created_at' => seeders_at('2021-07-01 10:47:45'),
                'updated_at' => seeders_at('2021-07-01 10:49:25'),
                'deleted_at' => NULL,
            ),
            2 =>
            array (
                'id' => 3,
                'project_id' => 2,
                'name' => '🛴 进度【20%】■□□□□',
                'color' => '',
                'sort' => 1,
                'created_at' => seeders_at('2021-07-01 10:50:47'),
                'updated_at' => seeders_at('2021-07-01 10:50:47'),
                'deleted_at' => NULL,
            ),
            3 =>
            array (
                'id' => 4,
                'project_id' => 2,
                'name' => '🚅 进度【60%】■■■□□',
                'color' => '',
                'sort' => 2,
                'created_at' => seeders_at('2021-07-01 10:51:47'),
                'updated_at' => seeders_at('2021-07-01 10:51:47'),
                'deleted_at' => NULL,
            ),
            4 =>
            array (
                'id' => 5,
                'project_id' => 2,
                'name' => '✈️ 进度【80%】■■■■□',
                'color' => '',
                'sort' => 3,
                'created_at' => seeders_at('2021-07-01 10:52:20'),
                'updated_at' => seeders_at('2021-07-01 10:52:47'),
                'deleted_at' => NULL,
            ),
            5 =>
            array (
                'id' => 6,
                'project_id' => 2,
                'name' => '🥂 进度【100%】■■■■■',
                'color' => '',
                'sort' => 4,
                'created_at' => seeders_at('2021-07-01 10:53:16'),
                'updated_at' => seeders_at('2021-07-01 10:53:16'),
                'deleted_at' => NULL,
            ),
            6 =>
            array (
                'id' => 7,
                'project_id' => 3,
                'name' => '灵感来源',
                'color' => '',
                'sort' => 0,
                'created_at' => seeders_at('2021-07-01 11:02:57'),
                'updated_at' => seeders_at('2021-07-01 11:07:38'),
                'deleted_at' => NULL,
            ),
            7 =>
            array (
                'id' => 8,
                'project_id' => 3,
                'name' => '📷 高清图库',
                'color' => '',
                'sort' => 1,
                'created_at' => seeders_at('2021-07-01 11:12:17'),
                'updated_at' => seeders_at('2021-07-01 11:12:17'),
                'deleted_at' => NULL,
            ),
            8 =>
            array (
                'id' => 9,
                'project_id' => 3,
                'name' => 'Photoshop插件',
                'color' => '',
                'sort' => 2,
                'created_at' => seeders_at('2021-07-01 11:14:23'),
                'updated_at' => seeders_at('2021-07-01 11:14:23'),
                'deleted_at' => NULL,
            ),
            9 =>
            array (
                'id' => 10,
                'project_id' => 3,
                'name' => 'icon图标',
                'color' => '',
                'sort' => 3,
                'created_at' => seeders_at('2021-07-01 11:16:21'),
                'updated_at' => seeders_at('2021-07-01 11:16:21'),
                'deleted_at' => NULL,
            ),
            10 =>
            array (
                'id' => 11,
                'project_id' => 3,
                'name' => 'Logo 设计',
                'color' => '',
                'sort' => 4,
                'created_at' => seeders_at('2021-07-01 11:23:07'),
                'updated_at' => seeders_at('2021-07-01 11:23:07'),
                'deleted_at' => NULL,
            ),
            11 =>
            array (
                'id' => 12,
                'project_id' => 3,
                'name' => '配色方案',
                'color' => '',
                'sort' => 5,
                'created_at' => seeders_at('2021-07-01 11:28:11'),
                'updated_at' => seeders_at('2021-07-01 11:28:11'),
                'deleted_at' => NULL,
            ),
            12 =>
            array (
                'id' => 13,
                'project_id' => 4,
                'name' => 'GTD用法说明',
                'color' => '',
                'sort' => 0,
                'created_at' => seeders_at('2021-07-01 11:43:01'),
                'updated_at' => seeders_at('2021-07-01 13:56:52'),
                'deleted_at' => NULL,
            ),
            13 =>
            array (
                'id' => 14,
                'project_id' => 4,
                'name' => '📒 收集箱',
                'color' => '',
                'sort' => 1,
                'created_at' => seeders_at('2021-07-01 12:00:51'),
                'updated_at' => seeders_at('2021-07-01 13:56:52'),
                'deleted_at' => NULL,
            ),
            14 =>
            array (
                'id' => 15,
                'project_id' => 4,
                'name' => '➹ 2分钟速战',
                'color' => '',
                'sort' => 2,
                'created_at' => seeders_at('2021-07-01 12:01:32'),
                'updated_at' => seeders_at('2021-07-01 13:56:52'),
                'deleted_at' => NULL,
            ),
            15 =>
            array (
                'id' => 16,
                'project_id' => 4,
                'name' => '✍ 执行清单',
                'color' => '',
                'sort' => 3,
                'created_at' => seeders_at('2021-07-01 12:02:29'),
                'updated_at' => seeders_at('2021-07-01 13:56:52'),
                'deleted_at' => NULL,
            ),
            16 =>
            array (
                'id' => 17,
                'project_id' => 4,
                'name' => '✿ 项目清单',
                'color' => '',
                'sort' => 4,
                'created_at' => seeders_at('2021-07-01 12:02:55'),
                'updated_at' => seeders_at('2021-07-01 13:56:52'),
                'deleted_at' => NULL,
            ),
            17 =>
            array (
                'id' => 18,
                'project_id' => 4,
                'name' => '✈ 等待清单',
                'color' => '',
                'sort' => 5,
                'created_at' => seeders_at('2021-07-01 13:56:22'),
                'updated_at' => seeders_at('2021-07-01 13:56:52'),
                'deleted_at' => NULL,
            ),
            18 =>
            array (
                'id' => 19,
                'project_id' => 4,
                'name' => '✿ 稍后做',
                'color' => '',
                'sort' => 6,
                'created_at' => seeders_at('2021-07-01 13:58:51'),
                'updated_at' => seeders_at('2021-07-01 13:58:51'),
                'deleted_at' => NULL,
            ),
            19 =>
            array (
                'id' => 20,
                'project_id' => 4,
                'name' => '✉️ Mark',
                'color' => '',
                'sort' => 7,
                'created_at' => seeders_at('2021-07-01 13:59:55'),
                'updated_at' => seeders_at('2021-07-01 14:00:08'),
                'deleted_at' => NULL,
            ),
            20 =>
            array (
                'id' => 21,
                'project_id' => 5,
                'name' => '需求池',
                'color' => '',
                'sort' => 0,
                'created_at' => seeders_at('2021-07-01 15:33:23'),
                'updated_at' => seeders_at('2021-07-01 15:35:13'),
                'deleted_at' => NULL,
            ),
            21 =>
            array (
                'id' => 22,
                'project_id' => 5,
                'name' => '调研池',
                'color' => '',
                'sort' => 1,
                'created_at' => seeders_at('2021-07-01 15:33:54'),
                'updated_at' => seeders_at('2021-07-01 15:35:13'),
                'deleted_at' => NULL,
            ),
            22 =>
            array (
                'id' => 23,
                'project_id' => 5,
                'name' => 'UX设计',
                'color' => '',
                'sort' => 2,
                'created_at' => seeders_at('2021-07-01 15:34:13'),
                'updated_at' => seeders_at('2021-07-01 15:35:13'),
                'deleted_at' => NULL,
            ),
            23 =>
            array (
                'id' => 24,
                'project_id' => 5,
                'name' => '版本确定',
                'color' => '',
                'sort' => 3,
                'created_at' => seeders_at('2021-07-01 15:34:33'),
                'updated_at' => seeders_at('2021-07-01 15:35:13'),
                'deleted_at' => NULL,
            ),
            24 =>
            array (
                'id' => 25,
                'project_id' => 5,
                'name' => '1.1.0（即将发布的版本号）',
                'color' => '',
                'sort' => 4,
                'created_at' => seeders_at('2021-07-01 15:34:40'),
                'updated_at' => seeders_at('2021-07-01 15:35:13'),
                'deleted_at' => NULL,
            ),
            25 =>
            array (
                'id' => 26,
                'project_id' => 6,
                'name' => '❓ 说明',
                'color' => '',
                'sort' => 0,
                'created_at' => seeders_at('2021-07-01 15:37:06'),
                'updated_at' => seeders_at('2021-07-01 15:37:16'),
                'deleted_at' => NULL,
            ),
            26 =>
            array (
                'id' => 27,
                'project_id' => 6,
                'name' => '⏰ 1月',
                'color' => NULL,
                'sort' => 1,
                'created_at' => seeders_at('2021-07-01 15:37:48'),
                'updated_at' => seeders_at('2021-07-01 17:42:54'),
                'deleted_at' => NULL,
            ),
            27 =>
            array (
                'id' => 28,
                'project_id' => 6,
                'name' => '⏰ 2月',
                'color' => '',
                'sort' => 2,
                'created_at' => seeders_at('2021-07-01 15:37:52'),
                'updated_at' => seeders_at('2021-07-01 15:37:52'),
                'deleted_at' => NULL,
            ),
            28 =>
            array (
                'id' => 29,
                'project_id' => 6,
                'name' => '⏰ 3月',
                'color' => '',
                'sort' => 3,
                'created_at' => seeders_at('2021-07-01 15:37:55'),
                'updated_at' => seeders_at('2021-07-01 15:37:55'),
                'deleted_at' => NULL,
            ),
            29 =>
            array (
                'id' => 30,
                'project_id' => 6,
                'name' => '⏰ 4月',
                'color' => '',
                'sort' => 4,
                'created_at' => seeders_at('2021-07-01 15:37:58'),
                'updated_at' => seeders_at('2021-07-01 15:37:58'),
                'deleted_at' => NULL,
            ),
            30 =>
            array (
                'id' => 31,
                'project_id' => 6,
                'name' => '⏰ 5月',
                'color' => '',
                'sort' => 5,
                'created_at' => seeders_at('2021-07-01 15:38:02'),
                'updated_at' => seeders_at('2021-07-01 15:38:02'),
                'deleted_at' => NULL,
            ),
            31 =>
            array (
                'id' => 32,
                'project_id' => 7,
                'name' => 'UI设计',
                'color' => '',
                'sort' => 0,
                'created_at' => seeders_at('2021-07-01 16:15:28'),
                'updated_at' => seeders_at('2021-07-01 16:15:45'),
                'deleted_at' => NULL,
            ),
            32 =>
            array (
                'id' => 33,
                'project_id' => 7,
                'name' => '前端',
                'color' => '',
                'sort' => 1,
                'created_at' => seeders_at('2021-07-01 16:18:25'),
                'updated_at' => seeders_at('2021-07-01 16:18:25'),
                'deleted_at' => NULL,
            ),
            33 =>
            array (
                'id' => 34,
                'project_id' => 7,
                'name' => '前端',
                'color' => '',
                'sort' => 2,
                'created_at' => seeders_at('2021-07-01 16:18:25'),
                'updated_at' => seeders_at('2021-07-01 16:19:05'),
                'deleted_at' => seeders_at('2021-07-01 16:19:05'),
            ),
            34 =>
            array (
                'id' => 35,
                'project_id' => 7,
                'name' => '前端',
                'color' => '',
                'sort' => 3,
                'created_at' => seeders_at('2021-07-01 16:18:25'),
                'updated_at' => seeders_at('2021-07-01 16:18:48'),
                'deleted_at' => seeders_at('2021-07-01 16:18:48'),
            ),
            35 =>
            array (
                'id' => 36,
                'project_id' => 7,
                'name' => '前端',
                'color' => '',
                'sort' => 4,
                'created_at' => seeders_at('2021-07-01 16:18:25'),
                'updated_at' => seeders_at('2021-07-01 16:18:45'),
                'deleted_at' => seeders_at('2021-07-01 16:18:45'),
            ),
            36 =>
            array (
                'id' => 37,
                'project_id' => 7,
                'name' => '前端',
                'color' => '',
                'sort' => 4,
                'created_at' => seeders_at('2021-07-01 16:18:25'),
                'updated_at' => seeders_at('2021-07-01 16:18:50'),
                'deleted_at' => seeders_at('2021-07-01 16:18:50'),
            ),
            37 =>
            array (
                'id' => 38,
                'project_id' => 7,
                'name' => '前端',
                'color' => '',
                'sort' => 5,
                'created_at' => seeders_at('2021-07-01 16:18:25'),
                'updated_at' => seeders_at('2021-07-01 16:18:52'),
                'deleted_at' => seeders_at('2021-07-01 16:18:52'),
            ),
            38 =>
            array (
                'id' => 39,
                'project_id' => 7,
                'name' => '前端',
                'color' => '',
                'sort' => 6,
                'created_at' => seeders_at('2021-07-01 16:18:25'),
                'updated_at' => seeders_at('2021-07-01 16:18:55'),
                'deleted_at' => seeders_at('2021-07-01 16:18:55'),
            ),
            39 =>
            array (
                'id' => 40,
                'project_id' => 7,
                'name' => '前端',
                'color' => '',
                'sort' => 7,
                'created_at' => seeders_at('2021-07-01 16:18:25'),
                'updated_at' => seeders_at('2021-07-01 16:18:57'),
                'deleted_at' => seeders_at('2021-07-01 16:18:57'),
            ),
            40 =>
            array (
                'id' => 41,
                'project_id' => 7,
                'name' => '前端',
                'color' => '',
                'sort' => 8,
                'created_at' => seeders_at('2021-07-01 16:18:26'),
                'updated_at' => seeders_at('2021-07-01 16:18:59'),
                'deleted_at' => seeders_at('2021-07-01 16:18:59'),
            ),
            41 =>
            array (
                'id' => 42,
                'project_id' => 7,
                'name' => '前端',
                'color' => '',
                'sort' => 9,
                'created_at' => seeders_at('2021-07-01 16:18:44'),
                'updated_at' => seeders_at('2021-07-01 16:19:02'),
                'deleted_at' => seeders_at('2021-07-01 16:19:02'),
            ),
        ));


    }
}
