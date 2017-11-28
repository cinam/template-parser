<?php

namespace Cinam\TemplateParser\Tests;

use Cinam\TemplateParser\VariablesParser;

class VariablesParserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var VariablesParser
     */
    protected $parser;

    public function setUp()
    {
        parent::setUp();

        $this->parser = new VariablesParser();
    }

    public function tearDown()
    {
        unset($this->parser);
    }

    public function testNoVariables()
    {
        $text = 'begin middle end';
        $this->assertEquals('begin middle end', $this->parser->parseStandard($text, ['var1 => 1']));
    }

    /**
     * @dataProvider providerStandard
     */
    public function testStandard($input, $variables, $expected)
    {
        $this->assertEquals($expected, $this->parser->parseStandard($input, $variables));
    }

    public function providerStandard()
    {
        return [
            ['begin {var1} end', ['var1' => 1], 'begin 1 end'],
            ['begin {var1} {var1} end', ['var1' => 1], 'begin 1 1 end'],
            ['begin {var1} {var2} end', ['var1' => 1, 'var2' => 2], 'begin 1 2 end'],
        ];
    }

    /**
     * @dataProvider providerStandardBorderCases
     */
    public function testStandardBorderCases($input, $variables, $expected)
    {
        $this->assertEquals($expected, $this->parser->parseStandard($input, $variables));
    }

    public function providerStandardBorderCases()
    {
        return [
            ['begin {var1}', ['var1' => 1], 'begin 1'],
            ['begin{var1}', ['var1' => 1], 'begin1'],
            ['{var1} end', ['var1' => 1], '1 end'],
            ['{var1}end', ['var1' => 1], '1end'],
            ['{var1}', ['var1' => 1], '1'],
            ['{var1}{var1}', ['var1' => 1], '11'],
            ['{var1}middle{var1}', ['var1' => 1], '1middle1'],
        ];
    }

    /**
     * @dataProvider providerStandardVariableCharacters
     */
    public function testStandardVariableCharacters($input, $variables, $expected)
    {
        $this->assertEquals($expected, $this->parser->parseStandard($input, $variables));
    }

    public function providerStandardVariableCharacters()
    {
        return [
            // printable characters
            ['begin {abcABC1_09} end', ['abcABC1_09' => 1], 'begin 1 end'],

            // invalid variable names
            ['begin {ab c} end', ['ab c' => 1], 'begin {ab c} end'],
            ['begin {ab#c} end', ['ab#c' => 1], 'begin {ab#c} end'],
            ['begin {ab;c} end', ['ab;c' => 1], 'begin {ab;c} end'],

            // {, } do not break the variable
            ['begin {{var1} end', ['var1' => 1], 'begin {1 end'],
            ['begin {var1}} end', ['var1' => 1], 'begin 1} end'],
        ];
    }
}

