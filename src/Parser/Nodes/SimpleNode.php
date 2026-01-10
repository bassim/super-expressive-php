<?php

declare(strict_types=1);

namespace Bassim\SuperExpressive\Parser\Nodes;

use Bassim\SuperExpressive\Parser\Node;

final class SimpleNode extends Node
{
    private const METHODS = [
        'startOfInput' => 'startOfInput()',
        'endOfInput' => 'endOfInput()',
        'digit' => 'digit()',
        'nonDigit' => 'nonDigit()',
        'word' => 'word()',
        'nonWord' => 'nonWord()',
        'whitespace' => 'whitespaceChar()',
        'nonWhitespace' => 'nonWhitespaceChar()',
        'anyChar' => 'anyChar()',
        'newline' => 'newline()',
        'carriageReturn' => 'carriageReturn()',
        'tab' => 'tab()',
        'wordBoundary' => 'wordBoundary()',
        'nonWordBoundary' => 'nonWordBoundary()',
    ];

    private string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function toSuperExpressive(): string
    {
        return $this->wrapWithQuantifier(self::METHODS[$this->type]);
    }
}
