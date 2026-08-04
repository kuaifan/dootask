<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('web_socket_dialog_msgs', function (Blueprint $table) {
            $table->index(
                ['type', 'deleted_at', 'id', 'dialog_id'],
                'idx_dialog_file_lists'
            );
        });
    }

    public function down(): void
    {
        Schema::table('web_socket_dialog_msgs', function (Blueprint $table) {
            $table->dropIndex('idx_dialog_file_lists');
        });
    }
};
