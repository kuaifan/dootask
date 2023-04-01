<?php

use Illuminate\Database\Migrations\Migration;

class UpdateFilePshare extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\Models\File::whereShare(1)->wherePshare(0)->update(['pshare' => DB::raw('id')]);
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
