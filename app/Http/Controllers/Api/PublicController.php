<?php

namespace App\Http\Controllers\Api;


use App\Exceptions\ApiException;
use App\Models\User;
use App\Module\Base;
use Carbon\Carbon;
use Request;

/**
 * @apiDefine public
 *
 * 公开
 */
class PublicController extends AbstractController
{
    const appid = "10001";
    const appkey = "TWVCVBJSiCjAFOPpFVdkpQCMWDw66EUY";

    /**
     * 验证签名
     * @return void
     */
    private function _sign()
    {
        $query = Request::query();
        $query['sign'] = Request::header('sign') ?: Request::input('sign');
        // 检查必要参数
        if ($query['appid'] != self::appid) {
            throw new ApiException('appid is error');
        }
        foreach (['appid', 'ver', 'ts', 'nonce'] as $key) {
            if (!isset($query[$key])) {
                throw new ApiException($key . ' parameter is empty');
            }
        }
        if (intval($query['ts']) + 300 < time()) {
            throw new ApiException('ts expired');
        }
        // 验证签名
        ksort($query);
        $string = "";
        foreach ($query as $k => $v) {
            if ($v != '' && $k != 'sign') {
                $string .= $k . "=" . $v . "&";
            }
        }
        $sign = md5($string . self::appkey);
        if ($sign != $query['sign']) {
            throw new ApiException('sign is error');
        }
    }

    /**
     * @api {get} api/public/attendance/portraitlist          01. 【考勤】人员头像数据
     *
     * @apiDescription 需要签名
     * @apiVersion 1.0.0
     * @apiGroup public
     * @apiName attendance__portraitlist
     *
     * @apiParam {String} last_at   最后获取时间（格式示例：2022-01-01 12:50:01）
     *
     * @apiParam {String} appid     唯一身份ID，跟签名appkey配合使用
     * @apiParam {String} ver       版本号，如：1.0
     * @apiParam {Number} ts        10位数时间戳（有效时间300秒）
     * @apiParam {String} nonce     随机字符串
     * @apiParam {String} sign      签名字符串=md5(query_key1=query_val1&query_key2=query_val2...&appkey)
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function attendance__portraitlist()
    {
        $this->_sign();
        //
        $last_at = Request::input('last_at');
        //
        $builder = User::where('userimg', '!=', '')->whereNull('disable_at');
        if (strtotime($last_at)) {
            $builder->where('updated_at', '>', Carbon::parse($last_at));
        }
        $list = $builder->orderBy('updated_at')->take(50)->get();
        //
        $array = [];
        foreach ($list as $item) {
            $array[] = [
                'userid' => $item->userid,
                'userimg' => $item->userimg,
                'updated_at' => $item->updated_at,
            ];
        }
        //
        return Base::retSuccess('success', $array);
    }

    /**
     * @api {get} api/public/attendance/update          02. 【考勤】上报考勤数据
     *
     * @apiDescription 需要签名
     * @apiVersion 1.0.0
     * @apiGroup public
     * @apiName attendance__update
     *
     * @apiParam {Number} userid    会员ID
     * @apiParam {Number} time      时间数据（10位数时间戳）
     *
     * @apiParam {String} appid     唯一身份ID，跟签名appkey配合使用
     * @apiParam {String} ver       版本号，如：1.0
     * @apiParam {Number} ts        10位数时间戳（有效时间300秒）
     * @apiParam {String} nonce     随机字符串
     * @apiParam {String} sign      签名字符串=md5(query_key1=query_val1&query_key2=query_val2...&appkey)
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function attendance__update()
    {
        $this->_sign();
        //
        $userid = intval(Request::input('userid'));
        $time = intval(Request::input('time'));
        //
        $user = User::whereUserid($userid)->first();
        if (empty($user)) {
            return Base::retError('user not exist');
        }
        // todo 保存到考勤数据库
        info([
            'userid' => $user->userid,
            'input' => Request::input(),
            'time' => $time,
            'at' => Carbon::now()->toDateTimeString(),
        ]);
        //
        return Base::retSuccess('success');
    }
}
