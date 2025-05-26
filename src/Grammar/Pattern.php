<?php

namespace Comp\Grammar;

class Pattern
{
    protected bool $produceLambda;

    public function __construct(
        /**
         * @var (string|NonTerminal|Terminal)[]
         */
        public array   $pat,
        public ?string $tag = null,
    )
    {
        $this->produceLambda = empty($this->pat);
    }

    public function doesProduceLambda(): bool
    {
        return $this->produceLambda;
    }
}