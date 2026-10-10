<?php

namespace App\Module;

use App\Models\User;
use App\Models\UserDepartment;

/**
 * 通讯录部门树
 */
class ContactsDepartment
{
    /**
     * 构建通讯录部门树（深度优先展开的扁平列表）
     *
     * 成员口径与 users/search 默认一致：未禁用、非机器人。
     * - member_count: 直属成员数
     * - total_count:  含全部下级部门的成员数（去重）
     *
     * @return array{total: int, no_department_count: int, departments: array}
     */
    public static function tree(): array
    {
        $departments = UserDepartment::select(['id', 'name', 'parent_id', 'owner_userid'])->orderBy('id')->get()->keyBy('id');

        // 部门 => 直属成员
        $members = [];
        $noDepartmentCount = 0;
        $total = 0;
        User::whereNull('disable_at')->where('bot', 0)->pluck('department', 'userid')->each(function ($department, $userid) use ($departments, &$members, &$noDepartmentCount, &$total) {
            $total++;
            // pluck 会经过 User::getDepartmentAttribute，得到的已是 int 数组
            $ids = array_filter((array)$department, fn($id) => $departments->has($id));
            if (empty($ids)) {
                $noDepartmentCount++;
                return;
            }
            foreach ($ids as $id) {
                $members[$id][$userid] = true;
            }
        });

        // 上级部门不存在时视为顶级
        $children = [];
        foreach ($departments as $department) {
            $parentId = $departments->has($department->parent_id) && $department->parent_id != $department->id ? $department->parent_id : 0;
            $children[$parentId][] = $department->id;
        }

        $list = [];
        $visited = [];
        $walk = function ($id, $level) use (&$walk, &$list, &$visited, &$children, &$members, $departments) {
            $visited[$id] = true;
            $department = $departments[$id];
            $index = count($list);
            $list[] = [
                'id' => $department->id,
                'name' => $department->name,
                'parent_id' => (int)$department->parent_id,
                'owner_userid' => (int)$department->owner_userid,
                'level' => $level,
                'member_count' => count($members[$id] ?? []),
                'total_count' => 0,
            ];
            $all = $members[$id] ?? [];
            foreach ($children[$id] ?? [] as $childId) {
                if (isset($visited[$childId])) {
                    continue;   // 防止异常数据形成环
                }
                $all += $walk($childId, $level + 1);
            }
            $list[$index]['total_count'] = count($all);
            return $all;
        };
        foreach ($children[0] ?? [] as $rootId) {
            $walk($rootId, 0);
        }
        foreach ($departments as $id => $department) {
            if (!isset($visited[$id])) {
                $walk($id, 0);  // 异常数据形成环时兜底，避免部门丢失
            }
        }

        return [
            'total' => $total,
            'no_department_count' => $noDepartmentCount,
            'departments' => $list,
        ];
    }
}
