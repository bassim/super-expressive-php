<?php

declare(strict_types=1);

namespace Bassim\SuperExpressive\Parser\Nodes;

use Bassim\SuperExpressive\Parser\Node;

final class CharSetNode extends Node
{
    /** @var array<array{type: string, value?: string, from?: string, to?: string}> */
    private array $items;
    private bool $negated;

    /**
     * @param array<array{type: string, value?: string, from?: string, to?: string}> $items
     */
    public function __construct(array $items, bool $negated)
    {
        $this->items = $items;
        $this->negated = $negated;
    }

    public function toSuperExpressive(): string
    {
        // Optimize: all simple chars
        if ($this->allChars()) {
            $chars = $this->collectChars();
            $escaped = addcslashes($chars, "'\\");
            $method = $this->negated ? "anythingButChars('{$escaped}')" : "anyOfChars('{$escaped}')";
            return $this->wrapWithQuantifier($method);
        }

        // Optimize: single range
        if (\count($this->items) === 1 && $this->items[0]['type'] === 'range') {
            $from = $this->items[0]['from'];
            $to = $this->items[0]['to'];
            $method = $this->negated
                ? "anythingButRange('{$from}', '{$to}')"
                : "range('{$from}', '{$to}')";
            return $this->wrapWithQuantifier($method);
        }

        // Complex: use anyOf()
        return $this->generateComplex();
    }

    private function allChars(): bool
    {
        foreach ($this->items as $item) {
            if ($item['type'] !== 'char') {
                return false;
            }
        }
        return \count($this->items) > 0;
    }

    private function collectChars(): string
    {
        $chars = '';
        foreach ($this->items as $item) {
            $chars .= $item['value'];
        }
        return $chars;
    }

    private function generateComplex(): string
    {
        $q = $this->quantifier !== null ? $this->quantifier->toMethod() . '->' : '';
        $lines = [$q . 'anyOf()'];

        foreach ($this->items as $item) {
            if ($item['type'] === 'char') {
                $escaped = addcslashes($item['value'], "'\\");
                $lines[] = "    char('{$escaped}')";
            } else {
                $lines[] = "    range('{$item['from']}', '{$item['to']}')";
            }
        }

        $lines[] = 'end()';
        return implode("\n", $lines);
    }
}
