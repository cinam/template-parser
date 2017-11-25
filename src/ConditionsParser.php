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

        $ifStarts = $this->getIfStarts($text);
        $ifEnds = $this->getIfEnds($text);
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

        return $result;
    }

    private function getIfStarts($text)
    {
        $result = [];
        $offset = 0;
        $stop = false;
        while (!$stop) {
            $position = strpos($text, '[IF ', $offset);
            if ($position !== false) {
                $result[] = $position;
                $offset = $position + 1;
            } else {
                $stop = true;
            }
        }

        return $result;
    }

    private function getIfEnds($text)
    {
        $result = [];
        $offset = 0;
        $stop = false;
        while (!$stop) {
            $position = strpos($text, '[ENDIF]', $offset);
            if ($position !== false) {
                $result[] = $position + 6;
                $offset = $position + 1;
            } else {
                $stop = true;
            }
        }

        return $result;
    }

    private function validateStartsAndEnds(array $starts, array $ends)
    {
        // todo
    }
}