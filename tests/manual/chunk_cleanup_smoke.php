<?php
/**
 * 验证 DeleteTmpTask::tmp_chunks 清理逻辑。
 *   docker exec dootask-php-3bed84 php /var/www/tests/manual/chunk_cleanup_smoke.php
 */
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Tasks\DeleteTmpTask;

function out(string $m): void { echo "[" . date('H:i:s') . "] $m\n"; }
function fail(string $m): void { out("FAIL: $m"); exit(1); }

$root = public_path('uploads/tmp/chunks');
@mkdir("$root/9999/old_session", 0775, true);
@mkdir("$root/9999/new_session", 0775, true);
file_put_contents("$root/9999/old_session/0", 'old');
file_put_contents("$root/9999/new_session/0", 'new');

// 把 old_session 的 mtime 改成 25 小时前
$past = time() - 3600 * 25;
touch("$root/9999/old_session", $past);
touch("$root/9999/old_session/0", $past);

$task = new DeleteTmpTask('tmp_chunks', 24);
$task->start();

if (is_dir("$root/9999/old_session")) {
    fail('old_session 应被清理但仍在');
}
if (!is_dir("$root/9999/new_session")) {
    fail('new_session 不应被清理却被删');
}
out('OK: old_session 清掉 / new_session 保留');

// 清理测试残留
exec("rm -rf " . escapeshellarg("$root/9999"));
out('=== 通过 ===');
