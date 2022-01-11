<?php

use App\Models\ProjectFlowItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProjectFlowItemsAddUsertype extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $isAdd = false;
        Schema::table('project_flow_items', function (Blueprint $table) use (&$isAdd) {
            if (!Schema::hasColumn('project_flow_items', 'usertype')) {
                $isAdd = true;
                $table->string('usertype', 10)->nullable()->default('')->after('userids')->comment('流转模式');
            }
        });
        if ($isAdd) {
            ProjectFlowItem::where("usertype", "")->update([
                'usertype' => 'add',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('project_flow_items', function (Blueprint $table) {
            $table->dropColumn("usertype");
        });
    }
}
