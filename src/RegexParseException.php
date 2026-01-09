<?php

declare(strict_types=1);

namespace Bassim\SuperExpressive;

class RegexParseException extends \RuntimeException
{
    private string $regexInput;
    private int $regexPosition;

    public function __construct(
        string $message,
        string $input,
        int $position,
        ?\Throwable $previous = null
    ) {
        $this->regexInput = $input;
        $this->regexPosition = $position;

        $contextMessage = sprintf(
            "%s at position %d: %s",
            $message,
            $position,
            $this->getContext($input, $position)
        );

        parent::__construct($contextMessage, 0, $previous);
    }

    public function getRegexInput(): string
    {
        return $this->regexInput;
    }

    public function getRegexPosition(): int
    {
        return $this->regexPosition;
    }

    private function getContext(string $input, int $position): string
    {
        $start = max(0, $position - 10);
        $end = min(\strlen($input), $position + 10);
        $context = substr($input, $start, $end - $start);
        $pointerPos = min($position, 10);

        return sprintf("\n  %s\n  %s^", $context, str_repeat(' ', $pointerPos));
    }
}
