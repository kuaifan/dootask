<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_bots', function (Blueprint $table) {
            if (!Schema::hasColumn('user_bots', 'webhook_events')) {
                $table->text('webhook_events')->nullable()->after('webhook_num')->comment('Webhook事件配置');
            }
        });

        DB::table('user_bots')
            ->where(function ($query) {
                $query->whereNull('webhook_events')->orWhere('webhook_events', '');
            })
            ->update(['webhook_events' => json_encode(['message'])]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_bots', function (Blueprint $table) {
            if (Schema::hasColumn('user_bots', 'webhook_events')) {
                $table->dropColumn('webhook_events');
            }
        });
    }
};
