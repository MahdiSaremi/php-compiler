<?php

namespace Comp\Parser;

use Comp\Automaton\Accept;
use Comp\Automaton\Automaton;
use Comp\Automaton\Reduce;
use Comp\Automaton\Shift;
use Comp\Automaton\ShiftOrReduce;
use Comp\Automaton\State;
use Comp\Grammar\EndTerminal;
use Comp\Grammar\NonTerminal;
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

        /** @var Node[] $nodes */
        $nodes = [];

        while ($stack && $ptr < count($tokens)) {
            /** @var State $state */
            $state = end($stack);

            $token = $tokens[$ptr];
            if (!is_null($operation = @$state->operationMap[(string)$token->type])) {
                if ($operation instanceof ShiftOrReduce && $nodes) {
                    for ($i = count($nodes) - 1; $i >= 0; $i--) {
                        $nodeTerm = $nodes[$i]->key instanceof Token ? $nodes[$i]->key->type : $nodes[$i]->key;

                        if (!is_null($pre = @$this->automaton->grammar->precedence[(string)$nodeTerm][(string)$token->type])) {
                            $operation = $pre ? $operation->shift : $operation->reduce;
                            break;
                        }
                    }
                }

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
                        continue 2;

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
                                continue 3;
                            }
                            if ($operation2 instanceof ShiftOrReduce && $operation2->see === $operation->reduceTo) {
                                $stack[] = $operation2->shift->newState;
                                continue 3;
                            }
                        }

                        break 2;

                    case $operation instanceof Accept:
                        if ($this->errors) {
                            break 2;
                        }

                        return new AbstractTree(array_pop($nodes));
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