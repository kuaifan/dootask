<?php


namespace App\Module;


use Maatwebsite\Excel\Concerns\ToArray;

class BillImport implements ToArray
{
    public function Array(Array $tables)
    {
        return $tables;
    }

}
