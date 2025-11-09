<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Module\Base;
use App\Module\Doo;
use App\Module\Timer;
use App\Module\AI;
use Carbon\Carbon;

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
            // 系统设置
            case 'system':
                $value['system_alias'] = $value['system_alias'] ?: env('APP_NAME');
                $value['image_compress'] = $value['image_compress'] ?: 'open';
                $value['image_quality'] = min(100, max(0, intval($value['image_quality']) ?: 90));
                $value['image_save_local'] = $value['image_save_local'] ?: 'open';
                if (!is_array($value['task_default_time']) || count($value['task_default_time']) != 2 || !Timer::isTime($value['task_default_time'][0]) || !Timer::isTime($value['task_default_time'][1])) {
                    $value['task_default_time'] = ['09:00', '18:00'];
                }
                break;

            // 文件设置
            case 'fileSetting':
                $value['permission_pack_type'] = $value['permission_pack_type'] ?: 'all';
                $value['permission_pack_userids'] = is_array($value['permission_pack_userids']) ? $value['permission_pack_userids'] : [];
                break;

            // AI 机器人设置
            case 'aibotSetting':
                if ($value['claude_token'] && empty($value['claude_key'])) {
                    $value['claude_key'] = $value['claude_token'];
                }
                $array = [];
                $aiList = ['openai', 'claude', 'deepseek', 'gemini', 'grok', 'ollama', 'zhipu', 'qianwen', 'wenxin'];
                $fieldList = ['key', 'secret', 'models', 'model', 'base_url', 'agency', 'temperature', 'system'];
                foreach ($aiList as $aiName) {
                    foreach ($fieldList as $fieldName) {
                        $key = $aiName . '_' . $fieldName;
                        $content = $value[$key] ? trim($value[$key]) : '';
                        switch ($fieldName) {
                            case 'models':
                                if ($content) {
                                    $content = explode("\n", $content);
                                    $content = array_filter($content);
                                }
                                $content = is_array($content) ? implode("\n", $content) : '';
                                break;
                            case 'model':
                                $models = Setting::AIBotModels2Array($array[$key . 's'], true);
                                $content = in_array($content, $models) ? $content : ($models[0] ?? '');
                                break;
                            case 'temperature':
                                if ($content) {
                                    $content = floatval(min(1, max(0, floatval($content) ?: 0.7)));
                                }
                                break;
                        }
                        $array[$key] = $content;
                    }
                }
                $value = $array;
                break;
        }
        return $value;
    }

    /**
     * 是否开启 AI 助手
     * @return bool
     */
    public static function AIOpen()
    {
        $setting = Base::setting('aibotSetting');
        if (!is_array($setting) || empty($setting)) {
            return false;
        }
        foreach (AI::TEXT_MODEL_PRIORITY as $vendor) {
            if (self::isAIBotVendorEnabled($setting, $vendor)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 判断 AI 机器人厂商是否启用
     * @param array $setting
     * @param string $vendor
     * @return bool
     */
    protected static function isAIBotVendorEnabled(array $setting, string $vendor): bool
    {
        $key = trim((string)($setting[$vendor . '_key'] ?? ''));
        return match ($vendor) {
            'ollama' => $key !== '' || !empty($setting['ollama_base_url']),
            'wenxin' => $key !== '' && !empty($setting['wenxin_secret']),
            default => $key !== '',
        };
    }

    /**
     * AI 机器人模型转数组
     * @param $models
     * @param bool $retValue
     * @return array
     */
    public static function AIBotModels2Array($models, $retValue = false)
    {
        $list = is_array($models) ? $models : explode("\n", $models);
        $array = [];
        foreach ($list as $item) {
            $arr = Base::newTrim(explode('|', $item . '|'));
            if ($arr[0]) {
                $array[] = [
                    'value' => $arr[0],
                    'label' => $arr[1] ?: $arr[0]
                ];
            }
        }
        if ($retValue) {
            return array_column($array, 'value');
        }
        return $array;
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

    /**
     * 验证消息限制
     * @param $type
     * @param $msg
     * @return void
     */
    public static function validateMsgLimit($type, $msg)
    {
        $keyName = 'msg_edit_limit';
        $error = '此消息不可修改';
        if ($type == 'rev') {
            $keyName = 'msg_rev_limit';
            $error = '此消息不可撤回';
        }
        $limitNum = intval(Base::settingFind('system', $keyName, 0));
        if ($limitNum <= 0) {
            return;
        }
        if ($msg instanceof WebSocketDialogMsg) {
            $dialogMsg = $msg;
        } else {
            $dialogMsg = WebSocketDialogMsg::find($msg);
        }
        if (!$dialogMsg) {
            return;
        }
        $limitTime = Carbon::parse($dialogMsg->created_at)->addMinutes($limitNum);
        if ($limitTime->lt(Carbon::now())) {
            throw new ApiException('已超过' . Doo::translate(Base::forumMinuteDay($limitNum)) . '，' . $error);
        }
    }
}
