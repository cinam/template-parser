<?php

namespace Cinam\TemplateParser;

use Cinam\TemplateParser\Exception\InvalidSyntaxException;

class ConditionsParser
{

    const OPERATORS = ['==', '<', '>', '<=', '>=', '!=', '<>'];

    public function parse($text)
    {
        // can it be removed?
        if (strpos($text, '[IF ') === false && strpos($text, '[ENDIF]') === false) {
            return $text;
        }

        $ifIndexes = $this->getIfIndexesForCurrentDepth($text);
        $ifStarts = $ifIndexes[0];
        $elseStarts = $ifIndexes[1];
        $ifEnds = $ifIndexes[2];

        $result = substr($text, 0, $ifStarts[0]);

        $cnt = count($ifStarts);
        for ($i = 0; $i < $cnt; $i++) {
            // search for an else between current if and endif
            $elseIndex = $this->getElseIndex($elseStarts, $ifStarts[$i], $ifEnds[$i]);

            $result .= $this->getConditionResult(substr($text, $ifStarts[$i], $ifEnds[$i] - $ifStarts[$i] + 1), $elseIndex);
            $nextIf = (isset($ifStarts[$i + 1]) ? $ifStarts[$i + 1] : strlen($text) + 1);
            $result .= substr($text, $ifEnds[$i] + 1, $nextIf - $ifEnds[$i] - 1);
        }

        return $result;
    }

    private function getConditionResult($text, $elseIndex)
    {
        $ifPosition = 0;
        $ifContentPosition = strpos($text, ']', $ifPosition + 1) + 1;
        $endifPosition = strrpos($text, '[ENDIF]'); // strRpos

        // strlen('[IF ') = 4
        $condition = substr($text, $ifPosition + 4, $ifContentPosition - 1 - $ifPosition - 4);

        $result = '';
        if ($this->evaluateCondition($condition)) {
            // content ending with position of else or endif
            if ($elseIndex !== null) {
                $result = substr($text, $ifContentPosition, $elseIndex - $ifContentPosition);
            } else {
                $result = substr($text, $ifContentPosition, $endifPosition - $ifContentPosition);
            }
        } else {
            if ($elseIndex !== null) {
                // strlen('[ELSE]') = 6
                $result = substr($text, $elseIndex + 6, $endifPosition - $elseIndex - 6);
            }
        }

        return $this->parse($result);
    }

    private function getIfIndexesForCurrentDepth($text)
    {
        $starts = [];
        $elses = [];
        $ends = [];

        $currentDepth = 0;
        $cnt = strlen($text);
        for ($i = 0; $i < $cnt; $i++) {
            if (substr($text, $i, 4) === '[IF ') {
                if ($currentDepth === 0) {
                    $starts[] = $i;
                }

                ++ $currentDepth;
            } elseif (substr($text, $i, 7) === '[ENDIF]') {
                -- $currentDepth;

                if ($currentDepth === 0) {
                    $ends[] = $i + 6; // last letter of "[ENDIF]"
                }
            } elseif (substr($text, $i, 6) === '[ELSE]') {
                if ($currentDepth === 1) {
                    $elses[] = $i;
                }
            }

            if ($currentDepth < 0) {
                throw new InvalidSyntaxException($text);
            }
        }

        if ($currentDepth !== 0) {
            throw new InvalidSyntaxException($text);
        }

        return [
            $starts,
            $elses,
            $ends,
        ];
    }

    private function getElseIndex(array $elseStarts, $ifStart, $ifEnd)
    {
        $elseIndex = null;
        foreach ($elseStarts as $elseStart) {
            if ($ifStart < $elseStart && $elseStart < $ifEnd) {
                if (isset($elseIndex)) {
                    // there already is an else -> too many elses in one if!
                    throw new InvalidSyntaxException(''); // todo another exception
                }

                $elseIndex = $elseStart - $ifStart;
            }
        }

        return $elseIndex;
    }

    private function evaluateCondition($text)
    {
        $parts = preg_split('#\s#', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (count($parts) === 1) {
            return (strtolower($parts[0]) !== 'null' && (boolean) $parts[0] === true);
        } elseif (in_array(count($parts), [0, 2])) {
            throw new InvalidSyntaxException($text);
        } else {
            // 3 parts or more
            $operatorIndex = $this->findOperatorIndex($parts, $text);
            $operator = $parts[$operatorIndex];

            $left = $parts[0];
            for ($i = 1; $i < $operatorIndex; $i++) {
                $left .= ' ' . $parts[$i];
            }

            $right = $parts[$operatorIndex + 1];
            for ($i = $operatorIndex + 2; $i < count($parts); $i++) {
                $right .= ' ' . $parts[$i];
            }

            $var1 = $this->createVariable($left);
            $var2 = $this->createVariable($right);

            if ($operator == '==') {
                $operator = '===';
            } elseif ($operator == '!=') {
                $operator = '!==';
            }

            // todo remove "eval"
            return eval(sprintf('return (%s %s %s);', $var1, $operator, $var2));
        }
    }

    private function findOperatorIndex($parts, $text)
    {
        $result = null;

        // it cannot be first or last
        for ($i = 1; $i < count($parts) - 1; $i++) {
            if (in_array($parts[$i], self::OPERATORS)) {
                if ($result !== null) {
                    // more than one operator
                    throw new InvalidSyntaxException($text);
                } else {
                    $result = $i;
                }
            }
        }

        if ($result === null) {
            // operator not found
            throw new InvalidSyntaxException($text);
        }

        return $result;
    }

    private function createVariable($value)
    {
        if (is_numeric($value)) {
            $result = $value;
        } elseif (strtolower($value) === 'null') {
            $result = 'NULL';
        } else {
            $result = "'" . str_replace("'", "\\'", $value) . "'";
        }

        return $result;
    }
}
