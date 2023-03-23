<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToUserEmailVerificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_email_verifications', function (Blueprint $table) {
            if (!Schema::hasColumn('user_email_verifications', 'type')) {
                $table->tinyInteger('type')->nullable()->default(1)->after('status')->comment('邮件类型：1-邮箱认证，2-修改邮箱');
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
        Schema::table('user_email_verifications', function (Blueprint $table) {
            $table->dropColumn("type");
        });
    }
}
