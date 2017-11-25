<?php

namespace Cinam\TemplateParser;

class ConditionsParser
{

    public function parse($text)
    {
        // can it be removed?
        if (strpos($text, '[IF ') === false) {
            return $text;
        }

        $ifIndexes = $this->getIfIndexesForCurrentDepth($text);
        $ifStarts = $ifIndexes[0];
        $ifEnds = $ifIndexes[1];
        $this->validateStartsAndEnds($ifStarts, $ifEnds);

        $result = substr($text, 0, $ifStarts[0]);

        $cnt = count($ifStarts);
        for ($i = 0; $i < $cnt; $i++) {
            $result .= $this->getConditionResult(substr($text, $ifStarts[$i], $ifEnds[$i] - $ifStarts[$i] + 1));
            $nextIf = (isset($ifStarts[$i + 1]) ? $ifStarts[$i + 1] : strlen($text) + 1);
            $result .= substr($text, $ifEnds[$i] + 1, $nextIf - $ifEnds[$i] - 1);
        }

        return $result;
    }

    private function getConditionResult($text)
    {
        $ifPosition = 0;
        $ifContentPosition = strpos($text, ']', $ifPosition + 1) + 1;
        $endifPosition = strlen($text) - 7;
        $endPosition = $endifPosition + 7; // strlen('[ENDIF'])

        $condition = substr($text, $ifPosition + 4, $ifContentPosition - 1 - $ifPosition - 4);

        $result = '';
        if ($condition) {
            $result = substr($text, $ifContentPosition, $endifPosition - $ifContentPosition);
        }

        return $this->parse($result);
    }

    private function getIfIndexesForCurrentDepth($text)
    {
        $starts = [];
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
            }

//            if ($currentDepth < 0) {
//                error
//            }
        }

//        if ($currentDepth !== 0) {
//            error
//        }

        return [
            $starts,
            $ends,
        ];
    }

    private function validateStartsAndEnds(array $starts, array $ends)
    {
        // todo
    }
}
