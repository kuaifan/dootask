<?php

namespace App\Models;

/**
 * App\Models\ProjectTaskUser
 *
 * @property int $id
 * @property int|null $project_id 项目ID
 * @property int|null $task_id 任务ID
 * @property int|null $task_pid 任务ID（如果是子任务则是父级任务ID）
 * @property int|null $userid 成员ID
 * @property int|null $owner 是否任务负责人
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProjectTask|null $projectTask
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser whereOwner($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser whereTaskPid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskUser whereUserid($value)
 * @mixin \Eloquent
 */
class ProjectTaskUser extends AbstractModel
{

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function projectTask(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProjectTask::class, 'id', 'task_id');
    }

    /**
     * 移交任务身份
     * @param $originalUserid
     * @param $newUserid
     * @return void
     */
    public static function transfer($originalUserid, $newUserid)
    {
        self::whereUserid($originalUserid)->chunkById(100, function ($list) use ($originalUserid, $newUserid) {
            $tastIds = [];
            /** @var self $item */
            foreach ($list as $item) {
                $row = self::whereTaskId($item->task_id)->whereUserid($newUserid)->first();
                if ($row) {
                    // 已存在则删除原数据，判断改变已存在的数据
                    $row->owner = max($row->owner, $item->owner);
                    $row->save();
                    $item->delete();
                } else {
                    // 不存在则改变原数据
                    $item->userid = $newUserid;
                    $item->save();
                }
                if ($item->projectTask) {
                    $item->projectTask->addLog("移交{任务}身份", ['userid' => [$originalUserid, ' => ', $newUserid]], 0, 1);
                    if (!in_array($item->task_pid, $tastIds)) {
                        $tastIds[] = $item->task_pid;
                        $item->projectTask->syncDialogUser();
                    }
                }
            }
        });
    }
}
