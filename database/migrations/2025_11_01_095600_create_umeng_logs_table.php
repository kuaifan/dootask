<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('umeng_logs', function (Blueprint $table) {
            $table->id();
            $table->text('request')->nullable()->comment('请求参数');
            $table->text('response')->nullable()->comment('推送返回');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('umeng_logs');
    }
};
