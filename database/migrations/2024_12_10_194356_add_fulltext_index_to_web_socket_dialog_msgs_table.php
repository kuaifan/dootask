<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddFulltextIndexToWebSocketDialogMsgsTable extends Migration
{
    public function up()
    {
        $tableName = 'web_socket_dialog_msgs';
        $column = 'key'; // 需要添加 FULLTEXT 索引的字段

        // 检查 FULLTEXT 索引是否已经存在
        if (!$this->fullTextIndexExists($tableName, $column)) {
            Schema::table($tableName, function (Blueprint $table) use ($column) {
                $table->fullText($column);
            });
        }
    }

    public function down()
    {
        // 删除 FULLTEXT 索引
        Schema::table('web_socket_dialog_msgs', function (Blueprint $table) {
            $table->dropFullText(['key']);
        });
    }

    private function fullTextIndexExists($tableName, $column)
    {
        // 获取当前数据库名称
        $databaseName = env('DB_DATABASE');

        // 查询 information_schema.statistics 表
        $prefix = DB::getTablePrefix();
        $indexExists = DB::table(DB::raw('information_schema.statistics'))
            ->where('table_schema', $databaseName)
            ->where('table_name', $prefix . $tableName)
            ->where('index_type', 'FULLTEXT')
            ->get();

        // 检查返回的索引是否包含指定的列
        foreach ($indexExists as $index) {
            $indexColumns = explode(',', $index->column_name);
            // 如果索引包含指定的列，则返回 true
            if (in_array($column, $indexColumns)) {
                return true;
            }
        }

        return false;
    }
}
