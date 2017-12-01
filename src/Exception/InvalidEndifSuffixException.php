<?php

namespace Cinam\TemplateParser\Exception;

class InvalidEndifSuffixException extends \Exception
{

    public function __construct()
    {
        parent::__construct('Invalid ENDIF suffix');
    }
}

