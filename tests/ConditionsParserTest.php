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
        $text = '[if 1]foo[endif]';
        $this->assertEquals($text, $this->parser->parse($text));
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

    /**
     * @dataProvider providerOneLevelBorderCases
     */
    public function testOneLevelBorderCases($input, $expected)
    {
        $this->assertEquals($expected, $this->parser->parse($input));
    }

    public function providerOneLevelBorderCases()
    {
        return [
            ['[IF 1]one[ENDIF]', 'one'],
            ['[IF 0]one[ENDIF]', ''],
            ['[IF 1]one[ENDIF][IF 1] two[ENDIF][IF 0] three[ENDIF]', 'one two'],
        ];
    }

    public function testEmptyIf()
    {
        $this->assertEquals('foo  bar', $this->parser->parse('foo [IF 1][ENDIF] bar'));
        $this->assertEquals('foo  bar', $this->parser->parse('foo [IF 0][ENDIF] bar'));
    }

    /**
     * @dataProvider providerTwoLevels
     */
    public function testTwoLevels($input, $expected)
    {
        $this->assertEquals($expected, $this->parser->parse($input));
    }

    public function providerTwoLevels()
    {
        return [
            ['[IF 1]foo [IF 1]bar[ENDIF][ENDIF]', 'foo bar'],
            ['[IF 1]foo [IF 0]bar[ENDIF][ENDIF]', 'foo '],
            ['[IF 0]foo [IF 1]bar[ENDIF][ENDIF]', ''],
            ['[IF 0]foo [IF 0]bar[ENDIF][ENDIF]', ''],
        ];
    }

    /**
     * @dataProvider providerThreeLevels
     */
    public function testThreeLevels($input, $expected)
    {
        $this->assertEquals($expected, $this->parser->parse($input));
    }

    public function providerThreeLevels()
    {
        return [
            ['[IF 1]foo [IF 1]bar [IF 1]baz[ENDIF][ENDIF][ENDIF]', 'foo bar baz'],
            ['[IF 1]foo [IF 1]bar [IF 0]baz[ENDIF][ENDIF][ENDIF]', 'foo bar '],
            ['[IF 1]foo [IF 0]bar [IF 1]baz[ENDIF][ENDIF][ENDIF]', 'foo '],
            ['[IF 0]foo [IF 1]bar [IF 1]baz[ENDIF][ENDIF][ENDIF]', ''],
        ];
    }

    /**
     * @dataProvider providerSyntaxError
     * @expectedException Cinam\TemplateParser\Exception\InvalidSyntaxException
     */
    public function testSyntaxError($input)
    {
        $this->parser->parse($input);
    }

    public function providerSyntaxError()
    {
        return [
            ['[IF 1]foo'],
            ['foo[ENDIF]'],
            ['[IF 1]foo[ENDIF] [ENDIF]'],
            ['[IF 1]foo[ENDIF] [IF 1]'],
            ['[IF 1] [IF 1]foo[ENDIF]'],
            ['[ENDIF] [IF 1]foo[ENDIF]'],
            ['[if 1]foo[ENDIF]'],
            ['[IF 1]foo[endif]'],
        ];
    }

    /**
     * @dataProvider providerElse
     */
    public function testElse($input, $expected)
    {
        $this->assertEquals($expected, $this->parser->parse($input));
    }

    public function providerElse()
    {
        return [
            // basic
            ['[IF 1]one[ELSE]two[ENDIF]', 'one'],
            ['[IF 0]one[ELSE]two[ENDIF]', 'two'],

            // two elses, one level
            ['[IF 1]one[ELSE]two[ENDIF] [IF 1]one[ELSE]two[ENDIF]', 'one one'],
            ['[IF 1]one[ELSE]two[ENDIF] [IF 0]one[ELSE]two[ENDIF]', 'one two'],
        ];
    }
}

