<?php

namespace Cinam\TemplateParser\Exception;

class InvalidTableVariableException extends \Exception
{

    private $variableName;

    public function __construct($variableName)
    {
        parent::__construct(sprintf('Variable %s must be an array', $variableName));
    }

    public function getVariableName()
    {
        return $this->variableName;
    }
}

