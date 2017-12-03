<?php

namespace Cinam\TemplateParser\Exception;

class InvalidSyntaxException extends \Exception
{

    private $context;

    public function __construct($context)
    {
        $this->context = $context;
        parent::__construct('Invalid syntax');
    }

    public function getContext()
    {
        return $this->context;
    }
}
