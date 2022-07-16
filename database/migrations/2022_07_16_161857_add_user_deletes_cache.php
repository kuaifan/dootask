<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserDeletesCache extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_deletes', function (Blueprint $table) {
            if (!Schema::hasColumn('user_deletes', 'cache')) {
                $table->text('cache')->after('reason')->nullable()->default('')->comment('会员资料缓存');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_deletes', function (Blueprint $table) {
            $table->dropColumn("cache");
        });
    }
}
