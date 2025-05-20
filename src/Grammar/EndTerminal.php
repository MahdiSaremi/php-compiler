<?php

namespace Comp\Grammar;

final class EndTerminal extends Terminal
{
    protected static EndTerminal|null $instance = null;

    protected function __construct()
    {
        parent::__construct();
    }

    public static function instance(): self
    {
        return self::$instance ??= new EndTerminal();
    }
}