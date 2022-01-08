<?php

namespace App\Models;

use App\Module\Base;

/**
 * App\Models\ProjectFlow
 *
 * @property int $id
 * @property int|null $project_id 项目ID
 * @property string|null $name 流程名称
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProjectFlowItem[] $projectFlowItem
 * @property-read int|null $project_flow_item_count
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlow query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlow whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlow whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectFlow whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectFlow extends AbstractModel
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function projectFlowItem(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProjectFlowItem::class, 'flow_id', 'id')->orderBy('sort');
    }

    public static function addFlow()
    {
        AbstractModel::transaction(function() {
            $projectFlow = ProjectFlow::whereProjectId($project->id)->first();
            if (empty($projectFlow)) {
                $projectFlow = ProjectFlow::createInstance([
                    'project_id' => $project->id,
                    'name' => 'Default'
                ]);
                if (!$projectFlow->save()) {
                    return Base::retError('工作流创建失败');
                }
            }
            //
            $ids = [];
            $idc = [];
            $hasStart = false;
            $hasEnd = false;
            foreach ($flows as $item) {
                $id = intval($item['id']);
                $turns = Base::arrayRetainInt($item['turns'] ?: [], true);
                $userids = Base::arrayRetainInt($item['userids'] ?: [], true);
                $flow = ProjectFlowItem::updateInsert([
                    'id' => $id,
                    'project_id' => $project->id,
                    'flow_id' => $projectFlow->id,
                ], [
                    'name' => trim($item['name']),
                    'status' => trim($item['status']),
                    'sort' => intval($item['sort']),
                    'turns' => $turns,
                    'userids' => $userids,
                ]);
                if ($flow) {
                    $ids[] = $flow->id;
                    if ($flow->id != $id) {
                        $idc[$id] = $flow->id;
                    }
                    if ($flow->status == 'start') {
                        $hasStart = true;
                    }
                    if ($flow->status == 'end') {
                        $hasEnd = true;
                    }
                }
            }
            if (!$hasStart) {
                return Base::retError('至少需要1个开始状态');
            }
            if (!$hasEnd) {
                return Base::retError('至少需要1个结束状态');
            }
            ProjectFlowItem::whereFlowId($projectFlow->id)->whereNotIn('id', $ids)->delete();
        });
        return Base::retSuccess("success");
    }
}
