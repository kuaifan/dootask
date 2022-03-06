<?php

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use App\Models\ProjectTaskContent;
use App\Module\Base;
use Illuminate\Database\Migrations\Migration;

class ProjectTaskContentsUpdateContent extends Migration
{
    /**
     * 任务详细描述保存至文件
     * @return void
     */
    public function up()
    {
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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // ... 退回去意义不大，文件内容不做回滚操作
    }
}
