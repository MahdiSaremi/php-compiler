<?php

require __DIR__ . '/vendor/autoload.php';

$num = new \Comp\Grammar\Terminal();
$plus = new \Comp\Grammar\Terminal();
$mines = new \Comp\Grammar\Terminal();
$div = new \Comp\Grammar\Terminal();
$mult = new \Comp\Grammar\Terminal();
$parOpen = new \Comp\Grammar\Terminal();
$parClose = new \Comp\Grammar\Terminal();

$expr = new \Comp\Grammar\NonTerminal();
$temp = new \Comp\Grammar\NonTerminal();
$value = new \Comp\Grammar\NonTerminal();

$grammar = new \Comp\Grammar\Grammar(
    compact('expr', 'temp', 'value'),
    compact('num', 'plus', 'mines', 'div', 'mult', 'parOpen', 'parClose'),
    <<<GRAMMAR
        $expr => $expr $plus $temp #plus
        $expr => $expr $mines $temp #mines
        $expr => $temp
        $temp => $temp $mult $value #mult
        $temp => $temp $div $value #div
        $temp => $value
        $value => $num
        $value => $parOpen $expr $parClose #par
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
    new \Comp\Lexer\Token($parOpen, '(', $source, 0, 0, 1),
    new \Comp\Lexer\Token($num, '10', $source, 0, 0, 1),
    new \Comp\Lexer\Token($plus, '+', $source, 0, 0, 1),
    new \Comp\Lexer\Token($num, '2', $source, 0, 0, 1),
    new \Comp\Lexer\Token($parClose, ')', $source, 0, 0, 1),
    new \Comp\Lexer\Token($mult, '*', $source, 0, 0, 1),
    new \Comp\Lexer\Token($num, '20', $source, 0, 0, 1),
    new \Comp\Lexer\Token($plus, '+', $source, 0, 0, 1),
    new \Comp\Lexer\Token($num, '1', $source, 0, 0, 1),
    new \Comp\Lexer\Token($mult, '*', $source, 0, 0, 1),
    new \Comp\Lexer\Token($num, '10', $source, 0, 0, 1),
]));

if ($tree === false) {
    print_r($parser->errors);

    return;
}

\Comp\Debugger\Display::abstractTree($automaton, $tree);

echo "\n\nResult = ";

$result = $tree->travel([
    "$num" => fn(\Comp\Lexer\Token $token) => +$token->value,
    "#plus" => fn(\Comp\Parser\Node $node) => $tree->travelThe($node->nodes[0]) + $tree->travelThe($node->nodes[2]),
    "#mines" => fn(\Comp\Parser\Node $node) => $tree->travelThe($node->nodes[0]) - $tree->travelThe($node->nodes[2]),
    "$expr" => fn(\Comp\Parser\Node $node) => $tree->travelThe($node->nodes[0]),
    "#mult" => fn(\Comp\Parser\Node $node) => $tree->travelThe($node->nodes[0]) * $tree->travelThe($node->nodes[2]),
    "#div" => fn(\Comp\Parser\Node $node) => $tree->travelThe($node->nodes[0]) / $tree->travelThe($node->nodes[2]),
    "$temp" => fn(\Comp\Parser\Node $node) => $tree->travelThe($node->nodes[0]),
    "#par" => fn(\Comp\Parser\Node $node) => $tree->travelThe($node->nodes[1]),
    "$value" => fn(\Comp\Parser\Node $node) => $tree->travelThe($node->nodes[0]),
]);

echo "$result\n";
