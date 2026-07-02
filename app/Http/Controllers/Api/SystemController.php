<?php

namespace App\Http\Controllers\Api;

use App\Models\UserDevice;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Module\AI;
use App\Module\Down;
use Request;
use Response;
use Carbon\Carbon;
use App\Module\Doo;
use App\Models\User;
use App\Module\Base;
use App\Module\OnlineLicense;
use App\Module\Timer;
use App\Models\Setting;
use LdapRecord\Container;
use App\Module\BillExport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use App\Models\UserCheckinRecord;
use App\Module\Apps;
use App\Module\BillMultipleExport;
use LdapRecord\LdapRecordException;
use Swoole\Coroutine;

/**
 * @apiDefine system
 *
 * 系统
 */
class SystemController extends AbstractController
{

    /**
     * @api {get} api/system/setting 获取设置、保存设置
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName setting
     *
     * @apiParam {String} type
     * - get: 获取（默认）
     * - all: 获取所有（需要管理员权限）
     * - save: 保存设置（参数：['reg', 'reg_identity', 'reg_invite', 'temp_account_alias', 'login_code', 'password_policy', 'project_invite', 'chat_information', 'anon_message', 'convert_video', 'compress_video', 'e2e_message', 'auto_archived', 'archived_day', 'task_visible', 'task_default_time', 'task_user_limit', 'all_group_mute', 'all_group_autoin', 'user_private_chat_mute', 'user_group_chat_mute', 'system_alias', 'system_welcome', 'image_compress', 'image_quality', 'image_save_local']）

     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function setting()
    {
        $type = trim(Request::input('type'));
        if ($type == 'save') {
            if (config('dootask.system_setting') == 'disabled') {
                return Base::retError('当前环境禁止修改');
            }
            Base::checkClientVersion('0.41.11');
            User::auth('admin');
            $all = Request::input();
            foreach ($all AS $key => $value) {
                if (!in_array($key, [
                    'reg',
                    'reg_identity',
                    'reg_invite',
                    'temp_account_alias',
                    'login_code',
                    'password_policy',
                    'project_invite',
                    'project_add_permission',
                    'project_add_userids',
                    'chat_information',
                    'anon_message',
                    'convert_video',
                    'compress_video',
                    'e2e_message',
                    'msg_rev_limit',
                    'msg_edit_limit',
                    'auto_archived',
                    'archived_day',
                    'task_visible',
                    'task_default_time',
                    'task_user_limit',
                    'all_group_mute',
                    'all_group_autoin',
                    'user_private_chat_mute',
                    'user_group_chat_mute',
                    'system_alias',
                    'system_welcome',
                    'image_compress',
                    'image_quality',
                    'image_save_local',
                    'file_upload_limit',
                    'unclaimed_task_reminder',
                    'unclaimed_task_reminder_time',
                    'task_ai_auto_analyze',
                    'department_owner_project_view',
                    'todo_set_permission',
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
            if ($all['system_alias'] == config('app.name')) {
                $all['system_alias'] = '';
            }
            if ($all['system_welcome'] == '欢迎您，{username}') {
                $all['system_welcome'] = '';
            }
            $setting = Base::setting('system', Base::newTrim($all));
        } else {
            $setting = Base::setting('system');
        }
        //
        if ($type == 'all' || $type == 'save') {
            User::auth('admin');
            $setting['reg_invite'] = $setting['reg_invite'] ?: Base::generatePassword();
        } else {
            if (isset($setting['reg_invite'])) unset($setting['reg_invite']);
        }
        //
        $setting['reg'] = $setting['reg'] ?: 'open';
        $setting['reg_identity'] = $setting['reg_identity'] ?: 'normal';
        $setting['temp_account_alias'] = $setting['temp_account_alias'] ?: '';
        $setting['login_code'] = $setting['login_code'] ?: 'auto';
        $setting['password_policy'] = $setting['password_policy'] ?: 'simple';
        $setting['project_invite'] = $setting['project_invite'] ?: 'open';
        $setting['chat_information'] = $setting['chat_information'] ?: 'optional';
        $setting['anon_message'] = $setting['anon_message'] ?: 'open';
        $setting['convert_video'] = $setting['convert_video'] ?: 'close';
        $setting['compress_video'] = $setting['compress_video'] ?: 'close';
        $setting['e2e_message'] = $setting['e2e_message'] ?: 'close';
        $setting['msg_rev_limit'] = $setting['msg_rev_limit'] ?: '';
        $setting['msg_edit_limit'] = $setting['msg_edit_limit'] ?: '';
        $setting['auto_archived'] = $setting['auto_archived'] ?: 'close';
        $setting['archived_day'] = floatval($setting['archived_day']) ?: 7;
        $setting['task_visible'] = $setting['task_visible'] ?: 'close';
        $setting['all_group_mute'] = $setting['all_group_mute'] ?: 'open';
        $setting['todo_set_permission'] = $setting['todo_set_permission'] ?: 'open';
        $setting['all_group_autoin'] = $setting['all_group_autoin'] ?: 'yes';
        $setting['user_private_chat_mute'] = $setting['user_private_chat_mute'] ?: 'open';
        $setting['user_group_chat_mute'] = $setting['user_group_chat_mute'] ?: 'open';
        $setting['file_upload_limit'] = $setting['file_upload_limit'] ?: '';
        $setting['unclaimed_task_reminder'] = $setting['unclaimed_task_reminder'] ?: 'close';
        $setting['unclaimed_task_reminder_time'] = $setting['unclaimed_task_reminder_time'] ?: '';
        $setting['task_ai_auto_analyze'] = $setting['task_ai_auto_analyze'] ?: 'open';
        $setting['department_owner_project_view'] = $setting['department_owner_project_view'] ?: 'close';
        $setting['server_timezone'] = config('app.timezone');
        $setting['server_version'] = Base::getVersion();
        // 指定人员名单仅管理员可见
        if ($type != 'all' && $type != 'save') {
            unset($setting['project_add_userids']);
        }
        //
        return Base::retSuccess($type == 'save' ? '保存成功' : 'success', $setting ?: json_decode('{}'));
    }

    /**
     * @api {get} api/system/setting/email 获取邮箱设置、保存邮箱设置（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName setting__email
     *
     * @apiParam {String} type
     * - get: 获取（默认）
     * - save: 保存设置（参数：['smtp_server', 'port', 'account', 'password', 'reg_verify', 'notice_msg', 'msg_unread_user_minute', 'msg_unread_group_minute', 'ignore_addr']）
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
            if (config('dootask.system_setting') == 'disabled') {
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
                    'notice_msg',
                    'msg_unread_user_minute',
                    'msg_unread_group_minute',
                    'msg_unread_time_ranges',
                    'ignore_addr'
                ])) {
                    unset($all[$key]);
                }
            }
            $ranges = array_map(function ($item) {
                return !is_array($item) ? explode(',', $item) : $item;
            }, is_array($all['msg_unread_time_ranges']) ? $all['msg_unread_time_ranges'] : []);
            $all['msg_unread_time_ranges'] = array_values(array_filter($ranges, function ($item) {
                return count($item) == 2 && Timer::isTime($item[0]) && Timer::isTime($item[1]);
            }));
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
        $setting['notice_msg'] = $setting['notice_msg'] ?: 'close';
        $setting['msg_unread_user_minute'] = intval($setting['msg_unread_user_minute'] ?? -1);
        $setting['msg_unread_group_minute'] = intval($setting['msg_unread_group_minute'] ?? -1);
        $setting['msg_unread_time_ranges'] = is_array($setting['msg_unread_time_ranges']) ? $setting['msg_unread_time_ranges'] : [[]];
        $setting['ignore_addr'] = $setting['ignore_addr'] ?: '';
        //
        if ($type != 'save' && !in_array('admin', $user->identity)) {
            $setting = array_intersect_key($setting, array_flip(['reg_verify']));
        }
        //
        return Base::retSuccess($type == 'save' ? '保存成功' : 'success', $setting ?: json_decode('{}'));
    }

    /**
     * @api {get} api/system/setting/meeting 获取会议设置、保存会议设置（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName setting__meeting
     *
     * @apiParam {String} type
     * - get: 获取（默认）
     * - save: 保存设置（参数：['open', 'appid', 'app_certificate', 'api_key', 'api_secret']）
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
            if (config('dootask.system_setting') == 'disabled') {
                return Base::retError('当前环境禁止修改');
            }
            $all = Request::input();
            foreach ($all as $key => $value) {
                if (!in_array($key, [
                    'open',
                    'appid',
                    'app_certificate',
                    'api_key',
                    'api_secret',
                ])) {
                    unset($all[$key]);
                }
            }
            if ($all['open'] === 'open' && (!$all['appid'] || !$all['app_certificate'])) {
                return Base::retError('请填写基本配置');
            }
            $setting = Base::setting('meetingSetting', Base::newTrim($all));
        } else {
            $setting = Base::setting('meetingSetting');
        }
        //
        $setting['open'] = $setting['open'] ?: 'close';
        if (config('dootask.system_setting') == 'disabled') {
            $setting['appid'] = substr($setting['appid'], 0, 4) . str_repeat('*', strlen($setting['appid']) - 8) . substr($setting['appid'], -4);
            $setting['app_certificate'] = substr($setting['app_certificate'], 0, 4) . str_repeat('*', strlen($setting['app_certificate']) - 8) . substr($setting['app_certificate'], -4);
            $setting['api_key'] = substr($setting['api_key'], 0, 4) . str_repeat('*', strlen($setting['api_key']) - 8) . substr($setting['api_key'], -4);
            $setting['api_secret'] = substr($setting['api_secret'], 0, 4) . str_repeat('*', strlen($setting['api_secret']) - 8) . substr($setting['api_secret'], -4);
        }
        //
        return Base::retSuccess($type == 'save' ? '保存成功' : 'success', $setting ?: json_decode('{}'));
    }

    /**
     * AI助手设置（限管理员）
     *
     * @deprecated 已废弃方法，仅保留路由占位，后续版本中移除
     */
    public function setting__ai()
    {
        Base::checkClientVersion('1.4.35');
    }

