<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDateTimesUserCheckinRecords extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $isAdd = false;
        Schema::table('user_checkin_records', function (Blueprint $table) use (&$isAdd) {
            if (!Schema::hasColumn('user_checkin_records', 'date')) {
                $isAdd = true;
                $table->string('date', 20)->nullable()->default('')->after('mac')->comment('签到日期');
                $table->text('times')->nullable()->after('date')->comment('签到时间');
                $table->renameColumn('time', 'report_time');
            }
        });
        if ($isAdd) {
            $userids = \App\Models\UserCheckinRecord::select('userid')->distinct()->get()->pluck('userid');
            foreach ($userids as $userid) {
                $list = \App\Models\UserCheckinRecord::whereUserid($userid)->orderBy('created_at')->get();
                $ids = [];
                $date = "";
                $array = [];
                foreach ($list as $item) {
                    $ids[] = $item->id;
                    $created_at = \Carbon\Carbon::parse($item->created_at);
                    if ($created_at->toDateString() != $date) {
                        $date = $created_at->toDateString();
                        if ($array) {
                            $record = \App\Models\UserCheckinRecord::createInstance($array);
                            $record->save();
                        }
                        $array = [
                            'userid' => $item->userid,
                            'mac' => $item->mac,
                            'date' => $date,
                            'times' => [],
                            'report_time' => $item->report_time,
                            'created_at' => $item->created_at,
                        ];
                    }
                    if ($array) {
                        $array['times'][] = $created_at->toTimeString();
                    }
                }
                if ($array) {
                    \App\Models\UserCheckinRecord::whereIn('id', $ids)->delete();
                }
                if ($array) {
                    $record = \App\Models\UserCheckinRecord::createInstance($array);
                    $record->save();
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // ... 退回去意义不大
    }
}
