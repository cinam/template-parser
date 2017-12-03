<?php

namespace Cinam\TemplateParser\Exception;

class InvalidEndifSuffixException extends \Exception
{

    private $context;

    public function __construct($context)
    {
        $this->context = $context;
        parent::__construct('Invalid ENDIF suffix');
    }

    public function getContext()
    {
        return $this->context;
    }
}

