<?php

namespace App\Tasks;

use App\Models\File;
use App\Module\Base;
use ZipArchive;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);


/**
 * 文件打包
 *
 */
class FilePackTask extends AbstractTask
{
    protected $user;
    protected $files;
    protected $downName;
    public function __construct($user, $files, $downName)
    {
        parent::__construct();
        $this->user = $user;
        $this->files = $files;
        $this->downName = $downName;
    }

    public function start()
    {

    }

    public function end()
    {
        // 压缩进度
        $progress = 0;
        $filePackName = $this->downName;

        $zip = new ZipArchive();
        $zipName = 'temp/download/' . date("Ym") . '/' . $this->user->userid . '/' . $filePackName;
        $zipPath = storage_path('app/'.$zipName);
        Base::makeDir(dirname($zipPath));

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return;
        }

        $zip->registerProgressCallback(0.05, function($ratio) use ($filePackName, &$progress) {
            $progress = round($ratio * 100);
            File::filePushMsg('compress', [
                'name'=> $filePackName,
                'progress' => $progress
            ]);
        });

        foreach ($this->files as $file) {
            File::addFileTreeToZip($zip, $file);
        }
        $zip->close();
        if ($progress < 100) {
            File::filePushMsg('compress', [
                'name'=> $filePackName,
                'progress' => 100
            ]);
        }
    }
}
