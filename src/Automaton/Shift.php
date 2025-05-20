<?php

namespace Comp\Automaton;

use Comp\Grammar\NonTerminal;
use Comp\Grammar\Terminal;

class Shift
{
    public function __construct(
        public Terminal|NonTerminal $see,
        public State                $newState,
    )
    {
    }
}