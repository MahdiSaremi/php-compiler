<?php

namespace Comp\Grammar;

use Comp\UuidMapper;
use Ramsey\Uuid\Uuid;

class NonTerminal
{
    protected string $uuid;

    public function __construct()
    {
        $this->uuid = Uuid::uuid4()->toString();
    }

    public function __toString(): string
    {
        return UuidMapper::uuidToTag($this->uuid);
    }
}