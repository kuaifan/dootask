<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRemarkToUserFavoritesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_favorites', function (Blueprint $table) {
            if (!Schema::hasColumn('user_favorites', 'remark')) {
                $table->string('remark', 255)->default('')->after('favoritable_id')->comment('收藏备注');
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
        Schema::table('user_favorites', function (Blueprint $table) {
            if (Schema::hasColumn('user_favorites', 'remark')) {
                $table->dropColumn('remark');
            }
        });
    }
}
