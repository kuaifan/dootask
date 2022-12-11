<?php

namespace App\Http\Controllers\Api;

use App\Models\Setting;
use App\Models\User;
use App\Models\UserCheckinRecord;
use App\Module\Base;
use App\Module\BillExport;
use App\Module\BillMultipleExport;
use Arr;
use Carbon\Carbon;
use Guanguans\Notify\Factory;
use Guanguans\Notify\Messages\EmailMessage;
use Madzipper;
use Request;
use Session;

/**
 * @apiDefine system
 *
 * 系统
 */
class SystemController extends AbstractController
{

    /**
     * @api {get} api/system/setting          01. 获取设置、保存设置
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName setting
     *
     * @apiParam {String} type
     * - get: 获取（默认）
     * - all: 获取所有（需要管理员权限）
     * - save: 保存设置（参数：['reg', 'reg_invite', 'login_code', 'password_policy', 'project_invite', 'chat_information', 'auto_archived', 'archived_day', 'all_group_mute', 'all_group_autoin', 'start_home', 'home_footer']）

     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function setting()
    {
        $type = trim(Request::input('type'));
        if ($type == 'save') {
            if (env("SYSTEM_SETTING") == 'disabled') {
                return Base::retError('当前环境禁止修改');
            }
            User::auth('admin');
            $all = Request::input();
            foreach ($all AS $key => $value) {
                if (!in_array($key, [
                    'reg',
                    'reg_invite',
                    'login_code',
                    'password_policy',
                    'project_invite',
                    'chat_information',
                    'auto_archived',
                    'archived_day',
                    'all_group_mute',
                    'all_group_autoin',
                    'start_home',
                    'home_footer'
                ])) {
                    unset($all[$key]);
                }
            }
            $all['archived_day'] = floatval($all['archived_day']);
            if ($all['auto_archived'] == 'open') {
                if ($all['archived_day'] <= 0) {
                    return Base::retError('自动归档时间不可小于1天！');
                } elseif ($all['archived_day'] > 100) {
                    return Base::retError('自动归档时间不可大于100天！');
                }
            }
            $setting = Base::setting('system', Base::newTrim($all));
        } else {
            $setting = Base::setting('system');
        }
        //
        if ($type == 'all' || $type == 'save') {
            User::auth('admin');
            $setting['reg_invite'] = $setting['reg_invite'] ?: Base::generatePassword(8);
        } else {
            if (isset($setting['reg_invite'])) unset($setting['reg_invite']);
        }
        //
        $setting['reg'] = $setting['reg'] ?: 'open';
        $setting['login_code'] = $setting['login_code'] ?: 'auto';
        $setting['password_policy'] = $setting['password_policy'] ?: 'simple';
        $setting['project_invite'] = $setting['project_invite'] ?: 'open';
        $setting['chat_information'] = $setting['chat_information'] ?: 'optional';
        $setting['auto_archived'] = $setting['auto_archived'] ?: 'close';
        $setting['archived_day'] = floatval($setting['archived_day']) ?: 7;
        $setting['all_group_mute'] = $setting['all_group_mute'] ?: 'open';
        $setting['all_group_autoin'] = $setting['all_group_autoin'] ?: 'yes';
        $setting['start_home'] = $setting['start_home'] ?: 'close';
        //
        return Base::retSuccess('success', $setting ?: json_decode('{}'));
    }

    /**
     * @api {get} api/system/setting/email          02. 获取邮箱设置、保存邮箱设置（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName setting__email
     *
     * @apiParam {String} type
     * - get: 获取（默认）
     * - save: 保存设置（参数：['smtp_server', 'port', 'account', 'password', 'reg_verify', 'notice', 'task_start_minute', 'task_remind_hours', 'task_remind_hours2', 'notice_msg', 'msg_unread_user_minute', 'msg_unread_group_minute', 'ignore_addr']）
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function setting__email()
    {
        $user = User::auth();
        //
        $type = trim(Request::input('type'));
        if ($type == 'save') {
            if (env("SYSTEM_SETTING") == 'disabled') {
                return Base::retError('当前环境禁止修改');
            }
            $user->identity('admin');
            $all = Request::input();
            foreach ($all as $key => $value) {
                if (!in_array($key, [
                    'smtp_server',
                    'port',
                    'account',
                    'password',
                    'reg_verify',
                    'notice',
                    'task_start_minute',
                    'task_remind_hours',
                    'task_remind_hours2',
                    'notice_msg',
                    'msg_unread_user_minute',
                    'msg_unread_group_minute',
                    'ignore_addr'
                ])) {
                    unset($all[$key]);
                }
            }
            $setting = Base::setting('emailSetting', Base::newTrim($all));
        } else {
            $setting = Base::setting('emailSetting');
        }
        //
        $setting['smtp_server'] = $setting['smtp_server'] ?: '';
        $setting['port'] = $setting['port'] ?: '';
        $setting['account'] = $setting['account'] ?: '';
        $setting['password'] = $setting['password'] ?: '';
        $setting['reg_verify'] = $setting['reg_verify'] ?: 'close';
        $setting['notice'] = $setting['notice'] ?: 'close';
        $setting['task_start_minute'] = intval($setting['task_start_minute'] ?? -1);
        $setting['task_remind_hours'] = floatval($setting['task_remind_hours'] ?? -1);
        $setting['task_remind_hours2'] = floatval($setting['task_remind_hours2'] ?? -1);
        $setting['notice_msg'] = $setting['notice_msg'] ?: 'close';
        $setting['msg_unread_user_minute'] = intval($setting['msg_unread_user_minute'] ?? -1);
        $setting['msg_unread_group_minute'] = intval($setting['msg_unread_group_minute'] ?? -1);
        $setting['ignore_addr'] = $setting['ignore_addr'] ?: '';
        //
        if ($type != 'save' && !in_array('admin', $user->identity)) {
            $setting = array_intersect_key($setting, array_flip(['reg_verify']));
        }
        //
        return Base::retSuccess('success', $setting ?: json_decode('{}'));
    }

    /**
     * @api {get} api/system/setting/meeting          03. 获取会议设置、保存会议设置（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName setting__meeting
     *
     * @apiParam {String} type
     * - get: 获取（默认）
     * - save: 保存设置（参数：['open', 'appid', 'app_certificate']）
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function setting__meeting()
    {
        User::auth('admin');
        //
        $type = trim(Request::input('type'));
        if ($type == 'save') {
            if (env("SYSTEM_SETTING") == 'disabled') {
                return Base::retError('当前环境禁止修改');
            }
            $all = Request::input();
            foreach ($all as $key => $value) {
                if (!in_array($key, [
                    'open',
                    'appid',
                    'app_certificate',
                ])) {
                    unset($all[$key]);
                }
            }
            $setting = Base::setting('meetingSetting', Base::newTrim($all));
        } else {
            $setting = Base::setting('meetingSetting');
        }
        //
        $setting['open'] = $setting['open'] ?: 'close';
        //
        return Base::retSuccess('success', $setting ?: json_decode('{}'));
    }

    /**
     * @api {get} api/system/setting/checkin          03. 获取签到设置、保存签到设置（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName setting__checkin
     *
     * @apiParam {String} type
     * - get: 获取（默认）
     * - save: 保存设置（参数：['open', 'edit', 'key']）
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function setting__checkin()
    {
        User::auth('admin');
        //
        $type = trim(Request::input('type'));
        if ($type == 'save') {
            if (env("SYSTEM_SETTING") == 'disabled') {
                return Base::retError('当前环境禁止修改');
            }
            $all = Request::input();
            foreach ($all as $key => $value) {
                if (!in_array($key, [
                    'open',
                    'edit',
                    'key',
                ])) {
                    unset($all[$key]);
                }
            }
            if ($all['open'] === 'close') {
                $all['key'] = md5(Base::generatePassword(32));
            }
            $setting = Base::setting('checkinSetting', Base::newTrim($all));
        } else {
            $setting = Base::setting('checkinSetting');
        }
        //
        if (empty($setting['key'])) {
            $setting['key'] = md5(Base::generatePassword(32));
            Base::setting('checkinSetting', $setting);
        }
        //
        $setting['open'] = $setting['open'] ?: 'close';
        $setting['edit'] = $setting['edit'] ?: 'close';
        $setting['cmd'] = "curl -sSL '" . Base::fillUrl("api/public/checkin/install?key={$setting['key']}") . "' | sh";
        //
        return Base::retSuccess('success', $setting ?: json_decode('{}'));
    }

    /**
     * @api {get} api/system/setting/apppush          04. 获取APP推送设置、保存APP推送设置（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName setting__apppush
     *
     * @apiParam {String} type
     * - get: 获取（默认）
     * - save: 保存设置（参数：['push', 'ios_key', 'ios_secret', 'android_key', 'android_secret', 'push_msg', 'push_task', 'task_start_minute', 'task_remind_hours', 'task_remind_hours2']）
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function setting__apppush()
    {
        User::auth('admin');
        //
        $type = trim(Request::input('type'));
        if ($type == 'save') {
            if (env("SYSTEM_SETTING") == 'disabled') {
                return Base::retError('当前环境禁止修改');
            }
            $all = Request::input();
            foreach ($all as $key => $value) {
                if (!in_array($key, [
                    'push',
                    'ios_key',
                    'ios_secret',
                    'android_key',
                    'android_secret',
                    'push_msg',
                    'push_task',
                    'task_start_minute',
                    'task_remind_hours',
                    'task_remind_hours2'
                ])) {
                    unset($all[$key]);
                }
            }
            $setting = Base::setting('appPushSetting', Base::newTrim($all));
        } else {
            $setting = Base::setting('appPushSetting');
        }
        //
        $setting['push'] = $setting['push'] ?: 'close';
        $setting['push_msg'] = $setting['push_msg'] ?: 'open';
        $setting['push_task'] = $setting['push_task'] ?: 'open';
        $setting['task_start_minute'] = intval($setting['task_start_minute']);
        $setting['task_remind_hours'] = floatval($setting['task_remind_hours']);
        $setting['task_remind_hours2'] = floatval($setting['task_remind_hours2']);
        //
        return Base::retSuccess('success', $setting ?: json_decode('{}'));
    }

    /**
     * @api {get} api/system/demo          05. 获取演示帐号
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName demo
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function demo()
    {
        $demo_account = env('DEMO_ACCOUNT');
        $demo_password = env('DEMO_PASSWORD');
        if (empty($demo_account) || empty($demo_password)) {
            return Base::retError('No demo account');
        }
        return Base::retSuccess('success', [
            'account' => $demo_account,
            'password' => $demo_password,
        ]);
    }

    /**
     * @api {post} api/system/priority          06. 任务优先级
     *
     * @apiDescription 获取任务优先级、保存任务优先级
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName priority
     *
     * @apiParam {String} type
     * - get: 获取（默认）
     * - save: 保存（限管理员）
     * @apiParam {Array} list   优先级数据，格式：[{name,color,days,priority}]
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function priority()
    {
        $type = trim(Request::input('type'));
        if ($type == 'save') {
            User::auth('admin');
            $list = Base::getPostValue('list');
            $array = [];
            if (empty($list) || !is_array($list)) {
                return Base::retError('参数错误');
            }
            foreach ($list AS $item) {
                if (empty($item['name']) || empty($item['color']) || empty($item['priority'])) {
                    continue;
                }
                $array[] = [
                    'name' => $item['name'],
                    'color' => $item['color'],
                    'days' => intval($item['days']),
                    'priority' => intval($item['priority']),
                ];
            }
            if (empty($array)) {
                return Base::retError('参数为空');
            }
            $setting = Base::setting('priority', $array);
        } else {
            $setting = Base::setting('priority');
        }
        //
        return Base::retSuccess('success', $setting);
    }

    /**
     * @api {post} api/system/column/template          07. 创建项目模板
     *
     * @apiDescription 获取创建项目模板、保存创建项目模板
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName column__template
     *
     * @apiParam {String} type
     * - get: 获取（默认）
     * - save: 保存（限管理员）
     * @apiParam {Array} list   优先级数据，格式：[{name,columns}]
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function column__template()
    {
        $type = trim(Request::input('type'));
        if ($type == 'save') {
            User::auth('admin');
            $list = Base::getPostValue('list');
            $array = [];
            if (empty($list) || !is_array($list)) {
                return Base::retError('参数错误');
            }
            foreach ($list AS $item) {
                if (empty($item['name']) || empty($item['columns'])) {
                    continue;
                }
                $array[] = [
                    'name' => $item['name'],
                    'columns' => array_values(array_filter(array_unique(explode(",", $item['columns']))))
                ];
            }
            if (empty($array)) {
                return Base::retError('参数为空');
            }
            $setting = Base::setting('columnTemplate', $array);
        } else {
            $setting = Base::setting('columnTemplate');
        }
        //
        return Base::retSuccess('success', $setting);
    }

    /**
     * @api {get} api/system/get/info          08. 获取终端详细信息
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName get__info
     *
     * @apiParam {String} key       key值
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function get__info()
    {
        if (Request::input("key") !== env('APP_KEY')) {
            return [];
        }
        return Base::retSuccess('success', [
            'ip' => Base::getIp(),
            'ip-info' => Base::getIpInfo(Base::getIp()),
            'ip-gcj02' => Base::getIpGcj02(Base::getIp()),
            'ip-iscn' => Base::isCnIp(Base::getIp()),
            'header' => Request::header(),
            'token' => Base::getToken(),
            'url' => url('') . Base::getUrl(),
        ]);
    }

    /**
     * @api {get} api/system/get/ip          09. 获取IP地址
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName get__ip
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function get__ip() {
        return Base::getIp();
    }

    /**
     * @api {get} api/system/get/cnip          10. 是否中国IP地址
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName get__cnip
     *
     * @apiParam {String} ip        IP值
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function get__cnip() {
        return Base::isCnIp(Request::input('ip'));
    }

    /**
     * @api {get} api/system/get/ipgcj02          11. 获取IP地址经纬度
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName get__ipgcj02
     *
     * @apiParam {String} ip        IP值
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function get__ipgcj02() {
        return Base::getIpGcj02(Request::input("ip"));
    }

    /**
     * @api {get} api/system/get/ipinfo          12. 获取IP地址详细信息
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName get__ipinfo
     *
     * @apiParam {String} ip        IP值
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function get__ipinfo() {
        return Base::getIpInfo(Request::input("ip"));
    }

    /**
     * @api {post} api/system/imgupload          13. 上传图片
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName imgupload
     *
     * @apiParam {File} image               post-图片对象
     * @apiParam {String} [image64]         post-图片base64（与'image'二选一）
     * @apiParam {String} filename          post-文件名
     * @apiParam {Number} [width]           压缩图片宽（默认0）
     * @apiParam {Number} [height]          压缩图片高（默认0）
     * @apiParam {String} [whcut]           压缩方式
     * - 1：裁切（默认，宽、高非0有效）
     * - 0：缩放
     * - -1或'auto'：保持等比裁切
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function imgupload()
    {
        if (User::userid() === 0) {
            return Base::retError('身份失效，等重新登录');
        }
        $width = intval(Request::input('width'));
        $height = intval(Request::input('height'));
        $whcut = intval(Request::input('whcut', 1));
        $scale = [2160, 4160, -1];
        if ($width > 0 || $height > 0) {
            $scale = [$width, $height, $whcut];
        }
        $path = "uploads/user/picture/" . User::userid() . "/" . date("Ym") . "/";
        $image64 = trim(Base::getPostValue('image64'));
        $fileName = trim(Base::getPostValue('filename'));
        if ($image64) {
            $data = Base::image64save([
                "image64" => $image64,
                "path" => $path,
                "fileName" => $fileName,
                "scale" => $scale
            ]);
        } else {
            $data = Base::upload([
                "file" => Request::file('image'),
                "type" => 'image',
                "path" => $path,
                "fileName" => $fileName,
                "scale" => $scale
            ]);
        }
        if (Base::isError($data)) {
            return Base::retError($data['msg']);
        } else {
            return Base::retSuccess('success', $data['data']);
        }
    }

    /**
     * @api {get} api/system/get/imgview          14. 浏览图片空间
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName imgview
     *
     * @apiParam {String} path        路径
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function imgview()
    {
        if (User::userid() === 0) {
            return Base::retError('身份失效，等重新登录');
        }
        $publicPath = "uploads/user/picture/" . User::userid() . "/";
        $dirPath = public_path($publicPath);
        $dirs = $files = [];
        //
        $path = Request::input('path');
        if ($path && is_string($path)) {
            $path = str_replace(array('||', '|'), '/', $path);
            $path = trim($path, '/');
            $path = str_replace('..', '', $path);
            $path = Base::leftDelete($path, $publicPath);
            if ($path) {
                $path = $path . '/';
                $dirPath .= $path;
                //
                $dirs[] = [
                    'type' => 'dir',
                    'title' => '...',
                    'path' => substr(substr($path, 0, -1), 0, strripos(substr($path, 0, -1), '/')),
                    'url' => '',
                    'thumb' => Base::fillUrl('images/other/dir.png'),
                    'inode' => 0,
                ];
            }
        } else {
            $path = '';
        }
        $list = glob($dirPath . '*', GLOB_BRACE);
        foreach ($list as $v) {
            $filename = basename($v);
            $pathTemp = $publicPath . $path . $filename;
            if (is_dir($v)) {
                $dirs[] = [
                    'type' => 'dir',
                    'title' => $filename,
                    'path' => $pathTemp,
                    'url' => Base::fillUrl($pathTemp),
                    'thumb' => Base::fillUrl('images/other/dir.png'),
                    'inode' => fileatime($v),
                ];
            } elseif (!str_ends_with($filename, "_thumb.jpg")) {
                $array = [
                    'type' => 'file',
                    'title' => $filename,
                    'path' => $pathTemp,
                    'url' => Base::fillUrl($pathTemp),
                    'thumb' => $pathTemp,
                    'inode' => fileatime($v),
                ];
                //
                $extension = pathinfo($dirPath . $filename, PATHINFO_EXTENSION);
                if (in_array($extension, array('gif', 'jpg', 'jpeg', 'png', 'bmp'))) {
                    if (file_exists($dirPath . $filename . '_thumb.jpg')) {
                        $array['thumb'] .= '_thumb.jpg';
                    }
                    $array['thumb'] = Base::fillUrl($array['thumb']);
                    $files[] = $array;
                }
            }
        }
        if ($dirs) {
            $inOrder = [];
            foreach ($dirs as $key => $item) {
                $inOrder[$key] = $item['title'];
            }
            array_multisort($inOrder, SORT_DESC, $dirs);
        }
        if ($files) {
            $inOrder = [];
            foreach ($files as $key => $item) {
                $inOrder[$key] = $item['inode'];
            }
            array_multisort($inOrder, SORT_DESC, $files);
        }
        //
        return Base::retSuccess('success', ['dirs' => $dirs, 'files' => $files]);
    }

    /**
     * @api {post} api/system/fileupload          15. 上传文件
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName fileupload
     *
     * @apiParam {String} [image64]         图片base64
     * @apiParam {String} filename          文件名
     * @apiParam {String} [files]           文件名
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function fileupload()
    {
        if (User::userid() === 0) {
            return Base::retError('身份失效，等重新登录');
        }
        $path = "uploads/user/file/" . User::userid() . "/" . date("Ym") . "/";
        $image64 = trim(Base::getPostValue('image64'));
        $fileName = trim(Base::getPostValue('filename'));
        if ($image64) {
            $data = Base::image64save([
                "image64" => $image64,
                "path" => $path,
                "fileName" => $fileName,
            ]);
        } else {
            $data = Base::upload([
                "file" => Request::file('files'),
                "type" => 'file',
                "path" => $path,
                "fileName" => $fileName,
            ]);
        }
        //
        return $data;
    }

    /**
     * @api {get} api/system/get/starthome          16. 启动首页设置信息
     *
     * @apiDescription 用于判断注册是否需要启动首页
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName get__starthome
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function get__starthome()
    {
        return Base::retSuccess('success', [
            'need_start' => Base::settingFind('system', 'start_home') == 'open',
            'home_footer' => Base::settingFind('system', 'home_footer')
        ]);
    }

    /**
     * @api {get} api/system/email/check          17. 邮件发送测试（限管理员）
     *
     * @apiDescription 测试配置邮箱是否能发送邮件
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName email__check
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function email__check()
    {
        User::auth('admin');
        //
        $all = Request::input();
        if (!Base::isEmail($all['to'])) {
            return Base::retError('请输入正确的收件人地址');
        }
        try {
            Setting::validateAddr($all['to'], function($to) use ($all) {
                Factory::mailer()
                    ->setDsn("smtp://{$all['account']}:{$all['password']}@{$all['smtp_server']}:{$all['port']}?verify_peer=0")
                    ->setMessage(EmailMessage::create()
                        ->from(env('APP_NAME', 'Task') . " <{$all['account']}>")
                        ->to($to)
                        ->subject('Mail sending test')
                        ->html('<p>收到此电子邮件意味着您的邮箱配置正确。</p><p>Receiving this email means that your mailbox is configured correctly.</p>'))
                    ->send();
            }, function () {
                throw new \Exception("收件人地址错误或已被忽略");
            });
            return Base::retSuccess('成功发送');
        } catch (\Throwable $e) {
            // 一般是请求超时
            if (str_contains($e->getMessage(), "Timed Out")) {
                return Base::retError("language.TimedOut");
            } elseif ($e->getCode() === 550) {
                return Base::retError('邮件内容被拒绝，请检查邮箱是否开启接收功能');
            } else {
                return Base::retError($e->getMessage());
            }
        }
    }

    /**
     * @api {get} api/system/checkin/export          17. 导出签到数据（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName checkin__export
     *
     * @apiParam {Array} [userid]               指定会员，如：[1, 2]
     * @apiParam {Array} [date]                 指定日期范围，如：['2020-12-12', '2020-12-30']
     * @apiParam {Array} [time]                 指定时间范围，如：['09:00', '18:00']
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function checkin__export()
    {
        User::auth('admin');
        //
        $userid = Base::arrayRetainInt(Request::input('userid'), true);
        $date = Request::input('date');
        $time = Request::input('time');
        //
        if (empty($userid) || empty($date) || empty($time)) {
            return Base::retError('参数错误');
        }
        if (count($userid) > 20) {
            return Base::retError('导出成员限制最多20个');
        }
        if (!(is_array($date) && Base::isDate($date[0]) && Base::isDate($date[1]))) {
            return Base::retError('日期选择错误');
        }
        if (Carbon::parse($date[1])->timestamp - Carbon::parse($date[0])->timestamp > 35 * 86400) {
            return Base::retError('日期范围限制最大35天');
        }
        if (!(is_array($time) && Base::isTime($time[0]) && Base::isTime($time[1]))) {
            return Base::retError('时间选择错误');
        }
        //
        $secondStart = strtotime("2000-01-01 {$time[0]}") - strtotime("2000-01-01 00:00:00");
        $secondEnd = strtotime("2000-01-01 {$time[1]}") - strtotime("2000-01-01 00:00:00");
        //
        $headings = [];
        $headings[] = '成员ID';
        $headings[] = '成员名称';
        $headings[] = '成员邮箱';
        $headings[] = '签到日期';
        $headings[] = '签到时间1';
        $headings[] = '签到时间2';
        //
        $sheets = [];
        $start = Carbon::parse($date[0])->startOfDay();
        $end = Carbon::parse($date[1])->endOfDay();
        $users = User::whereIn('userid', $userid)->take(20)->get();
        /** @var User $user */
        foreach ($users as $user) {
            $records = UserCheckinRecord::whereUserid($user->userid)->whereBetween("created_at", [$start, $end])->orderBy('id')->get();
            //
            $datas = [];
            $styles = [];
            $startT = $start->timestamp;
            $endT = $end->timestamp;
            $index = 1;
            while ($startT < $endT) {
                $index++;
                $first = $records->whereBetween("created_at", [Carbon::parse($startT), Carbon::parse($startT + $secondStart)])->first();
                $last = $records->whereBetween("created_at", [Carbon::parse($startT + $secondEnd), Carbon::parse($startT + 86400)])->last();
                $first = $first ? Carbon::parse($first->created_at)->timestamp : 0;
                $last = $last ? Carbon::parse($last->created_at)->timestamp : 0;
                if (empty($first) || $first > $startT + $secondStart) {
                    $styles["E{$index}"] = [
                        'font' => [
                            'color' => [
                                'rgb' => 'ff0000'
                            ]
                        ],
                    ];
                }
                if (empty($last) || $last < $startT + $secondEnd) {
                    $styles["F{$index}"] = [
                        'font' => [
                            'color' => [
                                'rgb' => 'ff0000'
                            ]
                        ],
                    ];
                }
                $datas[] = [
                    $user->userid,
                    $user->nickname,
                    $user->email,
                    date("Y-m-d", $startT),
                    $first ? date("H:i", $first) : '-',
                    $last ? date("H:i", $last) : '-',
                ];
                $startT += 86400;
            }
            $sheets[] = BillExport::create()->setTitle($user->nickname)->setHeadings($headings)->setData($datas)->setStyles($styles);
        }
        if (empty($sheets)) {
            return Base::retError('没有任何数据');
        }
        //
        $fileName = $users[0]->nickname;
        if (count($users) > 1) {
            $fileName .= "等" . count($userid) . "位成员";
        }
        $fileName .= '签到记录_' . Base::time() . '.xls';
        $filePath = "temp/checkin/export/" . date("Ym", Base::time());
        $export = new BillMultipleExport($sheets);
        $res = $export->store($filePath . "/" . $fileName);
        if ($res != 1) {
            return Base::retError('导出失败，' . $fileName . '！');
        }
        $xlsPath = storage_path("app/" . $filePath . "/" . $fileName);
        $zipFile = "app/" . $filePath . "/" . Base::rightDelete($fileName, '.xls') . ".zip";
        $zipPath = storage_path($zipFile);
        if (file_exists($zipPath)) {
            Base::deleteDirAndFile($zipPath, true);
        }
        try {
            Madzipper::make($zipPath)->add($xlsPath)->close();
        } catch (\Throwable) {
        }
        //
        if (file_exists($zipPath)) {
            $base64 = base64_encode(Base::array2string([
                'file' => $zipFile,
            ]));
            Session::put('checkin::export:userid', $user->userid);
            return Base::retSuccess('success', [
                'size' => Base::twoFloat(filesize($zipPath) / 1024, true),
                'url' => Base::fillUrl('api/system/checkin/down?key=' . urlencode($base64)),
            ]);
        } else {
            return Base::retError('打包失败，请稍后再试...');
        }
    }

    /**
     * @api {get} api/system/checkin/down          17. 下载导出的签到数据
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName checkin__down
     *
     * @apiParam {String} key               通过export接口得到的下载钥匙
     *
     * @apiSuccess {File} data     返回数据（直接下载文件）
     */
    public function checkin__down()
    {
        $userid = Session::get('checkin::export:userid');
        if (empty($userid)) {
            return Base::ajaxError("请求已过期，请重新导出！", [], 0, 502);
        }
        //
        $array = Base::string2array(base64_decode(urldecode(Request::input('key'))));
        $file = $array['file'];
        if (empty($file) || !file_exists(storage_path($file))) {
            return Base::ajaxError("文件不存在！", [], 0, 502);
        }
        return response()->download(storage_path($file));
    }

    /**
     * @api {get} api/system/version          18. 获取版本号
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName version
     *
     * @apiSuccess {String} version
     * @apiSuccess {String} publish
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
}
