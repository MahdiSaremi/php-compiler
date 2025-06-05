<?php

require __DIR__ . '/vendor/autoload.php';

$digits = new \Comp\Grammar\Terminal();
$plus = new \Comp\Grammar\Terminal('+');
$mines = new \Comp\Grammar\Terminal('-');
$div = new \Comp\Grammar\Terminal('/');
$mult = new \Comp\Grammar\Terminal('*');
$power = new \Comp\Grammar\Terminal('**');
$dot = new \Comp\Grammar\Terminal('.');

$expr = new \Comp\Grammar\NonTerminal();
$int = new \Comp\Grammar\NonTerminal();
$float = new \Comp\Grammar\NonTerminal();
$number = new \Comp\Grammar\NonTerminal();

$grammar = \Comp\Grammar\Grammar::makeFromString(
    compact('expr', 'int', 'float', 'number'),
    compact('digits', 'plus', 'mines', 'div', 'mult', 'power', 'dot'),
    <<<GRAMMAR
        left '+' = '-' < '/' = '*' < '**';
        start $expr;
        
        $float => $digits '.' $digits #both
                | $digits '.' #prefix
                | '.' $digits #suffix;
        $int => $digits;
        $number => $float | $int;
        
        $expr => $expr '+' $expr #plus
               | $expr '-' $expr #mines
               | $expr '/' $expr #div
               | $expr '*' $expr #mult
               | $expr '**' $expr #power
               | $number #num;
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
    new \Comp\Lexer\Token($digits, '10', $source, 0, 0, 1),
    new \Comp\Lexer\Token($mines, '-', $source, 0, 0, 1),
    new \Comp\Lexer\Token($digits, '2', $source, 0, 0, 1),
    new \Comp\Lexer\Token($mult, '*', $source, 0, 0, 1),
    new \Comp\Lexer\Token($digits, '20', $source, 0, 0, 1),
    new \Comp\Lexer\Token($power, '**', $source, 0, 0, 1),
    new \Comp\Lexer\Token($digits, '2', $source, 0, 0, 1),
]));

if ($tree === false) {
    print_r($parser->errors);

    return;
}

\Comp\Debugger\Display::abstractTree($automaton, $tree);

echo "\n\nResult = ";

$result = $tree->travel([
    "$digits" => fn(\Comp\Lexer\Token $token) => $token->value,
    "$dot" => fn(\Comp\Lexer\Token $token) => null,
    "$int" => fn(\Comp\Parser\Node $token) => +$tree->travelThe($token->nodes[0]),
    "$float#both" => fn(\Comp\Parser\Node $token) => +($tree->travelThe($token->nodes[0]) . '.' . $tree->travelThe($token->nodes[2])),
    "$float#prefix" => fn(\Comp\Parser\Node $token) => (float)+$tree->travelThe($token->nodes[0]),
    "$float#suffix" => fn(\Comp\Parser\Node $token) => +('0.' . $tree->travelThe($token->nodes[0])),
    "$number" => fn(\Comp\Parser\Node $token) => $tree->travelThe($token->nodes[0]),
    "#plus" => fn(\Comp\Parser\Node $node) => $tree->travelThe($node->nodes[0]) + $tree->travelThe($node->nodes[2]),
    "#mines" => fn(\Comp\Parser\Node $node) => $tree->travelThe($node->nodes[0]) - $tree->travelThe($node->nodes[2]),
    "#mult" => fn(\Comp\Parser\Node $node) => $tree->travelThe($node->nodes[0]) * $tree->travelThe($node->nodes[2]),
    "#div" => fn(\Comp\Parser\Node $node) => $tree->travelThe($node->nodes[0]) / $tree->travelThe($node->nodes[2]),
    "#power" => fn(\Comp\Parser\Node $node) => $tree->travelThe($node->nodes[0]) ** $tree->travelThe($node->nodes[2]),
    "$expr" => fn(\Comp\Parser\Node $node) => $tree->travelThe($node->nodes[0]),
]);

echo "$result\n";
