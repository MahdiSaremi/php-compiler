<?php

require __DIR__ . '/vendor/autoload.php';

$id = new \Comp\Grammar\Terminal();
$pls = new \Comp\Grammar\Terminal();

$E = new \Comp\Grammar\NonTerminal();
$T = new \Comp\Grammar\NonTerminal();

$grammar = new \Comp\Grammar\Grammar(
    compact('E', 'T'),
    compact('id', 'pls'),
    <<<GRAMMAR
        $E => $E $pls $T
        $E => $T
        $T => $id
    GRAMMAR,
);


//\Comp\Debugger\Display::grammar($grammar);

$automaton = new \Comp\Automaton\Automaton($grammar);

\Comp\Debugger\Display::automaton($automaton);

$parser = new \Comp\Parser\Parser($automaton);

var_dump($parser->parse(new \Comp\Lexer\TokenCollection([
    new \Comp\Lexer\Token($id, 'x'),
    new \Comp\Lexer\Token($pls, '+'),
    new \Comp\Lexer\Token($id, 'i'),
])));
