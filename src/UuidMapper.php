<?php

namespace Comp;

use Comp\Grammar\NonTerminal;
use Comp\Grammar\Pattern;
use Comp\Grammar\Terminal;

class UuidMapper
{
    public const PREFIX = '!!!&';
    public const SUFFIX = '&!!!';

    public static function uuidToTag(string $uuid): string
    {
        return self::PREFIX . $uuid . self::SUFFIX;
    }

    public static function extractPattern(string $string, array $map): Pattern
    {
        @[$string, $tag] = explode('#', $string, 2);

        if (isset($tag)) {
            $tag = trim($tag);

            if ($tag === '') {
                $tag = null;
            }
        }

        $string = trim($string);

        if (strlen($string) == 4 && strtolower($string) === 'null') {
            return new Pattern([], $tag);
        }

        $result = [];

        foreach (explode(self::PREFIX, $string) as $index => $part) {
            array_push($result, ...$index == 0 ? [$part] : explode(self::SUFFIX, $part, 2));
        }

        for ($i = 1; $i < count($result); $i += 2) {
            $item = @$map[static::uuidToTag($result[$i])];

            if (!($item instanceof NonTerminal || $item instanceof Terminal)) {
                throw new \Exception("Undefined terminal/non-terminal with uuid '{$result[$i]}'");
            }

            $result[$i] = $item;
        }

        $result = array_map(function ($item) {
            return is_string($item) ? trim($item) : $item;
        }, $result);

        return new Pattern(
            array_values(array_filter($result, function ($x) {
                if (is_string($x)) {
                    if ($x === '') {
                        return false;
                    }

                    throw new \Exception("Unknown '$x'");
                }

                return true;
            })),
            $tag,
        );
    }
}