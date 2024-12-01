<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddArchiveFieldsToPreProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'archive_method')) {
                $table->after('personal', function ($table) {
                    $table->string('archive_method', 20)->nullable()->default('system')->comment('自动归档方式');
                    $table->integer('archive_days')->nullable()->default(30)->comment('自动归档天数');
                });
                $table->index('archive_method');
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
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('idx_projects_archive_method');
            $table->dropColumn([
                'archive_method',
                'archive_days'
            ]);
        });
    }
}
