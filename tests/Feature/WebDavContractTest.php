<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\FileDavController;
use App\Http\Controllers\WebDavProtocolController;
use App\Models\File;
use App\Models\FileUser;
use App\Models\User;
use App\Models\WebDavCredential;
use App\Models\WebDavLock;
use App\Models\WebDavOperationLog;
use App\Services\WebDav\WebDavCredentialService;
use App\Services\WebDav\WebDavConfig;
use App\Services\WebDav\WebDavConflictService;
use App\Services\WebDav\WebDavExceptionMapper;
use App\Services\WebDav\WebDavServerFactory;
use App\Services\FileSystem\FileSystemService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Sabre\DAV\Exception\Conflict;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\HTTP\Response as SabreResponse;
use Tests\TestCase;

class WebDavContractTest extends TestCase
{
    use DatabaseTransactions;

    public function test_management_route_maps_action_to_dav_controller_method(): void
    {
        $route = app('router')->getRoutes()->match(Request::create('/api/file/dav/status', 'GET'));

        $this->assertSame(FileDavController::class, $route->getActionName());
        $this->assertSame('dav', $route->parameter('method'));
        $this->assertSame('status', $route->parameter('action'));

        $response = app('router')->dispatch(Request::create('/api/file/dav/missing', 'GET'));
        $this->assertStringContainsString('404 not found (dav/missing)', $response->getContent());
    }

    public function test_protocol_route_accepts_webdav_methods_and_captures_path(): void
    {
        foreach (['OPTIONS', 'PROPFIND', 'PROPPATCH', 'HEAD', 'GET', 'PUT', 'MKCOL', 'COPY', 'MOVE', 'DELETE', 'LOCK', 'UNLOCK'] as $method) {
            $route = app('router')->getRoutes()->match(Request::create('/dav/files/folder/a.txt', $method));
            $this->assertSame(WebDavProtocolController::class, $route->getActionName());
            $this->assertSame('files/folder/a.txt', $route->parameter('path'));
        }
    }

    public function test_admin_configuration_is_bounded_and_consistent(): void
    {
        $setting = WebDavConfig::normalizeAdminInput([
            'webdav_enabled' => 'open',
            'webdav_permission_type' => 'appoint',
            'webdav_permission_userids' => ['2', 2, 0, -1],
            'webdav_max_credentials' => 100,
            'webdav_default_expire_days' => 90,
            'webdav_max_expire_days' => 30,
            'webdav_max_file_bytes' => PHP_INT_MAX,
            'webdav_copy_max_nodes' => 0,
            'webdav_audit_retention_days' => 1,
        ]);

        $this->assertSame('open', $setting['webdav_enabled']);
        $this->assertSame('appoint', $setting['webdav_permission_type']);
        $this->assertSame([2], $setting['webdav_permission_userids']);
        $this->assertSame(20, $setting['webdav_max_credentials']);
        $this->assertSame(30, $setting['webdav_default_expire_days']);
        $this->assertSame(30, $setting['webdav_max_expire_days']);
        $this->assertSame(config('dootask.webdav.max_file_bytes'), $setting['webdav_max_file_bytes']);
        $this->assertSame(1, $setting['webdav_copy_max_nodes']);
        $this->assertSame(7, $setting['webdav_audit_retention_days']);
    }

