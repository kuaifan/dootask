<?php

namespace Tests\Feature;

use App\Models\AppPushDevice;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\WebSocketDialogMsgRead;
use App\Module\AppPush\AppPush;
use App\Module\AppPush\DooPushClient;
use App\Module\Base;
use App\Module\Doo;
use App\Services\RequestContext;
use App\Tasks\PushAppMsg;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AppPushTest extends TestCase
{
    use DatabaseTransactions;

    private $originalDispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        $this->originalDispatcher = Model::getEventDispatcher();
        // 测试数据不触发无关的搜索同步和 Swoole 任务。
        Model::setEventDispatcher(new Dispatcher(app()));
        RequestContext::clean();
        $this->settings();
    }

    protected function tearDown(): void
    {
        RequestContext::clean();
        Model::setEventDispatcher($this->originalDispatcher);
        parent::tearDown();
    }

    private function settings(array $overrides = []): void
    {
        Base::setting('appPushSetting', array_merge([
            'doopush' => 'custom',
            'doopush_app_id' => '123',
            'doopush_app_secret' => 'test-secret',
            'doopush_base_url' => '',
        ], $overrides));
    }

    private function userToken(bool $admin = false): array
    {
        $user = User::createInstance([
            'email' => uniqid('app_push_', true) . '@test.local',
            'nickname' => 'Push test',
            'encrypt' => bin2hex(random_bytes(8)),
            'identity' => $admin ? 'admin' : 'normal',
        ]);
        $user->save();
        $token = Doo::tokenEncode($user->userid, $user->email, $user->encrypt, 1);
        UserDevice::record($token);
        return [$user, $token];
    }

    private function device(int $userid, string $id, array $overrides = []): AppPushDevice
    {
        $device = AppPushDevice::createInstance(array_merge([
            'userid' => $userid, 'channel' => 'doopush', 'device_id' => $id,
            'vendor' => 'apns', 'platform' => 'ios', 'device_hash' => md5('test-token'),
        ], $overrides));
        $device->save();
        return $device;
    }

    public function test_get_and_post_update_transfer_device(): void
    {
        [$first, $token] = $this->userToken();
        $data = [
            'device_id' => '94001', 'vendor' => 'apns',
            'device_model' => 'iPhone', 'app_version' => '245', 'app_version_name' => '1.9.66',
            'user_agent' => 'Push test UA', 'is_notified' => 'true',
        ];
        $this->postJson('/api/push/device', $data, ['token' => $token, 'platform' => 'iOS'])
            ->assertOk()->assertJsonPath('ret', 1);
        $row = AppPushDevice::whereDeviceId('94001')->sole();
        $this->assertSame($first->userid, $row->userid);
        $this->assertSame(md5($token), $row->device_hash);
        $this->assertSame('1.9.66 (245)', $row->version);
        $this->assertSame('ios', $row->platform);
        $this->assertSame('iPhone', $row->device);
        $this->assertSame('Push test UA', $row->ua);
        $this->assertSame(1, $row->is_notified);

        [$second, $secondToken] = $this->userToken();
        $this->getJson('/api/push/device?' . http_build_query([
            'device_id' => '94001', 'vendor' => 'fcm', 'is_notified' => 1,
        ]), ['token' => $secondToken, 'platform' => 'ANDROID'])
            ->assertOk()->assertJsonPath('ret', 1);
        $updated = AppPushDevice::whereDeviceId('94001')->sole();
        $this->assertSame($row->id, $updated->id);
        $this->assertSame($second->userid, $updated->userid);
        $this->assertSame(md5($secondToken), $updated->device_hash);
        $this->assertSame($row->created_at->toDateTimeString(), $updated->created_at->toDateTimeString());
        $this->assertSame('android', $updated->platform);
        $this->assertSame(1, $updated->is_notified);
    }

    public function test_remove_accepts_invalid_token_but_requires_matching_hash_id_and_channel(): void
    {
        [, $token] = $this->userToken();
        [, $wrongToken] = $this->userToken();
        // 使用真实格式但已撤销的 token，避免格式错误字符串触发原生解码器异常。
        UserDevice::whereHash(md5($token))->delete();
        Cache::forget(UserDevice::ck(md5($token)));
        $row = $this->device(1, '94003', ['device_hash' => md5($token)]);
        $otherChannel = $this->device(1, '94003', ['channel' => 'other', 'device_hash' => md5($token)]);
        $otherId = $this->device(1, '94004', ['device_hash' => md5($token)]);
        $data = ['action' => 'remove', 'device_id' => '94003'];
        $this->postJson('/api/push/device', $data)->assertJsonPath('ret', 1);
        $this->postJson('/api/push/device', $data, ['token' => $wrongToken])->assertJsonPath('ret', 1);
        $this->assertNotNull($row->fresh());
        $this->getJson('/api/push/device?' . http_build_query($data), ['token' => $token])->assertJsonPath('ret', 1);
        $this->assertNull($row->fresh());
        $this->assertNotNull($otherChannel->fresh());
        $this->assertNotNull($otherId->fresh());
    }

    public function test_update_validates_token_platform_vendor_and_digit_string(): void
    {
        foreach (['', '-1', '12a', str_repeat('1', 65), ['12'], 123] as $id) {
            $this->postJson('/api/push/device', ['device_id' => $id])->assertJsonPath('ret', 0);
        }
        $this->postJson('/api/push/device', ['device_id' => '94005', 'vendor' => 'apns'])->assertJsonPath('ret', -1);
        [, $token] = $this->userToken();
        $this->postJson('/api/push/device', ['device_id' => '94005', 'vendor' => 'apns'], [
            'token' => $token, 'platform' => 'web',
        ])->assertJsonPath('ret', 0);
        $this->postJson('/api/push/device', ['device_id' => '94005', 'vendor' => 'unknown'], [
            'token' => $token, 'platform' => 'ios',
        ])->assertJsonPath('ret', 0);
        $this->assertFalse(AppPushDevice::whereDeviceId('94005')->exists());
    }

    public function test_logout_cleans_device_bindings(): void
    {
        $token = 'logout-test-token';
        $row = $this->device(1, '94006', ['device_hash' => md5($token)]);
        $other = $this->device(1, '94007');
        UserDevice::forget($token);
        $this->assertNull($row->fresh());
        $this->assertNotNull($other->fresh());
    }

    public function test_settings_default_and_save_filter_unknown_keys(): void
    {
        Base::setting('appPushSetting', []);
        [, $token] = $this->userToken(true);
        $this->getJson('/api/system/setting/apppush', ['token' => $token])
            ->assertJsonPath('ret', 1)->assertJsonPath('data.doopush', 'system');
        config(['dootask.system_setting' => 'enabled']);
        $this->postJson('/api/system/setting/apppush', [
            'type' => 'save', 'push' => 'open', 'ios_key' => 'old-key',
            'doopush' => 'custom', 'doopush_app_id' => '123', 'doopush_app_secret' => 'new-secret',
            'doopush_base_url' => 'https://push.test/api/v1/', 'unexpected' => 'ignored',
        ], ['token' => $token])->assertJsonPath('ret', 1)
            ->assertJsonMissingPath('data.push')->assertJsonMissingPath('data.ios_key')
            ->assertJsonPath('data.doopush', 'custom')->assertJsonPath('data.doopush_app_id', '123')
            ->assertJsonPath('data.doopush_app_secret', 'new-secret')
            ->assertJsonPath('data.doopush_base_url', 'https://push.test/api/v1/')->assertJsonMissingPath('data.unexpected');
    }

    public function test_send_groups_latest_five_active_devices_per_user_with_unread_badge_and_data_payload(): void
    {
        $uid = 910001;
        $otherUid = 910002;
        for ($i = 1; $i <= 7; $i++) {
            $this->device($uid, (string)(95000 + $i), ['updated_at' => now()->subMinutes(8 - $i)]);
        }
        $this->device($uid, '95999', ['updated_at' => now()->subMonths(2)]);
        $this->device($uid, '95998', ['channel' => 'other']);
        $this->device($otherUid, '96001');
        foreach ([['silence' => 0], ['silence' => 0], ['silence' => 1], ['silence' => 0, 'read_at' => now()]] as $i => $fields) {
            WebSocketDialogMsgRead::createInstance(array_merge(['userid' => $uid, 'msg_id' => 97000 + $i], $fields))->save();
        }
        $client = new RecordingDooPushClient();
        $client->pushToUsers([$uid, $uid, $otherUid, -1], [
            'title' => "标题\n", 'body' => "正文\r\n第二行", 'extra' => ['dialog_id' => 41, 'msg_id' => 42],
        ]);
        $this->assertCount(2, $client->requests);
        $request = $client->requests[0];
        $this->assertSame('https://doopush.com/api/v1/apps/123/push', $request['url']);
        $this->assertSame('Bearer test-secret', $request['headers']['Authorization']);
        $this->assertSame('application/json', $request['headers']['Content-Type']);
        $body = $request['body'];
        $this->assertSame(['type' => 'devices', 'device_ids' => [95007, 95006, 95005, 95004, 95003]], $body['target']);
        $this->assertSame('标题', $body['title']);
        $this->assertSame('正文第二行', $body['content']);
        $this->assertSame(2, $body['badge']);
        $this->assertSame(['dialog_id' => '41', 'msg_id' => '42'], json_decode($body['payload']['data'], true));
        $this->assertArrayNotHasKey('dialog_id', $body['payload']);
        $this->assertSame(['importance' => 'NORMAL', 'category' => 'IM'], $body['payload']['huawei']);
        $this->assertSame(['classification' => 1], $body['payload']['vivo']);
        $this->assertSame([96001], $client->requests[1]['body']['target']['device_ids']);
        $this->assertSame(0, $client->requests[1]['body']['badge']);
    }

    public function test_title_fallback_empty_targets_and_configuration_guards(): void
    {
        $this->device(910003, '96002');
        $client = new RecordingDooPushClient();
        foreach ([['doopush' => 'close'], ['doopush_app_id' => ''], ['doopush_app_secret' => '']] as $settings) {
            $this->settings($settings);
            $client->pushToUsers(910003, ['body' => '内容']);
        }
        $this->settings(['doopush_base_url' => 'https://push.test/api/v1///']);
        $client->pushToUsers([], ['body' => '内容']);
        $client->pushToUsers(910003, ['title' => "\n", 'body' => "\r\n"]);
        $this->assertCount(0, $client->requests);
        $client->pushToUsers(910003, ['body' => "内容\n"]);
        $this->assertCount(1, $client->requests);
        $this->assertSame('内容', $client->requests[0]['body']['title']);
        $this->assertSame('https://push.test/api/v1/apps/123/push', $client->requests[0]['url']);
    }

    public function test_device_ids_cannot_overflow_into_a_different_target(): void
    {
        $this->device(910007, str_repeat('9', 64));
        $this->device(910007, '00096006');
        $client = new RecordingDooPushClient();
        $client->pushToUsers(910007, ['body' => '消息']);
        $this->assertCount(1, $client->requests);
        $this->assertSame([96006], $client->requests[0]['body']['target']['device_ids']);
    }

    public function test_failures_retry_at_most_twice_and_do_not_block_the_next_user(): void
    {
        $this->device(910004, '96003');
        $this->device(910005, '96004');
        $client = new RecordingDooPushClient([
            new \RuntimeException('Connection timeout'),
            ['ret' => 1, 'msg' => '200', 'data' => 'not-json'],
            ['ret' => 1, 'msg' => '400', 'data' => '{"code":400,"message":"Invalid device"}'],
        ]);
        $client->pushToUsers([910004, 910005], ['body' => '消息']);
        $this->assertCount(4, $client->requests);
        $this->assertSame([96003], $client->requests[2]['body']['target']['device_ids']);
        $this->assertSame([96004], $client->requests[3]['body']['target']['device_ids']);

        $client = new RecordingDooPushClient([['ret' => 0, 'msg' => 'Timeout', 'data' => []]]);
        $client->pushToUsers(910004, ['body' => '消息']);
        $this->assertCount(2, $client->requests);
    }

    public function test_doopush_only_task_keeps_pc_delay_and_read_skip_behavior(): void
    {
        $userid = 910006;
        $this->device($userid, '96005');
        $client = new RecordingDooPushClient();
        $this->app->instance(DooPushClient::class, $client);
        $this->assertTrue(AppPush::enabled());
        $msg = ['body' => '消息', 'extra' => ['msg_id' => 98001]];
        Cache::put("user_pc_active:{$userid}", time(), 60);
        (new PushAppMsg($userid, $msg))->start();
        $this->assertCount(0, $client->requests);

        Cache::forget("user_pc_active:{$userid}");
        (new PushAppMsg($userid, $msg))->start();
        $this->assertCount(1, $client->requests);
        WebSocketDialogMsgRead::createInstance(['userid' => $userid, 'msg_id' => 98001, 'read_at' => now()])->save();
        $delayed = new PushAppMsg($userid, $msg);
        $delayed->delay(10);
        $delayed->start();
        $this->assertCount(1, $client->requests);
        $this->settings(['doopush' => 'close']);
        $this->assertFalse(AppPush::enabled());
    }

    public function test_settings_reject_unknown_mode(): void
    {
        [, $token] = $this->userToken(true);
        config(['dootask.system_setting' => 'enabled']);
        $this->postJson('/api/system/setting/apppush', ['type' => 'save', 'doopush' => 'open'], ['token' => $token])
            ->assertJsonPath('ret', 0);
    }

    private function systemMode(?string $secret = null): RecordingDooPushClient
    {
        $this->settings(['doopush' => 'system']);
        config(['dootask.doopush_system' => ['base_url' => 'https://push.test/api/v1', 'app_id' => '10', 'app_key' => 'dp_ak_test']]);
        Base::setting('appPushTenant', $secret ? [
            'base_url' => 'https://push.test/api/v1', 'app_id' => '10', 'tenant_id' => 1, 'tenant_secret' => $secret,
        ] : []);
        $client = new RecordingDooPushClient();
        $this->app->instance(DooPushClient::class, $client);
        return $client;
    }

    private static function reply(int $status, array $json): array
    {
        return ['ret' => 1, 'msg' => (string)$status, 'data' => json_encode($json)];
    }

    public function test_system_mode_registers_tenant_and_binds_by_push_token(): void
    {
        $client = $this->systemMode();
        [$user, $token] = $this->userToken();
        $data = ['device_id' => '94101', 'vendor' => 'apns', 'is_notified' => 1];
        $this->postJson('/api/push/device', $data, ['token' => $token, 'platform' => 'ios'])->assertJsonPath('ret', 0);
        $this->assertCount(0, $client->requests);

        $client->responses = [
            self::reply(201, ['code' => 201, 'data' => ['tenant_id' => 7, 'tenant_secret' => 'dp_ts_new']]),
            self::reply(200, ['code' => 200, 'data' => ['device_id' => 77001]]),
        ];
        $this->postJson('/api/push/device', $data + ['push_token' => 'apns-token'], ['token' => $token, 'platform' => 'ios'])
            ->assertJsonPath('ret', 1);
        $this->assertSame('https://push.test/api/v1/apps/10/tenants', $client->requests[0]['url']);
        $this->assertSame('dp_ak_test', $client->requests[0]['headers']['X-App-Key']);
        $this->assertSame('https://push.test/api/v1/apps/10/tenant/devices', $client->requests[1]['url']);
        $this->assertSame('Bearer dp_ts_new', $client->requests[1]['headers']['Authorization']);
        $this->assertSame(['token' => 'apns-token'], $client->requests[1]['body']);
        // 以 DooPush 返回的设备ID为准，不信任客户端自报。
        $row = AppPushDevice::whereChannel(AppPush::CHANNEL_SYSTEM)->sole();
        $this->assertSame('77001', $row->device_id);
        $this->assertSame($user->userid, $row->userid);
        $this->assertFalse(AppPushDevice::whereDeviceId('94101')->exists());
    }

    public function test_system_mode_reregisters_on_401_but_not_on_403(): void
    {
        $client = $this->systemMode('dp_ts_old');
        [, $token] = $this->userToken();
        $data = ['device_id' => '94102', 'vendor' => 'apns', 'push_token' => 'apns-token'];
        $client->responses = [
            self::reply(401, ['code' => 401, 'message' => 'invalid']),
            self::reply(201, ['code' => 201, 'data' => ['tenant_id' => 8, 'tenant_secret' => 'dp_ts_renew']]),
            self::reply(200, ['code' => 200, 'data' => ['device_id' => 77002]]),
        ];
        $this->postJson('/api/push/device', $data, ['token' => $token, 'platform' => 'ios'])->assertJsonPath('ret', 1);
        $this->assertCount(3, $client->requests);
        $this->assertSame('Bearer dp_ts_renew', $client->requests[2]['headers']['Authorization']);

        $client->requests = [];
        $client->responses = [self::reply(403, ['code' => 403, 'message' => 'disabled'])];
        $this->postJson('/api/push/device', $data, ['token' => $token, 'platform' => 'ios'])->assertJsonPath('ret', 0);
        $this->assertCount(1, $client->requests);
        $this->assertSame('dp_ts_renew', Base::setting('appPushTenant')['tenant_secret']);
    }

    public function test_system_mode_push_cleans_unbound_devices_and_remove_unbinds(): void
    {
        $client = $this->systemMode('dp_ts_live');
        $this->device(910101, '77003', ['channel' => AppPush::CHANNEL_SYSTEM]);
        $this->device(910101, '77004', ['channel' => AppPush::CHANNEL_SYSTEM, 'updated_at' => now()->subMinute()]);
        $this->device(910101, '96101');
        $client->responses = [self::reply(200, ['code' => 200, 'data' => ['count' => 1, 'unbound_device_ids' => [77004]]])];
        $client->pushToUsers(910101, ['body' => '消息']);
        $this->assertCount(1, $client->requests);
        $this->assertSame('https://push.test/api/v1/apps/10/tenant/push', $client->requests[0]['url']);
        $this->assertSame('Bearer dp_ts_live', $client->requests[0]['headers']['Authorization']);
        $this->assertSame([77003, 77004], $client->requests[0]['body']['device_ids']);
        $this->assertArrayNotHasKey('target', $client->requests[0]['body']);
        $this->assertFalse(AppPushDevice::whereChannel(AppPush::CHANNEL_SYSTEM)->whereDeviceId('77004')->exists());

        // 401 时清除失效凭证，不重试。
        $client->requests = [];
        $client->responses = [self::reply(401, ['code' => 401, 'message' => 'invalid'])];
        $client->pushToUsers(910101, ['body' => '消息']);
        $this->assertCount(1, $client->requests);
        $this->assertSame([], Base::setting('appPushTenant'));

        $client = $this->systemMode('dp_ts_live');
        [$user, $token] = $this->userToken();
        $this->device($user->userid, '77005', ['channel' => AppPush::CHANNEL_SYSTEM, 'device_hash' => md5($token)]);
        $this->postJson('/api/push/device', ['action' => 'remove', 'device_id' => '77005'], ['token' => $token])
            ->assertJsonPath('ret', 1);
        $this->assertFalse(AppPushDevice::whereDeviceId('77005')->exists());
        $this->assertSame('DELETE', $client->requests[0]['method']);
        $this->assertSame('https://push.test/api/v1/apps/10/tenant/devices/77005', $client->requests[0]['url']);
    }

    public function test_close_mode_does_not_record_devices(): void
    {
        $this->settings(['doopush' => 'close']);
        [, $token] = $this->userToken();
        $this->postJson('/api/push/device', ['device_id' => '94103', 'vendor' => 'apns'], ['token' => $token, 'platform' => 'ios'])
            ->assertJsonPath('ret', 1);
        $this->assertFalse(AppPushDevice::whereDeviceId('94103')->exists());
    }
}

/** 用可控响应替换外部网络，验证实际数据库选出的发送目标与载荷。 */
class RecordingDooPushClient extends DooPushClient
{
    public array $requests = [];

    public function __construct(public array $responses = []) {}

    protected function request(string $url, array $headers, array $body, string $method = 'POST'): array
    {
        $this->requests[] = compact('url', 'headers', 'body', 'method');
        $response = array_shift($this->responses);
        if ($response instanceof \Throwable) {
            throw $response;
        }
        return $response ?? ['ret' => 1, 'msg' => '200', 'data' => '{"code":200,"data":[]}'];
    }
}
