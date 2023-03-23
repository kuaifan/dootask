<?php

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use App\Models\ProjectLog;
use App\Module\Base;
use Illuminate\Database\Migrations\Migration;

class ProjectLogsRecordUserid extends Migration
{
    /**
     * 处理日志格式
     *
     * @return void
     */
    public function up()
    {
        ProjectLog::whereIn('detail', [
            '删除任务负责人',
            '删除子任务负责人',
            '删除任务协助人员',
            '删除子任务协助人员',
        ])->chunkById(100, function ($lists) {
            /** @var ProjectLog $log */
            foreach ($lists as $log) {
                $record = $log->record;
                if (is_string($record['userid']) && str_contains($record['userid'], ",")) {
                    $record['userid'] = Base::explodeInt($record['userid']);
                    $log->record = Base::array2json($record);
                    $log->save();
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
        //
    }
}
