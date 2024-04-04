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
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDepartment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDepartment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDepartment query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
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
     * @param $dialogUseid
     */
    public function saveDepartment($data = [], $dialogUseid = 0) {
        AbstractModel::transaction(function () use ($dialogUseid, $data) {
            $oldUser = null;
            $newUser = null;
            if ($data['owner_userid'] !== $this->owner_userid) {
                $oldUser = User::find($this->owner_userid);
                $newUser = User::find($data['owner_userid']);
            }
            $this->updateInstance($data);
            //
            if ($this->dialog_id > 0) {
                // 已有群
                $dialog = WebSocketDialog::find($this->dialog_id);
                if ($dialog) {
                    $dialog->name = $this->name;
                    $dialog->owner_id = $this->owner_userid;
                    if ($dialog->save()) {
                        $dialog->joinGroup($this->owner_userid, 0, true);
                        $dialog->pushMsg("groupUpdate", [
                            'id' => $dialog->id,
                            'name' => $dialog->name,
                            'owner_id' => $dialog->owner_id,
                        ]);
                    }
                }
            } elseif ($dialogUseid > 0) {
                // 使用现有群
                $dialog = WebSocketDialog::whereType('group')->whereGroupType('user')->find($dialogUseid);
                if (empty($dialog)) {
                    throw new ApiException("选择现有聊天群不存在");
                }
                $dialog->name = $this->name;
                $dialog->owner_id = $this->owner_userid;
                $dialog->group_type = 'department';
                if ($dialog->save()) {
                    $dialog->joinGroup($this->owner_userid, 0, true);
                    $dialog->pushMsg("groupUpdate", [
                        'id' => $dialog->id,
                        'name' => $dialog->name,
                        'owner_id' => $dialog->owner_id,
                        'group_type' => $dialog->group_type,
                    ]);
                    WebSocketDialogMsg::sendMsg(null, $dialog->id, 'notice', [
                        'notice' => User::nickname() . " 将此群改为部门群"
                    ], User::userid(), true, true);
                }
                $this->dialog_id = $dialog->id;
            } else {
                // 创建群
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
        }
        //
        $this->delete();
    }

    /**
     * 移交部门身份
     * @param $originalUserid
     * @param $newUserid
     * @return void
     */
    public static function transfer($originalUserid, $newUserid)
    {
        self::whereOwnerUserid($originalUserid)->chunkById(100, function ($list) use ($originalUserid, $newUserid) {
            /** @var self $item */
            foreach ($list as $item) {
                $item->saveDepartment([
                    'owner_userid' => $newUserid,
                ]);
            }
        });
    }
}
