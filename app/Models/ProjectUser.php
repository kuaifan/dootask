<?php

namespace App\Models;

use App\Module\Base;

/**
 * App\Models\ProjectUser
 *
 * @property int $id
 * @property int|null $project_id 项目ID
 * @property int|null $userid 成员ID
 * @property int|null $owner 是否负责人
 * @property string|null $top_at 置顶时间
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereOwner($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereTopAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereUserid($value)
 * @mixin \Eloquent
 */
class ProjectUser extends AbstractModel
{

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function project(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Project::class, 'id', 'project_id');
    }

    /**
     * 移交项目身份
     * @param $originalUserid
     * @param $newUserid
     * @return void
     */
    public static function transfer($originalUserid, $newUserid)
    {
        self::whereUserid($originalUserid)->chunkById(100, function ($list) use ($newUserid) {
            /** @var self $item */
            foreach ($list as $item) {
                $row = self::whereProjectId($item->project_id)->whereUserid($newUserid)->first();
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
                if ($item->project) {
                    $item->project->addLog("移交项目身份给", ['userid' => $newUserid]);
                    $item->project->syncDialogUser();
                }
            }
        });
    }

    /**
     * 退出项目
     */
    public function exitProject()
    {
        ProjectTaskUser::whereProjectId($this->project_id)
            ->whereUserid($this->userid)
            ->chunk(100, function ($list) {
                $tastIds = [];
                /** @var ProjectTaskUser $item */
                foreach ($list as $item) {
                    $item->delete();
                    if (!in_array($item->task_pid, $tastIds)) {
                        $tastIds[] = $item->task_pid;
                        $item->projectTask?->syncDialogUser();
                    }
                }
            });
        $this->delete();
    }
}
