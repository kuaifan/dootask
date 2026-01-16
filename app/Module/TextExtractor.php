<?php

namespace App\Module;

use Exception;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpPresentation\IOFactory as PresentationIOFactory;
use Illuminate\Support\Facades\File as FileFacade;


class TextExtractor
{
    private string $filePath;
    private string $fileMimeType;
    private string $fileExtension;

    /**
     * @param string $filePath
     * @throws Exception
     */
    public function __construct(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new Exception("File does not exist: {$filePath}");
        }
        $this->filePath = $filePath;
        $this->fileMimeType = FileFacade::mimeType($filePath);
        $this->fileExtension = $this->detectFileType();
    }

    /**
     * 从文件中提取文本
     * @return string
     * @throws Exception
     */
    public function extractContent(): string
    {
        return match ($this->fileExtension) {
            // Word文档
            'docx' => $this->parseWordDocument(),

            // Excel文档
            'xlsx', 'xls', 'csv' => $this->parseSpreadsheet(),

            // PowerPoint文档
            'ppt', 'pptx' => $this->parsePresentation(),

            // PDF文档
            'pdf' => $this->parsePdf(),

            // RTF文档
            'rtf' => $this->parseRtf(),

            // 其他文本文件
            default => $this->parseOther(),
        };
    }

    /**
     * 获取文件类型
     * @return string
     */
    private function detectFileType(): string
    {
        return match ($this->fileMimeType) {
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-excel' => 'xls',
            'text/csv', 'application/csv' => 'csv',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/pdf' => 'pdf',
            'application/rtf', 'text/rtf' => 'rtf',
            default => strtolower(pathinfo($this->filePath, PATHINFO_EXTENSION)),
        };
    }

    /**
     * Parse Word documents (.doc, .docx)
     * @return string
     */
    private function parseWordDocument(): string
    {
        $phpWord = WordIOFactory::load($this->filePath);
        $text = '';

        // Extract text from each section
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                } elseif (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $childElement) {
                        if (method_exists($childElement, 'getText')) {
                            $text .= $childElement->getText() . "\n";
                        }
                    }
                }
            }
        }

        return $text;
    }

    /**
     * Parse spreadsheet files (.xlsx, .xls, .csv)
     * @return string
     */
    private function parseSpreadsheet(): string
    {
        $spreadsheet = SpreadsheetIOFactory::load($this->filePath);
        $text = '';

        // Extract text from all worksheets
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $text .= 'Worksheet: ' . $worksheet->getTitle() . "\n";

            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $rowText = '';

                foreach ($cellIterator as $cell) {
                    $value = $cell->getValue();
                    if (!empty($value)) {
                        $rowText .= $value . "\t";
                    }
                }

                if (!empty(trim($rowText))) {
                    $text .= trim($rowText) . "\n";
                }
            }

            $text .= "\n";
        }

        return $text;
    }

    /**
     * Parse presentation files (.ppt, .pptx)
     * @return string
     * @throws Exception
     */
    private function parsePresentation(): string
    {
        $presentation = PresentationIOFactory::load($this->filePath);
        $text = '';

        // Extract text from all slides
        foreach ($presentation->getAllSlides() as $slide) {
            foreach ($slide->getShapeCollection() as $shape) {
                if ($shape instanceof \PhpOffice\PhpPresentation\Shape\RichText) {
                    foreach ($shape->getParagraphs() as $paragraph) {
                        foreach ($paragraph->getRichTextElements() as $element) {
                            $text .= $element->getText();
                        }
                        $text .= "\n";
                    }
                }
            }
            $text .= "\n";
        }

        return $text;
    }

    /**
     * Parse PDF files (requires additional library like Smalot\PdfParser)
     * @return string
     * @throws Exception
     */
    private function parsePdf(): string
    {
        // You'll need to install the Smalot PDF Parser: composer require smalot/pdfparser
        if (!class_exists('\Smalot\PdfParser\Parser')) {
            throw new \Exception("PDF Parser not available. Install with: composer require smalot/pdfparser");
        }

        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($this->filePath);
        return $pdf->getText();
    }

    /**
     * Parse RTF files
     * @return string
     */
    private function parseRtf(): string
    {
        // Simple RTF to text conversion
        $content = file_get_contents($this->filePath);

        // Remove RTF control words and groups
        $content = preg_replace('/\\\\([a-z]{1,32})(-?[0-9]{1,10})?[ ]?/i', '', $content);
        $content = preg_replace('/\\\\([^a-z]|[a-z]{33,})/i', '', $content);
        $content = preg_replace('/\{\*?\\\\[^{}]*\}/', '', $content);
        $content = preg_replace('/\{[\r\n]*\}/', '', $content);

        // Convert special characters
        $content = preg_replace('/\\\\\'([0-9a-f]{2})/i', '', $content);

        // Remove remaining curly braces
        $content = str_replace(['{', '}'], '', $content);

        return $content ?: '';
    }

    /**
     * Parse Other(text) files
     * @return string
     * @throws Exception
     */
    private function parseOther(): string
    {
        $isBinary = !str_contains($this->fileMimeType, 'text/')
            && !str_contains($this->fileMimeType, 'application/json')
            && !str_contains($this->fileMimeType, 'application/xml');

        if ($isBinary) {
            throw new Exception("Unable to read the text content of this type of file");
        }

        return file_get_contents($this->filePath);
    }

    /** ********************************************************************* */
    /** ********************************************************************* */
    /** ********************************************************************* */

    /**
     * 获取文件内容
     * @param $filePath
     * @param int $fileMaxSize      最大文件大小，单位KB，默认1024KB
     * @param int $contentMaxSize   最大内容大小，单位KB，默认300KB
     * @param bool $truncate        超过contentMaxSize时是否截取，默认true截取，false返回错误
     * @return array
     */
    public static function extractFile($filePath, int $fileMaxSize = 1024, int $contentMaxSize = 300, bool $truncate = true): array
    {
        if (!file_exists($filePath) || !is_file($filePath)) {
            return Base::retError("Failed to read contents of {$filePath}");
        }
        if (filesize($filePath) > $fileMaxSize * 1024) {
            return Base::retError("File size exceeds " . Base::readableBytes($fileMaxSize * 1024) . ", unable to display content");
        }
        try {
            $extractor = new self($filePath);
            $content = $extractor->extractContent();
            $maxBytes = $contentMaxSize * 1024;
            if (strlen($content) > $maxBytes) {
                if ($truncate) {
                    $content = mb_substr($content, 0, $maxBytes);
                } else {
                    return Base::retError("Content size exceeds " . Base::readableBytes($maxBytes) . ", unable to display content");
                }
            }
            return Base::retSuccess("success", $content);
        } catch (Exception $e) {
            return Base::retError($e->getMessage());
        }
    }
}
