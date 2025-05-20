<?php

namespace Comp\Lexer;

use Comp\Grammar\Terminal;

class Token
{
    public function __construct(
        public Terminal $type,
        public string   $value,
    )
    {
    }
}