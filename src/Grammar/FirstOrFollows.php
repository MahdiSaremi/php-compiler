<?php

namespace Comp\Grammar;

class FirstOrFollows
{
    public function __construct(
        /**
         * @var Terminal[]
         */
        public array $all,
        public bool  $lambda,
    )
    {
    }
}