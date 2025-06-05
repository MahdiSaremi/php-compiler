<?php

namespace Comp\Automaton;

use Comp\Grammar\EndTerminal;
use Comp\Grammar\Grammar;
use Comp\Grammar\NonTerminal;
use Comp\Grammar\Pattern;
use Comp\Grammar\Terminal;

class Automaton
{
    /**
     * @var State[]
     */
    public array $states;

    public NonTerminal $startNonTerminal;

    public function __construct(
        public Grammar $grammar,
    )
    {
        $this->states = [];
        $start = $this->startNonTerminal = new NonTerminal();
        $end = EndTerminal::instance();

        $state = new State(new Core([
            new Item($start, new Pattern([$this->grammar->start]), 0, [$end]),
        ]));

        $this->buildState($state);
    }

    protected function buildState(State $state): void
    {
        $this->states[] = $state;

        $queue = $state->core->items;
        while ($queue) {
            $head = array_shift($queue);
            $state->items[] = $head;

            if ($head->pointToNonTerminal()) {
                $left = $head->point();

                $aheadFirst = $this->grammar->firstOfSequence(
                    array_slice($head->pattern->pat, $head->index + 1),
                );

                $lookaheads = array_unique(array_merge($aheadFirst->all, $aheadFirst->lambda ? $head->lookaheads : []));

                foreach ($this->grammar->getProductionFor($left)->patterns as $pattern) {
                    $newItem = new Item($left, $pattern, 0, $lookaheads);

                    foreach ($state->items as $item) {
                        if ($item->equalsTo($newItem)) {
                            continue 3;
                        }
                    }

                    $queue[] = $newItem;
                }
            }
        }

        $state->items = $this->mergeSameCores($state->items);

        $goto = new \WeakMap();
        foreach ($state->items as $item) {
            if ($item->isFinished()) {
                if ($item->left === $this->startNonTerminal) {
                    foreach ($item->lookaheads as $see) {
                        $state->operations[] = new Accept($see);
                    }
                } else {
                    foreach ($item->lookaheads as $see) {
                        $state->operations[] = new Reduce($see, $item->left, $item->pattern);
                    }
                }
            } else {
                $point = $item->point();

                if (!$goto->offsetExists($point)) {
                    $goto->offsetSet($point, []);
                }

                $goto->offsetSet($point, [
                    ...$goto->offsetGet($point),
                    $item->makeNext(),
                ]);
            }
        }

        /**
         * @var NonTerminal|Terminal $point
         * @var Item[] $items
         */
        foreach ($goto as $point => $items) {
            $toState = $this->findOrBuildState($this->mergeSameCores($items));
            $state->operations[] = new Shift($point, $toState);
        }

        $state->calculateMap();
    }

    /**
     * @param Item[] $items
     * @return State
     */
    protected function findOrBuildState(array $items): State
    {
        foreach ($this->states as $state) {
            if (count($items) != count($state->core->items)) {
                continue;
            }

            $bItems = $state->core->items;

            foreach ($items as $j => $item) {
                foreach ($bItems as $i => $bItem) {
                    if ($bItem->equalsTo($item)) {
                        $items[$j] = $bItem;
                        unset($bItems[$i]);
                        continue 2;
                    }
                }

                continue 2;
            }

            return $state;
        }

        $state = new State(new Core($items));
        $this->buildState($state);

        return $state;
    }

    /**
     * @param Item[] $items
     * @return Item[]
     */
    protected function mergeSameCores(array $items): array
    {
        $mergedItems = $items;

        foreach ($items as $i => $item) {
            if (!isset($mergedItems[$i])) {
                continue;
            }

            for ($j = $i + 1; $j < count($items); $j++) {
                if ($item->equalsWithoutLATo($items[$j])) {
                    $mergedItems[$i] = $mergedItems[$i]->mergeLAs($mergedItems[$j]);
                    unset($mergedItems[$j]);
                }
            }
        }

        return $mergedItems;
    }
}