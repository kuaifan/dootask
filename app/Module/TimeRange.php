<?php

namespace App\Module;

use Carbon\Carbon;

class TimeRange
{
    public ?Carbon $updated;
    public ?Carbon $deleted;

    /**
     * @param $data
     */
    public function __construct($data)
    {
        if (is_array($data)) {
            $range = explode("-", str_replace([",", "|"], "-", $data['timerange']));
            if ($data['updated_at'] || $data['at_after']) {
                $range[0] = $data['updated_at'] ?: $data['at_after'];
            }
            if ($data['deleted_at']) {
                $range[1] = $data['deleted_at'];
            }
        } else {
            $range = explode("-", str_replace([",", "|"], "-", $data));
        }
        //
        $updated = Base::isNumber($range[0]) ? intval($range[0]) : trim($range[0]);
        $deleted = Base::isNumber($range[1]) ? intval($range[1]) : trim($range[1]);
        //
        $this->updated = $updated ? Carbon::parse($updated) : null;
        $this->deleted = $deleted ? Carbon::parse($deleted) : null;
    }

    /**
     * @param $data
     * @return TimeRange
     */
    public static function parse($data): TimeRange
    {
        return new self($data);
    }
}
