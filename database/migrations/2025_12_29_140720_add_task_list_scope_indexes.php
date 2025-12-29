<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_users', function (Blueprint $table) {
            $table->index(['userid', 'project_id']);
        });

        Schema::table('project_tasks', function (Blueprint $table) {
            $table->index(['project_id', 'archived_at', 'deleted_at', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No-op: do not drop indexes automatically.
    }
};
