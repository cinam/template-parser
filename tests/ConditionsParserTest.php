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

    public function testSimpleIf()
    {
        $text = 'begin [IF 1]one[ENDIF] end';
        $this->assertEquals('begin one end', $this->parser->parse($text));

        $text2 = 'begin [IF 0]two[ENDIF] end';
        $this->assertEquals('begin  end', $this->parser->parse($text2));
    }

    public function testTwoIfs()
    {
        $text = 'begin [IF 1]one[ENDIF] middle [IF 1]two[ENDIF] end';
        $this->assertEquals('begin one middle two end', $this->parser->parse($text));

        $text = 'begin [IF 1]one[ENDIF] middle [IF 0]two[ENDIF] end';
        $this->assertEquals('begin one middle  end', $this->parser->parse($text));

        $text = 'begin [IF 0]one[ENDIF] middle [IF 1]two[ENDIF] end';
        $this->assertEquals('begin  middle two end', $this->parser->parse($text));
    
        $text = 'begin [IF 0]one[ENDIF] middle [IF 0]two[ENDIF] end';
        $this->assertEquals('begin  middle  end', $this->parser->parse($text));
    }
}

