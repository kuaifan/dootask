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
 * @property \Illuminate\Support\Carbon|null $top_at 置顶时间
 * @property int|null $sort 排序(ASC)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereOwner($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereTopAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectUser whereUserid($value)
 * @mixin \Eloquent
 */
class ProjectUser extends AbstractModel
{
    /** @var int 普通成员编码 */
    const OWNER_MEMBER = 0;
    /** @var int 项目负责人编码 */
    const OWNER_PRIMARY = 1;
    /** @var int 项目管理员编码 */
    const OWNER_DEPUTY = 2;

    /**
     * 是否项目负责人（owner=1）
     */
    public function isPrimaryOwner(): bool
    {
        return (int)$this->owner === self::OWNER_PRIMARY;
    }

    /**
     * 是否项目管理员（owner=2）
     */
    public function isDeputyOwner(): bool
    {
        return (int)$this->owner === self::OWNER_DEPUTY;
    }

    /**
     * 是否负责人（含项目管理员）
     */
    public function isOwner(): bool
    {
        return $this->isPrimaryOwner() || $this->isDeputyOwner();
    }

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
        $projectIds = [];
        // 移交项目身份
        self::whereUserid($originalUserid)->chunkById(100, function ($list) use ($originalUserid, $newUserid, &$projectIds) {
            /** @var self $item */
            foreach ($list as $item) {
                $row = self::whereProjectId($item->project_id)->whereUserid($newUserid)->first();
                if ($row) {
                    // 已存在：仅当离职用户是项目负责人（owner=1）时把接收人升为项目负责人；
                    // 离职用户是项目管理员（owner=2）时不传项目管理员身份给接收人（spec：项目管理员不替补）
                    if ((int)$item->owner === self::OWNER_PRIMARY) {
                        $row->owner = self::OWNER_PRIMARY;
                    }
                    // owner=2/0：保留接收人原有 owner 值不变
                    $row->save();
                    $item->delete();
                } else {
                    // 不存在：转移时如果离职用户是项目管理员，降级为普通成员（不带项目管理员身份过户给接收人）
                    if ((int)$item->owner === self::OWNER_DEPUTY) {
                        $item->owner = self::OWNER_MEMBER;
                    }
                    $item->userid = $newUserid;
                    $item->save();
                }
                if ($item->project) {
                    if ($item->project->personal) {
                        $name = User::userid2nickname($originalUserid) ?: ('ID:' . $originalUserid);
                        $item->project->name = "【{$name}】{$item->project->name}";
                        $item->project->save();
                    }
                    $item->project->addLog("移交项目身份", [
                        'change' => [
                            [
                                'type' => 'user',
                                'data' => $originalUserid
                            ],
                            [
                                'type' => 'user',
                                'data' => $newUserid
                            ],
                        ],
                    ]);
                    $item->project->syncDialogUser();
                    $projectIds[] = $item->project_id;
                }
            }
        });
        // 移交工作流状态负责人
        if ($projectIds) {
            ProjectFlowItem::whereIn('project_id', $projectIds)->chunkById(100, function ($list) use ($originalUserid, $newUserid) {
                /** @var ProjectFlowItem $item */
                foreach ($list as $item) {
                    if (in_array($originalUserid, $item->userids)) {
                        $userids = array_values(array_diff($item->userids, [$originalUserid]));
                        $item->userids = Base::array2json(array_merge($userids, [$newUserid]));
                        $item->save();
                    }
                }
            });
        }
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
