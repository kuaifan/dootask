<?php
/**
 * 手工烟雾测试：ChunkUpload 端到端（跳过 HTTP，直接调模块）。
 *
 * 在 dootask-php-3bed84 容器内执行：
 *   docker exec dootask-php-3bed84 php /var/www/tests/manual/chunk_upload_smoke.php
 *
 * 步骤：新文件分片上传 → 秒传命中 → 续传场景 → 清理
 */
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\File as FileModel;
use App\Models\User;
use App\Module\ChunkUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Redis;

function out(string $msg): void
{
    echo "[" . date('H:i:s') . "] " . $msg . "\n";
}

function fail(string $msg): void
{
    out("FAIL: $msg");
    exit(1);
}

// 1) 找一个测试用户
$user = User::orderBy('userid')->first();
if (!$user) {
    fail('找不到任何用户');
}
out("使用用户 userid={$user->userid} email={$user->email}");

// 2) 准备 12MB 测试文件
$srcPath = '/tmp/cu_smoke_src.bin';
$size = 12 * 1024 * 1024;
$fp = fopen($srcPath, 'wb');
for ($i = 0; $i < $size / 4096; $i++) {
    fwrite($fp, str_repeat(chr($i % 256), 4096));
}
fclose($fp);
$hash = md5_file($srcPath);
out("准备文件: $srcPath size=$size hash=$hash");

// 3) 切分为 5MB 分片
$chunkSize = ChunkUpload::CHUNK_SIZE;
$chunkCount = intval(ceil($size / $chunkSize));
$chunks = [];
$fp = fopen($srcPath, 'rb');
for ($i = 0; $i < $chunkCount; $i++) {
    $partPath = "/tmp/cu_smoke_part_{$i}.bin";
    $wfp = fopen($partPath, 'wb');
    $bytesLeft = ($i === $chunkCount - 1) ? ($size - $chunkSize * $i) : $chunkSize;
    while ($bytesLeft > 0) {
        $buf = fread($fp, min(65536, $bytesLeft));
        fwrite($wfp, $buf);
        $bytesLeft -= strlen($buf);
    }
    fclose($wfp);
    $chunks[$i] = $partPath;
}
fclose($fp);
out("切分: count=$chunkCount");

function makeBlob(string $path, string $name): UploadedFile
{
    return new UploadedFile($path, $name, null, null, true);
}

// 4) 清理可能的残留
$existing = FileModel::whereHash($hash)->whereUserid($user->userid)->forceDelete();
out("清理残留 files 记录: $existing 条");
Redis::del("upload:hash:{$user->userid}:{$hash}");

// 5) 找一个根目录 pid=0 作为 file_cabinet 上传位置
$sceneParams = [
    'pid' => 0,
    'webkit_relative_path' => 'cu_smoke_test.bin',
    'overwrite' => false,
];

// === 测试 1：完整分片上传 ===
out("--- 测试 1: 完整分片上传 ---");
$res = ChunkUpload::start($user, [
    'hash' => $hash,
    'size' => $size,
    'name' => 'cu_smoke_test.bin',
    'scene' => 'file_cabinet',
    'scene_params' => $sceneParams,
]);
if ($res['ret'] !== 1) {
    fail("start 失败: " . json_encode($res));
}
if (!empty($res['data']['done'])) {
    fail("首次上传不应直接 done: " . json_encode($res['data']));
}
$uploadId = $res['data']['upload_id'];
out("start ok: upload_id=$uploadId chunk_count={$res['data']['chunk_count']} received=" . json_encode($res['data']['received']));

// 上传每个分片（每次都重新做 UploadedFile，因为 move 会移走文件）
for ($i = 0; $i < $chunkCount; $i++) {
    $copyPath = $chunks[$i] . '.tx';
    copy($chunks[$i], $copyPath);
    $res = ChunkUpload::receive($user, $uploadId, $i, makeBlob($copyPath, "part_$i"));
    if ($res['ret'] !== 1) {
        fail("receive[$i] 失败: " . json_encode($res));
    }
    out("  receive[$i] ok received=" . json_encode($res['data']['received']));
}

// 合并
$res = ChunkUpload::merge($user, $uploadId);
if ($res['ret'] !== 1) {
    fail("merge 失败: " . json_encode($res));
}
out("merge ok: " . substr(json_encode($res['data']), 0, 200));

// 验证 files 表
$created = FileModel::whereHash($hash)->whereUserid($user->userid)->first();
if (!$created) {
    fail("files 表未找到新记录");
}
out("files 表 OK: id={$created->id} name={$created->name}.{$created->ext} size={$created->size} hash={$created->hash}");

// === 测试 2：秒传 ===
out("--- 测试 2: 秒传 ---");
$res = ChunkUpload::start($user, [
    'hash' => $hash,
    'size' => $size,
    'name' => 'cu_smoke_test.bin',
    'scene' => 'file_cabinet',
    'scene_params' => $sceneParams,
]);
if ($res['ret'] !== 1 || empty($res['data']['done']) || empty($res['data']['instant'])) {
    fail("秒传未命中: " . json_encode($res));
}
$instantId = $res['data']['addItem'][0]['id'] ?? 0;
out("秒传 OK: 新建 file.id=$instantId");

// === 测试 3：续传 ===
out("--- 测试 3: 续传 ---");
// 删全部同 hash 文件（含秒传新建的那条）让 hash 反查失败
FileModel::whereHash($hash)->whereUserid($user->userid)->forceDelete();
// 启新会话
$res = ChunkUpload::start($user, [
    'hash' => $hash,
    'size' => $size,
    'name' => 'cu_smoke_test.bin',
    'scene' => 'file_cabinet',
    'scene_params' => $sceneParams,
]);
$uploadId2 = $res['data']['upload_id'];
out("续传场景 start: upload_id=$uploadId2");
// 只上传第 0 片
copy($chunks[0], $chunks[0] . '.r1');
ChunkUpload::receive($user, $uploadId2, 0, makeBlob($chunks[0] . '.r1', 'part_0'));
out("先传 1 片，received=" . json_encode([0]));
// 再次 start 同 hash → 应返回 received=[0]
$res = ChunkUpload::start($user, [
    'hash' => $hash,
    'size' => $size,
    'name' => 'cu_smoke_test.bin',
    'scene' => 'file_cabinet',
    'scene_params' => $sceneParams,
]);
if (!empty($res['data']['done'])) {
    fail("续传场景不应 done: " . json_encode($res));
}
if ($res['data']['upload_id'] !== $uploadId2) {
    fail("续传应复用 upload_id: 期望 $uploadId2, 得到 " . $res['data']['upload_id']);
}
if ($res['data']['received'] !== [0]) {
    fail("续传 received 应为 [0]: " . json_encode($res['data']['received']));
}
out("续传命中 OK，received=" . json_encode($res['data']['received']));
// 补传剩余分片
for ($i = 1; $i < $chunkCount; $i++) {
    copy($chunks[$i], $chunks[$i] . '.r2');
    ChunkUpload::receive($user, $uploadId2, $i, makeBlob($chunks[$i] . '.r2', "part_$i"));
}
$res = ChunkUpload::merge($user, $uploadId2);
if ($res['ret'] !== 1) {
    fail("续传 merge 失败: " . json_encode($res));
}
out("续传 merge OK");

// 清理
$final = FileModel::whereHash($hash)->whereUserid($user->userid)->first();
if ($final) {
    out("清理: 删除 files.id={$final->id}");
    $final->forceDelete();
}
foreach ($chunks as $p) {
    @unlink($p);
}
@unlink($srcPath);

out("=== 全部通过 ===");
