<?php

namespace Comp\Parser;

use Comp\Automaton\Accept;
use Comp\Automaton\Automaton;
use Comp\Automaton\Reduce;
use Comp\Automaton\Shift;
use Comp\Automaton\State;
use Comp\Grammar\EndTerminal;
use Comp\Lexer\Source;
use Comp\Lexer\Token;
use Comp\Lexer\TokenCollection;

class Parser
{
    public array $errors;

    public function __construct(
        public Automaton $automaton,
    )
    {
    }

    public function parse(Source $source, TokenCollection $tokens): AbstractTree|false
    {
        $this->errors = [];
        $end = @$tokens->all[count($tokens->all) - 1];
        $tokens = array_merge($tokens->all, [
            new Token(EndTerminal::instance(), '$', $source, $end?->endOffset ?? 0, $end?->endOffset ?? 0, $end?->line ?? 1),
        ]);

        $stack = [
            $this->automaton->states[0],
        ];
        $ptr = 0;

        $nodes = [];

        while ($stack && $ptr < count($tokens)) {
            /** @var State $state */
            $state = end($stack);

            $token = $tokens[$ptr];
            foreach ($state->operations as $operation) {
                if ($operation->see === $token->type) {
                    switch (true) {
                        case $operation instanceof Shift:
                            $nodes[] = new Node(
                                key: $token,
                                source: $source,
                                startOffset: $token->startOffset,
                                endOffset: $token->endOffset,
                                line: $token->line,
                            );

                            array_push($stack, $tokens[$ptr++], $operation->newState);
                            continue 3;

                        case $operation instanceof Reduce:
                            $reduceCount = count($operation->usingPattern->pat);
                            array_splice($stack, -$reduceCount * 2);

                            $reduceNodes = array_reverse(array_splice($nodes, -$reduceCount));
                            $first = @$reduceNodes[0];
                            $last = @$reduceNodes[count($reduceNodes) - 1];
                            $nodes[] = new Node(
                                key: $operation->reduceTo,
                                source: $source,
                                startOffset: $first?->startOffset ?? -1,
                                endOffset: $last?->endOffset ?? -1,
                                line: $first?->line ?? -1,
                                pattern: $operation->usingPattern,
                                nodes: $reduceNodes,
                            );

                            /** @var State $prevState */
                            $prevState = end($stack);
                            $stack[] = $operation->reduceTo;

                            foreach ($prevState->operations as $operation2) {
                                if ($operation2 instanceof Shift && $operation2->see === $operation->reduceTo) {
                                    $stack[] = $operation2->newState;
                                    continue 4;
                                }
                            }

                            break 3;

                        case $operation instanceof Accept:
                            if ($this->errors) {
                                break 3;
                            }

                            return new AbstractTree(array_pop($nodes));
                    }
                }
            }

            if ($token->type instanceof EndTerminal) {
                $this->errors[] = "Unexpected EOF on line {$token->line} and offset {$token->startOffset} in '{$token->source->filePath}'";
            } else {
                $this->errors[] = "Unexpected token '{$token->value}' on line {$token->line} and offset {$token->startOffset} in '{$token->source->filePath}'";
            }

            $ptr++;
        }

        return false;
    }
}