<?php

use App\Models\UserTransfer;
use Illuminate\Database\Migrations\Migration;

class ProcessUserTransferDialog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (UserTransfer::count() === 0) {
            return;
        }
        try {
            \App\Module\Ihttp::ihttp_request('http://127.0.0.1:' . config('laravels.listen_port') . '/migration/userdialog', [], [
                'app-key' => env('APP_KEY')
            ], 10);
        } catch (\Throwable $e) {
            info($e);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
