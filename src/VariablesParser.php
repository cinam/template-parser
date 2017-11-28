<?php

namespace Cinam\TemplateParser;

class VariablesParser
{

    public function parseStandard($text, array $variables)
    {
        $variableStart = strpos($text, '{');
        $variableEnd = strpos($text, '}', $variableStart);
        if ($variableStart !== false && $variableEnd !== false) {
            $variableName = substr($text, $variableStart + 1, $variableEnd - $variableStart - 1);
            if (array_key_exists($variableName, $variables)) {
                $text = substr_replace($text, $variables[$variableName], $variableStart, $variableEnd - $variableStart + 1);
            }
        }

        return $text;
    }

    public function parseBlocks($text, array $variables, array $blockVariables)
    {
    }
}
