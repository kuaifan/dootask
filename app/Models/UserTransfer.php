<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Module\Base;
use Carbon\Carbon;
use Guanguans\Notify\Factory;
use Guanguans\Notify\Messages\EmailMessage;

/**
 * App\Models\UserTransfer
 *
 * @property int $id
 * @property int|null $original_userid 原作者
 * @property int|null $new_userid 交接人
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|UserTransfer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserTransfer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserTransfer query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|UserTransfer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTransfer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTransfer whereNewUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTransfer whereOriginalUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTransfer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class UserTransfer extends AbstractModel
{

    /**
     * 开始移交
     * @return void
     */
    public function start()
    {
        // 移交部门
        UserDepartment::transfer($this->original_userid, $this->new_userid);
        // 移交项目身份
        ProjectUser::transfer($this->original_userid, $this->new_userid);
        // 移交任务身份
        ProjectTaskUser::transfer($this->original_userid, $this->new_userid);
        // 移交文件
        File::transfer($this->original_userid, $this->new_userid);
        // 离职移出群组
        WebSocketDialog::select(['web_socket_dialogs.*'])
            ->join('web_socket_dialog_users as u', 'web_socket_dialogs.id', '=', 'u.dialog_id')
            ->where('web_socket_dialogs.type', 'group')
            ->where('web_socket_dialogs.group_type', '!=', 'okr')
            ->where('u.userid', $this->original_userid)
            ->orderByDesc('web_socket_dialogs.id')
            ->chunk(100, function($list) {
                /** @var WebSocketDialog $dialog */
                foreach ($list as $dialog) {
                    // 离职员工退出群
                    $dialog->exitGroup($this->original_userid, 'remove', false, false);
                    if ($dialog->owner_id === $this->original_userid) {
                        // 如果是群主则把交接人设为群主
                        $dialog->owner_id = $this->new_userid;
                        if ($dialog->save()) {
                            $dialog->joinGroup($this->new_userid, 0);
                            $dialog->pushMsg("groupUpdate", [
                                'id' => $dialog->id,
                                'owner_id' => $dialog->owner_id,
                            ]);
                        }
                    }
                }
            });
    }
}
