<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Module\Base;
use App\Module\Ihttp;
use App\Module\RandomColor;
use App\Tasks\AppPushTask;
use App\Tasks\AutoArchivedTask;
use App\Tasks\DeleteTmpTask;
use App\Tasks\EmailNoticeTask;
use App\Tasks\JokeSoupTask;
use App\Tasks\LoopTask;
use Arr;
use Cache;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use LasseRafn\InitialAvatarGenerator\InitialAvatar;
use Redirect;
use Request;
use Response;


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
            "office/7.2.2-56/web-apps/vendor/requirejs/require.js",
            "office/7.2.2-56/web-apps/apps/api/documents/api.js",
            "office/7.2.2-56/sdkjs/common/AllFonts.js",
            "office/7.2.2-56/web-apps/vendor/xregexp/xregexp-all-min.js",
            "office/7.2.2-56/web-apps/vendor/sockjs/sockjs.min.js",
            "office/7.2.2-56/web-apps/vendor/jszip/jszip.min.js",
            "office/7.2.2-56/web-apps/vendor/jszip-utils/jszip-utils.min.js",
            "office/7.2.2-56/sdkjs/common/libfont/wasm/fonts.js",
            "office/7.2.2-56/sdkjs/common/Charts/ChartStyles.js",
            "office/7.2.2-56/sdkjs/slide/themes//themes.js",

            "office/7.2.2-56/web-apps/apps/presentationeditor/main/app.js",
            "office/7.2.2-56/sdkjs/slide/sdk-all-min.js",
            "office/7.2.2-56/sdkjs/slide/sdk-all.js",

            "office/7.2.2-56/web-apps/apps/documenteditor/main/app.js",
            "office/7.2.2-56/sdkjs/word/sdk-all-min.js",
            "office/7.2.2-56/sdkjs/word/sdk-all.js",

            "office/7.2.2-56/web-apps/apps/spreadsheeteditor/main/app.js",
            "office/7.2.2-56/sdkjs/cell/sdk-all-min.js",
            "office/7.2.2-56/sdkjs/cell/sdk-all.js",
        ];
        foreach ($array as &$item) {
            $item = url($item);
        }
        return implode(PHP_EOL, $array);
    }

    /**
     * 获取版本号
     * @return \Illuminate\Http\RedirectResponse
     */
    public function version()
    {
        return Redirect::to(Base::fillUrl('api/system/version'), 301);
    }

    /**
     * 头像
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function avatar()
    {
        $segment = Request::segment(2);
        if ($segment && preg_match('/.*?\.png$/i', $segment)) {
            $name = substr($segment, 0, -4);
        } else {
            $name = Request::input('name', 'H');
        }
        $size = Request::input('size', 128);
        $color = Request::input('color');
        $background = Request::input('background');
        //
        if (preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $name)) {
            $name = mb_substr($name, mb_strlen($name) - 2);
        }
        if (empty($color)) {
            $color = '#ffffff';
            $cacheKey = "avatarBackgroundColor::" . md5($name);
            $background = Cache::rememberForever($cacheKey, function() {
                return RandomColor::one(['luminosity' => 'dark']);
            });
        }
        //
        $avatar = new InitialAvatar();
        $content = $avatar->name($name)
            ->size($size)
            ->color($color)
            ->background($background)
            ->fontSize(0.35)
            ->autoFont()
            ->generate()
            ->stream('png', 100);
        //
        return response($content)
            ->header('Pragma', 'public')
            ->header('Cache-Control', 'max-age=1814400')
            ->header('Content-type', 'image/png')
            ->header('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 1814400));
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
        // App推送
        Task::deliver(new AppPushTask());
        // 删除过期的临时表数据
        Task::deliver(new DeleteTmpTask('wg_tmp_msgs', 1));
        Task::deliver(new DeleteTmpTask('task_worker', 12));
        Task::deliver(new DeleteTmpTask('tmp', 24));
        // 周期任务
        Task::deliver(new LoopTask());
        // 获取笑话/心灵鸡汤
        Task::deliver(new JokeSoupTask());

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
            //
            $path = "uploads/android";
            $dirPath = public_path($path);
            $lists = Base::readDir($dirPath);
            $apkFile = null;
            foreach ($lists as $file) {
                if (!str_ends_with($file, '.apk')) {
                    continue;
                }
                if ($apkFile && strtotime($apkFile['time']) > fileatime($file)) {
                    continue;
                }
                $fileName = Base::leftDelete($file, $dirPath);
                $apkFile = [
                    'name' => substr($fileName, 1),
                    'time' => date("Y-m-d H:i:s", fileatime($file)),
                    'size' => Base::readableBytes(filesize($file)),
                    'url' => Base::fillUrl($path . $fileName),
                ];
            }
            if ($apkFile) {
                $files = array_merge([$apkFile], $files);
            }
            return view('desktop', ['version' => $name, 'files' => $files]);
        }
        // 下载
        if ($name && file_exists($latestFile)) {
            $genericVersion = file_get_contents($latestFile);
            if (preg_match("/^\d+\.\d+\.\d+$/", $genericVersion)) {
                $filePath = public_path("uploads/desktop/{$genericVersion}/{$name}");
                if (file_exists($filePath)) {
                    return Response::download($filePath);
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
     * 预览文件
     * @return array|mixed
     */
    public function online__preview()
    {
        $key = trim(Request::input('key'));
        //
        $data = parse_url($key);
        $path = Arr::get($data, 'path');
        $file = public_path($path);
        //
        if (file_exists($file)) {
            parse_str($data['query'], $query);
            $name = Arr::get($query, 'name');
            $ext = strtolower(Arr::get($query, 'ext'));
            $userAgent = strtolower(Request::server('HTTP_USER_AGENT'));
            if ($ext === 'pdf'
                && (str_contains($userAgent, 'electron') || str_contains($userAgent, 'chrome'))) {
                return Response::download($file, $name, [
                    'Content-Type' => 'application/pdf'
                ], 'inline');
            }
            //
            if (in_array($ext, File::localExt)) {
                $url = Base::fillUrl($path);
            } else {
                $url = 'http://' . env('APP_IPPR') . '.3/' . $path;
            }
            if ($ext !== 'pdf') {
                $url = Base::urlAddparameter($url, [
                    'fullfilename' => $name . '.' . $ext
                ]);
            }
            $toUrl = Base::fillUrl("fileview/onlinePreview?url=" . urlencode(base64_encode($url)));
            return Redirect::to($toUrl, 301);
        }
        return abort(404);
    }

    /**
     * 设置语言和皮肤
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function setting__theme_language()
    {
        return view('setting', [
            'theme' => Request::input('theme'),
            'language' => Request::input('language')
        ]);
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
