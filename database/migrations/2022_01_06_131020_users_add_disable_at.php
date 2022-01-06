<?php

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UsersAddDisableAt extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $isAdd = false;
        Schema::table('users', function (Blueprint $table) use (&$isAdd) {
            if (!Schema::hasColumn('users', 'disable_at')) {
                $isAdd = true;
                $table->timestamp('disable_at')->nullable()->after('created_ip')->comment('禁用时间');
            }
        });
        if ($isAdd) {
            User::where("identity", "like", "%,disable,%")->update([
                'disable_at' => Carbon::now(),
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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn("disable_at");
        });
    }
}
