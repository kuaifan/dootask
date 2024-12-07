<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTagIdFieldsToPreProjectTaskTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_task_tags', function (Blueprint $table) {
            if (!Schema::hasColumn('project_task_tags', 'tag_id')) {
                $table->bigInteger('tag_id')->nullable()->default(0)->comment('标签ID');
                $table->index('tag_id');
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
        Schema::table('project_task_tags', function (Blueprint $table) {
            $table->dropColumn('tag_id');
        });
    }
}
