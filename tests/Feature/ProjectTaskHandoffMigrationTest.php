<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProjectTaskHandoffMigrationTest extends TestCase
{
    public function test_migration_creates_and_removes_handoff_table(): void
    {
        // 使用独立表前缀，避免迁移测试触及本机已有的流转记录。
        $connectionName = 'handoff_migration_test';
        $config = DB::connection()->getConfig();
        $config['prefix'] = 'ht_' . bin2hex(random_bytes(4)) . '_';
        config(['database.connections.' . $connectionName => $config]);
        $schema = DB::connection($connectionName)->getSchemaBuilder();
        $originalSchema = Schema::getFacadeRoot();
        $migration = require database_path('migrations/2026_09_08_000001_create_project_task_handoffs_table.php');

        Schema::swap($schema);
        try {
            $migration->up();
            $this->assertTrue($schema->hasTable('project_task_handoffs'));
            $this->assertTrue($schema->hasColumns('project_task_handoffs', [
                'id', 'task_id', 'userid', 'source', 'record', 'created_at', 'updated_at',
            ]));
            $this->assertTrue($schema->hasIndex('project_task_handoffs', ['task_id', 'id']));

            $migration->down();
            $this->assertFalse($schema->hasTable('project_task_handoffs'));
        } finally {
            $schema->dropIfExists('project_task_handoffs');
            Schema::swap($originalSchema);
            DB::purge($connectionName);
        }
    }
}
