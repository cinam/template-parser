<?php

namespace Cinam\TemplateParser;

class VariablesParser
{

    public function parseStandard($text, array $variables)
    {
        $result = '';

        $noMoreVariables = false;
        $currentIndex = 0;
        while (!$noMoreVariables) {
            $variableStart = strpos($text, '{', $currentIndex);
            $variableEnd = strpos($text, '}', $variableStart);
            if ($variableStart !== false && $variableEnd !== false) {
                $variableName = substr($text, $variableStart + 1, $variableEnd - $variableStart - 1);
                if (array_key_exists($variableName, $variables)) {
                    $result .= substr($text, $currentIndex, $variableStart - $currentIndex) . $variables[$variableName];
                } else {
                    $result .= substr($text, $currentIndex, $variableEnd - $currentIndex);
                }

                $currentIndex = $variableEnd + 1;
            } else {
                $result .= substr($text, $currentIndex);
                $noMoreVariables = true;
            }
        }

        return $result;
    }

    public function parseBlocks($text, array $variables, array $blockVariables)
    {
    }
}
