<?php

namespace Comp\Debugger;

use Comp\Automaton\Accept;
use Comp\Automaton\Automaton;
use Comp\Automaton\Reduce;
use Comp\Automaton\Shift;
use Comp\Grammar\EndTerminal;
use Comp\Grammar\Grammar;
use Comp\Grammar\NonTerminal;
use Comp\Grammar\Pattern;
use Comp\Grammar\Terminal;

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
                printf("\t\t%s  >  %s\n",
                    static::production($automaton, $item->left, $item->pattern, $item->index),
                    implode(' , ', array_map(function (Terminal $term) use ($automaton) {
                        if ($term instanceof EndTerminal) {
                            return '$';
                        }

                        return '[' . $automaton->grammar->nameOf($term) . ']';
                    }, $item->lookaheads)) ?: 'empty',
                );
            }

            echo "\t\t------------------------\n";

            foreach ($state->items as $item) {
                printf("\t\t%s  >  %s\n",
                    static::production($automaton, $item->left, $item->pattern, $item->index),
                    implode(' , ', array_map(function (Terminal $term) use ($automaton) {
                        if ($term instanceof EndTerminal) {
                            return '$';
                        }

                        return '[' . $automaton->grammar->nameOf($term) . ']';
                    }, $item->lookaheads)) ?: 'empty',
                );
            }

            printf("\t- Operation\n");

            foreach ($state->operations as $operation) {
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

                    case $operation instanceof Accept:
                        print("Accept\n");
                        break;
                }
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
            $str .= ' .';
        }

        foreach ($pattern->pat as $i => $term) {
            $str .= ' ' . static::fullName($grammar, $term);

            if (isset($index) && $index == $i + 1) {
                $str .= ' .';
            }
        }

        if (!$pattern->pat) {
            $str .= ' null';
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