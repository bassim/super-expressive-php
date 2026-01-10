<?php

declare(strict_types=1);

namespace Bassim\SuperExpressive\Parser\Nodes;

use Bassim\SuperExpressive\Parser\Node;

final class FlagNode extends Node
{
    private const METHODS = [
        'i' => 'caseInsensitive()',
        'm' => 'lineByLine()',
        's' => 'singleLine()',
    ];

    private string $flag;

    public function __construct(string $flag)
    {
        $this->flag = $flag;
    }

    public function toSuperExpressive(): string
    {
        return self::METHODS[$this->flag] ?? '';
    }
}
