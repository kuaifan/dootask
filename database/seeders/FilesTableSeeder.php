<?php

namespace Database\Seeders;

use App\Models\File;
use App\Models\FileContent;
use App\Module\Base;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FilesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        if (\DB::table('files')->count() > 0) {
            return;
        }

        \DB::table('files')->insert(array (
            0 =>
            array (
                'id' => 1,
                'pid' => 0,
                'cid' => 0,
                'name' => '设计知识',
                'type' => 'folder',
                'ext' => '',
                'size' => 0,
                'userid' => 1,
                'share' => 1,
                'created_id' => 1,
                'created_at' => seeders_at('2021-07-01 14:03:29'),
                'updated_at' => seeders_at('2021-07-01 14:03:29'),
                'deleted_at' => NULL,
            ),
            1 =>
            array (
                'id' => 2,
                'pid' => 1,
                'cid' => 0,
                'name' => '如何搭建B端设计规范？',
                'type' => 'document',
                'ext' => '',
                'size' => 16976,
                'userid' => 1,
                'share' => 0,
                'created_id' => 1,
                'created_at' => seeders_at('2021-07-01 14:03:37'),
                'updated_at' => seeders_at('2021-07-01 14:17:28'),
                'deleted_at' => NULL,
            ),
            2 =>
            array (
                'id' => 3,
                'pid' => 1,
                'cid' => 0,
                'name' => '页面设计中的信息组织策略探索-言之有序',
                'type' => 'document',
                'ext' => '',
                'size' => 11971,
                'userid' => 1,
                'share' => 0,
                'created_id' => 1,
                'created_at' => seeders_at('2021-07-01 15:46:59'),
                'updated_at' => seeders_at('2021-07-01 15:49:14'),
                'deleted_at' => NULL,
            ),
            3 =>
            array (
                'id' => 4,
                'pid' => 1,
                'cid' => 0,
                'name' => '素材整理',
                'type' => 'folder',
                'ext' => '',
                'size' => 0,
                'userid' => 1,
                'share' => 0,
                'created_id' => 1,
                'created_at' => seeders_at('2021-07-01 15:49:43'),
                'updated_at' => seeders_at('2021-07-01 15:49:43'),
                'deleted_at' => NULL,
            ),
            4 =>
            array (
                'id' => 5,
                'pid' => 4,
                'cid' => 0,
                'name' => '配置静态IP地址',
                'type' => 'document',
                'ext' => '',
                'size' => 285,
                'userid' => 1,
                'share' => 0,
                'created_id' => 1,
                'created_at' => seeders_at('2021-07-01 15:49:50'),
                'updated_at' => seeders_at('2021-07-01 15:53:09'),
                'deleted_at' => NULL,
            ),
            5 =>
            array (
                'id' => 6,
                'pid' => 1,
                'cid' => 0,
                'name' => '脑图',
                'type' => 'mind',
                'ext' => '',
                'size' => 1947,
                'userid' => 1,
                'share' => 0,
                'created_id' => 1,
                'created_at' => seeders_at('2021-07-01 15:53:50'),
                'updated_at' => seeders_at('2021-07-01 16:11:39'),
                'deleted_at' => NULL,
            ),
            6 =>
            array (
                'id' => 7,
                'pid' => 1,
                'cid' => 0,
                'name' => '数据统计',
                'type' => 'excel',
                'ext' => 'xlsx',
                'size' => 8128,
                'userid' => 1,
                'share' => 0,
                'created_id' => 1,
                'created_at' => seeders_at('2021-07-01 15:54:58'),
                'updated_at' => seeders_at('2021-07-01 15:54:58'),
                'deleted_at' => NULL,
            ),
            7 =>
            array (
                'id' => 8,
                'pid' => 14,
                'cid' => 0,
                'name' => '会议纪要',
                'type' => 'document',
                'ext' => '',
                'size' => 8088,
                'userid' => 1,
                'share' => 0,
                'created_id' => 1,
                'created_at' => seeders_at('2021-07-01 15:56:09'),
                'updated_at' => seeders_at('2021-07-01 16:11:13'),
                'deleted_at' => NULL,
            ),
            8 =>
            array (
                'id' => 9,
                'pid' => 0,
                'cid' => 0,
                'name' => '部门周报',
                'type' => 'document',
                'ext' => '',
                'size' => 23266,
                'userid' => 1,
                'share' => 0,
                'created_id' => 1,
                'created_at' => seeders_at('2021-07-01 15:57:39'),
                'updated_at' => seeders_at('2021-07-01 15:57:56'),
                'deleted_at' => NULL,
            ),
            9 =>
            array (
                'id' => 10,
                'pid' => 13,
                'cid' => 0,
                'name' => '项目管理',
                'type' => 'excel',
                'ext' => 'xlsx',
                'size' => 8128,
                'userid' => 1,
                'share' => 0,
                'created_id' => 1,
                'created_at' => seeders_at('2021-07-01 15:58:30'),
                'updated_at' => seeders_at('2021-07-01 16:10:17'),
                'deleted_at' => NULL,
            ),
            10 =>
            array (
                'id' => 11,
                'pid' => 0,
                'cid' => 0,
                'name' => '工作计划',
                'type' => 'excel',
                'ext' => 'xlsx',
                'size' => 8128,
                'userid' => 1,
                'share' => 1,
                'created_id' => 1,
                'created_at' => seeders_at('2021-07-01 15:59:59'),
                'updated_at' => seeders_at('2021-07-01 16:00:28'),
                'deleted_at' => NULL,
            ),
            11 =>
            array (
                'id' => 12,
                'pid' => 0,
                'cid' => 0,
                'name' => '流程图',
                'type' => 'drawio',
                'ext' => '',
                'size' => 5418,
                'userid' => 1,
                'share' => 1,
                'created_id' => 1,
                'created_at' => seeders_at('2021-07-01 16:01:27'),
                'updated_at' => seeders_at('2021-07-01 16:03:06'),
                'deleted_at' => NULL,
            ),
            12 =>
            array (
                'id' => 13,
                'pid' => 0,
                'cid' => 0,
                'name' => '项目管理',
                'type' => 'folder',
                'ext' => '',
                'size' => 0,
                'userid' => 1,
                'share' => 1,
                'created_id' => 1,
                'created_at' => seeders_at('2021-07-01 16:10:08'),
                'updated_at' => seeders_at('2021-07-01 16:10:08'),
                'deleted_at' => NULL,
            ),
            13 =>
            array (
                'id' => 14,
                'pid' => 0,
                'cid' => 0,
                'name' => '会议纪要',
                'type' => 'folder',
                'ext' => '',
                'size' => 0,
                'userid' => 1,
                'share' => 0,
                'created_id' => 1,
                'created_at' => seeders_at('2021-07-01 16:11:08'),
                'updated_at' => seeders_at('2021-07-01 16:11:08'),
                'deleted_at' => NULL,
            ),
            14 =>
            array (
                'id' => 15,
                'pid' => 0,
                'cid' => 0,
                'name' => '会议发言',
                'type' => 'word',
                'ext' => 'docx',
                'size' => 10994,
                'userid' => 1,
                'share' => 1,
                'created_id' => 1,
                'created_at' => seeders_at('2021-07-01 16:15:08'),
                'updated_at' => seeders_at('2021-07-01 16:15:08'),
                'deleted_at' => NULL,
            ),
            15 =>
            array (
                'id' => 16,
                'pid' => 0,
                'cid' => 0,
                'name' => '产品介绍',
                'type' => 'ppt',
                'ext' => 'pptx',
                'size' => 26882,
                'userid' => 1,
                'share' => 1,
                'created_id' => 1,
                'created_at' => seeders_at('2021-07-01 16:16:08'),
                'updated_at' => seeders_at('2021-07-01 16:16:08'),
                'deleted_at' => NULL,
            ),
        ));


        File::whereIn('type', ['mind', 'drawio', 'document'])->where('ext', '')->orderBy('id')->chunk(100, function($files) {
            /** @var File $file */
            foreach ($files as $file) {
                $fileContent = FileContent::whereFid($file->id)->orderByDesc('id')->first();
                $contentArray = Base::json2array($fileContent?->content);
                $contentString = '';
                //
                switch ($file->type) {
                    case 'document':
                        $file->ext = $contentArray['type'] ?: 'md';
                        $contentString = $contentArray['content'];
                        break;
                    case 'drawio':
                        $file->ext = 'drawio';
                        $contentString = $contentArray['xml'];
                        break;
                    case 'mind':
                        $file->ext = 'mind';
                        $contentString = $fileContent?->content;
                        break;
                }
                $file->save();
                //
                $path = 'uploads/file/' . $file->type . '/' . date("Ym", Carbon::parse($file->created_at)->timestamp) . '/' . $file->id . '/' . md5($contentString);
                $save = public_path($path);
                Base::makeDir(dirname($save));
                file_put_contents($save, $contentString);
                $content = [
                    'type' => $file->ext,
                    'url' => $path
                ];
                //
                $content = FileContent::createInstance([
                    'fid' => $file->id,
                    'content' => $content,
                    'text' => $fileContent?->text,
                    'size' => $file->size,
                    'userid' => $file->userid,
                ]);
                $content->save();
            }
        });

        File::where('pid', '>', 0)->chunkById(100, function ($lists) {
            /** @var File $item */
            foreach ($lists as $item) {
                $item->saveBeforePids();
            }
        });
    }
}
