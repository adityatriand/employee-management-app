<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter as BaseJsonFormatter;

class JsonFormatter extends BaseJsonFormatter
{
    public function __construct()
    {
        parent::__construct();
        $this->setJsonFlags(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function format(array $record): string
    {
        return parent::format($record);
    }
}

