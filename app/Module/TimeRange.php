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
            $range = Base::explodeInt($data['timerange']);
            if ($data['updated_at'] || $data['at_after']) {
                $range[0] = $data['updated_at'] ?: $data['at_after'];
            }
            if ($data['deleted_at']) {
                $range[1] = $data['deleted_at'];
            }
        } else {
            $range = Base::explodeInt($data);
        }
        //
        $this->updated = $range[0] ? Carbon::parse($range[0]) : null;
        $this->deleted = $range[1] ? Carbon::parse($range[1]) : null;
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
