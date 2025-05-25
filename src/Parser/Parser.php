<?php

namespace Comp\Parser;

use Comp\Automaton\Accept;
use Comp\Automaton\Automaton;
use Comp\Automaton\Reduce;
use Comp\Automaton\Shift;
use Comp\Automaton\State;
use Comp\Grammar\EndTerminal;
use Comp\Lexer\Token;
use Comp\Lexer\TokenCollection;

class Parser
{
    public function __construct(
        public Automaton $automaton,
    )
    {
    }

    public function parse(TokenCollection $tokens): bool
    {
        $tokens = array_merge($tokens->all, [
            new Token(EndTerminal::instance(), '$'),
        ]);

        $stack = [
            $this->automaton->states[0],
        ];
        $ptr = 0;

        while (true) {
            /** @var State $state */
            $state = end($stack);

            foreach ($state->operations as $operation) {
                if ($operation->see === $tokens[$ptr]->type) {
                    switch (true) {
                        case $operation instanceof Shift:
                            array_push($stack, $tokens[$ptr++], $operation->newState);
                            continue 3;

                        case $operation instanceof Reduce:
                            $reduceCount = count($operation->usingPattern->pat) * 2;
                            array_splice($stack, -$reduceCount);

                            /** @var State $prevState */
                            $prevState = end($stack);
                            $stack[] = $operation->reduceTo;

                            foreach ($prevState->operations as $operation2) {
                                if ($operation2 instanceof Shift && $operation2->see === $operation->reduceTo) {
                                    $stack[] = $operation2->newState;
                                    continue 4;
                                }
                            }

                            return false;

                        case $operation instanceof Accept:
                            return true;
                    }
                }
            }

            return false;
        }
    }
}