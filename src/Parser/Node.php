<?php

namespace Comp\Parser;

use Comp\Grammar\NonTerminal;
use Comp\Grammar\Pattern;
use Comp\Lexer\Token;

class Node
{
    public function __construct(
        public Token|NonTerminal $key,
        public ?Pattern          $pattern = null,
        /** @var Node[] */
        public array             $nodes = [],
    )
    {

    }
}