    /**
     * @api {get} api/system/setting/aibot 获取AI设置、保存AI机器人设置（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName setting__aibot
     *
     * @apiParam {String} type
     * - get: 获取（默认）
     * - save: 保存设置（参数：[...]）
     * @apiParam {String} filter    过滤字段（可选）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function setting__aibot()
    {
        User::auth('admin');
        //
        Apps::isInstalledThrow('ai');
        //
        $type = trim(Request::input('type'));
        $filter = trim(Request::input('filter'));
        $setting = Base::setting('aibotSetting');
        if ($type == 'save') {
            if (config('dootask.system_setting') == 'disabled') {
                return Base::retError('当前环境禁止修改');
            }
            Base::checkClientVersion('0.41.11');
            $all = Request::input();
            foreach ($all as $key => $value) {
                if (isset($setting[$key])) {
                    $setting[$key] = $value;
                }
            }
            $setting = Base::setting('aibotSetting', Base::newTrim($setting));
        }
        if ($filter) {
            $setting = array_filter($setting, function($value, $key) use ($filter) {
                return str_starts_with($key, $filter);
            }, ARRAY_FILTER_USE_BOTH);
        }
        //
        if (config('dootask.system_setting') == 'disabled') {
            foreach ($setting as $key => $item) {
                if (empty($item)) {
                    continue;
                }
                // dooai_key 是官方网关 token，需原样返回供鉴权
                if ($key === 'dooai_key') {
                    continue;
                }
                if (str_ends_with($key, '_key') || str_ends_with($key, '_secret')) {
                    $setting[$key] = substr($item, 0, 4) . str_repeat('*', strlen($item) - 8) . substr($item, -4);
                }
            }
        }
        //
        return Base::retSuccess($type == 'save' ? '保存成功' : 'success', $setting ?: json_decode('{}'));
    }

    /**
     * 获取AI模型
     *
     * @deprecated 已废弃方法，仅保留路由占位，后续版本中移除
     */
    public function setting__aibot_models()
    {
        Base::checkClientVersion('1.4.35');
    }

