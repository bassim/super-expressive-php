<?php

declare(strict_types=1);

namespace Bassim\SuperExpressive\Parser\Nodes;

use Bassim\SuperExpressive\Parser\Node;

final class LiteralNode extends Node
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function toSuperExpressive(): string
    {
        $escaped = addcslashes($this->value, "'\\");

        if (\strlen($this->value) === 1) {
            return $this->wrapWithQuantifier("char('{$escaped}')");
        }

        // Strings can't have quantifiers applied directly
        return "string('{$escaped}')";
    }
}
