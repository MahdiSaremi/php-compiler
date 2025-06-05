<?php

namespace Comp\Parser;

use Comp\Lexer\Token;

class AbstractTree
{
    public function __construct(
        public Node $head,
    )
    {
    }

    protected array $travelCallbacks;

    public function travel(array $callbacks): mixed
    {
        $this->travelCallbacks = $callbacks;
        $result = $this->travelThe($this->head);
        unset($this->travelCallbacks);

        return $result;
    }

    public function travelThe(Node $node): mixed
    {
        if ($node->key instanceof Token) {
            return $this->travelCallbacks[(string)$node->key->type]($node->key);
        } elseif (isset($this->travelCallbacks[$key = '#' . $node->pattern->tag])) {
            return $this->travelCallbacks[$key]($node);
        } elseif (isset($this->travelCallbacks[$key = $node->key . '#' . $node->pattern->tag])) {
            return $this->travelCallbacks[$key]($node);
        } elseif (isset($this->travelCallbacks[(string)$node->key])) {
            return $this->travelCallbacks[(string)$node->key]($node);
        } elseif (count($node->nodes) == 1) {
            return $this->travelThe($node->nodes[0]);
        } else {
            throw new \RuntimeException("Travel for " . $node->key . " not supported");
        }
    }
}