    /**
     * 获取AI默认模型
     *
     * @deprecated 已废弃方法，仅保留路由占位，后续版本中移除
     */
    public function setting__aibot_defmodels()
    {
        Base::checkClientVersion('1.4.35');
    }

    /**
     * @api {get} api/system/setting/checkin 获取签到设置、保存签到设置（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName setting__checkin
     *
     * @apiParam {String} type
     * - get: 获取（默认）
     * - save: 保存设置（参数：['open', 'time', 'advance', 'delay', 'remindin', 'remindexceed', 'edit', 'modes', 'key']）
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
            if (config('dootask.system_setting') == 'disabled') {
                return Base::retError('当前环境禁止修改');
            }
            $all = Request::input();
            foreach ($all as $key => $value) {
                if (!in_array($key, [
                    'open',
                    'time',
                    'advance',
                    'delay',
                    'remindin',
                    'remindexceed',
                    'edit',
                    'face_upload',
                    'face_remark',
                    'face_retip',
                    'locat_remark',
                    'locat_map_type',
                    'locat_bd_lbs_key',
                    'locat_bd_lbs_point', // 格式：{"lng":116.404, "lat":39.915, "radius":500}
                    'locat_amap_key',
                    'locat_amap_point', // 格式：{"lng":116.404, "lat":39.915, "radius":500}
                    'locat_tencent_key',
                    'locat_tencent_point', // 格式：{"lng":116.404, "lat":39.915, "radius":500}
                    'manual_remark',
                    'modes',
                    'key',
                ])) {
                    unset($all[$key]);
                }
            }
            if ($all['open'] === 'close') {
                $all['key'] = md5(Base::generatePassword(32));
                $all['face_key'] = md5(Base::generatePassword(32));
            } else {
                $botUser = User::botGetOrCreate('check-in');
                if (!$botUser) {
                    return Base::retError('创建签到机器人失败');
                }
                if (is_array($all['modes'])) {
                    if (in_array('locat', $all['modes'])) {
                        $mapTypes = [
                            'baidu' => ['key' => 'locat_bd_lbs_key', 'point' => 'locat_bd_lbs_point', 'msg' => '请填写百度地图AK'],
                            'amap' => ['key' => 'locat_amap_key', 'point' => 'locat_amap_point', 'msg' => '请填写高德地图Key'],
                            'tencent' => ['key' => 'locat_tencent_key', 'point' => 'locat_tencent_point', 'msg' => '请填写腾讯地图Key'],
                        ];
                        $type = $all['locat_map_type'];
                        if (!isset($mapTypes[$type])) {
                            return Base::retError('请选择地图类型');
                        }
                        $conf = $mapTypes[$type];
                        if (empty($all[$conf['key']])) {
                            return Base::retError($conf['msg']);
                        }
                        if (!is_array($all[$conf['point']])) {
                            return Base::retError('请选择允许签到位置');
                        }
                        $all[$conf['point']]['radius'] = intval($all[$conf['point']]['radius']);
                        $point = $all[$conf['point']];
                        if (empty($point['lng']) || empty($point['lat']) || empty($point['radius'])) {
                            return Base::retError('请选择有效的签到位置');
                        }
                    }
                    // 人脸识别
                    if (in_array('face', $all['modes'])) {
                        Apps::isInstalledThrow('face');
                    }
                }
            }
            if ($all['modes']) {
                $all['modes'] = array_intersect($all['modes'], ['auto', 'manual', 'locat', 'face']);
            }
            // 验证提前和延后时间是否重叠（跨天打卡支持）
            if ($all['open'] === 'open') {
                $times = is_array($all['time']) ? $all['time'] : Base::json2array($all['time']);
                if (count($times) >= 2) {
                    $startMinutes = intval(substr($times[0], 0, 2)) * 60 + intval(substr($times[0], 3, 2));
                    $endMinutes = intval(substr($times[1], 0, 2)) * 60 + intval(substr($times[1], 3, 2));
                    $shiftDuration = $endMinutes - $startMinutes;
                    if ($shiftDuration <= 0) {
                        $shiftDuration += 24 * 60; // 处理跨天班次
                    }
                    $advance = intval($all['advance']) ?: 120;
                    $delay = intval($all['delay']) ?: 120;
                    $maxAllowed = 24 * 60 - $shiftDuration;
                    if ($advance + $delay >= $maxAllowed) {
                        return Base::retError('提前和延后时间设置存在重叠，最大提前+延后时间不能超过 ' . ($maxAllowed - 1) . ' 分钟');
                    }
                }
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
        if (empty($setting['face_key'])) {
            $setting['face_key'] = md5(Base::generatePassword(32));
            Base::setting('checkinSetting', $setting);
        }
        //
        $setting['open'] = $setting['open'] ?: 'close';
        $setting['face_upload'] = $setting['face_upload'] ?: 'close';
        $setting['face_remark'] = $setting['face_remark'] ?: Doo::translate('考勤机');
        $setting['face_retip'] = $setting['face_retip'] ?: 'open';
        $setting['locat_remark'] = $setting['locat_remark'] ?: Doo::translate('定位签到');
        $setting['locat_map_type'] = $setting['locat_map_type'] ?: 'baidu';
        $setting['locat_bd_lbs_point'] = is_array($setting['locat_bd_lbs_point']) ? $setting['locat_bd_lbs_point'] : ['radius' => 500];
        $setting['locat_amap_point'] = is_array($setting['locat_amap_point']) ? $setting['locat_amap_point'] : ['radius' => 500];
        $setting['locat_tencent_point'] = is_array($setting['locat_tencent_point']) ? $setting['locat_tencent_point'] : ['radius' => 500];
        $setting['manual_remark'] = $setting['manual_remark'] ?: Doo::translate('手动签到');
        $setting['time'] = $setting['time'] ? Base::json2array($setting['time']) : ['09:00', '18:00'];
        $setting['advance'] = intval($setting['advance']) ?: 120;
        $setting['delay'] = intval($setting['delay']) ?: 120;
        $setting['remindin'] = intval($setting['remindin']) ?: 5;
        $setting['remindexceed'] = intval($setting['remindexceed']) ?: 10;
        $setting['edit'] = $setting['edit'] ?: 'close';
        $setting['modes'] = is_array($setting['modes']) ? $setting['modes'] : [];
        $setting['cmd'] = "curl -sSL '" . Base::fillUrl("api/public/checkin/install?key={$setting['key']}") . "' | sh";
        if (Base::judgeClientVersion('0.34.67')) {
            $setting['cmd'] = base64_encode($setting['cmd']);
        }
        //
        return Base::retSuccess($type == 'save' ? '保存成功' : 'success', $setting ?: json_decode('{}'));
    }

    /**
     * @api {get} api/system/setting/apppush 获取APP推送设置、保存APP推送设置（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName setting__apppush
     *
     * @apiParam {String} type
     * - get: 获取（默认）
     * - save: 保存设置（参数：['push', 'ios_key', 'ios_secret', 'android_key', 'android_secret']）
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
            if (config('dootask.system_setting') == 'disabled') {
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
        //
        return Base::retSuccess($type == 'save' ? '保存成功' : 'success', $setting ?: json_decode('{}'));
    }

    /**
     * @api {get} api/system/setting/thirdaccess 第三方帐号（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName setting__thirdaccess
     *
     * @apiParam {String} type
     * - get: 获取（默认）
     * - save: 保存设置（参数：['ldap_open', 'ldap_host', 'ldap_port', 'ldap_password', 'ldap_user_dn', 'ldap_base_dn', 'ldap_sync_local']）
     * - testldap: 测试ldap连接
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function setting__thirdaccess()
    {
        User::auth('admin');
        //
        $type = trim(Request::input('type'));
        if ($type == 'testldap') {
            $all = Base::newTrim(Request::input());
            $connection = Container::getDefaultConnection();
            try {
                $connection->setConfiguration([
                    "hosts" => [$all['ldap_host']],
                    "port" => intval($all['ldap_port']),
                    "password" => $all['ldap_password'],
                    "username" => $all['ldap_user_dn'],
                    "base_dn" => $all['ldap_base_dn'],
                ]);
                if ($connection->auth()->attempt($all['ldap_user_dn'], $all['ldap_password'])) {
                    return Base::retSuccess('验证通过');
                } else {
                    return Base::retError('验证失败');
                }
            } catch (LdapRecordException $e) {
                return Base::retError($e->getMessage() ?: "验证失败：未知错误", config("ldap.connections.default"));
            }
        } elseif ($type == 'save') {
            if (config('dootask.system_setting') == 'disabled') {
                return Base::retError('当前环境禁止修改');
            }
            $all = Base::newTrim(Request::input());
            foreach ($all as $key => $value) {
                if (!in_array($key, [
                    'ldap_open',
                    'ldap_host',
                    'ldap_port',
                    'ldap_password',
                    'ldap_user_dn',
                    'ldap_base_dn',
                    'ldap_login_attr',
                    'ldap_sync_local'
                ])) {
                    unset($all[$key]);
                }
            }
            $all['ldap_port'] = intval($all['ldap_port']) ?: 389;
            $setting = Base::setting('thirdAccessSetting', Base::newTrim($all));
        } else {
            $setting = Base::setting('thirdAccessSetting');
        }
        //
        $setting['ldap_open'] = $setting['ldap_open'] ?: 'close';
        $setting['ldap_port'] = intval($setting['ldap_port']) ?: 389;
        $setting['ldap_login_attr'] = $setting['ldap_login_attr'] ?: 'cn';
        $setting['ldap_sync_local'] = $setting['ldap_sync_local'] ?: 'close';
        //
        return Base::retSuccess($type == 'save' ? '保存成功' : 'success', $setting ?: json_decode('{}'));
    }

    /**
     * @api {get} api/system/setting/file 文件设置（限管理员）
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName setting__file
     *
     * @apiParam {String} type
     * - get: 获取（默认）
     * - save: 保存设置（参数：['permission_pack_type', 'permission_pack_userids']）
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function setting__file()
    {
        User::auth('admin');
        //
        $type = trim(Request::input('type'));
        if ($type == 'save') {
            if (config('dootask.system_setting') == 'disabled') {
                return Base::retError('当前环境禁止修改');
            }
            $all = Base::newTrim(Request::input());
            foreach ($all as $key => $value) {
                if (!in_array($key, [
                    'permission_pack_type',
                    'permission_pack_userids'
                ])) {
                    unset($all[$key]);
                }
            }
            $setting = Base::setting('fileSetting', Base::newTrim($all));
        } else {
            $setting = Base::setting('fileSetting');
        }
        //
        return Base::retSuccess($type == 'save' ? '保存成功' : 'success', $setting ?: json_decode('{}'));
    }

    /**
     * @api {get} api/system/demo 获取演示帐号
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
        $demo_account = config('dootask.demo_account');
        $demo_password = config('dootask.demo_password');
        if (empty($demo_account) || empty($demo_password)) {
            return Base::retError('No demo account');
        }
        return Base::retSuccess('success', [
            'account' => $demo_account,
            'password' => $demo_password,
        ]);
    }

    /**
     * @api {post} api/system/priority 任务优先级
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
            $list = Request::input('list');
            if (empty($list) || !is_array($list)) {
                return Base::retError('参数错误');
            }
            $array = Setting::normalizeTaskPriorityList($list);
            if (empty($array)) {
                return Base::retError('参数为空');
            }
            $setting = Base::setting('priority', $array);
        } else {
            $setting = Setting::normalizeTaskPriorityList(Base::setting('priority'));
        }
        //
        return Base::retSuccess($type == 'save' ? '保存成功' : 'success', $setting);
    }

    /**
     * @api {post} api/system/microapp_menu 自定义应用菜单
     *
     * @apiDescription 获取或保存自定义微应用菜单，仅管理员可配置
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName microapp_menu
     *
     * @apiParam {String} type
     * - get: 获取（默认）
     * - save: 保存（限管理员）
     * @apiParam {Array} list   菜单列表，格式：[{id,name,version,menu_items}]
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function microapp_menu()
    {
        $type = trim(Request::input('type'));
        $user = User::auth();
        if ($type == 'save') {
            User::auth('admin');
            $list = Request::input('list');
            if (empty($list) || !is_array($list)) {
                $list = [];
            }
            $apps = Setting::normalizeCustomMicroApps($list);
            $setting = Base::setting('microapp_menu', $apps);
            $setting = Setting::formatCustomMicroAppsForResponse($setting);
        } else {
            $setting = Base::setting('microapp_menu');
            if (!is_array($setting)) {
                $setting = [];
            }
            $setting = Setting::filterCustomMicroAppsForUser($setting, $user);
            $setting = Setting::formatCustomMicroAppsForResponse($setting);
        }
        return Base::retSuccess($type == 'save' ? '保存成功' : 'success', $setting);
    }

    /**
     * @api {post} api/system/column/template 创建项目模板
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
            $list = Request::input('list');
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
        return Base::retSuccess($type == 'save' ? '保存成功' : 'success', $setting);
    }

    /**
     * @api {post} api/system/license License
     *
     * @apiDescription 获取License信息、保存License（限管理员）
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName license
     *
     * @apiParam {String} type
     * - get: 获取
     * - save: 保存
     * @apiParam {String} license   License 原文
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function license()
    {
        User::auth('admin');
        //
        $type = trim(Request::input('type'));
        if ($type == 'save') {
            $license = Request::input('license');
            // 解密失败（sn 为空）视为无效 license
            $decoded = Doo::licenseDecode($license);
            if ((string)($decoded['sn'] ?? '') === '') {
                return Base::retError('LICENSE 格式错误');
            }
            if ($err = Doo::licenseBindingError($decoded)) {
                return Base::retError($err);
            }
            Doo::licenseSave($license);
            // 离线/在线互斥：保存离线 license 即退出在线模式（尽力释放座位+清在线标志，不删除刚写入的文件）
            OnlineLicense::switchToOffline();
        }
        //
        $data = [
            'license' => Doo::licenseContent(),
            'info' => Doo::license(),
            'macs' => Doo::macs(),
            'doo_sn' => Doo::dooSN(),
            'doo_version' => Doo::dooVersion(),
            'user_count' => User::whereBot(0)->whereNull('disable_at')->count(),
            'error' => []
        ];
        if ($data['info']['people'] == 0 || $data['info']['people'] > 3) {
            // 付费档才检查 SN/MAC
            if ($data['info']['sn'] != $data['doo_sn']) {
                $data['error'][] = '终端SN与License不匹配';
            }
            if ($data['info']['mac'] && $data['macs']) {
                $approved = false;
                foreach ($data['info']['mac'] as $mac) {
                    if (in_array($mac, $data['macs'])) {
                        $approved = true;
                        break;
                    }
                }
                if (!$approved) {
                    $data['error'][] = '终端MAC与License不匹配';
                }
            }
        }
        if ($data['info']['people'] > 0 && $data['user_count'] > $data['info']['people']) {
            $data['error'][] = '终端用户数超过License限制';
        }
        if ($data['info']['expired_at'] && strtotime($data['info']['expired_at']) <= Timer::time()) {
            $data['error'][] = '终端License已过期';
        }
        // 在线授权：把状态机提醒并入 error[]（dashboard 警告条与本页错误展示自动复用），并附在线状态
        foreach (OnlineLicense::stageMessages() as $msg) {
            $data['error'][] = $msg;
        }
        $data['online'] = OnlineLicense::status();
        //
        if ($type === 'error') {
            $data = [
                'error' => $data['error']
            ];
        }
        //
        return Base::retSuccess($type == 'save' ? '保存成功' : 'success', $data ?: json_decode('{}'));
    }

    /**
     * @api {get} api/system/get/info 获取终端详细信息
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
        if (Request::input("key") !== config('app.key')) {
            return [];
        }
        return Base::retSuccess('success', [
            'ip' => Base::getIp(),
            'ip-iscn' => Base::isCnIp(Base::getIp()),
            'header' => Request::header(),
            'token' => Doo::userToken(),
            'url' => url('') . Base::getUrl(),
        ]);
    }

    /**
     * @api {get} api/system/get/ip 获取IP地址
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
     * @api {get} api/system/get/cnip 是否中国IP地址
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
     * @api {post} api/system/imgupload 上传图片
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName imgupload
     *
     * @apiParam {File} image               post-图片对象
     * @apiParam {String} [image64]         post-图片base64（与'image'二选一）
     * @apiParam {String} [filename]        post-文件名
     * @apiParam {Number} [width]           压缩图片宽（默认0）
     * @apiParam {Number} [height]          压缩图片高（默认0）
     * @apiParam {String} [whcut]           压缩方式（等比缩放）
     * - cover：完全覆盖容器，可能图片部分不可见（width、height必须大于0）
     * - contain：完全装入容器，可能容器部分显示空白（width、height必须大于0）
     * - percentage：完全装入容器，可能容器有一边尺寸不足（默认，假如：width=200、height=0，则宽度最大不超过200、高度自动）
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
        $whcut = Request::input('whcut');
        $whcut = match (strval($whcut)) {
            '1' => 'cover',
            '0' => 'contain',
            'cover',
            'contain' => $whcut,
            default => 'percentage',
        };
        $scale = [$width ?: 2160, $height ?: 4160, $whcut];
        $path = "uploads/user/picture/" . User::userid() . "/" . date("Ym") . "/";
        $image64 = trim(Request::input('image64'));
        $fileName = trim(Request::input('filename'));
        if ($image64) {
            $data = Base::image64save([
                "image64" => $image64,
                "path" => $path,
                "fileName" => $fileName,
                "scale" => $scale,
                "quality" => true
            ]);
        } else {
            $data = Base::upload([
                "file" => Request::file('image'),
                "type" => 'image',
                "path" => $path,
                "fileName" => $fileName,
                "scale" => $scale,
                "quality" => true
            ]);
        }
        if (Base::isError($data)) {
            return Base::retError($data['msg']);
        } else {
            return Base::retSuccess('success', $data['data']);
        }
    }

    /**
     * @api {get} api/system/get/imgview 浏览图片空间
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
                    'inode' => filemtime($v),
                ];
            } elseif (!Base::isThumb($filename)) {
                $array = [
                    'type' => 'file',
                    'title' => $filename,
                    'path' => $pathTemp,
                    'url' => Base::fillUrl($pathTemp),
                    'thumb' => $pathTemp,
                    'inode' => filemtime($v),
                ];
                //
                $extension = pathinfo($dirPath . $filename, PATHINFO_EXTENSION);
                if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp'])) {
                    if ($extension = Base::getThumbExt($dirPath . $filename)) {
                        $array['thumb'] .= "_thumb.{$extension}";
                    } else {
                        $array['thumb'] = Base::fillUrl($array['thumb']);
                    }
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
     * @api {post} api/system/fileupload 上传文件
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName fileupload
     *
     * @apiParam {File} files               文件名
     * @apiParam {String} [image64]         图片base64（与'files'二选一）
     * @apiParam {String} [filename]        文件名
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
        $image64 = trim(Request::input('image64'));
        $fileName = trim(Request::input('filename'));
        if ($image64) {
            $data = Base::image64save([
                "image64" => $image64,
                "path" => $path,
                "fileName" => $fileName,
                "quality" => true
            ]);
        } else {
            $data = Base::upload([
                "file" => Request::file('files'),
                "type" => 'file',
                "path" => $path,
                "fileName" => $fileName,
                "quality" => true
            ]);
        }
        //
        return $data;
    }

    /**
     * @api {get} api/system/get/updatelog 获取更新日志
     *
     * @apiDescription 获取更新日志
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName get__updatelog
     *
     * @apiParam {Number} [take]        获取数量：10-100（留空默认：50）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function get__updatelog()
    {
        $take = min(100, max(10, intval(Request::input('take', 50))));
        $logPath = base_path('CHANGELOG.md');
        $logVersion = "";
        $logContent = "";
        $logResults = [];
        if (file_exists($logPath)) {
            $content = file_get_contents($logPath);
            $sections = preg_split("/## \[(.*?)\]/", $content, -1, PREG_SPLIT_DELIM_CAPTURE);
            for ($i = 1; $i < count($sections) && count($logResults) < $take; $i += 2) {
                $logResults[] = [
                    'title' => $sections[$i],
                    'content' => $sections[$i + 1]
                ];
            }
        }
        if ($logResults) {
            $logVersion = $logResults[0]['title'];
            $logContent = implode("\n", array_map(function($item) {
                return "## {$item['title']}" . $item['content'];
            }, $logResults));
        }
        return Base::retSuccess('success', [
            'logVersion' => $logVersion,
            'updateLog' => $logContent,
        ]);
    }

    /**
     * @api {get} api/system/email/check 邮件发送测试（限管理员）
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
                $mailer = new Mailer(Transport::fromDsn("smtp://{$all['account']}:{$all['password']}@{$all['smtp_server']}:{$all['port']}?verify_peer=0"));
                $mailer->send((new Email())
                    ->from(Base::settingFind('system', 'system_alias', 'Task') . " <{$all['account']}>")
                    ->to($to)
                    ->subject('Mail sending test')
                    ->html('<p>' . Doo::translate('收到此电子邮件意味着您的邮箱配置正确。') . '</p>'));
            }, function () {
                throw new \Exception("收件人地址错误或已被忽略");
            });
            return Base::retSuccess('成功发送');
        } catch (\Throwable $e) {
            // 一般是请求超时
            if (stripos($e->getMessage(), "timed out") !== false) {
                return Base::retError("邮件发送超时，请检查邮箱配置是否正确");
            } elseif ($e->getCode() === 550) {
                return Base::retError('邮件内容被拒绝，请检查邮箱是否开启接收功能');
            } else {
                return Base::retError($e->getMessage());
            }
        }
    }

    /**
     * @api {get} api/system/checkin/export 导出签到数据（限管理员）
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
        $user = User::auth('admin');
        //
        $setting = Base::setting('checkinSetting');
        if ($setting['open'] !== 'open') {
            return Base::retError('此功能未开启，请前往系统设置开启');
        }
        //
        $userid = Base::arrayRetainInt(Request::input('userid'), true);
        $date = Request::input('date');
        $time = Request::input('time');
        //
        if (empty($userid) || empty($date) || empty($time)) {
            return Base::retError('参数错误');
        }
        if (count($userid) > 100) {
            return Base::retError('导出成员限制最多100个');
        }
        if (!(is_array($date) && Timer::isDate($date[0]) && Timer::isDate($date[1]))) {
            return Base::retError('日期选择错误');
        }
        if (Carbon::parse($date[1])->timestamp - Carbon::parse($date[0])->timestamp > 35 * 86400) {
            return Base::retError('日期范围限制最大35天');
        }
        if (!(is_array($time) && Timer::isTime($time[0]) && Timer::isTime($time[1]))) {
            return Base::retError('时间选择错误');
        }
        //
        $secondStart = strtotime("2000-01-01 {$time[0]}") - strtotime("2000-01-01 00:00:00");
        $secondEnd = strtotime("2000-01-01 {$time[1]}") - strtotime("2000-01-01 00:00:00");
        // 获取延后时间配置（用于跨天打卡导出）
        $delaySeconds = (intval($setting['delay']) ?: 120) * 60;
        //
        $botUser = User::botGetOrCreate('system-msg');
        if (empty($botUser)) {
            return Base::retError('系统机器人不存在');
        }
        $dialog = WebSocketDialog::checkUserDialog($botUser, $user->userid);
        //
        $doo = Doo::load();
        go(function () use ($doo, $secondStart, $secondEnd, $time, $userid, $date, $user, $botUser, $dialog, $delaySeconds) {
            Coroutine::sleep(1);
            //
            $headings = [];
            $headings[] = $doo->translate('签到人');
            $headings[] = $doo->translate('签到日期');
            $headings[] = $doo->translate('班次时间');
            $headings[] = $doo->translate('首次签到时间');
            $headings[] = $doo->translate('首次签到结果');
            $headings[] = $doo->translate('最后签到时间');
            $headings[] = $doo->translate('最后签到结果');
            $headings[] = $doo->translate('参数数据');
            //
            $content = [];
            $content[] = [
                'content' => '导出签到数据已完成',
                'style' => 'font-weight: bold;padding-bottom: 4px;',
            ];
            //
            $sheets = [];
            $startD = Carbon::parse($date[0])->startOfDay();
            $endD = Carbon::parse($date[1])->endOfDay();
            $users = User::whereIn('userid', $userid)->take(100)->get();
            /** @var User $user */
            foreach ($users as $user) {
                $recordTimes = UserCheckinRecord::getTimes($user->userid, [$startD, $endD]);
                //
                $nickname = Base::filterEmoji($user->nickname);
                $styles = ["A1:H1" => ["font" => ["bold" => true]]];
                $datas = [];
                $startT = $startD->timestamp;
                $endT = $endD->timestamp;
                $index = 1;
                while ($startT < $endT) {
                    $index++;
                    $sameDate = date("Y-m-d", $startT);
                    $sameTimes = $recordTimes[$sameDate] ?? [];
                    $sameCollect = UserCheckinRecord::atCollect($sameDate, $sameTimes, $time[0]);
                    $firstBetween = [Carbon::createFromTimestamp($startT), Carbon::createFromTimestamp($startT + $secondEnd - 1)];
                    // 扩展下班打卡范围以支持跨天打卡
                    $lastBetween = [Carbon::createFromTimestamp($startT + $secondStart + 1), Carbon::createFromTimestamp($startT + 86400 + $delaySeconds)];
                    $firstRecord = $sameCollect?->whereBetween("datetime", $firstBetween)->first();
                    $lastRecord = $sameCollect?->whereBetween("datetime", $lastBetween)->last();
                    $firstTimestamp = $firstRecord['timestamp'] ?: 0;
                    $lastTimestamp = $lastRecord['timestamp'] ?: 0;
                    if (Timer::time() < $startT + $secondStart) {
                        $firstResult = "-";
                    } else {
                        $firstResult = $doo->translate("正常");
                        if (empty($firstTimestamp)) {
                            $firstResult = $doo->translate("缺卡");
                            $styles["E{$index}"] = ["font" => ["color" => ["rgb" => "ff0000"]]];
                        } elseif ($firstTimestamp > $startT + $secondStart) {
                            $firstResult = $doo->translate("迟到");
                            $styles["E{$index}"] = ["font" => ["color" => ["rgb" => "436FF6"]]];
                        }
                    }
                    if (Timer::time() < $startT + $secondEnd) {
                        $lastResult = "-";
                        $lastTimestamp = 0;
                    } else {
                        $lastResult = $doo->translate("正常");
                        if (empty($lastTimestamp) || $lastTimestamp === $firstTimestamp) {
                            $lastResult = $doo->translate("缺卡");
                            $styles["G{$index}"] = ["font" => ["color" => ["rgb" => "ff0000"]]];
                        } elseif ($lastTimestamp < $startT + $secondEnd) {
                            $lastResult = $doo->translate("早退");
                            $styles["G{$index}"] = ["font" => ["color" => ["rgb" => "436FF6"]]];
                        }
                    }
                    $firstTimestamp = $firstTimestamp ? date("H:i", $firstTimestamp) : "-";
                    $lastTimestamp = $lastTimestamp ? date("H:i", $lastTimestamp) : "-";
                    $section = array_map(function($item) {
                        return $item[0] . "-" . ($item[1] ?: "None");
                    }, UserCheckinRecord::atSection($sameTimes));
                    $datas[] = [
                        "{$nickname} (ID: {$user->userid})",
                        $sameDate,
                        implode("-", $time),
                        $firstTimestamp,
                        $firstResult,
                        $lastTimestamp,
                        $lastResult,
                        implode(", ", $section),
                    ];
                    $startT += 86400;
                }
                $title = (count($sheets) + 1) . "." . ($nickname ?: $user->userid);
                $sheets[] = BillExport::create()->setTitle($title)->setHeadings($headings)->setData($datas)->setStyles($styles);
            }
            if (empty($sheets)) {
                $content[] = [
                    'content' => '没有任何数据',
                    'style' => 'color: #ff0000;',
                ];
                WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
                    'type' => 'content',
                    'title' => $content[0]['content'],
                    'content' => $content,
                ], $botUser->userid, true, false, true);
                return;
            }
            //
            $fileName = $users[0]->nickname;
            if (count($users) > 1) {
                $fileName .= "等" . count($userid) . "位成员的签到记录";
            } else {
                $fileName .= '的签到记录';
            }
            $fileName = $doo->translate($fileName) . '_' . Timer::time() . '.xlsx';
            $filePath = "temp/checkin/export/" . date("Ym", Timer::time());
            $export = new BillMultipleExport($sheets);
            $res = $export->store($filePath . "/" . $fileName);
            if ($res != 1) {
                $content[] = [
                    'content' => "导出失败，{$fileName}！",
                    'style' => 'color: #ff0000;',
                ];
                WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
                    'type' => 'content',
                    'title' => $content[0]['content'],
                    'content' => $content,
                ], $botUser->userid, true, false, true);
                return;
            }
            $xlsPath = storage_path("app/" . $filePath . "/" . $fileName);
            $zipFile = "app/" . $filePath . "/" . Base::rightDelete($fileName, '.xlsx') . ".zip";
            $zipPath = storage_path($zipFile);
            if (file_exists($zipPath)) {
                Base::deleteDirAndFile($zipPath, true);
            }
            try {
                Base::zipAddFiles($zipPath, $xlsPath);
            } catch (\Throwable) {
            }
            //
            if (file_exists($zipPath)) {
                $key = Down::cache_encode([
                    'file' => $zipFile,
                ]);
                $fileUrl = Base::fillUrl('api/system/checkin/down?key=' . $key);
                WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
                    'type' => 'file_download',
                    'title' => '导出签到数据已完成',
                    'name' => $fileName,
                    'size' => filesize($zipPath),
                    'url' => $fileUrl,
                ], $botUser->userid, true, false, true);
            } else {
                $content[] = [
                    'content' => "打包失败，请稍后再试...",
                    'style' => 'color: #ff0000;',
                ];
                WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
                    'type' => 'content',
                    'title' => $content[0]['content'],
                    'content' => $content,
                ], $botUser->userid, true, false, true);
            }
        });
        //
        WebSocketDialogMsg::sendMsg(null, $dialog->id, 'template', [
            'type' => 'content',
            'content' => '正在导出签到数据，请稍等...',
        ], $botUser->userid, true, false, true);
        //
        return Base::retSuccess('success');
    }

    /**
     * @api {get} api/system/checkin/down 下载导出的签到数据
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
        $array = Down::cache_decode();
        $file = $array['file'];
        if (empty($file) || !file_exists(storage_path($file))) {
            return Base::ajaxError("文件不存在！", [], 0, 403);
        }
        return Response::download(storage_path($file));
    }

    /**
     * @api {get} api/system/version 获取版本号
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName version
     *
     * @apiSuccessExample {json} Success-Response:
    {
        "device_count": 3,  // 设备数量
        "version": "0.0.1", // 服务端版本号
        "publish": {
            "provider": "generic",
            "url": ""
        }
    }
    // 如果header请求中存在version字段，则返回数据包裹在 {ret:1,data:{},msg:"success"} 中
     */
    public function version()
    {
        $package = Base::getPackage();
        $array = [
            'device_count' => 0,
            'version' => Base::getVersion(),
            'publish' => [],
        ];
        if (Doo::userId()) {
            $array['device_count'] = UserDevice::whereUserid(Doo::userId())->count();
        }
        if (is_array($package['app'])) {
            $i = 0;
            $url = url('');
            foreach ($package['app'] as $item) {
                $urls = $item['urls'] && is_array($item['urls']) ? $item['urls'] : $item['url'];
                if (is_array($item['publish']) && ($i === 0 || Base::hostContrast($url, $urls))) {
                    $array['publish'] = $item['publish'];
                }
                $i++;
            }
        }
        if (Request::hasHeader('version')) {
            return Base::retSuccess('success', $array);
        }
        return $array;
    }

    /**
     * @api {get} api/system/prefetch 预加载的资源
     *
     * @apiVersion 1.0.0
     * @apiGroup system
     * @apiName prefetch
     *
     * @apiSuccessExample {array} Success-Response:
    [
        "https://......",
        "https://......",
        "......",
    ]
     */
    public function prefetch()
    {
        $userAgent = strtolower(Request::server('HTTP_USER_AGENT'));
        $isMain = str_contains($userAgent, 'maintaskwindow');
        $isApp = str_contains($userAgent, 'kuaifan_eeui');
        $version = Base::getVersion();
        $array = [];

        if ($isMain || $isApp) {
            $path = 'js/build/';
            $list = Base::recursiveFiles(public_path($path), false);
            foreach ($list as $item) {
                if (is_file($item) && filesize($item) > 50 * 1024) { // 50KB
                    $array[] = $path . basename($item);
                }
            }
        }

        if ($isMain) {
            $file = base_path('.prefetch');
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $items = explode("\n", $content);
                $array = array_merge($array, $items);
            }
            // 添加office资源
            $officePath = '';
            if (Apps::isInstalled('office')) {
                $officeApi = 'http://office/web-apps/apps/api/documents/api.js';
                $content = @file_get_contents($officeApi);
                if ($content) {
                    if (preg_match("/const\s+ver\s*=\s*'\/*([^']+)'/", $content, $matches)) {
                        $officePath = $matches[1];
                    }
                }
            }
            if ($officePath) {
                $array = array_map(function($item) use ($officePath) {
                    if (str_starts_with($item, 'office/{path}/')) {
                        return preg_replace("/office\/{path}\//", '/office/' . $officePath . '/', $item);
                    }
                    return $item;
                }, $array);
            } else {
                $array = array_filter($array, function($item) {
                    return !str_starts_with($item, 'office/{path}/');
                });
            }
            // 添加OKR资源
            if (Apps::isInstalled('okr')) {
                $okrContent = @file_get_contents("http://nginx/apps/okr/");
                preg_match_all('/<script[^>]*src=["\']([^"\']+)["\'][^>]*>/i', $okrContent, $scriptMatches);
                foreach ($scriptMatches[1] as $src) {
                    $array[] = $src;
                }
                preg_match_all('/<link[^>]*rel=["\']stylesheet["\'][^>]*href=["\']([^"\']+)["\'][^>]*>/i', $okrContent, $linkMatches);
                foreach ($linkMatches[1] as $href) {
                    $array[] = $href;
                }
            }
        }

        return array_map(function($item) use ($version) {
            $url = trim($item);
            $url = str_replace('{version}', $version, $url);
            return url($url);
        }, array_values(array_filter($array)));
    }
}
