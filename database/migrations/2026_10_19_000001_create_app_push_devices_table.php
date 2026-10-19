<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppPushDevicesTable extends Migration
{
    public function up(): void
    {
        Schema::create('app_push_devices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('userid')->index()->comment('用户ID');
            $table->string('channel', 20)->comment('推送通道');
            $table->string('device_id', 64)->comment('推送服务设备ID');
            $table->string('vendor', 20)->default('')->comment('厂商通道');
            $table->string('platform', 20)->default('')->comment('平台类型');
            $table->string('device_hash', 64)->index()->comment('登录TOKEN MD5，关联UserDevice');
            $table->string('device', 255)->default('')->comment('设备型号');
            $table->string('version', 100)->default('')->comment('应用版本号');
            $table->text('ua')->nullable()->comment('userAgent');
            $table->tinyInteger('is_notified')->default(0)->comment('通知权限');
            $table->timestamps();
            $table->unique(['channel', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_push_devices');
    }
}
