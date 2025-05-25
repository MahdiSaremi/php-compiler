<?php

namespace Comp\Parser;

class AbstractTree
{
    public function __construct(
        public Node $head,
    )
    {
    }
}