    public function test_path_conflicts_include_owner_path_and_file_ids(): void
    {
        $user = User::query()->firstOrFail();
        $folder = File::createInstance([
            'pid' => 0,
            'pids' => '',
            'userid' => $user->userid,
            'name' => 'WebDAV conflict folder',
            'ext' => '',
            'type' => 'folder',
        ]);
        $folder->save();
        $files = collect([1, 2])->map(function () use ($folder, $user) {
            $file = File::createInstance([
                'pid' => $folder->id,
                'pids' => ",{$folder->id},",
                'userid' => $user->userid,
                'name' => 'duplicate',
                'ext' => 'txt',
                'type' => 'file',
            ]);
            $file->save();
            return $file;
        });

        $result = WebDavConfig::pathConflicts(1, 100, $user);
        $conflict = collect($result['data'])->firstWhere('path', '/WebDAV conflict folder/duplicate.txt');

        $this->assertNotNull($conflict);
        $this->assertSame(intval($user->userid), $conflict['owner']['userid']);
        $this->assertSame(intval($folder->id), $conflict['parent_id']);
        $this->assertSame($files->pluck('id')->map('intval')->all(), array_column($conflict['files'], 'id'));
        $this->assertSame('duplicate.txt', $conflict['files'][0]['full_name']);
        $this->assertTrue($conflict['files'][0]['can_open_location']);
        $this->assertSame('mine', $conflict['files'][0]['location_board']);
        $this->assertSame(intval($folder->id), $conflict['files'][0]['location_parent_id']);

        (new FileSystemService())->rename($user, $files->first(), 'renamed.txt');
        $updated = WebDavConfig::pathConflicts(1, 100);
        $this->assertNull(collect($updated['data'])->firstWhere('path', '/WebDAV conflict folder/duplicate.txt'));
    }

    public function test_path_conflicts_only_expose_locations_allowed_by_existing_file_permissions(): void
    {
        $owner = User::query()->firstOrFail();
        $admin = User::createInstance([
            'userid' => intval($owner->userid) + 1000001,
            'identity' => ',admin,',
        ]);
        $admin->save();

        $sharedFiles = collect([1, 2])->map(function () use ($owner, $admin) {
            $file = File::createInstance([
                'pid' => 0,
                'pids' => '',
                'userid' => $owner->userid,
                'name' => 'shared-conflict',
                'ext' => 'txt',
                'type' => 'file',
            ]);
            $file->save();
            $file->share = 1;
            $file->pshare = $file->id;
            $file->save();
            FileUser::createInstance([
                'file_id' => $file->id,
                'userid' => $admin->userid,
                'permission' => 0,
            ])->save();
            return $file;
        });
        collect([1, 2])->each(function () use ($owner) {
            File::createInstance([
                'pid' => 0,
                'pids' => '',
                'userid' => $owner->userid,
                'name' => 'private-conflict',
                'ext' => 'txt',
                'type' => 'file',
            ])->save();
        });

        $result = WebDavConfig::pathConflicts(1, 100, $admin);
        $shared = collect($result['data'])->firstWhere('path', '/shared-conflict.txt');
        $private = collect($result['data'])->firstWhere('path', '/private-conflict.txt');

        $this->assertNotNull($shared);
        $this->assertSame($sharedFiles->pluck('id')->map('intval')->all(), array_column($shared['files'], 'id'));
        $this->assertTrue($shared['files'][0]['can_open_location']);
        $this->assertSame('shared', $shared['files'][0]['location_board']);
        $this->assertSame(0, $shared['files'][0]['location_parent_id']);
        $this->assertNotNull($private);
        $this->assertFalse($private['files'][0]['can_open_location']);
        $this->assertNull($private['files'][0]['location_board']);
        $this->assertNull($private['files'][0]['location_parent_id']);
    }

    public function test_file_errors_map_to_webdav_status_categories(): void
    {
        $this->assertInstanceOf(NotFound::class, WebDavExceptionMapper::map(new \App\Exceptions\ApiException('文件不存在')));
        $this->assertInstanceOf(Conflict::class, WebDavExceptionMapper::map(new \App\Exceptions\ApiException('文件已存在')));
        $this->assertSame(413, WebDavExceptionMapper::map(new \App\Exceptions\ApiException('文件大小超过限制'))->getHTTPCode());
        $this->assertInstanceOf(Forbidden::class, WebDavExceptionMapper::map(new \App\Exceptions\ApiException('没有修改写入权限')));
    }

