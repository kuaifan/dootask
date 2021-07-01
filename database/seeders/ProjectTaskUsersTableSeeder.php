<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProjectTaskUsersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        if (\DB::table('project_task_users')->count() > 0) {
            return;
        }

        \DB::table('project_task_users')->insert(array (
            0 =>
            array (
                'id' => 1,
                'project_id' => 2,
                'task_id' => 1,
                'task_pid' => 1,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 10:49:07'),
                'updated_at' => seeders_at('2021-07-01 10:49:07'),
            ),
            1 =>
            array (
                'id' => 3,
                'project_id' => 2,
                'task_id' => 3,
                'task_pid' => 2,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 10:50:23'),
                'updated_at' => seeders_at('2021-07-01 10:50:23'),
            ),
            2 =>
            array (
                'id' => 4,
                'project_id' => 2,
                'task_id' => 4,
                'task_pid' => 2,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 10:50:28'),
                'updated_at' => seeders_at('2021-07-01 10:50:28'),
            ),
            3 =>
            array (
                'id' => 5,
                'project_id' => 2,
                'task_id' => 5,
                'task_pid' => 5,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 10:51:05'),
                'updated_at' => seeders_at('2021-07-01 10:51:05'),
            ),
            4 =>
            array (
                'id' => 6,
                'project_id' => 2,
                'task_id' => 6,
                'task_pid' => 6,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 10:52:00'),
                'updated_at' => seeders_at('2021-07-01 10:52:00'),
            ),
            5 =>
            array (
                'id' => 7,
                'project_id' => 2,
                'task_id' => 7,
                'task_pid' => 7,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 10:53:07'),
                'updated_at' => seeders_at('2021-07-01 10:53:07'),
            ),
            6 =>
            array (
                'id' => 8,
                'project_id' => 2,
                'task_id' => 8,
                'task_pid' => 8,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 10:53:44'),
                'updated_at' => seeders_at('2021-07-01 10:53:44'),
            ),
            7 =>
            array (
                'id' => 10,
                'project_id' => 3,
                'task_id' => 10,
                'task_pid' => 10,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:09:51'),
                'updated_at' => seeders_at('2021-07-01 11:09:51'),
            ),
            8 =>
            array (
                'id' => 11,
                'project_id' => 3,
                'task_id' => 11,
                'task_pid' => 11,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:10:22'),
                'updated_at' => seeders_at('2021-07-01 11:10:22'),
            ),
            9 =>
            array (
                'id' => 12,
                'project_id' => 3,
                'task_id' => 12,
                'task_pid' => 12,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:11:26'),
                'updated_at' => seeders_at('2021-07-01 11:11:26'),
            ),
            10 =>
            array (
                'id' => 13,
                'project_id' => 3,
                'task_id' => 13,
                'task_pid' => 13,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:12:31'),
                'updated_at' => seeders_at('2021-07-01 11:12:31'),
            ),
            11 =>
            array (
                'id' => 15,
                'project_id' => 3,
                'task_id' => 15,
                'task_pid' => 15,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:14:01'),
                'updated_at' => seeders_at('2021-07-01 11:14:01'),
            ),
            12 =>
            array (
                'id' => 16,
                'project_id' => 3,
                'task_id' => 16,
                'task_pid' => 16,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:14:41'),
                'updated_at' => seeders_at('2021-07-01 11:14:41'),
            ),
            13 =>
            array (
                'id' => 17,
                'project_id' => 3,
                'task_id' => 17,
                'task_pid' => 17,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:14:53'),
                'updated_at' => seeders_at('2021-07-01 11:14:53'),
            ),
            14 =>
            array (
                'id' => 18,
                'project_id' => 3,
                'task_id' => 18,
                'task_pid' => 18,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:15:31'),
                'updated_at' => seeders_at('2021-07-01 11:15:31'),
            ),
            15 =>
            array (
                'id' => 19,
                'project_id' => 3,
                'task_id' => 19,
                'task_pid' => 19,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:16:31'),
                'updated_at' => seeders_at('2021-07-01 11:16:31'),
            ),
            16 =>
            array (
                'id' => 20,
                'project_id' => 3,
                'task_id' => 20,
                'task_pid' => 20,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:16:43'),
                'updated_at' => seeders_at('2021-07-01 11:16:43'),
            ),
            17 =>
            array (
                'id' => 21,
                'project_id' => 3,
                'task_id' => 21,
                'task_pid' => 21,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:16:52'),
                'updated_at' => seeders_at('2021-07-01 11:16:52'),
            ),
            18 =>
            array (
                'id' => 23,
                'project_id' => 3,
                'task_id' => 23,
                'task_pid' => 23,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:17:27'),
                'updated_at' => seeders_at('2021-07-01 11:17:27'),
            ),
            19 =>
            array (
                'id' => 24,
                'project_id' => 3,
                'task_id' => 24,
                'task_pid' => 24,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:17:52'),
                'updated_at' => seeders_at('2021-07-01 11:17:52'),
            ),
            20 =>
            array (
                'id' => 25,
                'project_id' => 3,
                'task_id' => 25,
                'task_pid' => 25,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:23:18'),
                'updated_at' => seeders_at('2021-07-01 11:23:18'),
            ),
            21 =>
            array (
                'id' => 26,
                'project_id' => 3,
                'task_id' => 26,
                'task_pid' => 26,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:23:29'),
                'updated_at' => seeders_at('2021-07-01 11:23:29'),
            ),
            22 =>
            array (
                'id' => 27,
                'project_id' => 3,
                'task_id' => 27,
                'task_pid' => 27,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:23:54'),
                'updated_at' => seeders_at('2021-07-01 11:23:54'),
            ),
            23 =>
            array (
                'id' => 30,
                'project_id' => 2,
                'task_id' => 30,
                'task_pid' => 30,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:26:33'),
                'updated_at' => seeders_at('2021-07-01 11:26:33'),
            ),
            24 =>
            array (
                'id' => 31,
                'project_id' => 2,
                'task_id' => 31,
                'task_pid' => 31,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:26:46'),
                'updated_at' => seeders_at('2021-07-01 11:26:46'),
            ),
            25 =>
            array (
                'id' => 32,
                'project_id' => 3,
                'task_id' => 32,
                'task_pid' => 32,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:27:11'),
                'updated_at' => seeders_at('2021-07-01 11:27:11'),
            ),
            26 =>
            array (
                'id' => 34,
                'project_id' => 3,
                'task_id' => 34,
                'task_pid' => 34,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:27:47'),
                'updated_at' => seeders_at('2021-07-01 11:27:47'),
            ),
            27 =>
            array (
                'id' => 38,
                'project_id' => 3,
                'task_id' => 38,
                'task_pid' => 38,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:29:17'),
                'updated_at' => seeders_at('2021-07-01 11:29:17'),
            ),
            28 =>
            array (
                'id' => 40,
                'project_id' => 3,
                'task_id' => 40,
                'task_pid' => 40,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:30:10'),
                'updated_at' => seeders_at('2021-07-01 11:30:10'),
            ),
            29 =>
            array (
                'id' => 42,
                'project_id' => 3,
                'task_id' => 42,
                'task_pid' => 42,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:31:19'),
                'updated_at' => seeders_at('2021-07-01 11:31:19'),
            ),
            30 =>
            array (
                'id' => 43,
                'project_id' => 4,
                'task_id' => 43,
                'task_pid' => 43,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:43:35'),
                'updated_at' => seeders_at('2021-07-01 11:43:35'),
            ),
            31 =>
            array (
                'id' => 44,
                'project_id' => 4,
                'task_id' => 44,
                'task_pid' => 44,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:43:52'),
                'updated_at' => seeders_at('2021-07-01 11:43:52'),
            ),
            32 =>
            array (
                'id' => 45,
                'project_id' => 4,
                'task_id' => 45,
                'task_pid' => 45,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:44:00'),
                'updated_at' => seeders_at('2021-07-01 11:44:00'),
            ),
            33 =>
            array (
                'id' => 46,
                'project_id' => 4,
                'task_id' => 46,
                'task_pid' => 46,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:44:13'),
                'updated_at' => seeders_at('2021-07-01 11:44:13'),
            ),
            34 =>
            array (
                'id' => 47,
                'project_id' => 2,
                'task_id' => 47,
                'task_pid' => 47,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 11:53:43'),
                'updated_at' => seeders_at('2021-07-01 11:53:43'),
            ),
            35 =>
            array (
                'id' => 52,
                'project_id' => 4,
                'task_id' => 54,
                'task_pid' => 54,
                'userid' => 1,
                'owner' => 0,
                'created_at' => seeders_at('2021-07-01 14:01:27'),
                'updated_at' => seeders_at('2021-07-01 14:01:27'),
            ),
            36 =>
            array (
                'id' => 53,
                'project_id' => 4,
                'task_id' => 48,
                'task_pid' => 48,
                'userid' => 1,
                'owner' => 0,
                'created_at' => seeders_at('2021-07-01 14:01:51'),
                'updated_at' => seeders_at('2021-07-01 14:01:51'),
            ),
            37 =>
            array (
                'id' => 54,
                'project_id' => 4,
                'task_id' => 49,
                'task_pid' => 49,
                'userid' => 1,
                'owner' => 0,
                'created_at' => seeders_at('2021-07-01 14:01:59'),
                'updated_at' => seeders_at('2021-07-01 14:01:59'),
            ),
            38 =>
            array (
                'id' => 55,
                'project_id' => 4,
                'task_id' => 50,
                'task_pid' => 50,
                'userid' => 1,
                'owner' => 0,
                'created_at' => seeders_at('2021-07-01 14:02:09'),
                'updated_at' => seeders_at('2021-07-01 14:02:09'),
            ),
            39 =>
            array (
                'id' => 56,
                'project_id' => 4,
                'task_id' => 51,
                'task_pid' => 51,
                'userid' => 1,
                'owner' => 0,
                'created_at' => seeders_at('2021-07-01 14:02:22'),
                'updated_at' => seeders_at('2021-07-01 14:02:22'),
            ),
            40 =>
            array (
                'id' => 57,
                'project_id' => 4,
                'task_id' => 53,
                'task_pid' => 53,
                'userid' => 1,
                'owner' => 0,
                'created_at' => seeders_at('2021-07-01 14:02:33'),
                'updated_at' => seeders_at('2021-07-01 14:02:33'),
            ),
            41 =>
            array (
                'id' => 58,
                'project_id' => 6,
                'task_id' => 77,
                'task_pid' => 77,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 15:38:36'),
                'updated_at' => seeders_at('2021-07-01 15:38:57'),
            ),
            42 =>
            array (
                'id' => 59,
                'project_id' => 6,
                'task_id' => 78,
                'task_pid' => 78,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 15:39:38'),
                'updated_at' => seeders_at('2021-07-01 15:39:38'),
            ),
            43 =>
            array (
                'id' => 60,
                'project_id' => 6,
                'task_id' => 79,
                'task_pid' => 79,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 15:40:13'),
                'updated_at' => seeders_at('2021-07-01 15:40:13'),
            ),
            44 =>
            array (
                'id' => 61,
                'project_id' => 6,
                'task_id' => 80,
                'task_pid' => 80,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 15:40:45'),
                'updated_at' => seeders_at('2021-07-01 15:40:45'),
            ),
            45 =>
            array (
                'id' => 62,
                'project_id' => 6,
                'task_id' => 81,
                'task_pid' => 80,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 15:41:34'),
                'updated_at' => seeders_at('2021-07-01 15:41:34'),
            ),
            46 =>
            array (
                'id' => 63,
                'project_id' => 6,
                'task_id' => 82,
                'task_pid' => 80,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 15:41:37'),
                'updated_at' => seeders_at('2021-07-01 15:41:37'),
            ),
            47 =>
            array (
                'id' => 64,
                'project_id' => 6,
                'task_id' => 83,
                'task_pid' => 80,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 15:41:40'),
                'updated_at' => seeders_at('2021-07-01 15:41:40'),
            ),
            48 =>
            array (
                'id' => 65,
                'project_id' => 6,
                'task_id' => 84,
                'task_pid' => 84,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 15:42:06'),
                'updated_at' => seeders_at('2021-07-01 15:42:06'),
            ),
            49 =>
            array (
                'id' => 66,
                'project_id' => 5,
                'task_id' => 69,
                'task_pid' => 69,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 15:44:47'),
                'updated_at' => seeders_at('2021-07-01 15:44:47'),
            ),
            50 =>
            array (
                'id' => 67,
                'project_id' => 7,
                'task_id' => 85,
                'task_pid' => 85,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 16:16:12'),
                'updated_at' => seeders_at('2021-07-01 16:16:12'),
            ),
            51 =>
            array (
                'id' => 68,
                'project_id' => 7,
                'task_id' => 86,
                'task_pid' => 85,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 16:16:40'),
                'updated_at' => seeders_at('2021-07-01 16:16:40'),
            ),
            52 =>
            array (
                'id' => 69,
                'project_id' => 7,
                'task_id' => 87,
                'task_pid' => 85,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 16:16:45'),
                'updated_at' => seeders_at('2021-07-01 16:16:45'),
            ),
            53 =>
            array (
                'id' => 70,
                'project_id' => 7,
                'task_id' => 88,
                'task_pid' => 85,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 16:16:51'),
                'updated_at' => seeders_at('2021-07-01 16:16:51'),
            ),
            54 =>
            array (
                'id' => 71,
                'project_id' => 7,
                'task_id' => 89,
                'task_pid' => 85,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 16:16:54'),
                'updated_at' => seeders_at('2021-07-01 16:16:54'),
            ),
            55 =>
            array (
                'id' => 72,
                'project_id' => 7,
                'task_id' => 90,
                'task_pid' => 85,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 16:17:01'),
                'updated_at' => seeders_at('2021-07-01 16:17:01'),
            ),
            56 =>
            array (
                'id' => 73,
                'project_id' => 7,
                'task_id' => 91,
                'task_pid' => 85,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 16:17:03'),
                'updated_at' => seeders_at('2021-07-01 16:17:03'),
            ),
            57 =>
            array (
                'id' => 74,
                'project_id' => 7,
                'task_id' => 92,
                'task_pid' => 92,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 16:18:11'),
                'updated_at' => seeders_at('2021-07-01 16:18:11'),
            ),
            58 =>
            array (
                'id' => 76,
                'project_id' => 7,
                'task_id' => 93,
                'task_pid' => 93,
                'userid' => 2,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 16:23:49'),
                'updated_at' => seeders_at('2021-07-01 16:23:49'),
            ),
            59 =>
            array (
                'id' => 77,
                'project_id' => 7,
                'task_id' => 93,
                'task_pid' => 93,
                'userid' => 1,
                'owner' => 0,
                'created_at' => seeders_at('2021-07-01 16:23:57'),
                'updated_at' => seeders_at('2021-07-01 16:23:57'),
            ),
            60 =>
            array (
                'id' => 78,
                'project_id' => 2,
                'task_id' => 6,
                'task_pid' => 6,
                'userid' => 2,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 16:24:16'),
                'updated_at' => seeders_at('2021-07-01 16:24:16'),
            ),
            61 =>
            array (
                'id' => 79,
                'project_id' => 2,
                'task_id' => 2,
                'task_pid' => 2,
                'userid' => 2,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 16:24:47'),
                'updated_at' => seeders_at('2021-07-01 16:24:47'),
            ),
            62 =>
            array (
                'id' => 81,
                'project_id' => 5,
                'task_id' => 71,
                'task_pid' => 71,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 16:29:55'),
                'updated_at' => seeders_at('2021-07-01 16:30:06'),
            ),
            63 =>
            array (
                'id' => 82,
                'project_id' => 5,
                'task_id' => 71,
                'task_pid' => 71,
                'userid' => 2,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 16:30:21'),
                'updated_at' => seeders_at('2021-07-01 16:30:21'),
            ),
            64 =>
            array (
                'id' => 86,
                'project_id' => 5,
                'task_id' => 94,
                'task_pid' => 94,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 16:44:53'),
                'updated_at' => seeders_at('2021-07-01 16:44:53'),
            ),
            65 =>
            array (
                'id' => 88,
                'project_id' => 5,
                'task_id' => 70,
                'task_pid' => 70,
                'userid' => 2,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 16:48:33'),
                'updated_at' => seeders_at('2021-07-01 16:48:33'),
            ),
            66 =>
            array (
                'id' => 89,
                'project_id' => 6,
                'task_id' => 76,
                'task_pid' => 76,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 16:48:45'),
                'updated_at' => seeders_at('2021-07-01 16:48:45'),
            ),
            67 =>
            array (
                'id' => 90,
                'project_id' => 3,
                'task_id' => 9,
                'task_pid' => 9,
                'userid' => 1,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 16:49:12'),
                'updated_at' => seeders_at('2021-07-01 16:49:12'),
            ),
            68 =>
            array (
                'id' => 91,
                'project_id' => 3,
                'task_id' => 9,
                'task_pid' => 9,
                'userid' => 2,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 16:49:16'),
                'updated_at' => seeders_at('2021-07-01 16:49:16'),
            ),
            69 =>
            array (
                'id' => 92,
                'project_id' => 3,
                'task_id' => 14,
                'task_pid' => 14,
                'userid' => 2,
                'owner' => 1,
                'created_at' => seeders_at('2021-07-01 16:49:29'),
                'updated_at' => seeders_at('2021-07-01 16:49:29'),
            ),
        ));


    }
}
