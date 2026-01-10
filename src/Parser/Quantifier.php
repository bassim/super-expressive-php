<?php

declare(strict_types=1);

namespace Bassim\SuperExpressive\Parser;

final class Quantifier
{
    public int $min;
    public ?int $max;
    public bool $lazy;

    public function __construct(int $min, ?int $max, bool $lazy = false)
    {
        $this->min = $min;
        $this->max = $max;
        $this->lazy = $lazy;
    }

    public function toMethod(): string
    {
        if ($this->min === 0 && $this->max === 1) {
            return 'optional()';
        }

        if ($this->min === 0 && $this->max === null) {
            return $this->lazy ? 'zeroOrMoreLazy()' : 'zeroOrMore()';
        }

        if ($this->min === 1 && $this->max === null) {
            return $this->lazy ? 'oneOrMoreLazy()' : 'oneOrMore()';
        }

        if ($this->min === $this->max) {
            return "exactly({$this->min})";
        }

        if ($this->max === null) {
            return "atLeast({$this->min})";
        }

        return $this->lazy
            ? "betweenLazy({$this->min}, {$this->max})"
            : "between({$this->min}, {$this->max})";
    }
}
