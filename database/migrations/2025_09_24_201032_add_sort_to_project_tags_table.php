<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSortToProjectTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $added = false;
        Schema::table('project_tags', function (Blueprint $table) use (&$added) {
            if (!Schema::hasColumn('project_tags', 'sort')) {
                $table->unsignedInteger('sort')->default(0)->after('color')->comment('排序');
                $added = true;
            }
        });

        if ($added) {
            \App\Models\ProjectTag::query()
                ->select('project_id')
                ->distinct()
                ->orderBy('project_id')
                ->chunk(100, function ($projectIds) {
                    foreach ($projectIds as $project) {
                        $tags = \App\Models\ProjectTag::query()
                            ->where('project_id', $project->project_id)
                            ->orderByDesc('id')
                            ->get(['id']);
                        $index = 0;
                        foreach ($tags as $tag) {
                            \App\Models\ProjectTag::where('id', $tag->id)->update(['sort' => $index++]);
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
        Schema::table('project_tags', function (Blueprint $table) {
            if (Schema::hasColumn('project_tags', 'sort')) {
                $table->dropColumn('sort');
            }
        });
    }
}
