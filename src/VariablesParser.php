<?php

namespace Cinam\TemplateParser;

use Cinam\TemplateParser\Exception\InvalidSyntaxException;
use Cinam\TemplateParser\Exception\MissingTableVariableException;
use Cinam\TemplateParser\Exception\InvalidTableVariableException;
use Cinam\TemplateParser\Exception\InvalidEndSuffixException;

class VariablesParser
{

    const MAX_IDENTIFIER_LENGTH = 50;

    const MAX_END_SUFFIX_LENGTH = 50;

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
                $result .= substr($text, $currentIndex, $ifStart - $currentIndex);
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
        $tableContentPosition = strpos($text, ']') + 1;
        $tableEndPosition = strrpos($text, '[END'); // strRpos
        $tableIdentifier = $this->getTableIdentifier($text, $variables);

        $result = '';
        foreach ($variables[$tableIdentifier] as $tableValues) {
            $textToParse = substr($text, $tableContentPosition, $tableEndPosition - $tableContentPosition);
            $mergedVariables = array_merge($variables, $tableValues);
            $textWithParsedTables = $this->parseTableVariablesWithConditions($textToParse, $mergedVariables);
            $result .= $this->parseStandard($textWithParsedTables, $mergedVariables);
        }

        return $result;
    }

    private function getTableIdentifier($text, array $variables)
    {
        // 7 = strlen('[TABLE ')
        $tableIdentifier = substr($text, 7, strpos($text, ']') - 7);
        if (!array_key_exists($tableIdentifier, $variables)) {
            throw new MissingTableVariableException($tableIdentifier);
        } elseif (!is_array($variables[$tableIdentifier])) {
            throw new InvalidTableVariableException($tableIdentifier);
        }

        return $tableIdentifier;
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
            } elseif (substr($text, $i, 5) === '[END ') {
                $endingPosition = strpos($text, ']', $i + 5);
                if ($endingPosition !== false) {
                    $suffix = substr($text, $i + 5, $endingPosition - $i - 5);
                    $this->checkEndSuffix($suffix);

                    -- $currentDepth;
                    if ($currentDepth === 0) {
                        $ends[] = $endingPosition; // last letter of "[END ...]"
                    }
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

    private function checkEndSuffix($text)
    {
        if (!(strlen($text) <= self::MAX_END_SUFFIX_LENGTH && preg_match('#^[[:alnum:]_]+$#', $text))) {
            throw new InvalidEndSuffixException();
        }
    }
}
