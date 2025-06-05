<?php

namespace Comp\Grammar;

use Comp\UuidMapper;

class Grammar
{
    /**
     * @var Production[]
     */
    public array $productions;

    /**
     * @var array<string, FirstOrFollows>
     */
    public array $firsts;

    /**
     * @var array<string, FirstOrFollows>
     */
    public array $follows;

    public static function makeFromString(
        array  $nonTerminals,
        array  $terminals,
        string $syntax,
    ): self
    {
        $map = [];
        $mapAs = [];

        foreach ($nonTerminals as $nonTerminal) {
            $map["$nonTerminal"] = $nonTerminal;
        }

        foreach ($terminals as $terminal) {
            $map["$terminal"] = $terminal;
            if (isset($terminal->as)) {
                $mapAs[$terminal->as] = $terminal;
            }
        }

        $syntax = preg_replace_callback('/\'(([^\'\\\\]*((\\\\)+|\\\w|\\\\\'|))*)\'/', function ($matches) use ($mapAs) {
            return (string)$mapAs[$matches[1]]; // todo escapes
        }, $syntax);

        $code = preg_split('/[\s\n\r]*;[\s\n\r]*/', trim($syntax), flags: PREG_SPLIT_NO_EMPTY);

        $productions = [];
        $precedence = [];
        foreach ($code as $line) {
            if (preg_match('/^(left|right)[\s\n\r]/', $line, $matches)) {
                $line = substr($line, strlen($matches[0]));
                $isLeft = $matches[1] == 'left';

                preg_match_all('/(.+?)([=<]|$)/', $line, $matches);

                $all = [[]];
                foreach (array_keys($matches[1]) as $i) {
                    $all[count($all) - 1][] = $map[trim($matches[1][$i])];

                    if ($matches[2][$i] == '<') {
                        $all[] = [];
                    }
                }

                foreach ($all as $i => $equals) {
                    foreach ($equals as $select) {
                        foreach ($all as $j => $withs) {
                            foreach ($withs as $with) {
                                @$precedence["$select"]["$with"] = $i == $j ? !$isLeft : $i < $j;
                            }
                        }
                    }
                }
            } else {
                @[$left, $right] = explode('=>', $line, 2);

                if ($right === null) {
                    throw new \Exception("Missing '=>'");
                }

                $left = rtrim($left);

                $productions[$left] ??= [];
                array_push($productions[$left], ...explode('|', $right));
            }
        }

        return new self($nonTerminals, $terminals, $productions, $precedence);
    }

    public function __construct(
        /**
         * @var NonTerminal[]
         */
        public array $nonTerminals,
        /**
         * @var Terminal[]
         */
        public array $terminals,
        array        $productions,
        public array $precedence,
    )
    {
        $map = [];

        foreach ($this->nonTerminals as $nonTerminal) {
            $map["$nonTerminal"] = $nonTerminal;
        }

        foreach ($this->terminals as $terminal) {
            $map["$terminal"] = $terminal;
        }

        $this->productions = [];

        foreach ($productions as $tag => $production) {
            $nonTerminal = @$map[$tag];

            if (!($nonTerminal instanceof NonTerminal)) {
                throw new \Exception("No non-terminal with tag '$tag' found.");
            }

            if (is_string($production)) {
                $production = explode('|', $production);
            }

            $production = array_unique(array_map('trim', $production));

            $patterns = array_map(function (string $string) use (&$map) {
                return UuidMapper::extractPattern($string, $map);
            }, $production);

            $this->productions[] = new Production($nonTerminal, $patterns);
        }

        foreach ($this->nonTerminals as $nonTerminal) {
            $first = [];
            $lambda = false;
            $this->calculateFirst($nonTerminal, $first, $lambda);
            $this->firsts["$nonTerminal"] = new FirstOrFollows($first, $lambda);
        }

        foreach ($this->nonTerminals as $nonTerminal) {
            $follow = [];
            $lambda = false;
            $this->calculateFollow($nonTerminal, $follow, $lambda);
            $this->follows["$nonTerminal"] = new FirstOrFollows($follow, $lambda);
        }
    }

    protected function calculateFirst(NonTerminal $for, array &$first, bool &$lambda, array &$sees = []): void
    {
        $sees[] = $for;
        foreach ($this->getProductionFor($for)?->patterns ?? [] as $pattern) {
            if ($pattern->doesProduceLambda()) {
                $lambda = true;
            } elseif ($pattern->pat[0] instanceof NonTerminal) {
                if (!in_array($pattern->pat[0], $sees)) {
                    $this->calculateFirst($pattern->pat[0], $first, $lambda, $sees);
                }
            } elseif (!in_array($pattern->pat[0], $first)) {
                $first[] = $pattern->pat[0];
            }
        }
    }

    protected function calculateFollow(NonTerminal $for, array &$follow, bool &$lambda, array &$sees = []): void
    {
        $sees[] = $for;

        if ($for === reset($this->productions)->nonTerminal && !in_array(EndTerminal::instance(), $follow)) {
            $follow[] = EndTerminal::instance();
        }

        foreach ($this->productions as $production) {
            foreach ($production->patterns as $pattern) {
                foreach ($pattern->pat as $index => $item) {
                    if ($item === $for) {

                        $ok = false;
                        while (++$index < count($pattern->pat)) {
                            $front = $pattern->pat[$index];

                            if ($front instanceof Terminal) {
                                if (!in_array($front, $follow)) {
                                    $follow[] = $front;
                                }
                                $ok = true;
                                break;
                            } elseif ($front instanceof NonTerminal) {
                                $frontFirst = $this->firsts["$front"];
                                $follow = array_unique(array_merge($follow, $frontFirst->all));

                                if (!$frontFirst->lambda) {
                                    $ok = true;
                                    break;
                                }
                            }
                        }

                        if (!$ok && !in_array($production->nonTerminal, $sees)) {
                            $this->calculateFollow($production->nonTerminal, $follow, $lambda, $sees);
                        }

                    }
                }
            }
        }
    }

    /**
     * @param NonTerminal $left
     * @return ?Production
     */
    public function getProductionFor(NonTerminal $left): ?Production
    {
        foreach ($this->productions as $production) {
            if ($production->nonTerminal === $left) {
                return $production;
            }
        }

        return null;
    }

    public function nameOf(Terminal|NonTerminal $term): ?string
    {
        $result = array_search($term, $term instanceof Terminal ? $this->terminals : $this->nonTerminals);
        return $result === false ? null : $result;
    }

    /**
     * @param (Terminal|NonTerminal)[] $sequence
     * @return FirstOrFollows
     */
    public function firstOfSequence(array $sequence): FirstOrFollows
    {
        $first = [];
        $lastLambda = true;

        foreach ($sequence as $x) {
            $lastLambda = false;

            if ($x instanceof Terminal) {
                $first[] = $x;
                break;
            }

            if ($x instanceof NonTerminal) {
                $xFirst = $this->firsts["$x"];

                $first = array_unique(array_merge($first, $xFirst->all));

                if (!$xFirst->lambda) {
                    break;
                }

                $lastLambda = true;
            }
        }

        return new FirstOrFollows($first, $lastLambda);
    }
}