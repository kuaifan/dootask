<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Module\Base;
use Cache;
use Request;

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
 * @property-read array $deputy_userids
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
     * 部门管理员 userid 列表
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
     * 是否部门负责人（与 owner_userid 一致）
     */
    public function isPrimaryOwner($userid): bool
    {
        if (empty($this->id) || $userid <= 0) {
            return false;
        }
        return (int)$this->owner_userid === (int)$userid;
    }

    /**
     * 是否部门管理员（在 user_department_owners 表里）
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
     * 是否负责人（含部门管理员）
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
            // 防御：新负责人若残留在 user_department_owners 中（如曾是该部门管理员），清理掉
            // 否则后续 delDeputy / 罢免接口会把当前部门负责人误移出部门
            if ($this->id && (int)$this->owner_userid > 0) {
                \DB::table('user_department_owners')
                    ->where('department_id', $this->id)
                    ->where('userid', (int)$this->owner_userid)
                    ->delete();
            }
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
                        // 同步 role：原负责人 role=0、新负责人 role=1（部门管理员 role=2 保留不动）
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
                    // 同步 role：原负责人 role=0、新负责人 role=1、原部门管理员 role=0
                    // 原部门管理员清零：避免 dialog_users.role=2 与 user_department_owners 不一致
                    // （部门管理员关系不带过来，须通过 addDeputy 显式重新任命）
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
     * 任命部门管理员
     * - 部门管理员自动加入 users.department（成为部门成员，与负责人对齐）
     * - 部门管理员自动加入部门群 + 设 role=2
     * - 幂等（已是部门管理员不报错）
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
            // 写部门管理员表（unique key 自动幂等）
            \DB::table('user_department_owners')->insertOrIgnore([
                'department_id' => $this->id,
                'userid' => $userid,
            ]);

            // 加入 users.department（成为部门成员，与负责人对齐）
            $userDeptIds = $user->department; // accessor 返回数组
            if (!in_array($this->id, $userDeptIds)) {
                $userDeptIds = array_merge($userDeptIds, [$this->id]);
                $user->department = "," . implode(",", $userDeptIds) . ",";
                $user->save();
            }

            // 加部门管理员入部门群 + 设 role=2 + important=true
            if ($this->dialog_id > 0) {
                $dialog = WebSocketDialog::find($this->dialog_id);
                if ($dialog) {
                    // joinGroup($userid, $inviter, $important=null, $pushMsg=true)
                    // important=true：部门管理员成员关系不可被普通群操作打散
                    $dialog->joinGroup($userid, 0, true, true);
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
     * 罢免部门管理员
     * - 删部门管理员表记录
     * - 从 users.department 移除该部门 ID（与负责人"离开部门"对齐）
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

        // 防御：当前部门负责人不能被罢免（saveDepartment 应已清理残留，此处兜底）
        // 仅清理 user_department_owners 中的悬挂记录，绝不联动移除其部门成员关系/部门群成员
        if ((int)$this->owner_userid === (int)$userid) {
            \DB::table('user_department_owners')
                ->where('department_id', $this->id)
                ->where('userid', $userid)
                ->delete();
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
        // 清理部门管理员记录（防悬挂）
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
        // 部门负责人转让（保持现有逻辑）
        self::whereOwnerUserid($originalUserid)->chunkById(100, function ($list) use ($originalUserid, $newUserid) {
            /** @var self $item */
            foreach ($list as $item) {
                $item->saveDepartment([
                    'owner_userid' => $newUserid,
                ]);
            }
        });
        // 部门管理员离职清理（新增）：直接删除离职用户的所有部门管理员记录
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
     * 获取用户可切换负责人视角的部门（正负责人 + 部门管理员）
     * @param int $userid
     * @return \Illuminate\Support\Collection
     */
    public static function getManagedDepartments($userid)
    {
        $userid = intval($userid);
        if ($userid <= 0) {
            return collect();
        }
        $deputyDepartmentIds = \DB::table('user_department_owners')
            ->where('userid', $userid)
            ->pluck('department_id')
            ->map(fn($v) => intval($v))
            ->toArray();

        return self::select(['id', 'name', 'parent_id', 'owner_userid'])
            ->where(function ($query) use ($userid, $deputyDepartmentIds) {
                $query->where('owner_userid', $userid);
                if ($deputyDepartmentIds) {
                    $query->orWhereIn('id', $deputyDepartmentIds);
                }
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * 获取用户选择的负责人视角部门范围（含所有下级部门）
     * @param int $userid
     * @param array|string|null $selectedIds all/空表示全部可管理部门
     * @return array
     */
    public static function getManagedDepartmentScopeIds($userid, $selectedIds = null): array
    {
        $managedIds = self::getManagedDepartments($userid)->pluck('id')->map(fn($v) => intval($v))->toArray();
        if (empty($managedIds)) {
            return [];
        }
        if ($selectedIds === 'all' || $selectedIds === null || $selectedIds === '' || $selectedIds === []) {
            $selected = $managedIds;
        } else {
            if (!is_array($selectedIds)) {
                $selectedIds = explode(',', (string)$selectedIds);
            }
            $selected = array_values(array_intersect(
                array_map('intval', $selectedIds),
                $managedIds
            ));
        }
        if (empty($selected)) {
            return [];
        }
        $scopeIds = [];
        foreach ($selected as $departmentId) {
            $scopeIds[] = $departmentId;
            $scopeIds = array_merge($scopeIds, self::getAllSubDepartmentIds($departmentId));
        }
        return array_values(array_unique(array_map('intval', $scopeIds)));
    }

    /**
     * 获取负责人视角可管理的成员 userid
     * @param int $userid
     * @param array|string|null $selectedIds
     * @return array
     */
    public static function getManagedMemberUserids($userid, $selectedIds = null): array
    {
        $departmentIds = self::getManagedDepartmentScopeIds($userid, $selectedIds);
        if (empty($departmentIds)) {
            return [];
        }
        return User::select(['userid'])
            ->where(function ($query) use ($departmentIds) {
                foreach ($departmentIds as $departmentId) {
                    $query->orWhere('department', 'like', "%,{$departmentId},%");
                }
            })
            ->pluck('userid')
            ->map(fn($v) => intval($v))
            ->unique()
            ->values()
            ->toArray();
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

    /**
     * 部门负责人视角上下文（只读）。
     * $defaultAll=true 用于项目内只读辅助接口兜底：前端漏传部门选择时按全部可管理部门判断。
     */
    public static function ownerViewContext(User $user, bool $defaultAll = false): array
    {
        $ids = Request::input('department_owner_ids', Request::input('department_ids'));
        if (($ids === null || $ids === '') && $defaultAll) {
            $ids = 'all';
        }
        $empty = [
            'enabled' => false,
            'member_userids' => [],
            'project_ids' => [],
            'project_id_map' => [],
            'own_project_ids' => [],
            'own_project_id_map' => [],
        ];
        if ($ids === null || $ids === '' || Base::settingFind('system', 'department_owner_project_view', 'close') !== 'open') {
            return $empty;
        }
        $memberUserids = self::getManagedMemberUserids($user->userid, $ids);
        if (empty($memberUserids)) {
            return $empty;
        }
        // 项目可单独关闭"部门负责人视角可见"，关闭后对负责人隐藏（含项目和任务群聊）
        $projectIds = ProjectUser::whereIn('project_users.userid', $memberUserids)
            ->join('projects', 'projects.id', '=', 'project_users.project_id')
            ->whereNull('projects.deleted_at')
            ->where(function ($query) {
                $query->where('projects.department_owner_view', '<>', 'close')
                    ->orWhereNull('projects.department_owner_view');
            })
            ->distinct()
            ->pluck('projects.id')
            ->map(fn($v) => intval($v))
            ->values()
            ->toArray();
        $ownProjectIds = ProjectUser::whereUserid($user->userid)
            ->pluck('project_id')
            ->map(fn($v) => intval($v))
            ->unique()
            ->values()
            ->toArray();
        return [
            'enabled' => !empty($projectIds),
            'member_userids' => $memberUserids,
            'project_ids' => $projectIds,
            'project_id_map' => array_fill_keys($projectIds, true),
            'own_project_ids' => $ownProjectIds,
            'own_project_id_map' => array_fill_keys($ownProjectIds, true),
        ];
    }

    /**
     * 判断项目是否属于部门只读范围（非本人项目）
     */
    public static function isDepartmentReadonlyProject(array $context, int $projectId): bool
    {
        return !empty($context['enabled'])
            && isset($context['project_id_map'][$projectId])
            && !isset($context['own_project_id_map'][$projectId]);
    }

    /**
     * 为项目数据附加部门只读标记
     */
    public static function appendDepartmentReadonlyProject(array $project, array $context): array
    {
        $project['department_readonly'] = self::isDepartmentReadonlyProject($context, intval($project['id']));
        return $project;
    }

    /**
     * 会员卡片「查看该会员项目/任务」的权限上下文。
     * 允许条件：本人 / 系统管理员 / 对该会员具有部门负责人只读视角。
     * @param User $viewer        当前登录用户
     * @param int  $targetUserid  目标会员
     * @return array ['allowed'=>bool, 'is_self'=>bool, 'is_admin'=>bool, 'project_ids'=>int[]]
     *               project_ids 仅在部门负责人视角下有意义（限定可见项目集合）；本人/管理员为空数组表示不限制
     */
    public static function userWorksContext(User $viewer, int $targetUserid): array
    {
        $result = [
            'allowed' => false,
            'is_self' => false,
            'is_admin' => false,
            'project_ids' => [],
        ];
        if ($targetUserid <= 0) {
            return $result;
        }
        // 机器人/系统账号（或不存在）不展示项目与任务
        $target = User::select(['userid', 'bot'])->whereUserid($targetUserid)->first();
        if (empty($target) || $target->bot) {
            return $result;
        }
        // 本人
        if ($viewer->userid === $targetUserid) {
            $result['allowed'] = true;
            $result['is_self'] = true;
            return $result;
        }
        // 系统管理员
        if ($viewer->isAdmin()) {
            $result['allowed'] = true;
            $result['is_admin'] = true;
            return $result;
        }
        // 部门负责人只读视角
        if (Base::settingFind('system', 'department_owner_project_view', 'close') !== 'open') {
            return $result;
        }
        $memberUserids = self::getManagedMemberUserids($viewer->userid, 'all');
        if (!in_array($targetUserid, $memberUserids, true)) {
            return $result;
        }
        // 目标会员参与、且未关闭「部门负责人视角可见」的项目
        $projectIds = ProjectUser::where('project_users.userid', $targetUserid)
            ->join('projects', 'projects.id', '=', 'project_users.project_id')
            ->whereNull('projects.deleted_at')
            ->where(function ($query) {
                $query->where('projects.department_owner_view', '<>', 'close')
                    ->orWhereNull('projects.department_owner_view');
            })
            ->distinct()
            ->pluck('projects.id')
            ->map(fn($v) => intval($v))
            ->values()
            ->toArray();
        if (empty($projectIds)) {
            return $result;
        }
        $result['allowed'] = true;
        $result['project_ids'] = $projectIds;
        return $result;
    }

}
