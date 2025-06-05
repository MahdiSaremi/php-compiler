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
    /**
     * @var array<string,Shift|Reduce|Accept|ShiftOrReduce>
     */
    public array $operationMap;

    public function __construct(
        public Core $core,
    )
    {
    }

    public function calculateMap(): void
    {
        $this->operationMap = [];
        $see = [];

        foreach ($this->operations as $operation) {
            if (in_array($operation->see, $see)) {
                continue;
            }
            $see[] = $operation->see;

            $same = [];
            foreach ($this->operations as $operation2) {
                if ($operation2->see === $operation->see) {
                    $same[] = $operation2;
                }
            }

            if (count($same) == 1) {
                $this->operationMap[(string)$same[0]->see] = $same[0];
                continue;
            }

            if (
                ($same[0] instanceof Reduce && $same[1] instanceof Shift) ||
                ($same[1] instanceof Reduce && $same[0] instanceof Shift)
            ) {
                $this->operationMap[(string)$same[0]->see] = new ShiftOrReduce(
                    see: $same[0]->see,
                    shift: $same[0] instanceof Shift ? $same[0] : $same[1],
                    reduce: $same[0] instanceof Reduce ? $same[0] : $same[1],
                );
                continue;
            }

            // todo: conflict
            $this->operationMap[(string)$same[0]->see] = $same[0];
        }
    }
}