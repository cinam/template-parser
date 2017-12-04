<?php

namespace Cinam\TemplateParser\Tests;

use Cinam\TemplateParser\Parser;

class ParserTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var Parser
     */
    protected $parser;

    public function setUp()
    {
        parent::setUp();

        $this->parser = new Parser();
    }

    public function tearDown()
    {
        unset($this->parser);
    }

    public function testParseOverall()
    {
        $input = 'start {var1} [START table1] foo {var1} [IF var1 == 1] bar [ENDIF] baz [END] end';
        $variables = [
            'var1' => 555,
            'table1' => [
                ['var1' => 1],
                ['var1' => 2],
            ],
        ];

        $this->assertEquals('start 555  foo 1  bar  baz  foo 2  baz  end', $this->parser->parse($input, $variables));
    }
}

