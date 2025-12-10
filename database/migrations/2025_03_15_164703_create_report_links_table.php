<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('report_links')) {
            return;
        }

        Schema::create('report_links', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('rid')->nullable()->default(0)->index()->comment('报告ID');
            $table->integer('num')->nullable()->default(0)->comment('累计访问');
            $table->string('code')->nullable()->default('')->comment('链接码');
            $table->bigInteger('userid')->nullable()->default(0)->index()->comment('会员ID');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('report_links');
    }
}
