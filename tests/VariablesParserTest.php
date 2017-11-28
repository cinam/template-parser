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

    /**
     * @dataProvider providerStandardVariableInConditions
     */
    public function testStandardVariableInConditions($input, $variables, $expected)
    {
        $this->assertEquals($expected, $this->parser->parseStandard($input, $variables));
    }

    public function providerStandardVariableInConditions()
    {
        return [
            ['[IF 1]', ['var1' => 1], '[IF 1]'],
            ['[IF var1]', ['var1' => 1], '[IF 1]'],
            ['[IF var1 == 5]', ['var1' => 1], '[IF 1 == 5]'],
            ['[IF 5 == var1]', ['var1' => 1], '[IF 5 == 1]'],
            ['[IF var1 == var1]', ['var1' => 1], '[IF 1 == 1]'],
            ['[IF var1 == var2]', ['var1' => 1, 'var2' => 2], '[IF 1 == 2]'],
            ['[IF var1]', [], '[IF var1]'],
        ];
    }

    /**
     * @dataProvider providerTable
     */
    public function testTable($input, $variables, $expected)
    {
        $this->assertEquals($expected, $this->parser->parseTables($input, $variables));
    }

    public function providerTable()
    {
        return [
            ['begin [START table1]foo [END] end', ['table1' => []], 'begin  end'],
            ['begin [START table1]foo [END] end', ['table1' => [[], [], []]], 'begin foo foo foo  end'],

            ['begin [START table1]{var1}[END] end', ['table1' => [['var1' => 1], ['var1' => 2], ['var1' => 3]]], 'begin 123 end'],
            ['begin [START table1]{var1}foo{var1} [END] end', ['table1' => [['var1' => 1], ['var1' => 2], ['var1' => 3]]], 'begin 1foo1 2foo2 3foo3  end'],

            // with conditions
            ['begin [START table1][IF cond]yes[ENDIF][END] end', ['table1' => [['cond' => 1], ['cond' => 0]]], 'begin [IF 1]yes[ENDIF][IF 0]yes[ENDIF] end'],

            // two tables
            [
                'begin [START table1]{var1}[END] middle [START table2]{var1}[END] end',
                ['table1' => [['var1' => 1], ['var1' => 2]], 'table2' => [['var1' => 3], ['var1' => 4]]],
                'begin 12 middle 34 end'
            ],

            // nested tables
            [
                '[START table1]{var1}[START table1]{var1}[END][END]',
                ['table1' => [
                    [
                        'var1' => 1,
                        'table1' => [
                            [
                                'var1' => 'a',
                            ],
                            [
                                'var1' => 'b',
                            ],
                        ],
                    ],
                    [
                        'var1' => 2,
                        'table1' => [
                            [
                                'var1' => 'c',
                            ],
                            [
                                'var1' => 'd',
                            ],
                        ],
                    ],
                ]],
                '1ab2cd'
            ],
        ];
    }
}

