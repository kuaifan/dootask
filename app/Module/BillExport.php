<?php

namespace App\Module;

use Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Writer\Exception;

class BillExport implements WithHeadings, WithEvents, FromCollection, WithTitle, WithStrictNullComparison
{
    public $title;
    public $headings = [];
    public $data = [];
    public $typeLists = [];
    public $typeNumber = 0;

    public function __construct($title, array $data)
    {
        $this->title = $title;
        $this->data = $data;
    }

    public static function create($data = [], $title = "Sheet1") {
        if (is_string($data)) {
            list($title, $data) = [$data, $title];
        }
        if (!is_array($data)) {
            $data = [];
        }
        return new BillExport($title, $data);
    }

    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    public function setHeadings(array $headings) {
        $this->headings = $headings;
        return $this;
    }

    public function setData(array $data) {
        $this->data = $data;
        return $this;
    }

    public function setTypeList(array $typeList, $number = 0) {
        $this->typeLists = $typeList;
        $this->typeNumber = $number;
        return $this;
    }

    public function store($fileName = '') {
        if (empty($fileName)) {
            $fileName = date("YmdHis") . '.xls';
        }
        try {
            return Excel::store($this, $fileName);
        } catch (Exception $e) {
            return "导出错误：" . $e->getMessage();
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            return "导出错误：" . $e->getMessage();
        }
    }

    public function download($fileName = '') {
        if (empty($fileName)) {
            $fileName = date("YmdHis") . '.xls';
        }
        try {
            return Excel::download($this, $fileName);
        } catch (Exception $e) {
            return "导出错误：" . $e->getMessage();
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            return "导出错误：" . $e->getMessage();
        }
    }

    /**
     * 导出的文件标题
     * @return string
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * 标题行
     * @return array
     */
    public function headings(): array
    {
        return $this->headings;
    }

    /**
     * 导出的内容
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect($this->data);
    }

    /**
     * 设置单元格事件
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::Class => function (AfterSheet $event) {
                $count = count($this->data);
                foreach ($this->typeLists AS $cell => $typeList) {
                    if ($cell && $typeList) {
                        $p = $this->headings ? 1 : 0;
                        for ($i = 1 + $p; $i <= max($count, $this->typeNumber) + $p; $i++) {
                            $validation = $event->sheet->getDelegate()->getCell($cell . $i)->getDataValidation();
                            $validation->setType(DataValidation::TYPE_LIST);
                            $validation->setErrorStyle(DataValidation::STYLE_WARNING);
                            $validation->setAllowBlank(false);
                            $validation->setShowDropDown(true);
                            $validation->setShowInputMessage(true);
                            $validation->setShowErrorMessage(true);
                            $validation->setErrorTitle('输入的值不合法');
                            $validation->setError('选择的值不在列表中，请选择列表中的值');
                            $validation->setPromptTitle('从列表中选择');
                            $validation->setPrompt('请选择下拉列表中的值');
                            $validation->setFormula1('"' . implode(',', $typeList) . '"');
                        }
                    }
                }
            }
        ];
    }
}
