<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class BackfillSortProjectTaskTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('project_task_templates') || !Schema::hasColumn('project_task_templates', 'sort')) {
            return;
        }

        \App\Models\ProjectTaskTemplate::query()
            ->select('project_id')
            ->distinct()
            ->orderBy('project_id')
            ->chunk(100, function ($projects) {
                foreach ($projects as $project) {
                    $templates = \App\Models\ProjectTaskTemplate::query()
                        ->where('project_id', $project->project_id)
                        ->orderByDesc('id')
                        ->get(['id']);
                    $index = 0;
                    foreach ($templates as $template) {
                        \App\Models\ProjectTaskTemplate::where('id', $template->id)->update(['sort' => $index++]);
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
        // no-op
    }
}
