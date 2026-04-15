<?php


namespace Antmin\Middleware;


class Filter
{


    public static function getFilterMethod(): array
    {
        return [
            'systemRegister',
            'systemLogin',
            'sendCodeByEmail',
            'systemUploadEditor',
        ];
    }

}