    public function test_admin_can_rename_another_users_conflicting_file_with_audit_log(): void
    {
        $owner = User::query()->firstOrFail();
        $admin = User::createInstance([
            'userid' => intval($owner->userid) + 1000000,
            'identity' => ',admin,',
        ]);
        $files = collect([1, 2])->map(function () use ($owner) {
            $file = File::createInstance([
                'pid' => 0,
                'pids' => '',
                'userid' => $owner->userid,
                'name' => 'admin-conflict',
                'ext' => 'txt',
                'type' => 'file',
            ]);
            $file->save();
            return $file;
        });

        $renamed = (new WebDavConflictService())->renameAsAdmin(
            $admin,
            intval($files->first()->id),
            'admin-renamed.txt',
            'test-request',
            '127.0.0.1',
            'phpunit'
        );

        $this->assertSame('admin-renamed.txt', $renamed->getNameAndExt());
        $this->assertSame(intval($owner->userid), intval($renamed->userid));
        $this->assertSame(1, File::whereName('admin-conflict')->whereExt('txt')->count());
        $this->assertDatabaseHas('webdav_operation_logs', [
            'userid' => $admin->userid,
            'method' => 'ADMIN_RENAME',
            'file_id' => $renamed->id,
            'status' => 200,
        ]);
        $log = WebDavOperationLog::whereFileId($renamed->id)->whereMethod('ADMIN_RENAME')->latest('id')->firstOrFail();
        $this->assertStringContainsString('admin-conflict.txt -> admin-renamed.txt', (string) $log->result);
    }

    public function test_only_inactive_credentials_can_be_permanently_deleted_with_audit_retained(): void
    {
        $user = User::query()->firstOrFail();
        [$credential] = WebDavCredential::issue($user, 'delete-test', 30);
        $service = new WebDavCredentialService();

        try {
            $service->deleteInactive($user, intval($credential->id), 'delete-active', '127.0.0.1', 'phpunit');
            $this->fail('Active credential deletion should be rejected.');
        } catch (\App\Exceptions\ApiException $exception) {
            $this->assertSame('有效的应用密码请先撤销', $exception->getMessage());
        }

        $credential->revoke();
        WebDavLock::createInstance([
            'token' => 'delete-test-lock',
            'userid' => $user->userid,
            'credential_id' => $credential->id,
            'uri' => 'files/delete-test',
            'uri_hash' => hash('sha256', 'files/delete-test'),
            'scope' => 'exclusive',
            'depth' => 'infinity',
            'timeout_at' => now()->addHour(),
        ])->save();

        $service->deleteInactive($user, intval($credential->id), 'delete-inactive', '127.0.0.1', 'phpunit');

        $this->assertNull(WebDavCredential::find($credential->id));
        $this->assertFalse(WebDavLock::whereCredentialId($credential->id)->exists());
        $this->assertDatabaseHas('webdav_operation_logs', [
            'request_id' => 'delete-inactive',
            'userid' => $user->userid,
            'credential_id' => $credential->id,
            'method' => 'CREDENTIAL_DELETE',
            'status' => 200,
        ]);
    }

    public function test_server_factory_registers_required_plugins(): void
    {
        $user = User::createInstance(['userid' => 123]);
        $credential = WebDavCredential::createInstance(['id' => 456]);
        $server = (new WebDavServerFactory())->make($user, $credential);

        $this->assertNotNull($server->getPlugin('locks'));
        $this->assertNotNull($server->getPlugin('property-storage'));
        $this->assertNotNull($server->getPlugin('dootask-move'));
        $this->assertNotNull($server->getPlugin('dootask-copy-guard'));
        $this->assertFalse(\Sabre\DAV\Server::$exposeVersion);
    }

    public function test_generated_api_map_contains_webdav_management_routes(): void
    {
        $map = file_get_contents(base_path('routes/api-map.md'));

        $this->assertStringContainsString('api/file/dav/status', $map);
        $this->assertStringContainsString('dav__status()', $map);
        $this->assertStringContainsString('api/file/dav/adminsetting', $map);
        $this->assertStringContainsString('api/file/dav/delete', $map);
    }

    public function test_protocol_bridge_handles_sabre_null_body_as_empty_response(): void
    {
        $controller = new WebDavProtocolController();
        $method = new \ReflectionMethod($controller, 'toSymfonyResponse');
        $response = $method->invoke($controller, new SabreResponse(201));

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }
}
