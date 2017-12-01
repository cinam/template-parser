<?php

namespace Cinam\TemplateParser\Exception;

class InvalidEndSuffixException extends \Exception
{

    public function __construct()
    {
        parent::__construct('Invalid END suffix');
    }
}

