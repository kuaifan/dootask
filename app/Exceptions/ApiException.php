<?php
namespace App\Exceptions;

use RuntimeException;

class ApiException extends RuntimeException
{
    /**
     * @var array
     */
    protected $data;

    /**
     * ApiException constructor.
     * @param string $msg
     * @param array $data
     * @param int $code
     */
    public function __construct($msg = '', $data = [], $code = 0)
    {
        $this->data = $data;
        parent::__construct($msg, $code);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
