<?php

namespace Comp\Grammar;

class Production
{
    protected bool $produceLambda;

    public function __construct(
        public NonTerminal $nonTerminal,
        /**
         * @var Pattern[]
         */
        public array       $patterns,
    )
    {
        $this->produceLambda = false;
        foreach ($this->patterns as $pattern) {
            if ($pattern->doesProduceLambda()) {
                $this->produceLambda = true;
                break;
            }
        }
    }

    public function doesProduceLambda(): bool
    {
        return $this->produceLambda;
    }
}