<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateManticoreSyncFailuresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manticore_sync_failures', function (Blueprint $table) {
            $table->id();
            $table->string('data_type', 20)->comment('数据类型: msg/file/task/project/user');
            $table->bigInteger('data_id')->comment('数据ID');
            $table->string('action', 20)->comment('操作类型: sync/delete');
            $table->string('error_message', 500)->nullable()->comment('错误信息');
            $table->integer('retry_count')->default(0)->comment('重试次数');
            $table->timestamp('last_retry_at')->nullable()->comment('最后重试时间');
            $table->timestamps();

            $table->unique(['data_type', 'data_id', 'action'], 'uk_type_id_action');
            $table->index(['last_retry_at', 'retry_count'], 'idx_retry');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('manticore_sync_failures');
    }
}
