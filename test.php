<?php

require __DIR__ . '/vendor/autoload.php';

//$id = new \Comp\Grammar\Terminal();
//$parOpen = new \Comp\Grammar\Terminal();
//$parClose = new \Comp\Grammar\Terminal();
//$colon = new \Comp\Grammar\Terminal();
//$equals = new \Comp\Grammar\Terminal();
//$dollar = new \Comp\Grammar\Terminal();
//$semi = new \Comp\Grammar\Terminal();
//
//$variable = new \Comp\Grammar\NonTerminal();
//$setVariable = new \Comp\Grammar\NonTerminal();
//$term = new \Comp\Grammar\NonTerminal();
//$call = new \Comp\Grammar\NonTerminal();
//$arguments = new \Comp\Grammar\NonTerminal();
//$argument = new \Comp\Grammar\NonTerminal();
//
//$grammar = new \Comp\Grammar\Grammar(
//    compact('variable', 'setVariable', 'term', 'call', 'arguments', 'argument'),
//    compact('id', 'parOpen', 'parClose', 'colon', 'equals', 'dollar', 'semi'),
//    [
//        "$term" => "$call|$variable|$setVariable",
//        "$call" => "$id$parOpen$arguments$parClose",
//        "$arguments" => "|$argument$colon$arguments|$argument",
//        "$argument" => "$id$semi$term|$term",
//        "$variable" => "$dollar$id",
//        "$setVariable" => "$variable$equals$term",
//    ],
//);


$id = new \Comp\Grammar\Terminal();
$mul = new \Comp\Grammar\Terminal();
$pls = new \Comp\Grammar\Terminal();
$parOpen = new \Comp\Grammar\Terminal();
$parClose = new \Comp\Grammar\Terminal();

$E = new \Comp\Grammar\NonTerminal();
$T = new \Comp\Grammar\NonTerminal();
$F = new \Comp\Grammar\NonTerminal();

$grammar = new \Comp\Grammar\Grammar(
    compact('E', 'T', 'F'),
    compact('id', 'mul', 'pls', 'parOpen', 'parClose'),
    <<<GRAMMAR
        $E => $E $pls $T
        $E => $T
        $T => $T $mul $F
        $T => $F
        $F => $parOpen $E $parClose
        $F => $id
    GRAMMAR,
);


//\Comp\Debugger\Display::grammar($grammar);

$automaton = new \Comp\Automaton\Automaton($grammar);

//\Comp\Debugger\Display::automaton($automaton);

$parser = new \Comp\Parser\Parser($automaton);

var_dump($parser->parse(new \Comp\Lexer\TokenCollection([
    new \Comp\Lexer\Token($id, 'x'),
    new \Comp\Lexer\Token($pls, '+'),
    new \Comp\Lexer\Token($parOpen, '('),
    new \Comp\Lexer\Token($id, 'i'),
    new \Comp\Lexer\Token($mul, '*'),
    new \Comp\Lexer\Token($id, 'j'),
    new \Comp\Lexer\Token($parClose, ')'),
])));
