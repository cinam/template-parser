Template parser
==============

A simple text parser. It allows you to perform following text transformations:

 - replace placeholders with scalar variables,
 - replace placeholders from array variables using iteration,
 - process logical conditions.

There is no limit for nesting arrays.

## Usage

``` php
$parser = new \Cinam\TemplateParser\Parser();
$result = $parser->parse($text, $variables);
```

## Examples

### Example 1 - Simple variable
``` php
$text = 'Hello, {user}!';
$variables = [
    'user' => 'Peter',
];

$parser = new \Cinam\TemplateParser\Parser();
echo $parser->parse($text, $variables);
```

*Hello, Peter!*

### Example 2 - Condition
``` php
$text = <<<EOT
Your score: {score} points.
[IF score > highScore]
  Congratulations, it's the new high score!
[ENDIF]
EOT;
$parser = new \Cinam\TemplateParser\Parser();

echo $parser->parse($text, ['score' => 50, 'highScore' => 60]);
```
*Your score: 50 points.*
``` php
echo $parser->parse($text, ['score' => 50, 'highScore' => 40]);
```
*Your score: 50 points.*
  *Congratulations, it's the new high score!*

### Example 3 - Condition with ELSE
``` php
$text = '[IF age >= 18]You are an adult[ELSE]Sorry, you are too young[ENDIF]';
$parser = new \Cinam\TemplateParser\Parser();

echo $parser->parse($text, ['age' => 18]);
```
*You are an adult*
``` php
echo $parser->parse($text, ['age' => 15]);
```
*Sorry, you are too young*

### Example 4 - Iteration
``` php
$text = <<<EOT
Summary:
[START scores]
  {user}, your score is {score}. [IF score > personalBest]Congratulations, it's your personal best![ENDIF]
[END]
EOT;
$variables = [
    'scores' => [
        [
            'user' => 'Peter',
            'score' => 20,
            'personalBest' => 25,
        ],
        [
            'user' => 'Mike',
            'score' => 30,
            'personalBest' => 30,
        ],
        [
            'user' => 'John',
            'score' => 30,
            'personalBest' => 25,
        ],
    ],
];

$parser = new \Cinam\TemplateParser\Parser();
echo $parser->parse($text, $variables);
```
*Summary:*
  *Peter, your score is 20.*
  *Mike, your score is 30.*
  *John, your score is 30. Congratulations, it's your personal best!*

