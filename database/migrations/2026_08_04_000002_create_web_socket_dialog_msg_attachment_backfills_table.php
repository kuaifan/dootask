<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_socket_dialog_msg_attachment_backfills', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('status', 20)->default('pending')->comment('状态：pending、running、failed、completed');
            $table->unsignedBigInteger('before_msg_id')->default(0)->comment('启动时消息快照上界');
            $table->unsignedBigInteger('last_msg_id')->default(0)->comment('已处理到的消息ID');
            $table->unsignedBigInteger('processed_count')->default(0)->comment('成功处理消息数');
            $table->unsignedBigInteger('attachment_count')->default(0)->comment('已索引附件数');
            $table->unsignedBigInteger('empty_count')->default(0)->comment('无有效附件消息数');
            $table->unsignedBigInteger('failure_count')->default(0)->comment('失败尝试次数');
            $table->unsignedInteger('retry_count')->default(0)->comment('当前消息连续重试次数');
            $table->text('last_error')->nullable()->comment('最近一次错误');
            $table->timestamp('started_at')->nullable()->comment('首次开始时间');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->timestamp('next_retry_at')->nullable()->comment('下次重试时间');
            $table->timestamps();

            $table->index(['status', 'next_retry_at'], 'idx_ws_msg_attachment_backfill_pending');
        });

        $beforeMsgId = intval(DB::table('web_socket_dialog_msgs')->max('id'));
        $now = now();
        DB::table('web_socket_dialog_msg_attachment_backfills')->insert([
            'status' => $beforeMsgId > 0 ? 'pending' : 'completed',
            'before_msg_id' => $beforeMsgId,
            'last_msg_id' => 0,
            'completed_at' => $beforeMsgId > 0 ? null : $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('web_socket_dialog_msg_attachment_backfills');
    }
};
