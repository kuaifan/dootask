<?php

namespace App\Module;


use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class BillMultipleExport implements WithMultipleSheets
{
    use Exportable;

    protected $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return $this->data;
    }
}
