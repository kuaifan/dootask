<?php

namespace App\Tasks;

use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogUser;
use App\Module\Apps;
use App\Module\ZincSearch\ZincSearchDialogMsg;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * 同步聊天数据到ZincSearch
 */
class ZincSearchSyncTask extends AbstractTask
{
    private $action;

    private $data;

    public function __construct($action = null, $data = null)
    {
        parent::__construct(...func_get_args());
        $this->action = $action;
        $this->data = $data;
    }

    public function start()
    {
        if (!Apps::isInstalled("search")) {
            // 如果没有安装搜索模块，则不执行
            return;
        }

        switch ($this->action) {
            case 'sync':
                // 同步消息数据
                ZincSearchDialogMsg::sync(WebSocketDialogMsg::fillInstance($this->data));
                break;

            case 'delete':
                // 删除消息数据
                ZincSearchDialogMsg::delete(WebSocketDialogMsg::fillInstance($this->data));
                break;

            case 'userSync':
                // 同步用户数据
                ZincSearchDialogMsg::userSync(WebSocketDialogUser::fillInstance($this->data));
                break;

            case 'deleteUser':
                // 删除用户数据
                ZincSearchDialogMsg::delete(WebSocketDialogUser::fillInstance($this->data));
                break;

            default:
                // 增量更新
                $this->incrementalUpdate();
                break;
        }
    }

    /**
     * 增量更新
     * @return void
     */
    private function incrementalUpdate()
    {
        // 120分钟执行一次
        $time = intval(Cache::get("ZincSearchSyncTask:Time"));
        if (time() - $time < 120 * 60) {
            return;
        }

        // 执行开始，120分钟后缓存标记失效
        Cache::put("ZincSearchSyncTask:Time", time(), Carbon::now()->addMinutes(120));

        // 开始执行同步
        @shell_exec("php /var/www/artisan zinc:sync-user-msg --i");

        // 执行完成，5分钟后缓存标记失效（5分钟任务可重复执行）
        Cache::put("ZincSearchSyncTask:Time", time(), Carbon::now()->addMinutes(5));
    }

    public function end()
    {
    }
}
