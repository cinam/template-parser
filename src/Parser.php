<?php

namespace Cinam\TemplateParser;

use Cinam\TemplateParser\VariablesParser;
use Cinam\TemplateParser\ConditionsParser;

class Parser
{

    /**
     * @var VariablesParser
     */
    private $variablesParser;

    /**
     * @var ConditionsParser
     */
    private $conditionsParser;

    public function __construct(VariablesParser $variablesParser, ConditionsParser $conditionsParser)
    {
        $this->variablesParser = $variablesParser;
        $this->conditionsParser = $conditionsParser;
    }

    public function parse($text, array $variables)
    {
        $text = $this->variablesParser->parseTables($text, $variables);
        $text = $this->variablesParser->parseStandard($text, $variables);
        $text = $this->conditionsParser->parse($text);

        return $text;
    }
}
