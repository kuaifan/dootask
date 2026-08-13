<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Models\User;
use App\Models\WebDavCredential;
use App\Models\WebDavLock;
use App\Module\Base;
use App\Services\WebDav\WebDavConfig;
use App\Services\WebDav\WebDavConflictService;
use App\Services\WebDav\WebDavCredentialService;
use Request;

/**
 * @apiDefine fileDav
 *
 * WebDAV 管理
 */
class FileDavController extends AbstractController
{
    /**
     * Laravel passes the dynamic action before the route default for this fixed-method route.
     */
    public function __invoke($action, $method = 'dav')
    {
        return parent::__invoke($method, $action);
    }

    /**
     * @api {get} api/file/dav/status 获取 WebDAV 状态
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup fileDav
     * @apiName dav__status
     */
    public function dav__status()
    {
        $user = User::auth();
        $config = WebDavConfig::get();
        return Base::retSuccess('success', [
            'enabled' => $config['enabled'],
            'allowed' => WebDavConfig::isAllowed($user, $config),
            'https' => Request::secure(),
            'url' => WebDavConfig::url(),
            'max_credentials' => $config['max_credentials'],
            'active_credentials' => WebDavCredential::whereUserid($user->userid)
                ->whereNull('revoked_at')
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })->count(),
            'default_expire_days' => $config['default_expire_days'],
            'max_expire_days' => $config['max_expire_days'],
            'max_file_bytes' => $config['max_file_bytes'],
        ]);
    }

    /**
     * @api {get} api/file/dav/credentials 获取 WebDAV 凭据
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup fileDav
     * @apiName dav__credentials
     */
    public function dav__credentials()
    {
        $user = User::auth();
        $list = WebDavCredential::whereUserid($user->userid)->orderByDesc('id')->get();
        $data = $list->map(function (WebDavCredential $credential) {
            return [
                'id' => $credential->id,
                'public_id' => $credential->public_id,
                'name' => $credential->name,
                'password_suffix' => $credential->password_suffix,
                'expires_at' => $credential->expires_at?->toDateTimeString(),
                'last_used_at' => $credential->last_used_at?->toDateTimeString(),
                'last_used_ip' => $credential->last_used_ip,
                'last_user_agent' => $credential->last_user_agent,
                'created_at' => $credential->created_at?->toDateTimeString(),
                'revoked_at' => $credential->revoked_at?->toDateTimeString(),
                'status' => $credential->status(),
            ];
        })->values();
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {post} api/file/dav/create 创建 WebDAV 应用密码
     * @apiDescription 需要token身份，密码只返回一次
     * @apiVersion 1.0.0
     * @apiGroup fileDav
     * @apiName dav__create
     */
    public function dav__create()
    {
        $user = User::auth();
        $config = WebDavConfig::get();
        if (!WebDavConfig::isAllowed($user, $config)) {
            throw new ApiException('WebDAV 未启用或你没有使用权限');
        }
        if (!Request::secure() && !app()->environment(['local', 'testing'])) {
            throw new ApiException('WebDAV 必须通过 HTTPS 使用');
        }
        $name = mb_substr(trim((string) Request::input('name')), 0, 100);
        if ($name === '') {
            throw new ApiException('请输入设备名称');
        }
        $expireDays = intval(Request::input('expire_days', $config['default_expire_days']));
        if ($expireDays < 1 || $expireDays > $config['max_expire_days']) {
            throw new ApiException('有效期超出允许范围');
        }
        $activeCount = WebDavCredential::whereUserid($user->userid)
            ->whereNull('revoked_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count();
        if ($activeCount >= $config['max_credentials']) {
            throw new ApiException('WebDAV 应用密码数量已达上限');
        }

        [$credential, $secret] = WebDavCredential::issue($user, $name, $expireDays);
        return Base::retSuccess('创建成功', [
            'id' => $credential->id,
            'public_id' => $credential->public_id,
            'password' => $secret,
            'password_suffix' => $credential->password_suffix,
            'url' => WebDavConfig::url(),
            'expires_at' => $credential->expires_at?->toDateTimeString(),
        ]);
    }

    /**
     * @api {post} api/file/dav/revoke 撤销 WebDAV 应用密码
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup fileDav
     * @apiName dav__revoke
     */
    public function dav__revoke()
    {
        $user = User::auth();
        $credential = WebDavCredential::whereUserid($user->userid)
            ->whereId(intval(Request::input('id')))
            ->first();
        if (!$credential) {
            throw new ApiException('WebDAV 应用密码不存在');
        }
        $credential->revoke();
        return Base::retSuccess('撤销成功', [
            'id' => $credential->id,
            'revoked_at' => $credential->revoked_at?->toDateTimeString(),
        ]);
    }

    /**
     * @api {post} api/file/dav/delete 永久删除已失效的 WebDAV 应用密码
     * @apiDescription 需要token身份，仅允许删除本人已撤销或已过期的凭据，操作日志保留
     * @apiVersion 1.0.0
     * @apiGroup fileDav
     * @apiName dav__delete
     *
     * @apiParam {Number} id 应用密码 ID
     */
    public function dav__delete()
    {
        $user = User::auth();
        $id = intval(Request::input('id'));
        (new WebDavCredentialService())->deleteInactive(
            $user,
            $id,
            Request::header('X-Request-Id'),
            Request::ip(),
            Request::userAgent()
        );
        return Base::retSuccess('删除成功', ['id' => $id]);
    }

    /**
     * @api {get} api/file/dav/adminsetting 获取或保存 WebDAV 设置
     * @apiDescription 需要管理员身份
     * @apiVersion 1.0.0
     * @apiGroup fileDav
     * @apiName dav__adminsetting
     */
    public function dav__adminsetting()
    {
        User::auth('admin');
        if (Request::input('type') === 'save') {
            if (config('dootask.system_setting') === 'disabled') {
                throw new ApiException('当前环境禁止修改');
            }
            $normalized = WebDavConfig::normalizeAdminInput(Request::input());
            if ($normalized['webdav_enabled'] === 'open' && WebDavConfig::pathConflictCount() > 0) {
                throw new ApiException('存在文件路径冲突，请先处理后再启用 WebDAV');
            }
            $current = Base::setting('fileSetting');
            $setting = array_merge($current, $normalized);
            Base::setting('fileSetting', $setting);
        }
        return Base::retSuccess(Request::input('type') === 'save' ? '保存成功' : 'success', WebDavConfig::adminForm());
    }

    /**
     * @api {get} api/file/dav/adminstatus 获取 WebDAV 运行状态
     * @apiDescription 需要管理员身份
     * @apiVersion 1.0.0
     * @apiGroup fileDav
     * @apiName dav__adminstatus
     */
    public function dav__adminstatus()
    {
        User::auth('admin');
        return Base::retSuccess('success', [
            'config' => WebDavConfig::get(),
            'active_credentials' => WebDavCredential::whereNull('revoked_at')
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })->count(),
            'active_locks' => WebDavLock::where('timeout_at', '>', now())->count(),
            'path_conflicts' => WebDavConfig::pathConflictCount(),
        ]);
    }

    /**
     * @api {get} api/file/dav/conflicts 获取 WebDAV 路径冲突明细
     * @apiDescription 需要管理员身份
     * @apiVersion 1.0.0
     * @apiGroup fileDav
     * @apiName dav__conflicts
     *
     * @apiParam {Number} [page=1] 页码
     * @apiParam {Number} [pagesize=20] 每页数量，最大 100
     * @apiSuccess {Boolean} data.data.files.can_open_location 当前管理员能否按现有文件权限打开所在位置
     * @apiSuccess {String} data.data.files.location_board 定位板块：mine 或 shared
     * @apiSuccess {Number} data.data.files.location_parent_id 定位目录 ID，共享根文件为 0
     */
    public function dav__conflicts()
    {
        $user = User::auth('admin');
        return Base::retSuccess('success', WebDavConfig::pathConflicts(
            intval(Request::input('page', 1)),
            intval(Request::input('pagesize', 20)),
            $user
        ));
    }

    /**
     * @api {post} api/file/dav/conflictrename 管理员重命名 WebDAV 冲突文件
     * @apiDescription 需要管理员身份，目标必须仍属于路径冲突组
     * @apiVersion 1.0.0
     * @apiGroup fileDav
     * @apiName dav__conflictrename
     *
     * @apiParam {Number} id 文件 ID
     * @apiParam {String} name 新的完整名称
     */
    public function dav__conflictrename()
    {
        $user = User::auth('admin');
        $name = trim((string) Request::input('name'));
        $file = (new WebDavConflictService())->renameAsAdmin(
            $user,
            intval(Request::input('id')),
            $name,
            Request::header('X-Request-Id'),
            Request::ip(),
            Request::userAgent()
        );
        return Base::retSuccess('重命名成功', [
            'id' => intval($file->id),
            'name' => $file->getNameAndExt(),
            'path_conflicts' => WebDavConfig::pathConflictCount(),
        ]);
    }

    /**
     * @api {post} api/file/dav/userrevoke 撤销用户全部 WebDAV 应用密码
     * @apiDescription 需要管理员身份
     * @apiVersion 1.0.0
     * @apiGroup fileDav
     * @apiName dav__userrevoke
     */
    public function dav__userrevoke()
    {
        User::auth('admin');
        $userid = intval(Request::input('userid'));
        if ($userid <= 0 || !User::whereUserid($userid)->exists()) {
            throw new ApiException('用户不存在');
        }
        $credentials = WebDavCredential::whereUserid($userid)->whereNull('revoked_at')->get();
        foreach ($credentials as $credential) {
            $credential->revoke();
        }
        return Base::retSuccess('撤销成功', [
            'userid' => $userid,
            'revoked_count' => $credentials->count(),
            'revoked_at' => now()->toDateTimeString(),
        ]);
    }
}
