<?php

namespace App\Models;

use App\Exceptions\ApiException;
use Cache;

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
    protected $appends = [
        'deputy_userids',
    ];

    /**
     * 获取所有父级部门
     * @return array
     */
    public function parents()
    {
        $parents = [];
        $parent = $this;
        while ($parent) {
            $parents[] = $parent;
            $parent = $parent->parent_id ? self::find($parent->parent_id) : null;
        }
        return $parents;
    }

    /**
     * 副负责人 userid 列表
     * @return array
     */
    public function getDeputyUseridsAttribute(): array
    {
        if (empty($this->id)) {
            return [];
        }
        return \DB::table('user_department_owners')
            ->where('department_id', $this->id)
            ->pluck('userid')
            ->map(fn($v) => (int)$v)
            ->toArray();
    }

    /**
     * 是否主负责人（与 owner_userid 一致）
     */
    public function isPrimaryOwner($userid): bool
    {
        if (empty($this->id) || $userid <= 0) {
            return false;
        }
        return (int)$this->owner_userid === (int)$userid;
    }

    /**
     * 是否副负责人（在 user_department_owners 表里）
     */
    public function isDeputyOwner($userid): bool
    {
        if (empty($this->id) || $userid <= 0) {
            return false;
        }
        return \DB::table('user_department_owners')
            ->where('department_id', $this->id)
            ->where('userid', $userid)
            ->exists();
    }

    /**
     * 是否负责人（主或副）
     */
    public function isOwner($userid): bool
    {
        return $this->isPrimaryOwner($userid) || $this->isDeputyOwner($userid);
    }

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
                    $oldOwnerId = (int)$dialog->owner_id;
                    $dialog->name = $this->name;
                    $dialog->owner_id = $this->owner_userid;
                    if ($dialog->save()) {
                        $dialog->joinGroup($this->owner_userid, 0, true);
                        // 同步 role：原主 role=0、新主 role=1（副 role=2 保留不动）
                        if ($oldOwnerId > 0 && $oldOwnerId !== (int)$this->owner_userid) {
                            WebSocketDialogUser::where('dialog_id', $dialog->id)
                                ->where('userid', $oldOwnerId)
                                ->update(['role' => 0]);
                        }
                        WebSocketDialogUser::where('dialog_id', $dialog->id)
                            ->where('userid', $this->owner_userid)
                            ->update(['role' => 1]);
                        $dialog->pushMsg("groupUpdate", [
                            'id' => $dialog->id,
                            'name' => $dialog->name,
                            'owner_id' => $dialog->owner_id,
                            'deputy_ids' => $dialog->fresh()->deputy_ids,
                        ]);
                    }
                }
            } elseif ($dialogUseid > 0) {
                // 使用现有群
                $dialog = WebSocketDialog::whereType('group')->whereGroupType('user')->find($dialogUseid);
                if (empty($dialog)) {
                    throw new ApiException("选择现有聊天群不存在");
                }
                $oldOwnerId = (int)$dialog->owner_id;
                $dialog->name = $this->name;
                $dialog->owner_id = $this->owner_userid;
                $dialog->group_type = 'department';
                if ($dialog->save()) {
                    $dialog->joinGroup($this->owner_userid, 0, true);
                    // 同步 role：原主 role=0、新主 role=1、原副 role=0
                    // 原副清零：避免 dialog_users.role=2 与 user_department_owners 不一致
                    // （副关系不带过来，须通过 addDeputy 显式重新任命）
                    if ($oldOwnerId > 0 && $oldOwnerId !== (int)$this->owner_userid) {
                        WebSocketDialogUser::where('dialog_id', $dialog->id)
                            ->where('userid', $oldOwnerId)
                            ->update(['role' => 0]);
                    }
                    WebSocketDialogUser::where('dialog_id', $dialog->id)
                        ->where('userid', '!=', $this->owner_userid)
                        ->where('role', 2)
                        ->update(['role' => 0]);
                    WebSocketDialogUser::where('dialog_id', $dialog->id)
                        ->where('userid', $this->owner_userid)
                        ->update(['role' => 1]);
                    $dialog->pushMsg("groupUpdate", [
                        'id' => $dialog->id,
                        'name' => $dialog->name,
                        'owner_id' => $dialog->owner_id,
                        'group_type' => $dialog->group_type,
                        'deputy_ids' => $dialog->fresh()->deputy_ids,
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
                // 原主从 users.department 移除后也要退出部门群（保持成员关系=群关系一致）
                // checkDelete=false：业务流程跳过 owner_id/important 校验
                if ($this->dialog_id > 0) {
                    $dialog = WebSocketDialog::find($this->dialog_id);
                    $dialog?->exitGroup($oldUser->userid, 'remove', false, true);
                }
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
     * 任命副负责人
     * - 副自动加入 users.department（成为部门成员，与主对齐）
     * - 副自动加入部门群 + 设 role=2
     * - 幂等（已是副不报错）
     *
     * @param int $userid
     * @return void
     * @throws ApiException
     */
    public function addDeputy($userid)
    {
        if ($userid <= 0) {
            throw new ApiException('请选择有效的成员');
        }
        $user = User::whereUserid($userid)->first();
        if (!$user) {
            throw new ApiException('该用户不存在');
        }
        if ((int)$this->owner_userid === (int)$userid) {
            throw new ApiException('不能将部门负责人任命为部门管理员');
        }

        AbstractModel::transaction(function () use ($userid, $user) {
            // 写副表（unique key 自动幂等）
            \DB::table('user_department_owners')->insertOrIgnore([
                'department_id' => $this->id,
                'userid' => $userid,
            ]);

            // 加入 users.department（成为部门成员，与主对齐）
            $userDeptIds = $user->department; // accessor 返回数组
            if (!in_array($this->id, $userDeptIds)) {
                $userDeptIds = array_merge($userDeptIds, [$this->id]);
                $user->department = "," . implode(",", $userDeptIds) . ",";
                $user->save();
            }

            // 加副入部门群 + 设 role=2
            if ($this->dialog_id > 0) {
                $dialog = WebSocketDialog::find($this->dialog_id);
                if ($dialog) {
                    // joinGroup($userid, $inviter, $important=null, $pushMsg=true)
                    $dialog->joinGroup($userid, 0, null, true);
                    WebSocketDialogUser::where('dialog_id', $dialog->id)
                        ->where('userid', $userid)
                        ->update(['role' => 2]);
                    $dialog->pushMsg('groupUpdate', [
                        'id' => $dialog->id,
                        'deputy_ids' => $dialog->fresh()->deputy_ids,
                    ]);
                }
            }
        });
    }

    /**
     * 罢免副负责人
     * - 删副表记录
     * - 从 users.department 移除该部门 ID（与主"离开部门"对齐）
     * - 退出部门群（成员关系=群关系一致）
     * - 幂等
     *
     * @param int $userid
     * @return void
     */
    public function delDeputy($userid)
    {
        if ($userid <= 0) {
            return;
        }

        AbstractModel::transaction(function () use ($userid) {
            $deleted = \DB::table('user_department_owners')
                ->where('department_id', $this->id)
                ->where('userid', $userid)
                ->delete();

            if ($deleted > 0) {
                // 从 users.department 移除该部门 ID
                $user = User::whereUserid($userid)->first();
                if ($user) {
                    $userDeptIds = $user->department;
                    if (in_array($this->id, $userDeptIds)) {
                        $userDeptIds = array_diff($userDeptIds, [$this->id]);
                        $user->department = "," . implode(",", $userDeptIds) . ",";
                        $user->save();
                    }
                }

                // 退出部门群（exitGroup 会清除 dialog_users 记录，role 随之消失）
                if ($this->dialog_id > 0) {
                    $dialog = WebSocketDialog::find($this->dialog_id);
                    if ($dialog) {
                        // checkDelete=false：业务流程跳过 owner_id/important 校验
                        $dialog->exitGroup($userid, 'remove', false, true);
                        $dialog->pushMsg('groupUpdate', [
                            'id' => $dialog->id,
                            'deputy_ids' => $dialog->fresh()->deputy_ids,
                        ]);
                    }
                }
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
        $dialog?->deleteDialog();
        // 清理副负责人记录（防悬挂）
        \DB::table('user_department_owners')->where('department_id', $this->id)->delete();
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
        // 主转让（保持现有逻辑）
        self::whereOwnerUserid($originalUserid)->chunkById(100, function ($list) use ($originalUserid, $newUserid) {
            /** @var self $item */
            foreach ($list as $item) {
                $item->saveDepartment([
                    'owner_userid' => $newUserid,
                ]);
            }
        });
        // 副离职清理（新增）：直接删除离职用户的所有副记录
        // 不需要清群 role —— UserTransfer::exitDialog 会把人踢出所有群，role 随成员关系一起消失
        \DB::table('user_department_owners')
            ->where('userid', $originalUserid)
            ->delete();
    }

    /**
     * 递归获取所有子部门ID
     * @param int $departmentId
     * @return array
     */
    public static function getAllSubDepartmentIds($departmentId)
    {
        $subIds = [];
        $directSubs = self::whereParentId($departmentId)->pluck('id')->toArray();
        
        foreach ($directSubs as $subId) {
            $subIds[] = $subId;
            // 递归获取子部门的子部门
            $subSubIds = self::getAllSubDepartmentIds($subId);
            $subIds = array_merge($subIds, $subSubIds);
        }
        
        return array_unique($subIds);
    }

    /**
     * 获取部门基本信息（缓存时间1小时）
     * @param int|array $ids
     * @return \Illuminate\Support\Collection|static|null
     */
    public static function getDepartmentsByIds($ids)
    {
        $ids = is_array($ids) ? $ids : [$ids];
        $departments = collect();
        $uncachedIds = [];

        foreach ($ids as $id) {
            $cacheKey = "department_info_{$id}";
            $department = Cache::get($cacheKey);
            if ($department) {
                $departments->push($department);
            } else {
                $uncachedIds[] = $id;
            }
        }

        if (!empty($uncachedIds)) {
            $dbDepartments = self::select(['id', 'name', 'parent_id', 'owner_userid'])->whereIn('id', $uncachedIds)->get();
            foreach ($dbDepartments as $department) {
                $cacheKey = "department_info_{$department->id}";
                Cache::put($cacheKey, $department, 60 * 60); // 1小时
                $departments->push($department);
            }
        }

        // 保持返回顺序与传入ids一致
        $departments = $departments->keyBy('id');
        $result = collect();
        foreach ($ids as $id) {
            if ($departments->has($id)) {
                $result->push($departments->get($id));
            }
        }

        return is_array($ids) ? $result : $result->first();
    }

}
