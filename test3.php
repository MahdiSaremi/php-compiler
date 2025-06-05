<?php

require __DIR__ . '/vendor/autoload.php';

$num = new \Comp\Grammar\Terminal();
$plus = new \Comp\Grammar\Terminal('+');
$mines = new \Comp\Grammar\Terminal('-');
$div = new \Comp\Grammar\Terminal('/');
$mult = new \Comp\Grammar\Terminal('*');

$expr = new \Comp\Grammar\NonTerminal();

$grammar = \Comp\Grammar\Grammar::makeFromString(
    compact('expr'),
    compact('num', 'plus', 'mines', 'div', 'mult'),
    <<<GRAMMAR
        left $plus = $mines < $div = $mult;
        
        $expr => $expr '+' $expr #plus
               | $expr '-' $expr #mines
               | $expr '/' $expr #div
               | $expr '*' $expr #mult
               | $num #num;
    GRAMMAR,
);


\Comp\Debugger\Display::grammar($grammar);

$automaton = new \Comp\Automaton\Automaton($grammar);

\Comp\Debugger\Display::automaton($automaton);

$parser = new \Comp\Parser\Parser($automaton);

$source = new \Comp\Lexer\Source("foo.bar");

// (10 + 2) * 20 + 1 * 10
//    12    * 20 + 1 * 10
//         240   +  10
//              250
$tree = $parser->parse($source, new \Comp\Lexer\TokenCollection([
    new \Comp\Lexer\Token($num, '10', $source, 0, 0, 1),
    new \Comp\Lexer\Token($mines, '-', $source, 0, 0, 1),
    new \Comp\Lexer\Token($num, '2', $source, 0, 0, 1),
    new \Comp\Lexer\Token($plus, '+', $source, 0, 0, 1),
    new \Comp\Lexer\Token($num, '20', $source, 0, 0, 1),
]));

if ($tree === false) {
    print_r($parser->errors);

    return;
}

\Comp\Debugger\Display::abstractTree($automaton, $tree);
