<?php

namespace Comp\Lexer;

use Comp\Grammar\Terminal;

class Token
{
    public function __construct(
        public Terminal $type,
        public string   $value,
        public Source   $source,
        public int      $startOffset,
        public int      $endOffset,
        public int      $line,
    )
    {
    }
}