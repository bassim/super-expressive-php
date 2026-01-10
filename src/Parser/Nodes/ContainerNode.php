<?php

declare(strict_types=1);

namespace Bassim\SuperExpressive\Parser\Nodes;

use Bassim\SuperExpressive\Parser\Node;

final class ContainerNode extends Node
{
    private const METHODS = [
        'group' => 'group()',
        'capture' => 'capture()',
        'assertAhead' => 'assertAhead()',
        'assertNotAhead' => 'assertNotAhead()',
        'assertBehind' => 'assertBehind()',
        'assertNotBehind' => 'assertNotBehind()',
    ];

    private string $type;

    /** @var Node[] */
    private array $children;

    /**
     * @param Node[] $children
     */
    public function __construct(string $type, array $children)
    {
        $this->type = $type;
        $this->children = $children;
    }

    public function toSuperExpressive(): string
    {
        $q = $this->quantifier !== null ? $this->quantifier->toMethod() . '->' : '';
        $lines = [$q . self::METHODS[$this->type]];

        foreach ($this->children as $child) {
            $childCode = $child->toSuperExpressive();
            foreach (explode("\n", $childCode) as $line) {
                $lines[] = '    ' . $line;
            }
        }

        $lines[] = 'end()';
        return implode("\n", $lines);
    }
}
