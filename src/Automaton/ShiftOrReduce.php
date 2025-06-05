<?php

namespace Comp\Automaton;

use Comp\Grammar\NonTerminal;
use Comp\Grammar\Terminal;

class ShiftOrReduce
{
    public function __construct(
        public Terminal|NonTerminal $see,
        public Shift                $shift,
        public Reduce               $reduce,
    )
    {
    }
}