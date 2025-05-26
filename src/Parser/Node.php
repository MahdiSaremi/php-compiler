<?php

namespace Comp\Parser;

use Comp\Grammar\NonTerminal;
use Comp\Grammar\Pattern;
use Comp\Lexer\Source;
use Comp\Lexer\Token;

class Node
{
    public function __construct(
        public Token|NonTerminal $key,
        public Source            $source,
        public int               $startOffset,
        public int               $endOffset,
        public int               $line,
        public ?Pattern          $pattern = null,
        /** @var Node[] */
        public array             $nodes = [],
    )
    {

    }
}