<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Module\Base;
use Carbon\Carbon;
use Config;
use Exception;
use Mail;

/**
 * App\Models\UserEmailVerification
 *
 * @property int $id
 * @property int|null $userid 用户id
 * @property string|null $code 验证参数
 * @property string|null $email 电子邮箱
 * @property int|null $status 0-未验证，1-已验证
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|UserEmailVerification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserEmailVerification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserEmailVerification query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserEmailVerification whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserEmailVerification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserEmailVerification whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserEmailVerification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserEmailVerification whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserEmailVerification whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserEmailVerification whereUserid($value)
 * @mixin \Eloquent
 */
class UserEmailVerification extends AbstractModel
{

    /**
     * 发验证邮箱
     * @param User $user
     */
    public static function userEmailSend(User $user)
    {
        $res = self::where('userid', $user->userid)->where('created_at', '>', Carbon::now()->subMinutes(1440))->first();
        if ($res) return;
        //删除
        self::where('userid', $user->userid)->delete();
        $info['created_at'] = date("Y-m-d H:i:s");
        $info['userid'] = $user->userid;
        $info['email'] = $user->email;
        $info['code'] = md5(uniqid(md5(microtime(true)), true)) . md5($user->userid . md5('lddsgagsgkdiid' . microtime(true)));
        $info['status'] = 0;
        $userEmailVerification = self::createInstance($info);
        $userEmailVerification->save();
        $url = Base::fillUrl('single/valid/email') . '?code=' . $info['code'];
        try {
            // 15秒后超时
            self::initMailConfig();
            Mail::send('email', ['url' => $url], function ($m) use ($user) {
                $m->from(Config::get("mail.mailers.smtp.username"), env('APP_NAME'));
                $m->to($user->email);
                $m->subject("绑定邮箱验证");
            });
        } catch (Exception $exception) {
            // 一般是请求超时
            if (str_contains($exception->getMessage(), "Timed Out")) {
                throw new ApiException("language.TimedOut");
            } elseif ($exception->getCode() == 550) {
                throw new ApiException('邮件内容被拒绝，请检查邮箱是否开启接收功能');
            } else {
                throw new ApiException($exception->getMessage());
            }
        }
    }

    /**
     * 初始化邮箱配置
     * @return void
     */
    public static function initMailConfig()
    {
        $config = Base::setting('emailSetting');
        Config::set("mail.mailers.smtp.host", $config['smtp_server'] ?: Config::get("mail.mailers.smtp.host"));
        Config::set("mail.mailers.smtp.port", $config['port'] ?: Config::get("mail.mailers.smtp.port"));
        Config::set("mail.mailers.smtp.username", $config['account'] ?: Config::get("mail.mailers.smtp.username"));
        Config::set("mail.mailers.smtp.password", $config['password'] ?: Config::get("mail.mailers.smtp.password"));
        Config::set("mail.mailers.smtp.encryption", 'ssl');
    }
}
