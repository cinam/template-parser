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
            ['{var1}', ['var1' => 1], '1'],
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

            ['foo [IF var1] bar [IF var2] baz', [], 'foo [IF var1] bar [IF var2] baz'],
        ];
    }

    /**
     * @dataProvider providerStringVariableInConditions
     */
    public function testStringVariableInConditions($input, $variables, $expected)
    {
        $this->assertEquals($expected, $this->parser->parseStandard($input, $variables));
    }

    public function providerStringVariableInConditions()
    {
        return [
            ['[IF var1]', ['var1' => 'foo'], '[IF foo]'],
            ['[IF var1 == 5]', ['var1' => 'foo'], '[IF foo == 5]'],
            ['[IF 5 == var1]', ['var1' => 'foo'], '[IF 5 == foo]'],
            ['[IF var1 == var1]', ['var1' => 'foo'], '[IF foo == foo]'],
            ['[IF var1 == var2]', ['var1' => 'foo', 'var2' => 'bar'], '[IF foo == bar]'],

            ['[IF var1]', ['var1' => 'foo bar'], '[IF foo bar]'],
            ['[IF var1]', ['var1' => 'foo bar  baz'], '[IF foo bar  baz]'],
            ['[IF var1 == 5]', ['var1' => 'foo bar'], '[IF foo bar == 5]'],
            ['[IF 5 == var1]', ['var1' => 'foo bar'], '[IF 5 == foo bar]'],
        ];
    }

    /**
     * @dataProvider providerSimpleTable
     */
    public function testSimpleTable($input, $variables, $expected)
    {
        $this->assertEquals($expected, $this->parser->parseTables($input, $variables));
    }

    public function providerSimpleTable()
    {
        return [
            // no tables
            ['begin end', [], 'begin end'],

            // one table without variables
            ['[START table1][END]', ['table1' => []], ''],
            ['begin [START table1]foo [END] end', ['table1' => []], 'begin  end'],
            ['begin [START table1]foo [END] end', ['table1' => [[], [], []]], 'begin foo foo foo  end'],

            // one table with variables
            ['begin [START table1]{var1}[END] end', ['table1' => [['var1' => 1], ['var1' => 2], ['var1' => 3]]], 'begin 123 end'],
            ['begin [START table1]{var1}foo{var1} [END] end', ['table1' => [['var1' => 1], ['var1' => 2], ['var1' => 3]]], 'begin 1foo1 2foo2 3foo3  end'],

            // with conditions
            ['begin [START table1][IF cond]yes[ENDIF][END] end', ['table1' => [['cond' => 1], ['cond' => 0]]], 'begin [IF 1]yes[ENDIF][IF 0]yes[ENDIF] end'],
            ['[IF 1][ENDIF][START table1][END]', ['table1' => []], '[IF 1][ENDIF]'],

            // two tables
            [
                'begin [START table1]{var1}[END] middle [START table2]{var1}[END] end',
                ['table1' => [['var1' => 1], ['var1' => 2]], 'table2' => [['var1' => 3], ['var1' => 4]]],
                'begin 12 middle 34 end'
            ],

            // not parsing variables outside tables
            ['{var1} [START table1]{var1}[END] end', ['var1' => 0, 'table1' => [['var1' => 1], ['var1' => 2], ['var1' => 3]]], '{var1} 123 end'],
        ];
    }

    /**
     * @dataProvider providerTableSyntaxError
     * @expectedException Cinam\TemplateParser\Exception\InvalidSyntaxException
     */
    public function testTableSyntaxError($input)
    {
        $this->parser->parseTables($input, []);
    }

    public function providerTableSyntaxError()
    {
        return [
            ['[START table1]'],
            ['[START table1][END ]'],
            ['[START table1][END foo]'],
        ];
    }

    /**
     * @dataProvider providerNestedTables
     */
    public function testNestedTables($input, $expected)
    {
        $variables = [
            'var1' => 0,
            'table1' => [
                [
                    'var1' => 1,
                    'var2' => 'w',
                    'table1' => [
                        [
                            'var1' => 'a',
                        ],
                        [
                            'var1' => 'b',
                        ],
                    ],
                    'table2' => [
                        [
                            'var1' => 'c',
                        ],
                        [
                            'var1' => 'd',
                        ],
                    ],
                ],
                [
                    'var1' => 2,
                    'var2' => 'x',
                    'table1' => [
                        [
                            'var1' => 'e',
                        ],
                        [
                            'var1' => 'f',
                        ],
                    ],
                    'table2' => [
                        [
                            'var1' => 'g',
                        ],
                        [
                            'var1' => 'h',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $this->parser->parseTables($input, $variables));
    }

    public function providerNestedTables()
    {
        return [
            ['[START table1]{var1}[START table2]{var1}[END][END]', '1cd2gh'],
            ['[START table1]{var1}[START table1]{var1}[END][END]', '1ab2ef'],
            ['[START table1]{var1}[START table2]{var1}[END][END] [START table1]{var1}[START table2]{var1}[END][END]', '1cd2gh 1cd2gh'],
            ['[START table1]{var1}[START table2]{var1}[END][END] [START table1]{var1}[START table1]{var1}[END][END]', '1cd2gh 1ab2ef'],

            [
                '[IF var1][ENDIF][START table1][IF var1][ENDIF][START table1][IF var1][ENDIF][END][END]',
                '[IF var1][ENDIF][IF 1][ENDIF][IF a][ENDIF][IF b][ENDIF][IF 2][ENDIF][IF e][ENDIF][IF f][ENDIF]'
            ],
        ];
    }

    /**
     * @dataProvider providerStandardMissingVariable
     * @expectedException Cinam\TemplateParser\Exception\MissingVariableException
     */
    public function testStandardMissingVariable($input, $variables)
    {
        $this->parser->parseStandard($input, $variables);
    }

    public function providerStandardMissingVariable()
    {
        return [
            ['{var1}', []],
            ['{var1}', ['VAR1']],
        ];
    }

    /**
     * @dataProvider providerTableMissingVariable
     * @expectedException Cinam\TemplateParser\Exception\MissingVariableException
     */
    public function testTableMissingVariable($input, $variables)
    {
        $this->parser->parseTables($input, $variables);
    }

    public function providerTableMissingVariable()
    {
        return [
            ['[START table1][END]', []],
            ['[START table1][END]', ['TABLE1' => []]],
        ];
    }

    /**
     * @expectedException Cinam\TemplateParser\Exception\InvalidTableVariableException
     */
    public function testTableInvalidVariable()
    {
        $this->parser->parseTables('[START table1][END]', ['table1' => 'foo']);
    }
}

