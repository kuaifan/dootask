<?php

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use App\Models\File;
use App\Models\FileContent;
use App\Module\Base;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;

class FilesUpdateExt extends Migration
{
    /**
     * 更新后缀
     * @return void
     */
    public function up()
    {
        File::whereIn('type', ['mind', 'drawio', 'document'])->where('ext', '')->orderBy('id')->chunk(100, function($files) {
            /** @var File $file */
            foreach ($files as $file) {
                $fileContent = FileContent::whereFid($file->id)->orderByDesc('id')->first();
                $contentArray = Base::json2array($fileContent?->content);
                $contentString = '';
                //
                switch ($file->type) {
                    case 'document':
                        $file->ext = $contentArray['type'] ?: 'md';
                        $contentString = $contentArray['content'];
                        break;
                    case 'drawio':
                        $file->ext = 'drawio';
                        $contentString = $contentArray['xml'];
                        break;
                    case 'mind':
                        $file->ext = 'mind';
                        $contentString = $fileContent?->content;
                        break;
                }
                $file->save();
                //
                $path = 'uploads/file/' . $file->type . '/' . date("Ym", Carbon::parse($file->created_at)->timestamp) . '/' . $file->id . '/' . md5($contentString);
                $save = public_path($path);
                Base::makeDir(dirname($save));
                file_put_contents($save, $contentString);
                $content = [
                    'type' => $file->ext,
                    'url' => $path
                ];
                //
                $content = FileContent::createInstance([
                    'fid' => $file->id,
                    'content' => $content,
                    'text' => $fileContent?->text,
                    'size' => $file->size,
                    'userid' => $file->userid,
                ]);
                $content->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        File::whereIn('ext', ['mind', 'drawio', 'md'])->update([
            'ext' => ''
        ]);
        // ... 退回去意义不大，文件内容不做回滚操作
    }
}
