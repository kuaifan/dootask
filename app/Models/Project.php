<?php

namespace App\Models;

use App\Module\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Project
 *
 * @package App\Models
 * @property int $id
 * @property string|null $name 名称
 * @property string|null $desc 描述、备注
 * @property int|null $userid 创建人
 * @property int|null $dialog_id 聊天会话ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProjectColumn[] $projectColumn
 * @property-read int|null $project_column_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProjectLog[] $projectLog
 * @property-read int|null $project_log_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProjectUser[] $projectUser
 * @property-read int|null $project_user_count
 * @method static \Illuminate\Database\Eloquent\Builder|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Project newQuery()
 * @method static \Illuminate\Database\Query\Builder|Project onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereUserid($value)
 * @method static \Illuminate\Database\Query\Builder|Project withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Project withoutTrashed()
 * @mixin \Eloquent
 */
class Project extends AbstractModel
{
    use SoftDeletes;

    /**
     * @param $value
     * @return int|mixed
     */
    public function getDialogIdAttribute($value)
    {
        if ($value === 0) {
            $userid = $this->projectUser->pluck('userid')->toArray();
            $dialog = WebSocketDialog::createGroup($this->name, $userid, 'project');
            if ($dialog) {
                $this->dialog_id = $value = $dialog->id;
                $this->save();
            }
        }
        return $value;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function projectColumn(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(projectColumn::class, 'project_id', 'id')->orderBy('id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function projectLog(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(projectLog::class, 'project_id', 'id')->orderByDesc('id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function projectUser(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(projectUser::class, 'project_id', 'id')->orderBy('id');
    }

    /**
     * 加入项目
     * @param int $userid   加入的会员ID
     * @return bool
     */
    public function joinProject($userid) {
        $result = AbstractModel::transaction(function () use ($userid) {
            ProjectUser::updateInsert([
                'project_id' => $this->id,
                'userid' => $userid,
            ]);
            WebSocketDialogUser::updateInsert([
                'dialog_id' => $this->dialog_id,
                'userid' => $userid,
            ]);
        });
        return Base::isSuccess($result);
    }

    /**
     * 删除项目
     * @return bool
     */
    public function deleteProject()
    {
        $result = AbstractModel::transaction(function () {
            ProjectTask::whereProjectId($this->id)->delete();
            ProjectColumn::whereProjectId($this->id)->delete();
            WebSocketDialog::whereId($this->dialog_id)->delete();
            if ($this->delete()) {
                return Base::retSuccess('success');
            } else {
                return Base::retError('error');
            }
        });
        return Base::isSuccess($result);
    }
}
