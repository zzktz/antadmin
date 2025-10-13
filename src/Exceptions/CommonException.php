<?php

namespace Antmin\Exceptions;

use Antmin\Common\Base;
use Exception;

class CommonException extends Exception
{

    protected $code;
    protected array $data;
    protected int $statusCode;

    public function __construct(string $message, array $data = [], int $code = 0, int $statusCode = 200)
    {
        parent::__construct($message, $code);
        $this->data       = $data;
        $this->code       = $code;
        $this->statusCode = $statusCode;
    }

    public function report()
    {

    }

    public function render()
    {
        $message = $this->getMessage(); // 返回异常信息
        $data    = empty($this->data) ? [] : $this->data;
        return Base::errJson($message, $data, $this->code);
    }
}
