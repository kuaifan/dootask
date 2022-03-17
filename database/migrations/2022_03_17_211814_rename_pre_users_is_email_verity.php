<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenamePreUsersIsEmailVerity extends Migration
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
            if (Schema::hasColumn('users', 'is_email_verity')) {
                $isAdd = true;
                $table->renameColumn('is_email_verity', 'email_verity');
            }
        });
        if ($isAdd) {
            \App\Models\User::where("identity", "like", "%,admin,%")->chunkById(100, function ($lists) {
                foreach ($lists as $item) {
                    if (!$item->email_verity) {
                        $item->email_verity = 1;
                        $item->save();
                    }
                }
            });
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
            //
        });
    }
}
