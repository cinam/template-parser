<?php

namespace Cinam\TemplateParser\Tests;

use Cinam\TemplateParser\ConditionsParser;

class ConditionsParserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConditionsParser
     */
    protected $parser;

    public function setUp()
    {
        parent::setUp();

        $this->parser = new ConditionsParser();
    }

    public function tearDown()
    {
        unset($this->parser);
    }

    public function testNoIfs()
    {
        $text = 'begin middle end';
        $this->assertEquals('begin middle end', $this->parser->parse($text));
    }

    /**
     * @dataProvider providerOneLevelIfs
     */
    public function testOneLevelIfs($input, $expected)
    {
        $this->assertEquals($expected, $this->parser->parse($input));
    }

    public function providerOneLevelIfs()
    {
        return [
            // one if
            ['begin [IF 1]one[ENDIF] end', 'begin one end'],
            ['begin [IF 0]one[ENDIF] end', 'begin  end'],

            // two ifs
            ['begin [IF 1]one[ENDIF] middle [IF 1]two[ENDIF] end', 'begin one middle two end'],
            ['begin [IF 1]one[ENDIF] middle [IF 0]two[ENDIF] end', 'begin one middle  end'],
            ['begin [IF 0]one[ENDIF] middle [IF 1]two[ENDIF] end', 'begin  middle two end'],
            ['begin [IF 0]one[ENDIF] middle [IF 0]two[ENDIF] end', 'begin  middle  end'],

            // three ifs
            ['begin [IF 1]one[ENDIF] middle [IF 1]two[ENDIF] middle2 [IF 1]three[ENDIF] end', 'begin one middle two middle2 three end'],
            ['begin [IF 1]one[ENDIF] middle [IF 0]two[ENDIF] middle2 [IF 1]three[ENDIF] end', 'begin one middle  middle2 three end'],
            ['begin [IF 0]one[ENDIF] middle [IF 1]two[ENDIF] middle2 [IF 0]three[ENDIF] end', 'begin  middle two middle2  end'],
        ];
    }
}

