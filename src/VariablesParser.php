<?php

namespace Cinam\TemplateParser;

class VariablesParser
{

    const MAX_VARIABLE_NAME_LENGTH = 50;

    public function parseStandard($text, array $variables)
    {
        $text = $this->parseStandardVariables($text, $variables);
        $text = $this->parseConditionVariables($text, $variables);

        return $text;
    }

    public function parseBlocks($text, array $variables, array $blockVariables)
    {
    }

    private function parseStandardVariables($text, array $variables)
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

    private function parseConditionVariables($text, array $variables)
    {
        $result = '';

        $noMoreIfs = false;
        $currentIndex = 0;
        while (!$noMoreIfs) {
            $ifStart = strpos($text, '[IF ', $currentIndex);
            $ifEnd = strpos($text, ']', $ifStart);
            if ($ifStart !== false && $ifEnd !== false) {
                $conditionString = substr($text, $ifStart + 4, $ifEnd - $ifStart - 4);
                $result .= '[IF ' . $this->replaceVariablesInCondition($conditionString, $variables) . ']';
                $currentIndex = $ifEnd + 1;
            } else {
                $result .= substr($text, $currentIndex);
                $noMoreIfs = true;
            }
        }

        return $result;

    }

    private function isCorrectVariableName($text)
    {
       return (strlen($text) <= self::MAX_VARIABLE_NAME_LENGTH && preg_match('#^[[:alnum:]_]+$#', $text));
    }

    private function replaceVariablesInCondition($text, array $variables)
    {
        $result = '';

        $parts = preg_split('#\s#', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (count($parts) !== 1 && count($parts) !== 3) {
            return $text;
        }

        if ($this->isCorrectVariableName($parts[0])) {
            if (array_key_exists($parts[0], $variables)) {
                $result .= $variables[$parts[0]];
            } else {
                $result .= $parts[0];
            }
        } else {
            $result .= $parts[0];
        }

        if (count($parts) === 3) {
            $result .= ' ' . $parts[1] . ' ';

            if ($this->isCorrectVariableName($parts[2])) {
                if (array_key_exists($parts[2], $variables)) {
                    $result .= $variables[$parts[2]];
                } else {
                    $result .= $parts[2];
                }
            } else {
                $result .= $parts[2];
            }
        }

        return $result;
    }
}
