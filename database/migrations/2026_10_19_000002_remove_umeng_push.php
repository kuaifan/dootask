<?php

use App\Models\Setting;
use App\Module\Base;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 移除友盟推送：删除别名、日志表及推送设置中的友盟参数，DooPush 开关迁移为三档模式
     */
    public function up(): void
    {
        Schema::dropIfExists('umeng_alias');
        Schema::dropIfExists('umeng_logs');

        $row = Setting::whereName('appPushSetting')->first();
        if ($row) {
            $setting = Base::string2array($row->setting);
            // DooPush 改为三档，升级后统一使用系统配置；原自定义凭证保留，需要时可切回「自定义」。
            $setting['doopush'] = 'system';
            foreach (['push', 'ios_key', 'ios_secret', 'android_key', 'android_secret'] as $key) {
                unset($setting[$key]);
            }
            $row->updateInstance(['setting' => $setting]);
            $row->save();
        }
    }

    public function down(): void
    {
        // 删除表无法恢复
    }
};
