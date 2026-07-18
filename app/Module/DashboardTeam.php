<?php

namespace App\Module;

use App\Exceptions\ApiException;
use App\Models\ProjectUser;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserDepartment;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 仪表盘负责人视角数据聚合。
 *
 * 口径：所选管理部门（含下级部门）的在职成员所参与、且允许负责人查看的未归档项目；
 * 仅统计主任务、全员可见任务，任务需由范围内成员负责或当前没有负责人。
 */
class DashboardTeam
{
    public const SOON_DAYS = 3;
    public const HIGH_PRIORITY_COUNT = 2;
    public const STATS_CACHE_SECONDS = 30;

    /**
     * 解析并校验负责人视角范围。
     */
    public static function context(User $user, $selectedDepartmentIds = null): array
    {
        if (Base::settingFind('system', 'department_owner_project_view', 'close') !== 'open') {
            throw new ApiException('未开启部门负责人视角功能');
        }

        $managedIds = UserDepartment::getManagedDepartments($user->userid)
            ->pluck('id')
            ->map(fn($id) => intval($id))
            ->values()
            ->toArray();
        if (empty($managedIds)) {
            throw new ApiException('没有可查看的部门数据');
        }

        $selectedIds = self::normalizeSelectedDepartmentIds($selectedDepartmentIds, $managedIds);
        if (empty($selectedIds)) {
            throw new ApiException('没有可查看的部门数据');
        }

        $departmentIds = self::expandDepartmentIds($selectedIds);
        $members = self::loadMembers($departmentIds);
        $memberUserids = $members->pluck('userid')->map(fn($id) => intval($id))->values()->toArray();
        $projectIds = self::loadProjectIds($memberUserids);
        $ownProjectIds = empty($projectIds) ? [] : ProjectUser::whereUserid($user->userid)
            ->whereIn('project_id', $projectIds)
            ->pluck('project_id')
            ->map(fn($id) => intval($id))
            ->unique()
            ->values()
            ->toArray();

        return [
            'viewer_userid' => intval($user->userid),
            'selected_department_ids' => $selectedIds,
            'department_ids' => $departmentIds,
            'member_userids' => $memberUserids,
            'member_map' => $members->keyBy('userid')->map(fn(User $member) => [
                'userid' => intval($member->userid),
                'nickname' => $member->nickname,
                'userimg' => $member->userimg,
            ])->toArray(),
            'project_ids' => $projectIds,
            'own_project_id_map' => array_fill_keys($ownProjectIds, true),
        ];
    }

