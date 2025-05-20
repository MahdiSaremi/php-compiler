<?php

namespace Comp\Automaton;

use Comp\Grammar\EndTerminal;
use Comp\Grammar\Grammar;
use Comp\Grammar\NonTerminal;
use Comp\Grammar\Pattern;

class Automaton
{
    /**
     * @var State[]
     */
    public array $states;

    public function __construct(
        public Grammar $grammar,
    )
    {
        $this->states = [];
        $start = new NonTerminal();
        $end = EndTerminal::instance();

        $state = new State(new Core([
            new Item($start, new Pattern([$this->grammar->productions[0]->nonTerminal]), 0, [$end]),
        ]));

        $queue = $state->core->items;
        while ($queue) {
            $head = array_shift($queue);
            $state->items[] = $head;

            if ($head->pointToNonTerminal()) {
                $left = $head->point();
                foreach ($this->grammar->getProductionFor($left)->patterns as $pattern) {
                    $queue[] = new Item($left, $pattern, 0, []); // todo lookahead...
                }
            }
        }
    }
}