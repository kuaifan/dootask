<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_task_handoffs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('task_id');
            $table->unsignedInteger('userid')->default(0);
            $table->string('source', 32);
            $table->json('record');
            $table->timestamps();
            $table->index(['task_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_task_handoffs');
    }
};
