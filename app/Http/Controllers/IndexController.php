<?php

namespace App\Http\Controllers;

use Arr;
use Cache;
use Request;
use Redirect;
use Response;
use App\Module\Doo;
use App\Models\File;
use App\Module\Base;
use App\Module\Extranet;
use App\Module\RandomColor;
use App\Tasks\LoopTask;
use App\Tasks\AppPushTask;
use App\Tasks\JokeSoupTask;
use App\Tasks\DeleteTmpTask;
use App\Tasks\EmailNoticeTask;
use App\Tasks\AutoArchivedTask;
use App\Tasks\DeleteBotMsgTask;
use App\Tasks\CheckinRemindTask;
use App\Tasks\CloseMeetingRoomTask;
use App\Tasks\UnclaimedTaskRemindTask;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Laravolt\Avatar\Avatar;


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
        if ($app == 'default') {
            return '';
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
        $hotFile = public_path('hot');
        $manifestFile = public_path('manifest.json');
        if (file_exists($hotFile)) {
            $array = Base::json2array(file_get_contents($hotFile));
            $style = null;
            $script = preg_replace("/^(\/\/(.*?))(:\d+)?\//i", "$1:" . $array['APP_DEV_PORT'] . "/", asset_main("resources/assets/js/app.js"));
        } else {
            $array = Base::json2array(file_get_contents($manifestFile));
            $style = asset_main($array['resources/assets/js/app.js']['css'][0]);
            $script = asset_main($array['resources/assets/js/app.js']['file']);
        }
        return response()->view('main', [
            'version' => Base::getVersion(),
            'style' => $style,
            'script' => $script,
        ]);
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
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function avatar()
    {
        $segment = Request::segment(2);
        if ($segment && preg_match('/.*?\.png$/i', $segment)) {
            $name = substr($segment, 0, -4);
        } else {
            $name = Request::input('name', 'D');
        }
        $size = Request::input('size', 128);
        $color = Request::input('color');
        $background = Request::input('background');
        // 移除各种括号及其内容
        $pattern = '/[(（\[【{［<＜『「](.*?)[)）\]】}］>＞』」]/u';
        $name = preg_replace($pattern, '', $name) ?: preg_replace($pattern, '$1', $name);
        // 移除常见标识词（不区分大小写）
        $filterWords = [
            // 测试相关
            '测试', '测试号', '测试账号', '内测', '体验', '试用', 'test', 'testing', 'beta',
            // 账号相关
            '账号', '帐号', '账户', '帐户', 'account', 'acc', 'id', 'uid',
            // 临时标识
            '临时', '暂用', '备用', '主号', '副号', '小号', '大号', 'temp', 'temporary', 'backup',
            // 系统相关
            '系统', '管理员', 'admin', 'administrator', 'system', 'sys', 'root',
            // 用户相关
            '用户', 'user', '会员', 'member', 'vip', 'svip', 'mvip', 'premium',
            // 官方相关
            '官方', '正式', '认证', 'official', 'verified', 'auth',
            // 客服相关
            '客服', '售后', '服务', 'service', 'support', 'helper', 'assistant',
            // 游戏相关
            'game', 'gaming', 'player', 'gamer',
            // 社交媒体相关
            'ins', 'instagram', 'fb', 'facebook', 'tiktok', 'tweet', 'weibo', 'wechat',
            // 常见后缀
            'official', 'real', 'fake', 'copy', 'channel', 'studio', 'team', 'group',
            // 职业相关
            'dev', 'developer', 'designer', 'artist', 'writer', 'editor',
            // 其他
            'bot', 'robot', 'auto', 'anonymous', 'guest', 'default', 'new', 'old'
        ];
        $filterWords = array_map(function($word) {
            return preg_quote($word, '/');
        }, $filterWords);
        $name = preg_replace('/' . implode('|', $filterWords) . '/iu', '', $name) ?: $name;
        // 移除分隔符和特殊字符
        $filterSymbols = [
            // 常见分隔符
            '-', '_', '=', '+', '/', '\\', '|',
            '~', '@', '#', '$', '%', '^', '&', '*',
            // 空格类字符
            ' ', '　', "\t", "\n", "\r",
            // 标点符号（中英文）
            '。', '，', '、', '；', '：', '？', '！',
            '．', '…', '‥', '′', '″', '℃',
            '.', ',', ';', ':', '?', '!',
            // 引号类（修正版）
            '"', "'", '‘', '’', '“', '”', '`',
            // 特殊符号
            '★', '☆', '○', '●', '◎', '◇', '◆',
            '□', '■', '△', '▲', '▽', '▼',
            '♀', '♂', '♪', '♫', '♯', '♭', '♬',
            '→', '←', '↑', '↓', '↖', '↗', '↙', '↘',
            '√', '×', '÷', '±', '∵', '∴',
            '♠', '♥', '♣', '♦',
            // emoji 表情符号范围
            '\x{1F300}-\x{1F9FF}',
            '\x{2600}-\x{26FF}',
            '\x{2700}-\x{27BF}',
            '\x{1F900}-\x{1F9FF}',
            '\x{1F600}-\x{1F64F}'
        ];
        $filterSymbols = array_map(function($symbol) {
            return preg_quote($symbol, '/');
        }, $filterSymbols);
        $name = preg_replace('/[' . implode('', $filterSymbols) . ']/u', '', $name) ?: $name;
        //
        if (preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $name)) {
            $name = mb_substr($name, mb_strlen($name) - 2);
        }
        if (empty($name)) {
            $name = 'D';
        }
        if (empty($color)) {
            $color = '#ffffff';
            $cacheKey = "avatarBackgroundColor::" . md5($name);
            $background = Cache::rememberForever($cacheKey, function() {
                return RandomColor::one(['luminosity' => 'dark']);
            });
        }
        //
        $path = public_path('uploads/tmp/avatar/' . substr(md5($name), 0, 2));
        $file = Base::joinPath($path, md5($name) . '.png');
        if (file_exists($file)) {
            return response()->file($file, [
                'Pragma' => 'public',
                'Cache-Control' => 'max-age=1814400',
                'Content-type' => 'image/png',
                'Expires' => gmdate('D, d M Y H:i:s \G\M\T', time() + 1814400),
            ]);
        }
        Base::makeDir($path);
        //
        $avatar = new Avatar([
            'shape' => 'square',
            'width' => $size,
            'height' => $size,
            'chars'    => 2,
            'fontSize' => $size / 2.9,
            'uppercase' => true,
            'fonts' => [resource_path('assets/statics/fonts/Source_Han_Sans_SC_Regular.otf')],
            'foregrounds' => [$color],
            'backgrounds' => [$background],
            'border' => [
                'size' => 0,
                'color' => 'foreground',
                'radius' => 0,
            ],
        ]);
        return response($avatar->create($name)->save($file))
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
        Task::deliver(new DeleteTmpTask('tmp'));
        Task::deliver(new DeleteTmpTask('file'));
        Task::deliver(new DeleteTmpTask('tmp_file', 24));
        // 删除机器人消息
        Task::deliver(new DeleteBotMsgTask());
        // 周期任务
        Task::deliver(new LoopTask());
        // 签到提醒
        Task::deliver(new CheckinRemindTask());
        // 获取笑话/心灵鸡汤
        Task::deliver(new JokeSoupTask());
        // 未领取任务通知
        Task::deliver(new UnclaimedTaskRemindTask());
        // 关闭会议室
        Task::deliver(new CloseMeetingRoomTask());

        return "success";
    }

    /**
     * 桌面客户端发布
     */
    public function desktop__publish($name = '')
    {
        $publishVersion = Request::header('publish-version');
        $fileNum = Request::get('file_num', 1);
        $latestFile = public_path("uploads/desktop/latest");
        $latestVersion = file_exists($latestFile) ? trim(file_get_contents($latestFile)) : "0.0.1";
        if (strtolower($name) === 'latest') {
            $name = $latestVersion;
        }
        // 上传
        if (preg_match("/^\d+\.\d+\.\d+$/", $publishVersion)) {
            $uploadSuccessFileNum = (int)Cache::get($publishVersion, 0);
            $publishKey = Request::header('publish-key');
            if ($publishKey !== env('APP_KEY')) {
                return Base::retError("key error");
            }
            if (version_compare($publishVersion, $latestVersion) > -1) {    // 限制上传版本必须 ≥ 当前版本
                $publishPath = "uploads/desktop/{$publishVersion}/";
                $res = Base::upload([
                    "file" => Request::file('file'),
                    "type" => 'publish',
                    "path" => $publishPath,
                    "fileName" => true,
                    "quality" => 100
                ]);
                if (Base::isSuccess($res)) {
                    file_put_contents($latestFile, $publishVersion);
                    $uploadSuccessFileNum = $uploadSuccessFileNum + 1;
                    Cache::set($publishVersion, $uploadSuccessFileNum, 7200);
                }
                if ($uploadSuccessFileNum >= $fileNum) {
                    // 删除旧版本
                    $dirs = Base::recursiveDirs(public_path("uploads/desktop"), false);
                    sort($dirs);
                    $num = 0;
                    foreach ($dirs as $dir) {
                        if (!preg_match("/\/\d+\.\d+\.\d+$/", $dir)) {
                            continue;
                        }
                        $num++;
                        if ($num < 2) {
                            continue;
                        }
                        $time = filemtime($dir);
                        if ($time < time() - 3600 * 24 * 30) {
                            Base::deleteDirAndFile($dir);
                        }
                    }
                }
                return $res;
            }
        }
        // 列表
        if (preg_match("/^\d+\.\d+\.\d+$/", $name)) {
            $path = "uploads/desktop/{$name}";
            $dirPath = public_path($path);
            $lists = Base::recursiveFiles($dirPath, false);
            $files = [];
            foreach ($lists as $file) {
                if (str_ends_with($file, '.yml') || str_ends_with($file, '.yaml') || str_ends_with($file, '.blockmap')) {
                    continue;
                }
                $fileName = basename($file, $dirPath);
                $fileSize = filesize($file);
                $files[] = [
                    'name' => $fileName,
                    'time' => date("Y-m-d H:i:s", filemtime($file)),
                    'size' => $fileSize > 0 ? Base::readableBytes($fileSize) : 0,
                    'url' => Base::fillUrl(Base::joinPath($path, $fileName)),
                ];
            }
            //
            return view('desktop', ['version' => $name, 'files' => $files]);
        }
        // 下载
        if ($name && file_exists($latestFile)) {
            $publishVersion = file_get_contents($latestFile);
            if (preg_match("/^\d+\.\d+\.\d+$/", $publishVersion)) {
                $filePath = public_path("uploads/desktop/{$publishVersion}/{$name}");
                if (file_exists($filePath)) {
                    return Response::download($filePath);
                }
            }
        }
        abort(404);
    }

    /**
     * Drawio 图标搜索
     * @return array|mixed
     */
    public function drawio__iconsearch()
    {
        $query = trim(Request::input('q'));
        $page = trim(Request::input('p'));
        $size = trim(Request::input('c'));
        return Extranet::drawioIconSearch($query, $page, $size);
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
        // 防止 ../ 穿越获取到系统文件
        if (!str_starts_with(realpath($file), public_path())) {
            abort(404);
        }
        //
        if (!file_exists($file)) {
            abort(404);
        }
        //
        parse_str($data['query'], $query);
        $name = Arr::get($query, 'name');
        $ext = strtolower(Arr::get($query, 'ext'));
        $userAgent = strtolower(Request::server('HTTP_USER_AGENT'));
        if ($ext === 'pdf') {
            // 文件超过 10m 不支持在线预览，提示下载
            if (filesize($file) > 10 * 1024 * 1024) {
                return view('download', [
                    'name' => $name,
                    'size' => Base::readableBytes(filesize($file)),
                    'url' => Base::fillUrl($path),
                    'button' => Doo::translate('点击下载'),
                ]);
            }
            // 浏览器类型
            $browser = 'none';
            if (str_contains($userAgent, 'chrome') || str_contains($userAgent, 'android_kuaifan_eeui')) {
                $browser = str_contains($userAgent, 'android_kuaifan_eeui') ? 'android-mobile' : 'chrome-desktop';
            } elseif (str_contains($userAgent, 'safari') || str_contains($userAgent, 'ios_kuaifan_eeui')) {
                $browser = str_contains($userAgent, 'ios_kuaifan_eeui') ? 'safari-mobile' : 'safari-desktop';
            }
            // electron 直接在线预览查看
            if (str_contains($userAgent, 'electron') || str_contains($browser, 'desktop')) {
                return Response::download($file, $name, [
                    'Content-Type' => 'application/pdf'
                ], 'inline');
            }
            // EEUI App 直接在线预览查看
            if (Base::isEEUIApp() && Base::judgeClientVersion("0.34.47")) {
                if ($browser === 'safari-mobile') {
                    $redirectUrl = Base::fillUrl($path);
                    return <<<EOF
                        <script>
                            window.top.postMessage({
                                action: "eeuiAppSendMessage",
                                data: [
                                    {
                                        action: 'setPageData',
                                        data: {
                                            showProgress: true,
                                            titleFixed: true,
                                            urlFixed: true,
                                        }
                                    },
                                    {
                                        action: 'createTarget',
                                        url: "{$redirectUrl}",
                                    }
                                ]
                            }, "*")
                        </script>
                        EOF;
                }
            }
        }
        //
        if (in_array($ext, File::localExt)) {
            $url = Base::fillUrl($path);
        } else {
            $url = 'http://' . env('APP_IPPR') . '.3/' . $path;
        }
        $url = Base::urlAddparameter($url, [
            'fullfilename' => Base::rightDelete($name, '.' . $ext) . '_' . filemtime($file) . '.' . $ext
        ]);
        $redirectUrl = Base::fillUrl("fileview/onlinePreview?url=" . urlencode(base64_encode($url)));
        return Redirect::to($redirectUrl, 301);
    }

    /**
     * 保存配置 (todo 已废弃)
     * @return string
     */
    public function storage__synch()
    {
        return '<!-- Deprecated -->';
    }
}
