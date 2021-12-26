<?php

namespace App\Models;

/**
 * App\Models\ProjectInvite
 *
 * @property int $id
 * @property int|null $project_id 项目ID
 * @property int|null $num 累计邀请
 * @property string|null $code 链接码
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read bool $already
 * @property-read \App\Models\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectInvite newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectInvite newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectInvite query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectInvite whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectInvite whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectInvite whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectInvite whereNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectInvite whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectInvite whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectInvite extends AbstractModel
{
    protected $appends = [
        'already',
    ];

    /**
     * 是否已加入
     * @return bool
     */
    public function getAlreadyAttribute()
    {
        if (!isset($this->appendattrs['already'])) {
            $this->appendattrs['already'] = false;
            if (User::userid()) {
                $this->appendattrs['already']  = (bool)$this->project?->projectUser?->where('userid', User::userid())->count();
            }
        }
        return $this->appendattrs['already'];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function project(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Project::class, 'id', 'project_id');
    }
}
