<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('web_socket_dialog_msg_attachments', function (Blueprint $table) {
            // MariaDB 10.7 ignores DESC index direction, so negate msg_id to preserve mixed ordering.
            $table->bigInteger('cursor_msg_id')->storedAs('-`msg_id`');
            $table->index(['cursor_msg_id', 'position', 'id'], 'idx_ws_msg_attachment_cursor');
            $table->index(['ext', 'msg_id'], 'idx_ws_msg_attachment_ext_msg');
        });
    }

    public function down(): void
    {
        $hasCursorColumn = Schema::hasColumn('web_socket_dialog_msg_attachments', 'cursor_msg_id');
        Schema::table('web_socket_dialog_msg_attachments', function (Blueprint $table) use ($hasCursorColumn) {
            $table->dropIndex('idx_ws_msg_attachment_cursor');
            $table->dropIndex('idx_ws_msg_attachment_ext_msg');
            if ($hasCursorColumn) {
                $table->dropColumn('cursor_msg_id');
            }
        });
    }
};
