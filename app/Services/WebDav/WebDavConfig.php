<?php

namespace App\Services\WebDav;

use App\Models\File;
use App\Models\User;
use App\Module\Base;
use Illuminate\Support\Facades\DB;

class WebDavConfig
{
    public static function get(): array
    {
        $setting = Base::setting('fileSetting');
        $permissionType = $setting['webdav_permission_type'] ?? 'all';
        if (!in_array($permissionType, ['all', 'appoint'], true)) {
            $permissionType = 'all';
        }

        $maxExpireDays = min(3650, max(1, intval($setting['webdav_max_expire_days'] ?? 365)));
        return [
            'enabled' => ($setting['webdav_enabled'] ?? 'close') === 'open',
            'permission_type' => $permissionType,
            'permission_userids' => self::normalizeUserIds($setting['webdav_permission_userids'] ?? []),
            'max_credentials' => min(20, max(1, intval($setting['webdav_max_credentials'] ?? 5))),
            'default_expire_days' => min($maxExpireDays, max(1, intval($setting['webdav_default_expire_days'] ?? 90))),
            'max_expire_days' => $maxExpireDays,
            'max_file_bytes' => min(
                intval(config('dootask.webdav.max_file_bytes', 1024 * 1024 * 1024)),
                max(1024 * 1024, intval($setting['webdav_max_file_bytes'] ?? 1024 * 1024 * 1024))
            ),
            'copy_max_nodes' => min(10000, max(1, intval($setting['webdav_copy_max_nodes'] ?? 1000))),
            'audit_retention_days' => min(365, max(7, intval($setting['webdav_audit_retention_days'] ?? 90))),
        ];
    }

    public static function isAllowed(User $user, ?array $config = null): bool
    {
        $config ??= self::get();
        if (!$config['enabled'] || $user->isDisable(true)) {
            return false;
        }
        return $config['permission_type'] === 'all'
            || in_array(intval($user->userid), $config['permission_userids'], true);
    }

    public static function url(): string
    {
        return rtrim(request()->getSchemeAndHttpHost(), '/') . '/dav/';
    }

    public static function normalizeAdminInput(array $input): array
    {
        $enabled = ($input['webdav_enabled'] ?? 'close') === 'open' ? 'open' : 'close';
        $permissionType = ($input['webdav_permission_type'] ?? 'all') === 'appoint' ? 'appoint' : 'all';
        $maxExpireDays = min(3650, max(1, intval($input['webdav_max_expire_days'] ?? 365)));
        return [
            'webdav_enabled' => $enabled,
            'webdav_permission_type' => $permissionType,
            'webdav_permission_userids' => self::normalizeUserIds($input['webdav_permission_userids'] ?? []),
            'webdav_max_credentials' => min(20, max(1, intval($input['webdav_max_credentials'] ?? 5))),
            'webdav_default_expire_days' => min($maxExpireDays, max(1, intval($input['webdav_default_expire_days'] ?? 90))),
            'webdav_max_expire_days' => $maxExpireDays,
            'webdav_max_file_bytes' => min(
                intval(config('dootask.webdav.max_file_bytes', 1024 * 1024 * 1024)),
                max(1024 * 1024, intval($input['webdav_max_file_bytes'] ?? 1024 * 1024 * 1024))
            ),
            'webdav_copy_max_nodes' => min(10000, max(1, intval($input['webdav_copy_max_nodes'] ?? 1000))),
            'webdav_audit_retention_days' => min(365, max(7, intval($input['webdav_audit_retention_days'] ?? 90))),
        ];
    }

    public static function adminForm(): array
    {
        $config = self::get();
        return [
            'webdav_enabled' => $config['enabled'] ? 'open' : 'close',
            'webdav_permission_type' => $config['permission_type'],
            'webdav_permission_userids' => $config['permission_userids'],
            'webdav_max_credentials' => $config['max_credentials'],
            'webdav_default_expire_days' => $config['default_expire_days'],
            'webdav_max_expire_days' => $config['max_expire_days'],
            'webdav_max_file_bytes' => $config['max_file_bytes'],
            'webdav_copy_max_nodes' => $config['copy_max_nodes'],
            'webdav_audit_retention_days' => $config['audit_retention_days'],
        ];
    }

    public static function pathConflictCount(): int
    {
        return DB::query()->fromSub(function ($query) {
            $query->from('files')
                ->select(['pid', 'userid', 'name', 'ext'])
                ->selectRaw('COUNT(*) AS aggregate')
                ->whereNull('deleted_at')
                ->groupBy(['pid', 'userid', 'name', 'ext'])
                ->havingRaw('COUNT(*) > 1');
        }, 'duplicates')->count();
    }

