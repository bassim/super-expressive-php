<?php

declare(strict_types=1);

namespace Bassim\SuperExpressive\Parser;

final class Token
{
    public const ANCHOR_START = 'ANCHOR_START';
    public const ANCHOR_END = 'ANCHOR_END';
    public const CHAR_CLASS = 'CHAR_CLASS';
    public const DOT = 'DOT';
    public const CHAR_SET = 'CHAR_SET';
    public const GROUP_START = 'GROUP_START';
    public const GROUP_NC_START = 'GROUP_NC_START';
    public const LOOKAHEAD = 'LOOKAHEAD';
    public const NEGATIVE_LOOKAHEAD = 'NEGATIVE_LOOKAHEAD';
    public const LOOKBEHIND = 'LOOKBEHIND';
    public const NEGATIVE_LOOKBEHIND = 'NEGATIVE_LOOKBEHIND';
    public const GROUP_END = 'GROUP_END';
    public const QUANTIFIER = 'QUANTIFIER';
    public const LITERAL = 'LITERAL';

    public string $type;
    public int $position;

    /** @var mixed */
    public $value;

    /** @var array<string, mixed> */
    public array $meta;

    /**
     * @param mixed $value
     * @param array<string, mixed> $meta
     */
    public function __construct(string $type, $value, int $position, array $meta = [])
    {
        $this->type = $type;
        $this->value = $value;
        $this->position = $position;
        $this->meta = $meta;
    }

    public function is(string $type): bool
    {
        return $this->type === $type;
    }
}
