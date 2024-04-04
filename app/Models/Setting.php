<?php

namespace App\Models;

use App\Module\Base;

/**
 * App\Models\Setting
 *
 * @property int $id
 * @property string|null $name
 * @property string|null $desc 参数描述、备注
 * @property string|null $setting
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereSetting($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Setting extends AbstractModel
{
    /**
     * 验证邮箱地址（过滤忽略地址）
     * @param $array
     * @param \Closure $resultClosure
     * @param \Closure|null $emptyClosure
     * @return array|mixed
     */
    public static function validateAddr($array, $resultClosure, $emptyClosure = null)
    {
        if (!is_array($array)) {
            $array = [$array];
        }
        $ignoreAddr = Base::settingFind('emailSetting', 'ignore_addr');
        $ignoreAddr = explode("\n", $ignoreAddr);
        $ignoreArray = ['admin@dootask.com', 'test@dootask.com'];
        foreach ($ignoreAddr as $item) {
            if (Base::isEmail($item)) {
                $ignoreArray[] = trim($item);
            }
        }
        if ($ignoreArray) {
            $array = array_diff($array, $ignoreArray);
        }
        if ($array) {
            if ($resultClosure instanceof \Closure) {
                foreach ($array as $value) {
                    $resultClosure($value);
                }
            }
        } else {
            if ($emptyClosure instanceof \Closure) {
                $emptyClosure();
            }
        }
        return $array;
    }
}
