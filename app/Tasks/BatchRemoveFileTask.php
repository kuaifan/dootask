<?php

namespace App\Tasks;

use App\Exceptions\ApiException;
use App\Models\File;
use App\Models\Tmp;
use App\Models\User;
use App\Models\WebSocketTmpMsg;
use Carbon\Carbon;
use Log;
use Throwable;

/**
 * 删除过期临时数据任务
 * Class DeleteTmpTask
 * @package App\Tasks
 */
class BatchRemoveFileTask extends AbstractTask
{
    protected array $_ids = [];

    protected int $_userid;

    public function __construct(array $ids, $userid)
    {
        $this->_ids = $ids;
        $this->_userid = $userid;
    }

    public function start()
    {
        foreach ($this->_ids as $id) {
            Log::info("---------- $id ----------");
            Log::info("尝试删除Id为[$id]的文件");
            $file = File::find($id);
            if (empty($file)) {
                Log::warning("Id为[$id]的文件不存在或已被删除");
                continue;
            }
            Log::info("获取到文件名为[" . $file->name . "]，类型为[" . ( $file->type ?: $file->ext ) . "]");
            $permission = $file->getPermission($this->_userid);
            if ($permission < 1000) {
                Log::warning("文件[$id][" . $file->name . "]仅限所有者或创建者操作");
                continue;
            }
            try {
                $file->deleteFile();
                Log::info("删除Id为[$id]的文件成功");
            } catch (Throwable $throwable) {
                Log::error("删除Id为[$id]的文件失败，原因是：" . $throwable->getMessage());
            }
        }
    }
}