    /**
     * 团队统计。
     */
    public static function stats(array $context, bool $refresh = false): array
    {
        $cacheKey = 'dashboard:team:stats:v3:' . $context['viewer_userid'] . ':' . sha1(json_encode([
            $context['selected_department_ids'],
            $context['department_ids'],
            $context['member_userids'],
            $context['project_ids'],
        ]));
        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addSeconds(self::STATS_CACHE_SECONDS), function () use ($context) {
            $now = Carbon::now();
            $taskAlias = DB::getTablePrefix() . 't';
            $soonEnd = $now->clone()->addDays(self::SOON_DAYS)->endOfDay();
            $weekStart = $now->clone()->startOfWeek(Carbon::MONDAY)->startOfDay();
            $nextWeekStart = $weekStart->clone()->addWeek();
            $lastWeekStart = $weekStart->clone()->subWeek();
            $base = self::baseTaskBuilder($context);

            $core = (clone $base)->selectRaw("
                SUM(CASE WHEN {$taskAlias}.complete_at IS NULL THEN 1 ELSE 0 END) AS stat_uncompleted,
                SUM(CASE WHEN {$taskAlias}.complete_at IS NULL AND {$taskAlias}.end_at IS NOT NULL AND {$taskAlias}.end_at < ? THEN 1 ELSE 0 END) AS stat_overdue,
                SUM(CASE WHEN {$taskAlias}.complete_at IS NULL AND {$taskAlias}.end_at IS NOT NULL AND {$taskAlias}.end_at >= ? AND {$taskAlias}.end_at <= ? THEN 1 ELSE 0 END) AS stat_due_soon,
                SUM(CASE WHEN {$taskAlias}.complete_at >= ? AND {$taskAlias}.complete_at < ? THEN 1 ELSE 0 END) AS stat_week_completed,
                SUM(CASE WHEN {$taskAlias}.complete_at >= ? AND {$taskAlias}.complete_at < ? THEN 1 ELSE 0 END) AS stat_last_week_completed
            ", [
                $now->toDateTimeString(),
                $now->toDateTimeString(),
                $soonEnd->toDateTimeString(),
                $weekStart->toDateTimeString(),
                $nextWeekStart->toDateTimeString(),
                $lastWeekStart->toDateTimeString(),
                $weekStart->toDateTimeString(),
            ])->first();

            $noOwner = (clone $base)
                ->whereNull('t.complete_at')
                ->whereNotExists(self::ownerExistsQuery('t.id'))
                ->count();
            $members = self::memberDistribution($context, $now);

            return [
                'generated_at' => $now->toDateTimeString(),
                'member_count' => count($context['member_userids']),
                'blocks' => [
                    'uncompleted' => intval($core->stat_uncompleted ?? 0),
                    'overdue' => intval($core->stat_overdue ?? 0),
                    'overdue_owner_count' => count(array_filter($members, fn($member) => $member['overdue'] > 0)),
                    'due_soon' => intval($core->stat_due_soon ?? 0),
                    'week_completed' => intval($core->stat_week_completed ?? 0),
                    'last_week_completed' => intval($core->stat_last_week_completed ?? 0),
                    'no_owner' => intval($noOwner),
                ],
                'priority' => self::priorityDistribution($context),
                'members' => $members,
                'high_levels' => self::highPriorityLevels(),
            ];
        });
    }

    /**
     * 分页任务列表。
     */
    public static function tasks(array $context, array $filters)
    {
        $now = Carbon::now();
        $soonEnd = $now->clone()->addDays(self::SOON_DAYS)->endOfDay();
        $memberId = intval($filters['member_id'] ?? 0);
        $level = $filters['level'] ?? null;
        $type = $filters['type'] ?? '';

        $builder = self::baseTaskBuilder($context)->whereNull('t.complete_at');
        if ($memberId > 0) {
            $builder->whereExists(self::ownerExistsQuery('t.id', [$memberId]));
        } elseif ($level !== null) {
            if (intval($level) === -1) {
                $levels = self::priorityLevels();
                if (!empty($levels)) {
                    $builder->whereNotIn('t.p_level', $levels);
                }
            } else {
                $builder->where('t.p_level', intval($level));
            }
        } else {
            switch ($type) {
                case 'overdue':
                    $builder->whereNotNull('t.end_at')->where('t.end_at', '<', $now);
                    break;

                case 'soon':
                    $builder->whereNotNull('t.end_at')->whereBetween('t.end_at', [$now, $soonEnd]);
                    break;

                case 'hi':
                    $levels = self::highPriorityLevels();
                    $builder->whereIn('t.p_level', $levels ?: [-1]);
                    break;

                case 'noowner':
                    $builder->whereNotExists(self::ownerExistsQuery('t.id'));
                    break;
            }
        }

        $taskAlias = DB::getTablePrefix() . 't';
        $builder->leftJoin('project_flow_items as fi', 'fi.id', '=', 't.flow_item_id')
            ->select([
                't.id',
                't.parent_id',
                't.project_id',
                't.column_id',
                't.name',
                't.end_at',
                't.p_level',
                't.p_name',
                't.p_color',
                't.flow_item_id',
                't.flow_item_name',
                'p.name as project_name',
                'fi.status as flow_item_status',
                'fi.color as flow_item_color',
            ])
            ->orderByRaw("{$taskAlias}.end_at IS NULL")
            ->orderBy('t.end_at')
            ->orderByDesc('t.id');

        $list = $builder->paginate(Base::getPaginate(50, 20));
        $taskIds = $list->getCollection()->pluck('id')->map(fn($id) => intval($id))->toArray();
        $owners = self::taskOwners($taskIds, $context['member_userids'], $memberId);
        $ownProjectMap = $context['own_project_id_map'];

        $list->setCollection($list->getCollection()->map(function ($task) use ($owners, $ownProjectMap) {
            $item = (array)$task;
            $item['id'] = intval($item['id']);
            $item['parent_id'] = intval($item['parent_id']);
            $item['project_id'] = intval($item['project_id']);
            $item['column_id'] = intval($item['column_id']);
            $item['p_level'] = intval($item['p_level']);
            $flowParts = explode('|', $item['flow_item_name'] ?: '');
            if (count($flowParts) >= 2) {
                $item['flow_item_status'] = $item['flow_item_status'] ?: ($flowParts[0] ?? '');
                $item['flow_item_name'] = $flowParts[1] ?? $item['flow_item_name'];
                $item['flow_item_color'] = $item['flow_item_color'] ?: ($flowParts[2] ?? '');
            }
            $item['owners'] = $owners[$item['id']] ?? [];
            $item['owner'] = $item['owners'][0] ?? null;
            $item['department_readonly'] = !isset($ownProjectMap[$item['project_id']]);
            return $item;
        }));
        return $list;
    }

    /**
     * 当前系统优先级ID列表（保持设置顺序）。
     * @return array<int>
     */
    public static function priorityLevels(): array
    {
        return array_map(fn($item) => intval($item['priority']), self::priorityList());
    }

    /**
     * 基础任务范围：主任务、全员可见、未归档项目，且由团队成员负责或没有负责人。
     */
    protected static function baseTaskBuilder(array $context): Builder
    {
        $projectIds = $context['project_ids'] ?: [0];
        $memberUserids = $context['member_userids'];

        return DB::table('project_tasks as t')
            ->join('projects as p', 'p.id', '=', 't.project_id')
            ->whereIn('t.project_id', $projectIds)
            ->where('t.parent_id', 0)
            ->where('t.visibility', 1)
            ->whereNull('t.archived_at')
            ->whereNull('t.deleted_at')
            ->whereNull('p.archived_at')
            ->whereNull('p.deleted_at')
            ->where(function (Builder $query) {
                $query->where('p.department_owner_view', '<>', 'close')
                    ->orWhereNull('p.department_owner_view');
            })
            ->where(function (Builder $query) use ($memberUserids) {
                if (empty($memberUserids)) {
                    $query->whereRaw('1 = 0');
                    return;
                }
                $query->whereExists(self::ownerExistsQuery('t.id', $memberUserids))
                    ->orWhereNotExists(self::ownerExistsQuery('t.id'));
            });
    }

    /**
     * 负责人存在子查询。
     */
    protected static function ownerExistsQuery(string $taskColumn, ?array $userids = null): callable
    {
        return function (Builder $query) use ($taskColumn, $userids) {
            $query->selectRaw('1')
                ->from('project_task_users as owner_scope')
                ->whereColumn('owner_scope.task_id', $taskColumn)
                ->where('owner_scope.owner', 1);
            if ($userids !== null) {
                $query->whereIn('owner_scope.userid', $userids ?: [0]);
            }
        };
    }

    /**
     * 优先级分布（包含无负责人任务）。
     */
    protected static function priorityDistribution(array $context): array
    {
        $taskAlias = DB::getTablePrefix() . 't';
        $rows = self::baseTaskBuilder($context)
            ->whereNull('t.complete_at')
            ->selectRaw("{$taskAlias}.p_level, COUNT(*) AS stat_num")
            ->groupBy('t.p_level')
            ->pluck('stat_num', 't.p_level');

        $result = [];
        $matched = [];
        foreach (self::priorityList() as $item) {
            $level = intval($item['priority']);
            $matched[] = $level;
            $result[] = [
                'level' => $level,
                'name' => $item['name'],
                'color' => $item['color'],
                'num' => intval($rows->get($level, 0)),
            ];
        }

        $unset = 0;
        foreach ($rows as $level => $num) {
            if (!in_array(intval($level), $matched, true)) {
                $unset += intval($num);
            }
        }
        if ($unset > 0) {
            $result[] = [
                'level' => -1,
                'name' => '',
                'color' => '#c5c8ce',
                'num' => $unset,
            ];
        }
        return $result;
    }

    /**
     * 成员任务分配。流程阶段与超期风险独立统计，无工作流的普通未完成任务归入待处理；
     * 多负责人任务会分别计入每个负责人的工作量，团队总数仍按任务去重。
     */
    protected static function memberDistribution(array $context, Carbon $now): array
    {
        $memberUserids = $context['member_userids'];
        if (empty($memberUserids) || empty($context['project_ids'])) {
            return array_values(array_map(fn($member) => array_merge($member, [
                'total' => 0,
                'overdue' => 0,
                'segments' => ['progress' => 0, 'start' => 0, 'test' => 0],
            ]), $context['member_map']));
        }

        $nowText = $now->toDateTimeString();
        $prefix = DB::getTablePrefix();
        $taskAlias = $prefix . 't';
        $taskUserAlias = $prefix . 'tu';
        $flowAlias = $prefix . 'fi';
        $rows = DB::table('project_task_users as tu')
            ->join('project_tasks as t', 't.id', '=', 'tu.task_id')
            ->join('projects as p', 'p.id', '=', 't.project_id')
            ->leftJoin('project_flow_items as fi', 'fi.id', '=', 't.flow_item_id')
            ->whereIn('tu.userid', $memberUserids)
            ->where('tu.owner', 1)
            ->whereIn('t.project_id', $context['project_ids'])
            ->where('t.parent_id', 0)
            ->where('t.visibility', 1)
            ->whereNull('t.complete_at')
            ->whereNull('t.archived_at')
            ->whereNull('t.deleted_at')
            ->whereNull('p.archived_at')
            ->whereNull('p.deleted_at')
            ->where(function (Builder $query) {
                $query->where('p.department_owner_view', '<>', 'close')
                    ->orWhereNull('p.department_owner_view');
            })
            ->selectRaw("
                {$taskUserAlias}.userid,
                COUNT(DISTINCT {$taskAlias}.id) AS stat_total,
                COUNT(DISTINCT CASE WHEN {$taskAlias}.end_at IS NOT NULL AND {$taskAlias}.end_at < ? THEN {$taskAlias}.id END) AS stat_overdue,
                COUNT(DISTINCT CASE WHEN {$flowAlias}.status = 'progress' THEN {$taskAlias}.id END) AS stat_progress,
                COUNT(DISTINCT CASE WHEN {$flowAlias}.status = 'test' THEN {$taskAlias}.id END) AS stat_test,
                COUNT(DISTINCT CASE WHEN {$flowAlias}.status IS NULL OR {$flowAlias}.status NOT IN ('progress', 'test') THEN {$taskAlias}.id END) AS stat_start
            ", [$nowText])
            ->groupBy('tu.userid')
            ->get()
            ->keyBy(fn($row) => intval($row->userid));

        $members = [];
        foreach ($context['member_map'] as $userid => $member) {
            $row = $rows->get(intval($userid));
            $members[] = array_merge($member, [
                'total' => intval($row->stat_total ?? 0),
                'overdue' => intval($row->stat_overdue ?? 0),
                'segments' => [
                    'progress' => intval($row->stat_progress ?? 0),
                    'start' => intval($row->stat_start ?? 0),
                    'test' => intval($row->stat_test ?? 0),
                ],
            ]);
        }
        usort($members, function ($a, $b) {
            return $b['overdue'] <=> $a['overdue']
                ?: $b['total'] <=> $a['total']
                ?: $a['userid'] <=> $b['userid'];
        });
        return $members;
    }

    /**
     * 任务负责人数据，范围内成员优先展示；成员筛选时将该成员置首。
     */
    protected static function taskOwners(array $taskIds, array $memberUserids, int $preferredUserid = 0): array
    {
        if (empty($taskIds)) {
            return [];
        }
        $rows = DB::table('project_task_users')
            ->whereIn('task_id', $taskIds)
            ->where('owner', 1)
            ->orderBy('id')
            ->get(['task_id', 'userid']);
        $userids = $rows->pluck('userid')->map(fn($id) => intval($id))->unique()->values()->toArray();
        $users = empty($userids) ? collect() : User::select(User::$basicField)
            ->whereIn('userid', $userids)
            ->get()
            ->keyBy('userid');
        $memberMap = array_fill_keys($memberUserids, true);
        $owners = [];
        foreach ($rows as $row) {
            $userid = intval($row->userid);
            $user = $users->get($userid);
            $owners[intval($row->task_id)][] = [
                'userid' => $userid,
                'nickname' => $user?->nickname ?? '',
            ];
        }
        foreach ($owners as &$list) {
            usort($list, function ($a, $b) use ($memberMap, $preferredUserid) {
                if ($preferredUserid > 0) {
                    $preferred = ($b['userid'] === $preferredUserid) <=> ($a['userid'] === $preferredUserid);
                    if ($preferred !== 0) {
                        return $preferred;
                    }
                }
                $managed = isset($memberMap[$b['userid']]) <=> isset($memberMap[$a['userid']]);
                return $managed !== 0 ? $managed : $a['userid'] <=> $b['userid'];
            });
        }
        unset($list);
        return $owners;
    }

    /**
     * 在全部部门中一次性展开下级部门，避免递归 N+1。
     */
    protected static function expandDepartmentIds(array $selectedIds): array
    {
        $children = UserDepartment::select(['id', 'parent_id'])
            ->get()
            ->groupBy(fn(UserDepartment $department) => intval($department->parent_id));
        $result = [];
        $queue = array_values(array_unique(array_map('intval', $selectedIds)));
        while (!empty($queue)) {
            $departmentId = array_shift($queue);
            if ($departmentId <= 0 || isset($result[$departmentId])) {
                continue;
            }
            $result[$departmentId] = true;
            foreach ($children->get($departmentId, collect()) as $child) {
                $queue[] = intval($child->id);
            }
        }
        return array_keys($result);
    }

    /**
     * 当前范围内的在职非机器人成员。users.department 为现有权限口径的权威数据源。
     */
    protected static function loadMembers(array $departmentIds): Collection
    {
        if (empty($departmentIds)) {
            return collect();
        }
        return User::select(User::$basicField)
            ->whereNull('disable_at')
            ->where('bot', 0)
            ->where(function ($query) use ($departmentIds) {
                foreach ($departmentIds as $departmentId) {
                    $query->orWhere('department', 'like', "%,{$departmentId},%");
                }
            })
            ->orderBy('userid')
            ->get();
    }

    /**
     * 成员参与且允许负责人查看的活动项目。
     */
    protected static function loadProjectIds(array $memberUserids): array
    {
        if (empty($memberUserids)) {
            return [];
        }
        return DB::table('project_users as pu')
            ->join('projects as p', 'p.id', '=', 'pu.project_id')
            ->whereIn('pu.userid', $memberUserids)
            ->whereNull('p.archived_at')
            ->whereNull('p.deleted_at')
            ->where(function (Builder $query) {
                $query->where('p.department_owner_view', '<>', 'close')
                    ->orWhereNull('p.department_owner_view');
            })
            ->distinct()
            ->orderBy('p.id')
            ->pluck('p.id')
            ->map(fn($id) => intval($id))
            ->toArray();
    }

    protected static function normalizeSelectedDepartmentIds($selectedIds, array $managedIds): array
    {
        if ($selectedIds === null || $selectedIds === '' || $selectedIds === 'all' || $selectedIds === []) {
            return $managedIds;
        }
        if (!is_array($selectedIds)) {
            $selectedIds = explode(',', (string)$selectedIds);
        }
        return array_values(array_unique(array_intersect(
            array_map('intval', $selectedIds),
            $managedIds
        )));
    }

    protected static function highPriorityLevels(): array
    {
        return array_slice(self::priorityLevels(), 0, self::HIGH_PRIORITY_COUNT);
    }

    protected static function priorityList(): array
    {
        return Setting::normalizeTaskPriorityList(Base::setting('priority'));
    }
}
