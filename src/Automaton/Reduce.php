<?php

namespace Comp\Automaton;

use Comp\Grammar\NonTerminal;
use Comp\Grammar\Terminal;

class Reduce
{
    public function __construct(
        public Terminal|NonTerminal $see,
        public NonTerminal          $reduceTo,
    )
    {
    }
}