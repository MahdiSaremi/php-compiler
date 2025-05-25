<?php

namespace Comp\Lexer;

class TokenCollection
{
    public function __construct(
        /**
         * @var Token[]
         */
        public array $all,
    )
    {
    }
}