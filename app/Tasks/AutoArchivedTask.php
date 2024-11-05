<?php
namespace App\Tasks;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use App\Models\ProjectTask;
use App\Module\Base;
use Carbon\Carbon;

/**
 * 完成的任务自动归档
 * Class AutoArchivedTask
 * @package App\Tasks
 */
class AutoArchivedTask extends AbstractTask
{

    public function __construct()
    {
        parent::__construct();
    }

    public function start()
    {
        $setting = Base::setting('system');
        if ($setting['auto_archived'] === 'open') {
            $archivedDay = floatval($setting['archived_day']);
            if ($archivedDay > 0) {
                $archivedDay = min(100, $archivedDay);
                $archivedTime = Carbon::now()->subDays($archivedDay);
                //获取已完成未归档的任务
                $taskLists = ProjectTask::whereNotNull('complete_at')
                    ->where('complete_at', '<=', $archivedTime)
                    ->where('archived_userid', 0)
                    ->whereNull('archived_at')
                    ->take(100)
                    ->get();
                /** @var ProjectTask $task */
                foreach ($taskLists AS $task) {
                    $task->archivedTask(Carbon::now(), true);
                }
            }
        }
    }

    public function end()
    {

    }
}
