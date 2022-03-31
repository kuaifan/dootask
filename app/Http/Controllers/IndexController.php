<?php

namespace App\Http\Controllers;

use App\Module\Base;
use App\Tasks\AutoArchivedTask;
use App\Tasks\DeleteTmpTask;
use App\Tasks\OverdueRemindEmailTask;
use Arr;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Redirect;
use Request;


/**
 * 页面
 * Class IndexController
 * @package App\Http\Controllers
 */
class IndexController extends InvokeController
{
    public function __invoke($method, $action = '', $child = '')
    {
        $app = $method ?: 'main';
        if ($action) {
            $app .= "__" . $action;
        }
        if (!method_exists($this, $app)) {
            $app = method_exists($this, $method) ? $method : 'main';
        }
        return $this->$app($child);
    }

    /**
     * 首页
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function main()
    {
        $hash = 'no';
        $path = public_path('js/hash');
        if (file_exists($path)) {
            $hash = trim(file_get_contents(public_path('js/hash')));
            if (strlen($hash) > 16) {
                $hash = 'long';
            }
        }
        return view('main', [
            'version' => Base::getVersion(),
            'hash' => $hash
        ]);
    }

    /**
     * 获取版本号
     * @return array
     */
    public function version()
    {
        $url = url('');
        $package = Base::getPackage();
        $array = [
            'version' => Base::getVersion(),
            'publish' => Arr::get($package, 'app.0.publish'),
        ];
        if (is_array($package['app'])) {
            foreach ($package['app'] as $item) {
                if (is_array($item['publish']) && Base::hostContrast($url, $item['url'])) {
                    $array['publish'] = $item['publish'];
                    break;
                }
            }
        }
        return $array;
    }

    /**
     * 接口文档
     * @return \Illuminate\Http\RedirectResponse
     */
    public function api()
    {
        return Redirect::to(Base::fillUrl('docs/index.html'), 301);
    }

    /**
     * 系统定时任务，限制内网访问（1分钟/次）
     * @return string
     */
    public function crontab()
    {
        if (!Base::is_internal_ip(Base::getIp())) {
            // 限制内网访问
            return "Forbidden Access";
        }
        // 删除过期的临时表数据
        Task::deliver(new DeleteTmpTask('wg_tmp_msgs', 1));
        Task::deliver(new DeleteTmpTask('tmp', 24));
        // 自动归档任务
        Task::deliver(new AutoArchivedTask());
        // 任务到期邮件提醒
        Task::deliver(new OverdueRemindEmailTask());

        return "success";
    }

    /**
     * 桌面客户端发布
     */
    public function desktop__publish($name = '')
    {
        $latestFile = public_path("uploads/desktop/latest");
        $genericVersion = Request::header('generic-version');
        // 上传
        if (preg_match("/^\d+\.\d+\.\d+$/", $genericVersion)) {
            $genericPath = "uploads/desktop/" . $genericVersion . "/";
            $res = Base::upload([
                "file" => Request::file('file'),
                "type" => 'desktop',
                "path" => $genericPath,
                "fileName" => true
            ]);
            if (Base::isSuccess($res)) {
                file_put_contents($latestFile, $genericVersion);
            }
            return $res;
        }
        // 列表
        if (preg_match("/^\d+\.\d+\.\d+$/", $name)) {
            $path = "uploads/desktop/{$name}";
            $dirPath = public_path($path);
            $lists = Base::readDir($dirPath);
            $files = [];
            foreach ($lists as $file) {
                $fileName = Base::leftDelete($file, $dirPath);
                $files[] = [
                    'name' => substr($fileName, 1),
                    'time' => date("Y-m-d H:i:s", fileatime($file)),
                    'size' => Base::readableBytes(filesize($file)),
                    'url' => Base::fillUrl($path . $fileName),
                ];
            }
            return view('desktop', ['version' => $name, 'files' => $files]);
        }
        // 下载
        if ($name && file_exists($latestFile)) {
            $genericVersion = file_get_contents($latestFile);
            if (preg_match("/^\d+\.\d+\.\d+$/", $genericVersion)) {
                $filePath = public_path("uploads/desktop/{$genericVersion}/{$name}");
                if (file_exists($filePath)) {
                    return response()->download($filePath);
                }
            }
        }
        return abort(404);
    }

    /**
     * 提取所有中文
     * @return array|string
     */
    public function allcn()
    {
        if (!Base::is_internal_ip(Base::getIp())) {
            // 限制内网访问
            return "Forbidden Access";
        }
        $list = Base::readDir(resource_path());
        $array = [];
        foreach ($list as $item) {
            $content = file_get_contents($item);
            preg_match_all("/\\\$L\((.*?)\)/", $content, $matchs);
            if ($matchs) {
                foreach ($matchs[1] as $text) {
                    $array[trim(trim($text, '"'), "'")] = trim(trim($text, '"'), "'");
                }
            }
        }
        return array_values($array);
    }

    /**
     * 提取所有中文
     * @return array|string
     */
    public function allcn__php()
    {
        if (!Base::is_internal_ip(Base::getIp())) {
            // 限制内网访问
            return "Forbidden Access";
        }
        $list = Base::readDir(app_path());
        $array = [];
        foreach ($list as $item) {
            $content = file_get_contents($item);
            preg_match_all("/(retSuccess|retError|ApiException)\((.*?)[,|)]/", $content, $matchs);
            if ($matchs) {
                foreach ($matchs[2] as $text) {
                    $array[trim(trim($text, '"'), "'")] = trim(trim($text, '"'), "'");
                }
            }
        }
        return array_values($array);
    }

    /**
     * 提取所有中文
     * @return array|string
     */
    public function allcn__all()
    {
        if (!Base::is_internal_ip(Base::getIp())) {
            // 限制内网访问
            return "Forbidden Access";
        }
        $list = array_merge(Base::readDir(app_path()), Base::readDir(resource_path()));
        $array = [];
        foreach ($list as $item) {
            if (Base::rightExists($item, "language.all.js")) {
                continue;
            }
            if (Base::rightExists($item, ".php") || Base::rightExists($item, ".vue") || Base::rightExists($item, ".js")) {
                $content = file_get_contents($item);
                preg_match_all("/(['\"])(.*?)[\u{4e00}-\u{9fa5}\u{FE30}-\u{FFA0}]+([\s\S]((?!\n).)*)\\1/u", $content, $matchs);
                if ($matchs) {
                    foreach ($matchs[0] as $text) {
                        $tmp = preg_replace("/\/\/(.*?)$/", "", $text);
                        $tmp = preg_replace("/\/\/(.*?)\n/", "", $tmp);
                        $tmp = str_replace("：", "", $tmp);
                        if (!preg_match("/[\u{4e00}-\u{9fa5}\u{FE30}-\u{FFA0}]/u", $tmp)){
                            continue;  // 没有中文
                        }
                        $val = trim(trim($text, '"'), "'");
                        $array[md5($val)] = $val;
                    }
                }
            }
        }
        return implode("\n", array_values($array));
    }
}
