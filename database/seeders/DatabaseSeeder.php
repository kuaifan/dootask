<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        $this->call(FileContentsTableSeeder::class);
        $this->call(FilesTableSeeder::class);
        $this->call(FileUsersTableSeeder::class);
        $this->call(ProjectColumnsTableSeeder::class);
        $this->call(ProjectFlowItemsTableSeeder::class);
        $this->call(ProjectFlowsTableSeeder::class);
        $this->call(ProjectLogsTableSeeder::class);
        $this->call(ProjectTaskContentsTableSeeder::class);
        $this->call(ProjectTaskUsersTableSeeder::class);
        $this->call(ProjectTasksTableSeeder::class);
        $this->call(ProjectUsersTableSeeder::class);
        $this->call(ProjectsTableSeeder::class);
        $this->call(SettingsTableSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(WebSocketDialogMsgReadsTableSeeder::class);
        $this->call(WebSocketDialogMsgsTableSeeder::class);
        $this->call(WebSocketDialogUsersTableSeeder::class);
        $this->call(WebSocketDialogsTableSeeder::class);
        $this->call(WebSocketsTableSeeder::class);
    }
}
