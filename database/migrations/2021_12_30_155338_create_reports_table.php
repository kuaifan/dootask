<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( Schema::hasTable('reports') )
            return;

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("title")->default("")->comment("标题");
            $table->enum("type", ["weekly", "daily"])->default("daily")->comment("汇报类型");
            $table->unsignedBigInteger("userid")->default(0);
            $table->longText("content")->nullable();
            $table->index(["userid", "created_at"], "default");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reports');
    }
}
