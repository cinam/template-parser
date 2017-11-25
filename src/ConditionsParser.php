<?php

namespace Cinam\TemplateParser;

class ConditionsParser
{

    public function parse($text, array $values = [])
    {
        $ifPosition = strpos($text, '[IF ');
        $ifContentPosition = strpos($text, ']', $ifPosition + 1) + 1;
        $endifPosition = strpos($text, '[ENDIF]');
        $endPosition = $endifPosition + 7; // strlen('[ENDIF'])

        $condition = substr($text, $ifPosition + 4, $ifContentPosition - 1 - $ifPosition - 4);

        $result = substr($text, 0, $ifPosition);
        if ($condition) {
            $result .= substr($text, $ifContentPosition, $endifPosition - $ifContentPosition);
        }

        $result .= substr($text, $endPosition);

        return $result;
    }
}
