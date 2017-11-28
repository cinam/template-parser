<?php

namespace Cinam\TemplateParser;

class VariablesParser
{

    const MAX_VARIABLE_NAME_LENGTH = 50;

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
                if ($this->isCorrectVariableName($variableName)) {
                    if (array_key_exists($variableName, $variables)) {
                        $result .= substr($text, $currentIndex, $variableStart - $currentIndex) . $variables[$variableName];
                    } else {
                        $result .= substr($text, $currentIndex, $variableEnd - $currentIndex);
                    }

                    $currentIndex = $variableEnd + 1;
                } else {
                    $result .= $text[$currentIndex];
                    ++ $currentIndex;
                }
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

    private function isCorrectVariableName($text)
    {
       return (strlen($text) <= self::MAX_VARIABLE_NAME_LENGTH && preg_match('#^[[:alnum:]_]+$#', $text));
    }
}
