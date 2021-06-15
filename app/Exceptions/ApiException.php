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
        if (is_array($msg) && isset($msg['code'])) {
            $code = $msg['code'];
            $data = $msg['data'];
            $msg = $msg['msg'];
        }
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
