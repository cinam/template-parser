<?php

namespace Cinam\TemplateParser\Tests;

use Cinam\TemplateParser\ConditionsParser;

class ConditionsParserTest extends \PHPUnit_Framework_TestCase
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

    public function testSimpleIf()
    {
        $text = 'begin [IF 1]one[ENDIF] end';
        $this->assertEquals('begin one end', $this->parser->parse($text));

        $text2 = 'begin [IF 0]two[ENDIF] end';
        $this->assertEquals('begin end', $this->parser->parse($text2));
    }
}

