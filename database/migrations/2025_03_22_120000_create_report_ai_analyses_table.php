<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportAiAnalysesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('report_ai_analyses')) {
            return;
        }

        Schema::create('report_ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rid')->comment('报告ID');
            $table->unsignedBigInteger('userid')->comment('生成分析的会员ID');
            $table->string('model')->default('')->comment('使用的模型名称');
            $table->longText('analysis_text')->comment('AI 分析的原始文本（Markdown）');
            $table->json('meta')->nullable()->comment('额外的上下文信息');
            $table->timestamps();

            $table->unique(['rid', 'userid'], 'uk_report_ai_analysis_rid_userid');
            $table->index(['userid', 'updated_at'], 'idx_report_ai_analysis_user_updated');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('report_ai_analyses');
    }
}
