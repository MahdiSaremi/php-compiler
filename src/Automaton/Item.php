<?php

namespace Comp\Automaton;

use Comp\Grammar\NonTerminal;
use Comp\Grammar\Pattern;
use Comp\Grammar\Terminal;

class Item
{
    public function __construct(
        public NonTerminal $left,
        public Pattern     $pattern,
        public int         $index,
        /**
         * @var Terminal[]
         */
        public array       $lookaheads,
    )
    {
    }

    public function isFinished(): bool
    {
        return $this->index >= count($this->pattern->pat);
    }

    public function point(): Terminal|NonTerminal|string
    {
        return $this->pattern->pat[$this->index];
    }

    public function nextPoint(): null|Terminal|NonTerminal|string
    {
        return @$this->pattern->pat[$this->index + 1];
    }

    public function pointToNonTerminal(): bool
    {
        return @$this->pattern->pat[$this->index] instanceof NonTerminal;
    }

    public function equalsTo(Item $other): bool
    {
        if ($this === $other) {
            return true;
        }

        if (!$this->equalsWithoutLATo($other)) {
            return false;
        }

        $a = $this->lookaheads;
        $b = $other->lookaheads;

        sort($a);
        sort($b);

        return $a == $b;
    }

    public function equalsWithoutLATo(Item $other): bool
    {
        if ($this === $other) {
            return true;
        }

        return $this->left === $other->left &&
            $this->index == $other->index &&
            $this->pattern === $other->pattern;
    }

    public function mergeLAs(Item $other): Item
    {
        return new Item($this->left, $this->pattern, $this->index, array_unique(array_merge($this->lookaheads, $other->lookaheads)));
    }

    public function makeNext(array $lookaheads): Item
    {
        return new Item($this->left, $this->pattern, $this->index + 1, $lookaheads);
    }
}