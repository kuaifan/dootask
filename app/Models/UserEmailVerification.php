<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Module\Base;
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
        $res = self::whereUserid($user->userid)->where('created_at', '>', Carbon::now()->subMinutes(30))->first();
        if ($res) return;
        //删除
        self::whereUserid($user->userid)->delete();
        $userEmailVerification = self::createInstance([
            'userid' => $user->userid,
            'email' => $user->email,
            'code' => Base::generatePassword(64),
            'status' => 0,
        ]);
        $userEmailVerification->save();

        $setting = Base::setting('emailSetting');
        $url = Base::fillUrl('single/valid/email') . '?code=' . $userEmailVerification->code;
        try {
            if (!Base::isEmail($user->email)) {
                throw new \Exception("User email '{$user->email}' address error");
            }
            $subject = env('APP_NAME') . " 绑定邮箱验证";
            $content = "<p>{$user->nickname} 您好，您正在绑定 " . env('APP_NAME') . " 的邮箱，请于30分钟之内点击以下链接完成验证 :</p><p style='display: flex; justify-content: center;'><a href='{$url}' target='_blank'>{$url}</a></p>";
            Factory::mailer()
                ->setDsn("smtp://{$setting['account']}:{$setting['password']}@{$setting['smtp_server']}:{$setting['port']}?verify_peer=0")
                ->setMessage(EmailMessage::create()
                    ->from(env('APP_NAME', 'Task') . " <{$setting['account']}>")
                    ->to($user->email)
                    ->subject($subject)
                    ->html($content))
                ->send();
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), "Timed Out")) {
                throw new ApiException("language.TimedOut");
            } elseif ($e->getCode() === 550) {
                throw new ApiException('邮件内容被拒绝，请检查邮箱是否开启接收功能');
            } else {
                throw new ApiException($e->getMessage());
            }
        }
    }
}
