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

    /**
     * @return mixed
     */
    public function deleteFlow()
    {
        return AbstractModel::transaction(function() {
            ProjectFlowItem::whereProjectId($this->project_id)->chunk(100, function($list) {
                foreach ($list as $item) {
                    $item->deleteFlowItem();
                }
            });
            return $this->delete();
        });
    }
}
