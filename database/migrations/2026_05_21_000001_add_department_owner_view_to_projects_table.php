<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDepartmentOwnerViewToProjectsTable extends Migration
{
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'department_owner_view')) {
                $table->string('department_owner_view', 20)->default('open')->after('task_template_share')->comment('部门负责人视角可见开关');
            }
        });
    }

    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'department_owner_view')) {
                $table->dropColumn('department_owner_view');
            }
        });
    }
}
