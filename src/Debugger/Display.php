<?php

namespace Comp\Debugger;

use Comp\Automaton\Accept;
use Comp\Automaton\Automaton;
use Comp\Automaton\Reduce;
use Comp\Automaton\Shift;
use Comp\Automaton\ShiftOrReduce;
use Comp\Grammar\EndTerminal;
use Comp\Grammar\Grammar;
use Comp\Grammar\NonTerminal;
use Comp\Grammar\Pattern;
use Comp\Grammar\Terminal;
use Comp\Lexer\Token;
use Comp\Parser\AbstractTree;
use Comp\Parser\Node;

class Display
{
    public static function grammar(
        Grammar $grammar,
    ): void
    {
        echo "Grammar:\n";

        echo "  -  Productions:\n";
        foreach ($grammar->productions as $production) {
            foreach ($production->patterns as $pattern) {
                printf("\t%s\n",
                    static::production($grammar, $production->nonTerminal, $pattern),
                );
            }
        }

        echo "\n";

        echo "  -  First:\n";
        foreach ($grammar->nonTerminals as $nonTerminal) {
            $first = $grammar->firsts["$nonTerminal"];

            printf("\t%s -> %s%s\n",
                $grammar->nameOf($nonTerminal),
                implode(', ', array_map(function (Terminal $term) use ($grammar) {
                    return '[' . $grammar->nameOf($term) . ']';
                }, $first->all)) ?: 'empty',
                $first->lambda ? ', nullable' : '',
            );
        }

        echo "\n";

        echo "  -  Follow:\n";
        foreach ($grammar->nonTerminals as $nonTerminal) {
            $follow = $grammar->follows["$nonTerminal"];

            printf("\t%s -> %s%s\n",
                $grammar->nameOf($nonTerminal),
                implode(', ', array_map(function (Terminal $term) use ($grammar) {
                    if ($term instanceof EndTerminal) {
                        return '$';
                    }

                    return '[' . $grammar->nameOf($term) . ']';
                }, $follow->all)) ?: 'empty',
                $follow->lambda ? ', nullable' : '',
            );
        }

        echo "\n";

        echo "  -  Precedence:\n";
        foreach ([...$grammar->nonTerminals, ...$grammar->terminals] as $from) {
            foreach ([...$grammar->nonTerminals, ...$grammar->terminals] as $to) {
                if (isset($grammar->precedence["$from"]["$to"])) {
                    printf("\t%s %s %s\n",
                        $grammar->nameOf($from),
                        $grammar->precedence["$from"]["$to"] ? "<" : ">",
                        $grammar->nameOf($to),
                    );
                }
            }
        }
    }

    public static function automaton(
        Automaton $automaton,
    ): void
    {
        echo "Automaton:\n";

        echo "  -  States:\n";
        foreach ($automaton->states as $i => $state) {
            echo "\n\n\n";
            printf("\t- State #%s\n", $i);
            foreach ($state->core->items as $item) {
                printf("\t\t%s  ,  %s\n",
                    static::production($automaton, $item->left, $item->pattern, $item->index),
                    implode(' | ', array_map(function (Terminal $term) use ($automaton) {
                        if ($term instanceof EndTerminal) {
                            return '$';
                        }

                        return '[' . $automaton->grammar->nameOf($term) . ']';
                    }, $item->lookaheads)) ?: 'empty',
                );
            }

            echo "\t\t------------------------\n";

            foreach ($state->items as $item) {
                printf("\t\t%s  ,  %s\n",
                    static::production($automaton, $item->left, $item->pattern, $item->index),
                    implode(' | ', array_map(function (Terminal $term) use ($automaton) {
                        if ($term instanceof EndTerminal) {
                            return '$';
                        }

                        return '[' . $automaton->grammar->nameOf($term) . ']';
                    }, $item->lookaheads)) ?: 'empty',
                );
            }

            printf("\t- Operation\n");

            foreach ($state->operationMap as $operation) {
                printf("\t\t%s -> ",
                    static::fullName($automaton->grammar, $operation->see),
                );

                switch (true) {
                    case $operation instanceof Shift:
                        printf("Shift to #%s\n",
                            array_search($operation->newState, $automaton->states),
                        );
                        break;

                    case $operation instanceof Reduce:
                        printf("Reduce by %s\n",
                            static::production($automaton, $operation->reduceTo, $operation->usingPattern),
                        );
                        break;

                    case $operation instanceof ShiftOrReduce:
                        printf("Shift to #%s | Reduce by %s\n",
                            array_search($operation->shift->newState, $automaton->states),
                            static::production($automaton, $operation->reduce->reduceTo, $operation->reduce->usingPattern),
                        );
                        break;

                    case $operation instanceof Accept:
                        print("Accept\n");
                        break;
                }
            }
        }
    }

    public static function abstractTree(
        Automaton    $automaton,
        AbstractTree $tree,
    )
    {
        $stack = [
            [0, $tree->head],
        ];

        while ($stack) {
            /** @var Node $node */
            [$tabs, $node] = array_pop($stack);

            $tabsString = $tabs ? str_repeat("|\t", $tabs) : '';

            if ($node->key instanceof NonTerminal) {
                printf("%s- %s\n",
                    $tabsString,
                    static::fullName($automaton->grammar, $node->key),
                );

                printf("%s- \t# Pattern: %s\n",
                    $tabsString,
                    static::production($automaton, $node->key, $node->pattern),
                );

                foreach ($node->nodes as $child) {
                    $stack[] = [$tabs + 1, $child];
                }
            } elseif ($node->key instanceof Token) {
                printf("%s- %s\n",
                    $tabsString,
                    static::fullName($automaton->grammar, $node->key->type),
                );

                printf("%s- \t# Value: %s\n",
                    $tabsString,
                    $node->key->value,
                );
            }
        }
    }


    protected static function production(Grammar|Automaton $base, NonTerminal $left, Pattern $pattern, ?int $index = null): string
    {
        $grammar = $base instanceof Grammar ? $base : $base->grammar;

        if ($base instanceof Automaton && $base->startNonTerminal === $left) {
            $str = 'start';
        } else {
            $str = $grammar->nameOf($left);
        }

        $str .= ' =>';

        if (isset($index) && $index == 0) {
            $str .= ' *';
        }

        foreach ($pattern->pat as $i => $term) {
            $str .= ' ' . static::fullName($grammar, $term);

            if (isset($index) && $index == $i + 1) {
                $str .= ' *';
            }
        }

        if (!$pattern->pat) {
            $str .= ' null';
        }

        if (isset($pattern->tag)) {
            $str .= ' (#' . $pattern->tag . ')';
        }

        return $str;
    }

    protected static function fullName(Grammar $grammar, Terminal|NonTerminal $x): string
    {
        if ($x instanceof EndTerminal) {
            return '$';
        }

        return $x instanceof Terminal ?
            '[' . $grammar->nameOf($x) . ']' :
            '{' . $grammar->nameOf($x) . '}';
    }
}