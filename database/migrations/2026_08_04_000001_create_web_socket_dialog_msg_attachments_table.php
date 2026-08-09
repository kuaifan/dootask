<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_socket_dialog_msg_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('msg_id')->comment('消息ID');
            $table->unsignedBigInteger('dialog_id')->comment('会话ID');
            $table->string('source_type', 20)->comment('来源类型：file_message、inline_image');
            $table->string('kind', 20)->comment('附件类型：file、image');
            $table->unsignedSmallInteger('position')->default(0)->comment('消息内附件顺序');
            $table->string('name')->default('')->comment('附件名称');
            $table->string('ext', 20)->default('')->comment('文件扩展名');
            $table->text('path')->nullable()->comment('原文件地址');
            $table->text('thumb')->nullable()->comment('缩略图地址');
            $table->unsignedBigInteger('size')->default(0)->comment('文件大小(B)');
            $table->unsignedInteger('width')->default(0)->comment('图片宽度');
            $table->unsignedInteger('height')->default(0)->comment('图片高度');
            $table->timestamps();

            $table->unique(['msg_id', 'source_type', 'position'], 'uniq_ws_msg_attachment_position');
            $table->index(['dialog_id', 'id'], 'idx_ws_msg_attachment_dialog');
            $table->index(['kind', 'id'], 'idx_ws_msg_attachment_kind');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_socket_dialog_msg_attachments');
    }
};
