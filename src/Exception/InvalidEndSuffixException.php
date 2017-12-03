<?php

namespace Cinam\TemplateParser\Exception;

class InvalidEndSuffixException extends \Exception
{

    private $context;

    public function __construct($context)
    {
        $this->context = $context;
        parent::__construct('Invalid END suffix');
    }

    public function getContext()
    {
        return $this->context;
    }
}
