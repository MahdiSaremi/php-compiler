<?php

namespace Comp\Automaton;

class State
{
    /**
     * @var Item[]
     */
    public array $items = [];

    /**
     * @var (Shift|Reduce|Accept)[]
     */
    public array $operations = [];

    public function __construct(
        public Core $core,
    )
    {
    }
}