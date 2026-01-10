<?php

declare(strict_types=1);

namespace Bassim\SuperExpressive\Parser;

abstract class Node
{
    public ?Quantifier $quantifier = null;

    public function quantify(Quantifier $q): self
    {
        $this->quantifier = $q;
        return $this;
    }

    abstract public function toSuperExpressive(): string;

    protected function wrapWithQuantifier(string $method): string
    {
        if ($this->quantifier === null) {
            return $method;
        }
        return $this->quantifier->toMethod() . '->' . $method;
    }
}
