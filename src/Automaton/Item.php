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

    public function point(): Terminal|NonTerminal|string
    {
        return $this->pattern->pat[$this->index];
    }

    public function pointToNonTerminal(): bool
    {
        return @$this->pattern->pat[$this->index] instanceof NonTerminal;
    }
}