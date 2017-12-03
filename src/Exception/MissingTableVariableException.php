<?php

namespace Cinam\TemplateParser\Exception;

class MissingTableVariableException extends \Exception
{

    private $variableName;

    public function __construct($variableName)
    {
        parent::__construct(sprintf('Variable %s is missing', $variableName));
    }

    public function getVariableName()
    {
        return $this->variableName;
    }
}

