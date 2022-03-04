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
 * @property int $userid  用户id
 * @property string $code  验证参数
 * @property string $email  电子邮箱
 * @property string $status  0-未验证，1-已验证
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocket query()
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocket whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocket whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocket whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocket whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocket whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocket whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WebSocket whereUserid($value)
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
        $url = Base::fillUrl('single/valid/email') . '?code=' . $info['code'];
        $info['status'] = 0;
        $userEmailVerification = self::createInstance($info);
        $userEmailVerification->save();
        try {
            // 15秒后超时
            Config::set("mail.mailers.smtp.host", Base::settingFind('emailSetting', 'smtp_server') ?: Config::get("mail.mailers.smtp.host"));
            Config::set("mail.mailers.smtp.port", Base::settingFind('emailSetting', 'port') ?: Config::get("mail.mailers.smtp.port"));
            Config::set("mail.mailers.smtp.username", Base::settingFind('emailSetting', 'account') ?: Config::get("mail.mailers.smtp.username"));
            Config::set("mail.mailers.smtp.password",  Base::settingFind('emailSetting', 'password') ?: Config::get("mail.mailers.smtp.password"));
            Mail::send('email', ['url' => $url], function ($m) use ($user) {
                $m->from(Base::settingFind('emailSetting', 'account') ?: Config::get("mail.mailers.smtp.username"), env('APP_NAME'));
                $m->to($user->email);
                $m->subject("绑定邮箱验证");
            });
        } catch (Exception $exception) {
            // 一般是请求超时
            if (strpos($exception->getMessage(), "Timed Out") !== false) {
                throw new ApiException("language.TimedOut");
            } elseif ($exception->getCode() == 550) {
                throw new ApiException('邮件内容被拒绝，请检查邮箱是否开启接收功能');
            } else {
                throw new ApiException($exception->getMessage());
            }
        }
    }
}
