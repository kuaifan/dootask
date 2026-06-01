<?php

namespace App\Module;

use Maatwebsite\Excel\Concerns\ToArray;

class UserImport implements ToArray
{
    public function array(array $array)
    {
        return $array;
    }
}
