<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Module\Base;
use App\Module\Doo;
use App\Module\Timer;
use Carbon\Carbon;
use Guanguans\Notify\Factory;
use Guanguans\Notify\Messages\EmailMessage;

/**
 * App\Models\UserEmailVerification
 *
 * @property int $id
 * @property int|null $userid 用户id
 * @property string|null $code 验证参数
 * @property string|null $email 电子邮箱
 * @property int|null $status 0-未验证，1-已验证
 * @property int|null $type 邮件类型：1-邮箱认证，2-修改邮箱
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|UserEmailVerification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserEmailVerification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserEmailVerification query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|UserEmailVerification whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserEmailVerification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserEmailVerification whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserEmailVerification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserEmailVerification whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserEmailVerification whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserEmailVerification whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserEmailVerification whereUserid($value)
 * @mixin \Eloquent
 */
class UserEmailVerification extends AbstractModel
{

    /**
     * 发验证邮箱
     * @param User $user
     * @param int $type
     * @param null $email
     */
    public static function userEmailSend(User $user, $type = 1, $email = null)
    {
        $email = $type == 1 ? $user->email : $email;
        $res = self::whereEmail($email)->where('created_at', '>', Carbon::now()->subMinutes(30))->whereType($type)->first();
        if ($res && $type == 1) return;
        //删除
        self::whereUserid($email)->delete();
        $code = $type == 1 ? Base::generatePassword(64) : rand(100000, 999999);
        $row = self::createInstance([
            'userid' => $user->userid,
            'email' => $email,
            'code' => $code,
            'status' => 0,
            'type' => $type
        ]);
        $row->save();
        $setting = Base::setting('emailSetting');
        $alias = Base::settingFind('system', 'system_alias', 'Task');
        try {
            if (!Base::isEmail($email)) {
                throw new \Exception("User email '{$email}' address error");
            }
            switch ($type) {
                case 2:
                    $subject = Doo::translate($alias . "修改邮箱验证");
                    $content = sprintf("<p>%s</p><p style='color: #0000DD;'><u>%s</u></p><p>%s</p>",
                        Doo::translate($user->nickname . " 您好，您正在修改 " . $alias . " 的邮箱，验证码如下。请在30分钟内输入验证码"),
                        $code,
                        Doo::translate("如果不是本人操作，您的帐号可能存在风险，请及时修改密码！")
                    );
                    break;
                case 3:
                    $subject = Doo::translate($alias . "注销帐号验证");
                    $content = sprintf("<p>%s</p><p style='color: #0000DD;'><u>%s</u></p><p>%s</p>",
                        Doo::translate($user->nickname . " 您好，您正在注销 " . $alias . " 的帐号，验证码如下。请在30分钟内输入验证码"),
                        $code,
                        Doo::translate("如果不是本人操作，您的帐号可能存在风险，请及时修改密码！")
                    );
                    break;
                default:
                    $url = Base::fillUrl('single/valid/email') . '?code=' . $row->code;
                    $subject = Doo::translate($alias . "绑定邮箱验证");
                    $content = sprintf("<p>%s</p><p style='display: flex; justify-content: center;'>%s</p>",
                        Doo::translate($user->nickname . " 您好，您正在绑定 " . $alias . " 的邮箱，请于30分钟之内点击以下链接完成验证:"),
                        "<a href='{$url}' target='_blank'>{$url}</a>"
                    );
                    break;
            }
            Factory::mailer()
                ->setDsn("smtp://{$setting['account']}:{$setting['password']}@{$setting['smtp_server']}:{$setting['port']}?verify_peer=0")
                ->setMessage(EmailMessage::create()
                    ->from($alias . " <{$setting['account']}>")
                    ->to($email)
                    ->subject($subject)
                    ->html($content))
                ->send();
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), "Timed Out")) {
                throw new ApiException("邮件发送超时，请检查邮箱配置是否正确");
            } elseif ($e->getCode() === 550) {
                throw new ApiException('邮件内容被拒绝，请检查邮箱是否开启接收功能');
            } else {
                throw new ApiException($e->getMessage());
            }
        }
    }

    /**
     * 校验验证码
     * @param $email
     * @param $code
     * @param int $type
     * @return bool
     */
    public static function verify($email, $code, $type = 1)
    {
        if (!$code) {
            throw new ApiException('请输入验证码');
        }
        /** @var UserEmailVerification $emailVerify */
        $emailVerify = self::whereEmail($email)->whereType($type)->orderByDesc('id')->first();

        if (empty($emailVerify) || $emailVerify->code != $code) {
            throw new ApiException('验证码错误');
        }

        $oldTime = Carbon::parse($emailVerify->created_at)->timestamp;
        $time = Timer::Time();

        // 30分钟失效
        if (abs($time - $oldTime) > 1800) {
            throw new ApiException('验证码已失效');
        }

        self::whereEmail($email)->whereCode($code)->whereType($type)->update([
            'status' => 1
        ]);

        return true;
    }

}