    public static function pathConflicts(int $page = 1, int $pageSize = 20, ?User $viewer = null): array
    {
        $page = max(1, $page);
        $pageSize = min(100, max(1, $pageSize));
        $total = self::pathConflictCount();
        $groups = DB::table('files')
            ->select(['pid', 'userid', 'name', 'ext'])
            ->selectRaw('COUNT(*) AS file_count')
            ->whereNull('deleted_at')
            ->groupBy(['pid', 'userid', 'name', 'ext'])
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('userid')
            ->orderBy('pid')
            ->orderBy('name')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        $userIds = $groups->pluck('userid')->map('intval')->unique()->values();
        $users = User::whereIn('userid', $userIds)->get(['userid', 'nickname', 'email'])->keyBy('userid');
        $data = $groups->map(function ($group) use ($users, $viewer) {
            $files = File::wherePid($group->pid)
                ->whereUserid($group->userid)
                ->whereName($group->name)
                ->whereExt($group->ext)
                ->orderBy('id')
                ->get(['id', 'pid', 'pids', 'name', 'ext', 'type', 'userid', 'created_id', 'share', 'pshare', 'updated_at']);
            $ancestorIds = $files->flatMap(fn(File $file) => self::pathIds($file->pids))->unique()->values();
            $ancestors = File::withTrashed()->whereIn('id', $ancestorIds)
                ->get(['id', 'name', 'ext', 'type', 'deleted_at'])->keyBy('id');
            $first = $files->first();
            $parentNames = $first ? collect(self::pathIds($first->pids))->map(function (int $id) use ($ancestors) {
                /** @var File|null $ancestor */
                $ancestor = $ancestors->get($id);
                return $ancestor ? $ancestor->getNameAndExt() : "#{$id}";
            })->all() : [];
            $fullName = $first?->getNameAndExt() ?? (string) $group->name;
            $user = $users->get(intval($group->userid));

            return [
                'owner' => [
                    'userid' => intval($group->userid),
                    'nickname' => $user?->nickname ?: '',
                    'email' => $user?->email ?: '',
                ],
                'parent_id' => intval($group->pid),
                'parent_path' => '/' . implode('/', $parentNames),
                'path' => '/' . implode('/', array_merge($parentNames, [$fullName])),
                'file_count' => intval($group->file_count),
                'files' => $files->map(function (File $file) use ($viewer) {
                    $canOpenLocation = false;
                    $locationBoard = null;
                    $locationParentId = null;
                    if ($viewer) {
                        $isOwner = intval($file->userid) === intval($viewer->userid);
                        $permission = $file->getPermission($viewer->isTemp() ? [$viewer->userid] : [0, $viewer->userid]);
                        $isShared = intval($file->pshare) > 0;
                        $canOpenLocation = $isOwner || ($isShared && $permission >= 0);
                        if ($canOpenLocation) {
                            $locationBoard = $isOwner ? 'mine' : 'shared';
                            $locationParentId = !$isOwner && intval($file->pshare) === intval($file->id)
                                ? 0
                                : intval($file->pid);
                        }
                    }

                    return [
                        'id' => intval($file->id),
                        'type' => $file->type,
                        'full_name' => $file->getNameAndExt(),
                        'updated_at' => $file->updated_at?->toDateTimeString(),
                        'can_open_location' => $canOpenLocation,
                        'location_board' => $locationBoard,
                        'location_parent_id' => $locationParentId,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return [
            'current_page' => $page,
            'per_page' => $pageSize,
            'total' => $total,
            'data' => $data,
        ];
    }

    public static function filePath(File $file): string
    {
        $ancestorIds = self::pathIds($file->pids);
        $ancestors = File::withTrashed()->whereIn('id', $ancestorIds)
            ->get(['id', 'name', 'ext'])->keyBy('id');
        $names = collect($ancestorIds)->map(function (int $id) use ($ancestors) {
            /** @var File|null $ancestor */
            $ancestor = $ancestors->get($id);
            return $ancestor ? $ancestor->getNameAndExt() : "#{$id}";
        })->all();
        return '/' . implode('/', array_merge($names, [$file->getNameAndExt()]));
    }

    private static function pathIds(?string $pids): array
    {
        if (!$pids) {
            return [];
        }
        return array_values(array_filter(array_map('intval', explode(',', trim($pids, ',')))));
    }

    private static function normalizeUserIds(mixed $userIds): array
    {
        if (!is_array($userIds)) {
            return [];
        }
        return array_values(array_unique(array_filter(
            array_map('intval', $userIds),
            fn(int $userId) => $userId > 0
        )));
    }
}
