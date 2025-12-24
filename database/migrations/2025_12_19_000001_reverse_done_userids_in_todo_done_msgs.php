<?php

use App\Module\Base;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ReverseDoneUseridsInTodoDoneMsgs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->reverseDoneUserids('2025-12-19 00:00:00');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->reverseDoneUserids('2025-12-19 00:00:00');
    }

    private function reverseDoneUserids(string $after)
    {
        DB::table('web_socket_dialog_msgs')
            ->select(['id', 'msg'])
            ->where('type', 'todo')
            ->where('created_at', '>', $after)
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $msg = Base::json2array($row->msg);
                    if (empty($msg) || !is_array($msg)) {
                        continue;
                    }
                    if (($msg['action'] ?? '') !== 'done') {
                        continue;
                    }
                    $data = $msg['data'] ?? null;
                    if (!is_array($data)) {
                        continue;
                    }
                    $doneUserids = $data['done_userids'] ?? null;
                    if (!is_array($doneUserids) || count($doneUserids) < 2) {
                        continue;
                    }
                    $data['done_userids'] = array_reverse($doneUserids);
                    $msg['data'] = $data;
                    DB::table('web_socket_dialog_msgs')
                        ->where('id', $row->id)
                        ->update(['msg' => Base::array2json($msg)]);
                }
            });
    }
}
