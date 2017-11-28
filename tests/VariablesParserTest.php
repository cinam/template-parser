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
}

