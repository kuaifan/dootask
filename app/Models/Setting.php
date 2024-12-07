<?php

namespace App\Models;

use App\Module\Base;
use App\Module\Timer;

/**
 * App\Models\Setting
 *
 * @property int $id
 * @property string|null $name
 * @property string|null $desc 参数描述、备注
 * @property array $setting
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
     * 格式化设置参数
     * @param $value
     * @return array
     */
    public function getSettingAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        $value = Base::json2array($value);
        switch ($this->name) {
            case 'system':
                $value['system_alias'] = $value['system_alias'] ?: env('APP_NAME');
                $value['image_compress'] = $value['image_compress'] ?: 'open';
                $value['image_quality'] = min(100, max(0, intval($value['image_quality']) ?: 90));
                $value['image_save_local'] = $value['image_save_local'] ?: 'open';
                if (!is_array($value['task_default_time']) || count($value['task_default_time']) != 2 || !Timer::isTime($value['task_default_time'][0]) || !Timer::isTime($value['task_default_time'][1])) {
                    $value['task_default_time'] = ['09:00', '18:00'];
                }
                break;

            case 'fileSetting':
                $value['permission_pack_type'] = $value['permission_pack_type'] ?: 'all';
                $value['permission_pack_userids'] = is_array($value['permission_pack_userids']) ? $value['permission_pack_userids'] : [];
                break;

            case 'aibotSetting':
                if ($value['claude_token'] && empty($value['claude_key'])) {
                    $value['claude_key'] = $value['claude_token'];
                }
                $array = [];
                $aiList = ['openai', 'claude', 'gemini', 'zhipu', 'qianwen', 'wenxin'];
                $fieldList = ['key', 'model', 'agency', 'system', 'secret'];
                foreach ($aiList as $aiName) {
                    foreach ($fieldList as $fieldName) {
                        $key = $aiName . '_' . $fieldName;
                        $array[$key] = $value[$key] ?: match ($key) {
                            'openai_model' => 'gpt-4o-mini',
                            'claude_model' => 'claude-3-5-sonnet-latest',
                            'gemini_model' => 'gemini-1.5-flash',
                            'zhipu_model' => 'glm-4',
                            'qianwen_model' => 'qwen-turbo',
                            'wenxin_model' => 'ernie-4.0-8k',
                            default => '',
                        };
                    }
                }
                $value = $array;
                break;
        }
        return $value;
    }

    /**
     * 是否开启AI
     * @param $ai
     * @return bool
     */
    public static function AIOpen($ai = 'openai')
    {
        $array = Base::setting('aibotSetting');
        return !!$array[$ai . '_key'];
    }

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
