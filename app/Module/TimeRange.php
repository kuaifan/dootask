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
            $keys = array_keys($data);
            if (count($keys) === 2 && $keys[0] === 0 && $keys[1] === 1) {
                $range = $data;
            } else {
                $range = $this->format($data['timerange']);
                if ($data['updated_at'] || $data['at_after']) {
                    $range[0] = $data['updated_at'] ?: $data['at_after'];
                }
                if ($data['deleted_at']) {
                    $range[1] = $data['deleted_at'];
                }
            }
        } else {
            $range = $this->format($data);
        }
        //
        $this->updated = $range[0] ? Base::newCarbon($range[0]) : null;
        $this->deleted = $range[1] ? Base::newCarbon($range[1]) : null;
    }

    /**
     * @return Carbon|null
     */
    public function firstTime(): ?Carbon
    {
        return $this->updated;
    }

    /**
     * @return Carbon|null
     */
    public function lastTime(): ?Carbon
    {
        return $this->deleted;
    }

    /**
     * @return bool
     */
    public function isExist(): bool
    {
        return $this->updated && $this->deleted;
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
        return Base::newTrim(explode(",", str_replace($search, ",", $timerange)));
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
