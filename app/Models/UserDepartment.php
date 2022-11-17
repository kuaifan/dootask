<?php

namespace App\Models;

use App\Exceptions\ApiException;

/**
 * App\Models\UserDepartment
 *
 * @property int $id
 * @property string|null $name 部门名称
 * @property int|null $dialog_id 聊天会话ID
 * @property int|null $parent_id 上级部门
 * @property int|null $owner_userid 部门负责人
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|UserDepartment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDepartment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDepartment query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDepartment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDepartment whereDialogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDepartment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDepartment whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDepartment whereOwnerUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDepartment whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDepartment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class UserDepartment extends AbstractModel
{
    /**
     * 保存部门
     * @param $data
     */
    public function saveDepartment($data = []) {
        AbstractModel::transaction(function () use ($data) {
            $oldUser = null;
            $newUser = null;
            if ($data['owner_userid'] !== $this->owner_userid) {
                $oldUser = User::find($this->owner_userid);
                $newUser = User::find($data['owner_userid']);
            }
            $this->updateInstance($data);
            //
            if ($this->dialog_id > 0) {
                $dialog = WebSocketDialog::find($this->dialog_id);
                if ($dialog) {
                    $dialog->name = $this->name;
                    $dialog->owner_id = $this->owner_userid;
                    $dialog->save();
                }
            } else {
                $dialog = WebSocketDialog::createGroup($this->name, [$this->owner_userid], 'department', $this->owner_userid);
                if (empty($dialog)) {
                    throw new ApiException("创建群组失败");
                }
                $this->dialog_id = $dialog->id;
            }
            $this->save();
            //
            if ($oldUser) {
                $oldUser->department = array_diff($oldUser->department, [$this->id]);
                $oldUser->department = "," . implode(",", $oldUser->department) . ",";
                $oldUser->save();
            }
            if ($newUser) {
                $newUser->department = array_diff($newUser->department, [$this->id]);
                $newUser->department = array_merge($newUser->department, [$this->id]);
                $newUser->department = "," . implode(",", $newUser->department) . ",";
                $newUser->save();
            }
        });
    }

    /**
     * 删除部门
     * @return void
     */
    public function deleteDepartment() {
        // 删除子部门
        $list = self::whereParentId($this->id)->get();
        foreach ($list as $item) {
            $item->deleteDepartment();
        }
        // 移出成员
        User::where("department", "like", "%,{$this->id},%")->chunk(100, function($items) {
            /** @var User $user */
            foreach ($items as $user) {
                $user->department = array_diff($user->department, [$this->id]);
                $user->department = "," . implode(",", $user->department) . ",";
                $user->save();
            }
        });
        // 解散群组
        $dialog = WebSocketDialog::find($this->dialog_id);
        if ($dialog) {
            $dialog->deleteDialog();
            $dialog->pushMsg("groupDelete");
        }
        //
        $this->delete();
    }
}
