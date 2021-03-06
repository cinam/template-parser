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
            ['[IF 1]foo[ELSE]bar[ELSE]baz[ENDIF]'],
            ['[IF 1 <]foo[ENDIF]'],
            ['[IF 1 2 <]foo[ENDIF]'],
            ['[IF < 1]foo[ENDIF]'],
            ['[IF < 1 2]foo[ENDIF]'],
            ['[IF 1 < 2 == 3]foo[ENDIF]'],
            ['[IF <]foo[ENDIF]'],
            ['[IF ==]foo[ENDIF]'],

            ['[IF 1]foo[ENDIF ]'],
            ['[IF 1]foo[ENDIF bar]'],
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

            // multiple levels
            ['[IF 1]one[IF 1]two[ELSE]three[ENDIF][ELSE]four[IF 1]five[ELSE][IF 1]six[ELSE]seven[ENDIF][ENDIF][ENDIF]', 'onetwo'],
            ['[IF 1]one[IF 0]two[ELSE]three[ENDIF][ELSE]four[IF 1]five[ELSE][IF 1]six[ELSE]seven[ENDIF][ENDIF][ENDIF]', 'onethree'],
            ['[IF 0]one[IF 0]two[ELSE]three[ENDIF][ELSE]four[IF 1]five[ELSE][IF 1]six[ELSE]seven[ENDIF][ENDIF][ENDIF]', 'fourfive'],
            ['[IF 0]one[IF 0]two[ELSE]three[ENDIF][ELSE]four[IF 0]five[ELSE][IF 1]six[ELSE]seven[ENDIF][ENDIF][ENDIF]', 'foursix'],
            ['[IF 0]one[IF 0]two[ELSE]three[ENDIF][ELSE]four[IF 0]five[ELSE][IF 0]six[ELSE]seven[ENDIF][ENDIF][ENDIF]', 'fourseven'],

            // not strict "[ELSE]" is treated as normal text
            ['[IF 1]one ELSE two[ENDIF]', 'one ELSE two'],
            ['[IF 1]one [ELSE two[ENDIF]', 'one [ELSE two'],
            ['[IF 1]one [else] two[ENDIF]', 'one [else] two'],
            ['[IF 1]one [ElSE] two[ENDIF]', 'one [ElSE] two'],
        ];
    }

    /**
     * @dataProvider providerSimpleCondition
     */
    public function testSimpleCondition($input, $expected)
    {
        $this->assertEquals($expected, $this->parser->parse($input));
    }

    public function providerSimpleCondition()
    {
        return [
            ['[IF 1]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF 0]yes[ELSE]no[ENDIF]', 'no'],
            ['[IF  1]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF  0]yes[ELSE]no[ENDIF]', 'no'],

            ['[IF 1 == 1]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF 1 == 2]yes[ELSE]no[ENDIF]', 'no'],
            ['[IF 2 > 1]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF 1 > 2]yes[ELSE]no[ENDIF]', 'no'],
            ['[IF 1 > 1]yes[ELSE]no[ENDIF]', 'no'],
            ['[IF 1 < 2]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF 2 < 1]yes[ELSE]no[ENDIF]', 'no'],
            ['[IF 1 < 1]yes[ELSE]no[ENDIF]', 'no'],
            ['[IF 1 <= 2]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF 2 <= 2]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF 3 <= 2]yes[ELSE]no[ENDIF]', 'no'],
            ['[IF 2 >= 1]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF 2 >= 2]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF 2 >= 3]yes[ELSE]no[ENDIF]', 'no'],
            ['[IF 2 != 1]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF 1 != 1]yes[ELSE]no[ENDIF]', 'no'],
            ['[IF 2 <> 1]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF 1 <> 1]yes[ELSE]no[ENDIF]', 'no'],

            ['[IF  1   ==  1 ]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF  1   ==  2 ]yes[ELSE]no[ENDIF]', 'no'],

            ['[IF 1 2]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF 1 0]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF 0 2]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF 0 0]yes[ELSE]no[ENDIF]', 'yes'],
        ];
    }

    /**
     * @dataProvider providerStringCondition
     */
    public function testStringCondition($input, $expected)
    {
        $this->assertEquals($expected, $this->parser->parse($input));
    }

    public function providerStringCondition()
    {
        return [
            ['[IF foo]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF foo != null]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF foo == null]yes[ELSE]no[ENDIF]', 'no'],
            ['[IF foo bar != null]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF null != foo bar]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF foo bar baz != null]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF foo bar == baz]yes[ELSE]no[ENDIF]', 'no'],
            ['[IF foo bar == foo bar]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF foo bar]yes[ELSE]no[ENDIF]', 'yes'],
        ];
    }

    /**
     * @dataProvider providerNullCondition
     */
    public function testNullCondition($input, $expected)
    {
        $this->assertEquals($expected, $this->parser->parse($input));
    }

    public function providerNullCondition()
    {
        return [
            ['[IF null]yes[ELSE]no[ENDIF]', 'no'],
            ['[IF NULL]yes[ELSE]no[ENDIF]', 'no'],
            ['[IF null == null]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF null == NULL]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF NULL == null]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF NULL == NULL]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF null != null]yes[ELSE]no[ENDIF]', 'no'],
            ['[IF null != NULL]yes[ELSE]no[ENDIF]', 'no'],
            ['[IF NULL != null]yes[ELSE]no[ENDIF]', 'no'],
            ['[IF NULL != NULL]yes[ELSE]no[ENDIF]', 'no'],
            ['[IF 0 == NULL]yes[ELSE]no[ENDIF]', 'no'],
            ['[IF 0 != NULL]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF 08 != NULL]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF 0xG != NULL]yes[ELSE]no[ENDIF]', 'yes'],
            ['[IF -1 != NULL]yes[ELSE]no[ENDIF]', 'yes'],
        ];
    }
}

