<?php

require __DIR__ . '/vendor/autoload.php';

$id = new \Comp\Grammar\Terminal();
$parOpen = new \Comp\Grammar\Terminal();
$parClose = new \Comp\Grammar\Terminal();
$colon = new \Comp\Grammar\Terminal();
$equals = new \Comp\Grammar\Terminal();
$dollar = new \Comp\Grammar\Terminal();
$semi = new \Comp\Grammar\Terminal();

$variable = new \Comp\Grammar\NonTerminal();
$setVariable = new \Comp\Grammar\NonTerminal();
$term = new \Comp\Grammar\NonTerminal();
$call = new \Comp\Grammar\NonTerminal();
$arguments = new \Comp\Grammar\NonTerminal();
$argument = new \Comp\Grammar\NonTerminal();

$grammar = new \Comp\Grammar\Grammar(
    compact('variable', 'setVariable', 'term', 'call', 'arguments', 'argument'),
    compact('id', 'parOpen', 'parClose', 'colon', 'equals', 'dollar', 'semi'),
    [
        "$term" => "$call|$variable|$setVariable",
        "$call" => "$id$parOpen$arguments$parClose",
        "$arguments" => "|$argument$colon$arguments|$argument",
        "$argument" => "$id$semi$term|$term",
        "$variable" => "$dollar$id",
        "$setVariable" => "$variable$equals$term",
    ],
);

$r = [];
foreach ($grammar->nonTerminals as $name => $t) {
    $r[] = [
        'name' => $name,
        'follows' => array_map(function ($x) use ($grammar) {
            if ($x instanceof \Comp\Grammar\Terminal) {
                return 'T: ' . array_search($x, $grammar->terminals);
            } else {
                return $x;
            }
        }, $grammar->follows["$t"]->all),
        'lambda' => $grammar->follows["$t"]->lambda,
    ];
}
var_dump($r);
