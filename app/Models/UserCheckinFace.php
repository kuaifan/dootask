<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Module\Base;
use App\Module\Ihttp;

/**
 * App\Models\UserCheckinFace
 *
 * @property int $id
 * @property int|null $userid 会员id
 * @property string|null $faceimg 人脸图片
 * @property int|null $status 状态
 * @property string|null $remark 备注
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinFace newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinFace newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinFace query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinFace whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinFace whereFaceimg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinFace whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinFace whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinFace whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinFace whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCheckinFace whereUserid($value)
 * @mixin \Eloquent
 */
class UserCheckinFace extends AbstractModel
{

    public static function saveFace($userid, $nickname, $faceimg, $remark='')
    {
        // 取上传图片的URL
        $faceimg = Base::unFillUrl($faceimg);
        $record = "";
        if ($faceimg != '') {
            $faceFile = public_path($faceimg);
            $record = base64_encode(file_get_contents($faceFile));
        }
       
        $url = 'http://' . env('APP_IPPR') . '.55' . ":7788/user";
        $data = [
            'name' => $nickname,
            'enrollid' => $userid,
            'admin' => 0,
            'backupnum' => 50,
        ];
        if ($record != '') {
            $data['record'] = $record;
        }
        
        $res = Ihttp::ihttp_post($url, json_encode($data));
        if($res['data'] && $data = json_decode($res['data'])){
            if($data->ret != 1 && $data->msg){
                return Base::retError($data->msg);
            }
        }
        
        
        return AbstractModel::transaction(function() use ($userid, $faceimg, $remark) {
            // self::updateInsert([
            //     'userid' => $userid,
            //     'faceimg' => $faceimg,
            //     'status' => 1,
            //     'remark' => $remark
            // ]);
            $checkinFace = self::query()->whereUserid($userid)->first();
            if ($checkinFace) {
                self::updateData(['id' => $checkinFace->id], [
                    'faceimg' => $faceimg,
                    'status' => 1,
                    'remark' => $remark
                ]);
            } else {
                $checkinFace = new UserCheckinFace();
                $checkinFace->faceimg = $faceimg;
                $checkinFace->userid = $userid;
                $checkinFace->remark = $remark;
                $checkinFace->save();
            }
            if ($faceimg == '') {
                $res = UserCheckinFace::deleteDeviceUser($userid);
                if ($res) {
                    return $res;
                }
            }
            return Base::retSuccess('上传成功');
        });
    }

    public static function deleteDeviceUser($userid) {
        $url = 'http://' . env('APP_IPPR') . '.55' . ":7788/user/delete";
        $data = [
            'enrollid' => $userid,
            'backupnum' => 50,
        ];
        
        $res = Ihttp::ihttp_post($url, json_encode($data));
        if($res['data'] && $data = json_decode($res['data'])){
            if($data->ret != 1 && $data->msg){
                return Base::retError($data->msg);
            }
        }
    }
}
