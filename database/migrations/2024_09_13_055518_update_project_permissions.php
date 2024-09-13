<?php

use App\Models\ProjectPermission;
use Illuminate\Database\Migrations\Migration;

class UpdateProjectPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        ProjectPermission::orderBy('id')->chunk(100, function ($rows) {
            /** @var ProjectPermission $row */
            foreach ($rows as $row) {
                $permissions = $row->permissions;
                if (!isset($permissions[ProjectPermission::TASK_TIME])) {
                    $permissions[ProjectPermission::TASK_TIME] = $permissions[ProjectPermission::TASK_UPDATE];
                    $row->permissions = \App\Module\Base::array2json($permissions);
                    $row->save();
                }
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
        //
    }
}
