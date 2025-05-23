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
     * @var bool
     */
    protected $writeLog = true;

    /**
     * ApiException constructor.
     * @param string|array $msg
     * @param array $data
     * @param int $code
     */
    public function __construct($msg = '', $data = [], $code = 0, $writeLog = true)
    {
        if (is_array($msg) && isset($msg['code'])) {
            $code = $msg['code'];
            $data = $msg['data'];
            $msg = $msg['msg'];
        }
        $this->data = $data;
        $this->writeLog = $writeLog && $code !== -1;
        parent::__construct($msg, $code);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return bool
     */
    public function isWriteLog(): bool
    {
        return $this->writeLog;
    }
}
