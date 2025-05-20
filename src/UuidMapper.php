<?php

namespace Comp;

use Comp\Grammar\NonTerminal;
use Comp\Grammar\Terminal;

class UuidMapper
{
    public static function uuidToTag(string $uuid): string
    {
        return "<<<#{$uuid}#>>>";
    }

    public static function extract(string $string, array $map): array
    {
        $result = [];

        foreach (explode('<<<#', $string) as $index => $part) {
            array_push($result, ...$index == 0 ? [$part] : explode('#>>>', $part, 2));
        }

        for ($i = 1; $i < count($result); $i += 2) {
            $item = @$map[static::uuidToTag($result[$i])];
            assert($item instanceof NonTerminal || $item instanceof Terminal);
            $result[$i] = $item;
        }

        return array_values(array_filter($result, function (string $x) {
            return $x !== '';
        }));
    }
}