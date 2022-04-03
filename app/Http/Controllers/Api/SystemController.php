<?php

namespace App\Http\Controllers\Api;

use App\Models\AbstractModel;
use App\Models\NotifyRule;
use App\Models\User;
use App\Module\Base;
use App\Module\Telegram;
use Request;

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
     * - save: 保存设置（参数：['reg', 'reg_invite', 'login_code', 'password_policy', 'project_invite', 'chat_nickname', 'auto_archived', 'archived_day', 'start_home', 'home_footer']）

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
                    'chat_nickname',
                    'auto_archived',
                    'archived_day',
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
        $setting['chat_nickname'] = $setting['chat_nickname'] ?: 'optional';
        $setting['auto_archived'] = $setting['auto_archived'] ?: 'close';
        $setting['archived_day'] = floatval($setting['archived_day']) ?: 7;
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
     * - save: 保存设置（参数：['smtp_server', 'port', 'account', 'password', 'reg_verify', 'notice', 'task_remind_hours', 'task_remind_hours2']）
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function setting__email()
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
                    'smtp_server',
                    'port',
                    'account',
                    'password',
                    'reg_verify',
                    'notice',
                    'task_remind_hours',
                    'task_remind_hours2'
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
        $setting['notice'] = $setting['notice'] ?: 'open';
        $setting['task_remind_hours'] = floatval($setting['task_remind_hours']) ?: 0;
        $setting['task_remind_hours2'] = floatval($setting['task_remind_hours2']) ?: 0;
        //
        return Base::retSuccess('success', $setting ?: json_decode('{}'));
    }

    /**
     * @api {get} api/system/demo          03. 获取演示账号
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
     * @api {post} api/system/priority          04. 任务优先级
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
     * @api {post} api/system/column/template          05. 创建项目模板
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
     * @api {get} api/system/get/info          06. 获取终端详细信息
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
     * @api {get} api/system/get/ip          07. 获取IP地址
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
     * @api {get} api/system/get/cnip          08. 是否中国IP地址
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
     * @api {get} api/system/get/ipgcj02          09. 获取IP地址经纬度
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
     * @api {get} api/system/get/ipinfo          10. 获取IP地址详细信息
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
     * @api {post} api/system/imgupload          11. 上传图片
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName imgupload
     *
     * @apiParam {String} image64         图片base64
     * @apiParam {String} filename        文件名
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
        $scale = [intval(Request::input('width')), intval(Request::input('height'))];
        if (!$scale[0] && !$scale[1]) {
            $scale = [2160, 4160, -1];
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
     * @api {get} api/system/get/imgview          12. 浏览图片空间
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
     * @api {post} api/system/fileupload          13. 上传文件
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
     * @api {get} api/system/get/starthome          14. 启动首页设置信息
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
     * @api {get} api/system/notify/lists          推送通知 - 列表（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName notify__lists
     *
     * @apiParam {Object} [keys]             搜索条件
     * - keys.name: 名称关键词
     *
     * @apiParam {Number} [page]        当前页，默认:1
     * @apiParam {Number} [pagesize]    每页显示数量，默认:20，最大:100
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function notify__lists()
    {
        User::auth('admin');
        //
        $builder = NotifyRule::select(['*']);
        //
        $keys = Request::input('keys');
        if (is_array($keys)) {
            if ($keys['name']) {
                $builder->where("name", "like", "%{$keys['name']}%");
            }
        }
        $list = $builder->orderByDesc('id')->paginate(Base::getPaginate(100, 20));
        //
        return Base::retSuccess('success', $list);
    }

    /**
     * @api {post} api/system/notify/add          推送通知 - 添加规则（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName notify__add
     *
     * @apiParam {Number} [id]                  数据ID（为空时添加）
     * @apiParam {String} mode                  推送方式
     * @apiParam {String} name                  规则名称
     * @apiParam {String} event                 触发条件
     * @apiParam {String} content               推送内容
     * @apiParam {String} [webhook_url]         推送URL（推送方式为：webhook时使用）
     * @apiParam {Number} [expire_hours]        时间条件（触发条件为：taskExpireBefore、taskExpireAfter时使用）
     * @apiParam {Number} [status]              是否启用
     * - 1：启用（默认）
     * - 0：关闭
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function notify__add()
    {
        User::auth('admin');
        //
        $id = intval(Base::getPostValue('id'));
        $mode = trim(Base::getPostValue('mode'));
        $name = trim(Base::getPostValue('name'));
        $event = trim(Base::getPostValue('event'));
        $content = Base::getPostValue('content');
        $webhook_url = trim(Base::getPostValue('webhook_url'));
        $expire_hours = round(Base::getPostValue('expire_hours'), 1);
        $status = Base::getPostInt('status', 1);
        if (empty($name)) {
            return Base::retError('请填写规则名称');
        }
        if (empty($mode)) {
            return Base::retError('请选择推送方式');
        }
        if ($mode === 'webhook') {
            if (empty($webhook_url)) {
                return Base::retError('请填写推送URL');
            }
            if (!preg_match("/^https*:\/\//", $webhook_url)) {
                return Base::retError('推送URL必须是以 http:// 或 https:// 开头');
            }
            $content = Base::json2array($content);
            if (empty($content)) {
                return Base::retError('请填写有效的推送内容');
            }
            $content = Base::array2json($content, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        }
        if (empty($event)) {
            return Base::retError('请选择触发条件');
        }
        if (in_array($event, ['taskExpireBefore', 'taskExpireAfter'])) {
            if ($expire_hours <= 0 || $expire_hours > 720) {
                return Base::retError('请填写有效的时间条件');
            }
        }
        if (NotifyRule::whereMode($mode)->whereEvent($event)->count() >= 10) {
            return Base::retError("同个推送方式和触发条件最多只能添加10条规则");
        }
        $data = [
            'mode' => $mode,
            'name' => $name,
            'event' => $event,
            'content' => $content,
            'webhook_url' => $webhook_url,
            'expire_hours' => $expire_hours,
            'status' => $status,
        ];
        if ($id > 0) {
            //修改
            $rule = NotifyRule::createInstance($data, $id);
            if ($rule->save()) {
                return Base::retSuccess('修改成功！');
            }
        } else {
            //添加
            $rule = NotifyRule::createInstance($data);
            if ($rule->save()) {
                return Base::retSuccess('添加成功！');
            }
        }
        return Base::retError('操作失败！');
    }


    /**
     * @api {post} api/system/notify/delete          推送通知 - 删除规则（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName notify__delete
     *
     * @apiParam {Number} id              数据ID（支持数组）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function notify__delete()
    {
        User::auth('admin');
        //
        $id = Request::input('id');
        //
        if (is_array($id)) {
            $builder = NotifyRule::whereIn("id", Base::arrayRetainInt($id));
        } else {
            $builder = NotifyRule::whereId(intval($id));
        }
        $list = $builder->get();
        if ($list->isEmpty()) {
            return Base::retError('规则不存在或已被删除');
        }
        return AbstractModel::transaction(function() use ($list) {
            $tmp = [];
            foreach ($list as $item) {
                $tmp[] = $item->id;
                $item->delete();
            }
            return Base::retSuccess('删除成功', $tmp);
        });
    }

    /**
     * @api {get} api/system/notify/config          推送通知 - 参数配置（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName notify__config
     *
     * @apiParam {String} type
     * - get: 获取（默认）
     * - all: 获取所有（需要管理员权限）
     * - save: 保存设置（参数：['mail_server', 'mail_port', 'mail_account', 'mail_password', 'dingding_token', 'dingding_secret', 'feishu_token', 'feishu_secret', 'wework_token', 'xizhi_token', 'telegram_token', 'gitter_token', 'gitter_roomid', 'googlechat_token', 'googlechat_key', 'googlechat_space']）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function notify__config()
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
                    'mail_server',
                    'mail_port',
                    'mail_account',
                    'mail_password',
                    'dingding_token',
                    'dingding_secret',
                    'feishu_token',
                    'feishu_secret',
                    'wework_token',
                    'xizhi_token',
                    'telegram_token',
                    'gitter_token',
                    'gitter_roomid',
                    'googlechat_token',
                    'googlechat_key',
                    'googlechat_space'
                ])) {
                    unset($all[$key]);
                } else {
                    $all[$key] = substr($value, 0, 128);
                }
            }
            $old = Base::setting('notifyConfig');
            $new = Base::newTrim($all);
            if ($old['telegram_token'] != $new['telegram_token']) {
                $res = Telegram::create($new['telegram_token'])->setWebhook();
                if (Base::isError($res)) {
                    return $res;
                }
                $resData = Base::json2array($res['data']);
                if ($resData['ok'] !== true) {
                    return Base::retError($resData['description'] ?? $resData['error_code'] ?? 'Telegram token unknown error');
                }
            } else {
                $new['telegram_webhook_token'] = $old['telegram_webhook_token'] ?? '';
            }
            $setting = Base::setting('notifyConfig', $new);
        } else {
            $setting = Base::setting('notifyConfig');
        }
        //
        return Base::retSuccess('success', $setting ?: json_decode('{}'));
    }
}
