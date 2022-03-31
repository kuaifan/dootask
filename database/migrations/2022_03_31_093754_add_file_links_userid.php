<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFileLinksUserid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('file_links', function (Blueprint $table) {
            if (!Schema::hasColumn('file_links', 'userid')) {
                $table->integer('userid')->nullable()->default(0)->after('code')->comment('会员ID');
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
        Schema::table('file_links', function (Blueprint $table) {
            $table->dropColumn("userid");
        });
    }
}
