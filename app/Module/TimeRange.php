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
            $range = $this->format($data['timerange']);
            if ($data['updated_at'] || $data['at_after']) {
                $range[0] = $data['updated_at'] ?: $data['at_after'];
            }
            if ($data['deleted_at']) {
                $range[1] = $data['deleted_at'];
            }
        } else {
            $range = $this->format($data);
        }
        //
        $updated = Base::isNumber($range[0]) ? intval($range[0]) : trim($range[0]);
        $deleted = Base::isNumber($range[1]) ? intval($range[1]) : trim($range[1]);
        //
        $timezone = config('app.timezone');
        $this->updated = $updated ? Carbon::parse($updated)->setTimezone($timezone) : null;
        $this->deleted = $deleted ? Carbon::parse($deleted)->setTimezone($timezone) : null;
    }

    /**
     * @param $timerange
     * - 格式1：2021-01-01 00:00:00,2021-01-01 23:59:59
     * - 格式2：1612051200-1612137599
     * @return array
     */
    private function format($timerange)
    {
        $search = str_contains($timerange, ":") ? ["|"] : ["|", "-"];
        return explode(",", str_replace($search, ",", $timerange));
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
