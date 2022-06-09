<?php

namespace App\Http\Controllers;

use App\Module\Base;
use App\Module\Ihttp;
use App\Tasks\AutoArchivedTask;
use App\Tasks\DeleteTmpTask;
use App\Tasks\EmailNoticeTask;
use Arr;
use Cache;
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
        if ($app === 'manifest.txt') {
            $app = 'manifest';
            $child = 'txt';
        }
        if (!method_exists($this, $app)) {
            $app = method_exists($this, $method) ? $method : 'main';
        }
        return $this->$app($child);
    }

    /**
     * 首页
     * @return \Illuminate\Http\Response
     */
    public function main()
    {
        $hash = 'no';
        $path = public_path('js/hash');
        $murl = url('manifest.txt');
        if (file_exists($path)) {
            $hash = trim(file_get_contents(public_path('js/hash')));
            if (strlen($hash) > 16) {
                $hash = 'long';
            }
        }
        return response()->view('main', [
            'version' => Base::getVersion(),
            'hash' => $hash
        ])->header('Link', "<{$murl}>; rel=\"prefetch\"");
    }

    /**
     * Manifest
     * @param $child
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response|string
     */
    public function manifest($child = '')
    {
        if (empty($child)) {
            $murl = url('manifest.txt');
            return response($murl)->header('Link', "<{$murl}>; rel=\"prefetch\"");
        }
        $array = [
            "office/web-apps/apps/api/documents/api.js?hash=" . Base::getVersion(),
            "office/7.1.1-23/web-apps/vendor/requirejs/require.js",
            "office/7.1.1-23/web-apps/apps/api/documents/api.js",
            "office/7.1.1-23/sdkjs/common/AllFonts.js",
            "office/7.1.1-23/web-apps/vendor/xregexp/xregexp-all-min.js",
            "office/7.1.1-23/web-apps/vendor/sockjs/sockjs.min.js",
            "office/7.1.1-23/web-apps/vendor/jszip/jszip.min.js",
            "office/7.1.1-23/web-apps/vendor/jszip-utils/jszip-utils.min.js",
            "office/7.1.1-23/sdkjs/common/libfont/wasm/fonts.js",
            "office/7.1.1-23/sdkjs/common/Charts/ChartStyles.js",
            "office/7.1.1-23/sdkjs/slide/themes//themes.js",

            "office/7.1.1-23/web-apps/apps/presentationeditor/main/app.js",
            "office/7.1.1-23/sdkjs/slide/sdk-all-min.js",
            "office/7.1.1-23/sdkjs/slide/sdk-all.js",

            "office/7.1.1-23/web-apps/apps/documenteditor/main/app.js",
            "office/7.1.1-23/sdkjs/word/sdk-all-min.js",
            "office/7.1.1-23/sdkjs/word/sdk-all.js",

            "office/7.1.1-23/web-apps/apps/spreadsheeteditor/main/app.js",
            "office/7.1.1-23/sdkjs/cell/sdk-all-min.js",
            "office/7.1.1-23/sdkjs/cell/sdk-all.js",
        ];
        foreach ($array as &$item) {
            $item = url($item);
        }
        return implode(PHP_EOL, $array);
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
                $urls = $item['urls'] && is_array($item['urls']) ? $item['urls'] : $item['url'];
                if (is_array($item['publish']) && Base::hostContrast($url, $urls)) {
                    $array['publish'] = $item['publish'];
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
        // 自动归档
        Task::deliver(new AutoArchivedTask());
        // 邮件通知
        Task::deliver(new EmailNoticeTask());
        // 删除过期的临时表数据
        Task::deliver(new DeleteTmpTask('wg_tmp_msgs', 1));
        Task::deliver(new DeleteTmpTask('tmp', 24));

        return "success";
    }

    /**
     * 桌面客户端发布
     */
    public function desktop__publish($name = '')
    {
        $genericVersion = Request::header('generic-version');
        $latestFile = public_path("uploads/desktop/latest");
        $latestVersion = file_exists($latestFile) ? trim(file_get_contents($latestFile)) : "0.0.1";
        if (strtolower($name) === 'latest') {
            $name = $latestVersion;
        }
        // 上传
        if (preg_match("/^\d+\.\d+\.\d+$/", $genericVersion)) {
            if (version_compare($genericVersion, $latestVersion) > -1) {    // 限制上传版本必须 ≥ 当前版本
                $genericPath = "uploads/desktop/{$genericVersion}/";
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
        }
        // 列表
        if (preg_match("/^\d+\.\d+\.\d+$/", $name)) {
            $path = "uploads/desktop/{$name}";
            $dirPath = public_path($path);
            $lists = Base::readDir($dirPath);
            $files = [];
            foreach ($lists as $file) {
                if (str_ends_with($file, '.yml') || str_ends_with($file, '.yaml')) {
                    continue;
                }
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
     * Drawio 图标搜索
     * @return array|mixed
     */
    public function drawio__iconsearch()
    {
        $query = Request::input('q');
        $page = Request::input('p');
        $size = Request::input('c');
        $url = "https://app.diagrams.net/iconSearch?q={$query}&p={$page}&c={$size}";
        $result = Cache::remember("drawioIconsearch::" . md5($url), now()->addDays(15), function () use ($url) {
            return Ihttp::ihttp_get($url);
        });
        if (Base::isSuccess($result)) {
            return $result['data'];
        }
        return [
            'icons' => [],
            'total_count' => 0
        ];
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
