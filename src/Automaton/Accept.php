<?php

namespace Comp\Automaton;

use Comp\Grammar\NonTerminal;
use Comp\Grammar\Terminal;

class Accept
{
    public function __construct(
        public Terminal|NonTerminal $see,
    )
    {
    }
}