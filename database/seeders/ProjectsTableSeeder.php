<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProjectsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        if (\DB::table('projects')->count() > 0) {
            return;
        }

        \DB::table('projects')->insert(array (
            0 =>
            array (
                'id' => 1,
                'name' => '测试',
                'desc' => '',
                'userid' => 1,
                'user_simple' => '',
                'dialog_id' => 3,
                'archived_at' => NULL,
                'archived_userid' => 0,
                'created_at' => seeders_at('2021-07-01 10:46:47'),
                'updated_at' => seeders_at('2021-07-01 10:46:55'),
                'deleted_at' => seeders_at('2021-07-01 10:46:55'),
            ),
            1 =>
            array (
                'id' => 2,
                'name' => '🗓 项目进度管理',
                'desc' => '❓❗ 说明：将进度分成多级
每张卡片为一个项目任务，标签表示任务状况
通过将卡片拖至不同的进度列表下，来表示各项目进度',
                'userid' => 1,
                'user_simple' => '2|1,2',
                'dialog_id' => 5,
                'archived_at' => NULL,
                'archived_userid' => 0,
                'created_at' => seeders_at('2021-07-01 10:47:45'),
                'updated_at' => seeders_at('2021-07-01 16:42:23'),
                'deleted_at' => NULL,
            ),
            2 =>
            array (
                'id' => 3,
                'name' => '📇 设计必备网站',
                'desc' => '针对灵感缺乏的情况，把一些知名设计网站都梳理到了这个项目中。',
                'userid' => 1,
                'user_simple' => '2|1,2',
                'dialog_id' => 7,
                'archived_at' => NULL,
                'archived_userid' => 0,
                'created_at' => seeders_at('2021-07-01 11:02:57'),
                'updated_at' => seeders_at('2021-07-01 16:41:59'),
                'deleted_at' => NULL,
            ),
            3 =>
            array (
                'id' => 4,
                'name' => '⏰ GTD时间管理方法',
                'desc' => '将收集箱的事务进行判断。要立即执行：进入Q2列表。非立即执行：判断——1.不做（删掉）、2.稍晚再做（进入「稍后做」）、3.可做可不做的任务或可能有用的资源（进入「Mark」列表）。
2分钟内能做完贴上2分钟标签（进入「2分钟速战」列表）。2分钟以上做完的事务进入Q3。
判断能否一步做完，能进入Q4，不能打上多步标签（进入「项目清单」）；或将多步骤任务分解成多个一步做完任务，进入Q4。',
                'userid' => 1,
                'user_simple' => '1|1',
                'dialog_id' => 9,
                'archived_at' => NULL,
                'archived_userid' => 0,
                'created_at' => seeders_at('2021-07-01 11:43:01'),
                'updated_at' => seeders_at('2021-07-01 17:04:04'),
                'deleted_at' => NULL,
            ),
            4 =>
            array (
                'id' => 5,
                'name' => '💡产品迭代发布',
                'desc' => '团队真实使用的版本迭代模板，分阶段记录和展示需求设计与发布进度。很清晰，很直观。',
                'userid' => 1,
                'user_simple' => '2|1,2',
                'dialog_id' => 11,
                'archived_at' => NULL,
                'archived_userid' => 0,
                'created_at' => seeders_at('2021-07-01 15:33:23'),
                'updated_at' => seeders_at('2021-07-01 16:09:27'),
                'deleted_at' => NULL,
            ),
            5 =>
            array (
                'id' => 6,
                'name' => '🚩 里程碑管理',
                'desc' => '为一年12个月设置工作里程碑，目标为导向，抓住每个关键节点！',
                'userid' => 1,
                'user_simple' => '1|1',
                'dialog_id' => 13,
                'archived_at' => NULL,
                'archived_userid' => 0,
                'created_at' => seeders_at('2021-07-01 15:37:06'),
                'updated_at' => seeders_at('2021-07-01 17:03:29'),
                'deleted_at' => NULL,
            ),
            6 =>
            array (
                'id' => 7,
                'name' => '🏢 产品官网项目',
                'desc' => '设置各小组成员的工作列表，各自领取或领导分配任务，将做好的任务分期归档，方便复盘！',
                'userid' => 1,
                'user_simple' => '2|1,2',
                'dialog_id' => 15,
                'archived_at' => NULL,
                'archived_userid' => 0,
                'created_at' => seeders_at('2021-07-01 16:15:28'),
                'updated_at' => seeders_at('2021-07-01 17:04:46'),
                'deleted_at' => NULL,
            ),
        ));


    }
}
