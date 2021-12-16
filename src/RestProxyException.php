<?php

declare(strict_types=1);

namespace Dduers\PhpRestProxy;

use Exception;

class RestProxyException extends Exception
{
    public function __construct($message_, $value_ = 0, Exception $old_ = null)
    {
        parent::__construct($message_, $value_, $old_);
    }
}
