<?php

namespace Cinam\TemplateParser\Exception;

class InvalidSyntaxException extends \Exception
{

    public function __construct()
    {
        parent::__construct('Invalid syntax');
    }
}

