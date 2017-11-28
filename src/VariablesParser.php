<?php

namespace Cinam\TemplateParser;

use Cinam\TemplateParser\Exception\InvalidSyntaxException;
use Cinam\TemplateParser\Exception\MissingTableIdentifierException;
use Cinam\TemplateParser\Exception\TableVariableNotSetException;

class VariablesParser
{

    const MAX_IDENTIFIER_LENGTH = 50;

    public function parseStandard($text, array $variables)
    {
        $text = $this->parseStandardVariables($text, $variables);
        $text = $this->parseConditionVariables($text, $variables);

        return $text;
    }

    public function parseTables($text, array $variables)
    {
        $text = $this->parseTableVariablesWithConditions($text, $variables);

        return $text;
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
       return (strlen($text) <= self::MAX_IDENTIFIER_LENGTH && preg_match('#^[[:alnum:]_]+$#', $text));
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

    private function parseTableVariablesWithConditions($text, $variables)
    {
        $tableIndexes = $this->getTableIndexesForCurrentDepth($text);
        $tableStarts = $tableIndexes[0];
        $tableEnds = $tableIndexes[1];
        if (empty($tableStarts)) {
            return $text;
        }

        $result = substr($text, 0, $tableStarts[0]);

        $cnt = count($tableStarts);
        for ($i = 0; $i < $cnt; $i++) {
            // todo stop passing $variables over and over
            $result .= $this->parseTable(substr($text, $tableStarts[$i], $tableEnds[$i] - $tableStarts[$i] + 1), $variables);
            $nextTable = (isset($tableStarts[$i + 1]) ? $tableStarts[$i + 1] : strlen($text) + 1);
            $result .= substr($text, $tableEnds[$i] + 1, $nextTable - $tableEnds[$i] - 1);
        }

        return $result;
    }

    private function parseTable($text, array $variables)
    {
        $tableStartPosition = 0;
        $tableContentPosition = strpos($text, ']', $tableStartPosition + 1) + 1;
        $tableEndPosition = strlen($text) - 5; // strlen('[END'])

        $tableIdentifier = substr($text, $tableStartPosition + 7, $tableContentPosition - 1 - $tableStartPosition - 7);
        if (!array_key_exists($tableIdentifier, $variables)) {
            throw new MissingTableIdentifierException($tableIdentifier);
        } elseif (!is_array($variables[$tableIdentifier])) {
            throw new TableVariableNotSetException($tableIdentifier);
        }

        $result = '';
        foreach ($variables[$tableIdentifier] as $tableValues) {
            $result .= $this->parseStandard(substr($text, $tableContentPosition, $tableEndPosition - $tableContentPosition), $tableValues);
        }

        return $result;
    }

    private function getTableIndexesForCurrentDepth($text)
    {
        $starts = [];
        $ends = [];

        $currentDepth = 0;
        $cnt = strlen($text);
        for ($i = 0; $i < $cnt; $i++) {
            if (substr($text, $i, 7) === '[START ') {
                if ($currentDepth === 0) {
                    $starts[] = $i;
                }

                ++ $currentDepth;
            } elseif (substr($text, $i, 5) === '[END]') {
                -- $currentDepth;

                if ($currentDepth === 0) {
                    $ends[] = $i + 4; // last letter of "[END]"
                }
            }

            if ($currentDepth < 0) {
                throw new InvalidSyntaxException();
            }
        }

        if ($currentDepth !== 0) {
            throw new InvalidSyntaxException();
        }

        return [
            $starts,
            $ends,
        ];
    }
}
