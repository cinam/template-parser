<?php

namespace Cinam\TemplateParser;

use Cinam\TemplateParser\Exception\InvalidSyntaxException;

class ConditionsParser
{

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
            $elseIndex = null;
            foreach ($elseStarts as $elseStart) {
                if ($ifStarts[$i] < $elseStart && $elseStart < $ifEnds[$i]) {
                    if (isset($elseIndex)) {
                        // there already is an else -> too many elses!
                        throw new InvalidSyntaxException();
                    }

                    $elseIndex = $elseStart - $ifStarts[$i];
                }
            }

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
        $endifPosition = strlen($text) - 7; // // strlen('[ENDIF'])

        $condition = substr($text, $ifPosition + 4, $ifContentPosition - 1 - $ifPosition - 4);

        $result = '';
        if ($condition) {
            // content ending with position of else or endif
            if ($elseIndex !== null) {
                $result = substr($text, $ifContentPosition, $elseIndex - $ifContentPosition);
            } else {
                $result = substr($text, $ifContentPosition, -7);
            }
        } else {
            if ($elseIndex !== null) {
                $result = substr($text, $elseIndex + 6, -7);
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
                throw new InvalidSyntaxException();
            }
        }

        if ($currentDepth !== 0) {
            throw new InvalidSyntaxException();
        }

        return [
            $starts,
            $elses,
            $ends,
        ];
    }
}
