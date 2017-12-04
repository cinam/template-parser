<?php

namespace Cinam\TemplateParser\Exception;

class MissingVariableException extends \Exception
{

    private $variableName;

    public function __construct($variableName)
    {
        $this->variableName = $variableName;
        parent::__construct(sprintf('Variable %s is missing', $variableName));
    }

    public function getVariableName()
    {
        return $this->variableName;
    }
}

