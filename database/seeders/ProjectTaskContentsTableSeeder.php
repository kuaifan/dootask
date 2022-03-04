<?php

namespace Database\Seeders;

use App\Models\ProjectTaskContent;
use App\Module\Base;
use Illuminate\Database\Seeder;

class ProjectTaskContentsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        if (\DB::table('project_task_contents')->count() > 0) {
            return;
        }

        \DB::table('project_task_contents')->insert(array (
            0 =>
            array (
                'id' => 1,
                'project_id' => 3,
                'task_id' => 9,
                'content' => 'https://sketchrepo.com/',
                'created_at' => seeders_at('2021-07-01 11:08:30'),
                'updated_at' => seeders_at('2021-07-01 11:08:30'),
            ),
            1 =>
            array (
                'id' => 2,
                'project_id' => 3,
                'task_id' => 10,
                'content' => 'https://psdrepo.com/',
                'created_at' => seeders_at('2021-07-01 11:10:05'),
                'updated_at' => seeders_at('2021-07-01 11:10:05'),
            ),
            2 =>
            array (
                'id' => 3,
                'project_id' => 3,
                'task_id' => 11,
                'content' => '<a>https://magdeleine.co/</a>',
                'created_at' => seeders_at('2021-07-01 11:11:05'),
                'updated_at' => seeders_at('2021-07-01 11:11:11'),
            ),
            3 =>
            array (
                'id' => 4,
                'project_id' => 3,
                'task_id' => 12,
                'content' => '<a>https://weheartit.com/</a>',
                'created_at' => seeders_at('2021-07-01 11:11:36'),
                'updated_at' => seeders_at('2021-07-01 11:11:36'),
            ),
            4 =>
            array (
                'id' => 5,
                'project_id' => 3,
                'task_id' => 18,
                'content' => '<a>http://wit-web.azurewebsites.net/assistor/download</a>',
                'created_at' => seeders_at('2021-07-01 11:15:40'),
                'updated_at' => seeders_at('2021-07-01 11:15:40'),
            ),
            5 =>
            array (
                'id' => 6,
                'project_id' => 3,
                'task_id' => 22,
                'content' => 'https://www.easyicon.net/',
                'created_at' => seeders_at('2021-07-01 11:17:13'),
                'updated_at' => seeders_at('2021-07-01 11:17:13'),
            ),
            6 =>
            array (
                'id' => 7,
                'project_id' => 3,
                'task_id' => 23,
                'content' => 'https://www.icondeposit.com/',
                'created_at' => seeders_at('2021-07-01 11:17:35'),
                'updated_at' => seeders_at('2021-07-01 11:17:35'),
            ),
            7 =>
            array (
                'id' => 8,
                'project_id' => 3,
                'task_id' => 17,
                'content' => 'https://guideguide.me/photoshop/',
                'created_at' => seeders_at('2021-07-01 11:18:17'),
                'updated_at' => seeders_at('2021-07-01 11:18:17'),
            ),
            8 =>
            array (
                'id' => 9,
                'project_id' => 3,
                'task_id' => 26,
                'content' => '<a>https://www.logolounge.com/</a>',
                'created_at' => seeders_at('2021-07-01 11:23:38'),
                'updated_at' => seeders_at('2021-07-01 11:23:38'),
            ),
            9 =>
            array (
                'id' => 10,
                'project_id' => 3,
                'task_id' => 27,
                'content' => 'https://www.logomoose.com/',
                'created_at' => seeders_at('2021-07-01 11:24:03'),
                'updated_at' => seeders_at('2021-07-01 11:24:10'),
            ),
            10 =>
            array (
                'id' => 11,
                'project_id' => 3,
                'task_id' => 25,
                'content' => '设计https://www.logaster.cn/',
                'created_at' => seeders_at('2021-07-01 11:24:26'),
                'updated_at' => seeders_at('2021-07-01 11:24:26'),
            ),
            11 =>
            array (
                'id' => 12,
                'project_id' => 3,
                'task_id' => 24,
                'content' => 'https://iconify.net/',
                'created_at' => seeders_at('2021-07-01 11:24:33'),
                'updated_at' => seeders_at('2021-07-01 11:24:33'),
            ),
            12 =>
            array (
                'id' => 13,
                'project_id' => 3,
                'task_id' => 16,
                'content' => 'http://retinize.it/',
                'created_at' => seeders_at('2021-07-01 11:24:39'),
                'updated_at' => seeders_at('2021-07-01 11:24:39'),
            ),
            13 =>
            array (
                'id' => 14,
                'project_id' => 3,
                'task_id' => 13,
                'content' => 'https://huaban.com/',
                'created_at' => seeders_at('2021-07-01 11:24:44'),
                'updated_at' => seeders_at('2021-07-01 11:24:44'),
            ),
            14 =>
            array (
                'id' => 15,
                'project_id' => 3,
                'task_id' => 14,
                'content' => 'https://wallhaven.typepad.com/',
                'created_at' => seeders_at('2021-07-01 11:24:51'),
                'updated_at' => seeders_at('2021-07-01 11:24:51'),
            ),
            15 =>
            array (
                'id' => 16,
                'project_id' => 3,
                'task_id' => 15,
                'content' => 'https://www.pexels.com/',
                'created_at' => seeders_at('2021-07-01 11:24:57'),
                'updated_at' => seeders_at('2021-07-01 11:24:57'),
            ),
            16 =>
            array (
                'id' => 17,
                'project_id' => 3,
                'task_id' => 28,
                'content' => 'http://www.logoed.co.uk/page/2/',
                'created_at' => seeders_at('2021-07-01 11:25:30'),
                'updated_at' => seeders_at('2021-07-01 11:25:30'),
            ),
            17 =>
            array (
                'id' => 18,
                'project_id' => 2,
                'task_id' => 30,
                'content' => '7777777',
                'created_at' => seeders_at('2021-07-01 11:26:41'),
                'updated_at' => seeders_at('2021-07-01 11:26:41'),
            ),
            18 =>
            array (
                'id' => 19,
                'project_id' => 3,
                'task_id' => 29,
                'content' => 'https://logooftheday.com/',
                'created_at' => seeders_at('2021-07-01 11:27:00'),
                'updated_at' => seeders_at('2021-07-01 11:27:00'),
            ),
            19 =>
            array (
                'id' => 20,
                'project_id' => 3,
                'task_id' => 33,
                'content' => 'LogoDesignLove：Logo设计技巧分享网',
                'created_at' => seeders_at('2021-07-01 11:27:43'),
                'updated_at' => seeders_at('2021-07-01 11:27:43'),
            ),
            20 =>
            array (
                'id' => 21,
                'project_id' => 3,
                'task_id' => 35,
                'content' => '<a>https://coolors.co/</a>',
                'created_at' => seeders_at('2021-07-01 11:28:29'),
                'updated_at' => seeders_at('2021-07-01 11:28:29'),
            ),
            21 =>
            array (
                'id' => 22,
                'project_id' => 3,
                'task_id' => 36,
                'content' => 'https://www.materialpalette.com/',
                'created_at' => seeders_at('2021-07-01 11:28:51'),
                'updated_at' => seeders_at('2021-07-01 11:28:51'),
            ),
            22 =>
            array (
                'id' => 23,
                'project_id' => 3,
                'task_id' => 37,
                'content' => 'https://www.bootcss.com/p/websafecolors/',
                'created_at' => seeders_at('2021-07-01 11:29:07'),
                'updated_at' => seeders_at('2021-07-01 11:29:07'),
            ),
            23 =>
            array (
                'id' => 24,
                'project_id' => 3,
                'task_id' => 38,
                'content' => 'https://www.colorzilla.com/',
                'created_at' => seeders_at('2021-07-01 11:29:31'),
                'updated_at' => seeders_at('2021-07-01 11:29:31'),
            ),
            24 =>
            array (
                'id' => 25,
                'project_id' => 3,
                'task_id' => 40,
                'content' => 'https://www.iconfont.cn/',
                'created_at' => seeders_at('2021-07-01 11:30:16'),
                'updated_at' => seeders_at('2021-07-01 11:30:16'),
            ),
            25 =>
            array (
                'id' => 26,
                'project_id' => 3,
                'task_id' => 41,
                'content' => 'https://www.iconfont.cn/',
                'created_at' => seeders_at('2021-07-01 11:30:41'),
                'updated_at' => seeders_at('2021-07-01 11:30:41'),
            ),
            26 =>
            array (
                'id' => 27,
                'project_id' => 4,
                'task_id' => 48,
                'content' => '',
                'created_at' => seeders_at('2021-07-01 11:58:10'),
                'updated_at' => seeders_at('2021-07-01 11:58:26'),
            ),
            27 =>
            array (
                'id' => 28,
                'project_id' => 4,
                'task_id' => 59,
                'content' => '',
                'created_at' => seeders_at('2021-07-01 12:04:08'),
                'updated_at' => seeders_at('2021-07-01 16:47:40'),
            ),
            28 =>
            array (
                'id' => 29,
                'project_id' => 3,
                'task_id' => 19,
                'content' => 'http://fontello.com/',
                'created_at' => seeders_at('2021-07-01 16:45:47'),
                'updated_at' => seeders_at('2021-07-01 16:45:47'),
            ),
            29 =>
            array (
                'id' => 30,
                'project_id' => 3,
                'task_id' => 20,
                'content' => 'https://www.iconfont.cn/',
                'created_at' => seeders_at('2021-07-01 16:45:52'),
                'updated_at' => seeders_at('2021-07-01 16:45:52'),
            ),
            30 =>
            array (
                'id' => 31,
                'project_id' => 3,
                'task_id' => 21,
                'content' => 'https://thenounproject.com/',
                'created_at' => seeders_at('2021-07-01 16:45:57'),
                'updated_at' => seeders_at('2021-07-01 16:45:57'),
            ),
        ));

        ProjectTaskContent::orderBy('id')->chunk(100, function($items) {
            /** @var ProjectTaskContent $item */
            foreach ($items as $item) {
                $content = Base::json2array($item->content);
                if (!isset($content['url'])) {
                    $item->content = Base::array2json([
                        'url' => ProjectTaskContent::saveContent($item->task_id, $item->content)
                    ]);
                    $item->save();
                }
            }
        });
    }
}
