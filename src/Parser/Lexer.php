<?php

declare(strict_types=1);

namespace Bassim\SuperExpressive\Parser;

use Bassim\SuperExpressive\RegexParseException;

final class Lexer
{
    private const CHAR_CLASSES = [
        'd' => 'digit',
        'D' => 'nonDigit',
        'w' => 'word',
        'W' => 'nonWord',
        's' => 'whitespace',
        'S' => 'nonWhitespace',
        'n' => 'newline',
        'r' => 'carriageReturn',
        't' => 'tab',
        'b' => 'wordBoundary',
        'B' => 'nonWordBoundary',
    ];

    private const VALID_DELIMITERS = ['/', '#', '~', '@', ';', '%', '`'];
    private const VALID_FLAGS = ['i', 'm', 's', 'g', 'u', 'y'];

    private string $input;
    private string $pattern;
    private int $pos = 0;

    /** @var string[] */
    private array $flags = [];

    public function __construct(string $input)
    {
        $this->input = $input;
        $this->extractDelimitersAndFlags();
    }

    /**
     * @return Token[]
     */
    public function tokenize(): array
    {
        $tokens = [];

        while (!$this->eof()) {
            $token = $this->nextToken();
            if ($token !== null) {
                $tokens[] = $token;
            }
        }

        return $tokens;
    }

    /**
     * @return string[]
     */
    public function getFlags(): array
    {
        return $this->flags;
    }

    private function nextToken(): ?Token
    {
        $char = $this->peek();
        $pos = $this->pos;

        // Order matters - check multi-char patterns first
        return $this->matchEscape($pos)
            ?? $this->matchAnchor($char, $pos)
            ?? $this->matchDot($char, $pos)
            ?? $this->matchCharSet($char, $pos)
            ?? $this->matchGroup($char, $pos)
            ?? $this->matchQuantifier($char, $pos)
            ?? $this->matchLiteral($char, $pos);
    }

    private function matchEscape(int $pos): ?Token
    {
        if ($this->peek() !== '\\') {
            return null;
        }

        $this->advance(); // consume \
        $char = $this->advance();

        if ($char === null) {
            throw new RegexParseException('Incomplete escape sequence', $this->input, $pos);
        }

        if (isset(self::CHAR_CLASSES[$char])) {
            return new Token(Token::CHAR_CLASS, self::CHAR_CLASSES[$char], $pos);
        }

        if (ctype_digit($char)) {
            throw new RegexParseException('Backreferences are not supported', $this->input, $pos);
        }

        return new Token(Token::LITERAL, $char, $pos);
    }

    private function matchAnchor(string $char, int $pos): ?Token
    {
        if ($char === '^') {
            $this->advance();
            return new Token(Token::ANCHOR_START, '^', $pos);
        }

        if ($char === '$') {
            $this->advance();
            return new Token(Token::ANCHOR_END, '$', $pos);
        }

        return null;
    }

    private function matchDot(string $char, int $pos): ?Token
    {
        if ($char !== '.') {
            return null;
        }

        $this->advance();
        return new Token(Token::DOT, '.', $pos);
    }

    private function matchCharSet(string $char, int $pos): ?Token
    {
        if ($char !== '[') {
            return null;
        }

        $this->advance();
        $negated = false;

        if ($this->peek() === '^') {
            $negated = true;
            $this->advance();
        }

        $items = [];
        while (!$this->eof() && $this->peek() !== ']') {
            $c = $this->readCharSetChar();

            if ($this->peek() === '-' && $this->peek(1) !== null && $this->peek(1) !== ']') {
                $this->advance();
                $end = $this->readCharSetChar();
                $items[] = ['type' => 'range', 'from' => $c, 'to' => $end];
            } else {
                $items[] = ['type' => 'char', 'value' => $c];
            }
        }

        if ($this->peek() !== ']') {
            throw new RegexParseException('Unclosed character set', $this->input, $pos);
        }
        $this->advance();

        return new Token(Token::CHAR_SET, $items, $pos, ['negated' => $negated]);
    }

    private function readCharSetChar(): string
    {
        if ($this->peek() === '\\') {
            $this->advance();
            $c = $this->advance();
            return $this->unescapeCharSetChar($c);
        }

        return $this->advance();
    }

    private function unescapeCharSetChar(?string $char): string
    {
        $map = [
            'n' => "\n",
            'r' => "\r",
            't' => "\t",
            '\\' => '\\',
            ']' => ']',
            '[' => '[',
            '-' => '-',
            '^' => '^',
        ];

        return $map[$char] ?? $char;
    }

    private function matchGroup(string $char, int $pos): ?Token
    {
        if ($char !== '(') {
            return null;
        }

        $this->advance();

        if ($this->peek() === ')') {
            $this->advance();
            return null; // Empty group, skip
        }

        if ($this->peek() !== '?') {
            return new Token(Token::GROUP_START, '(', $pos);
        }

        // Special group types
        $second = $this->peek(1);
        $third = $this->peek(2);

        $groupTypes = [
            [':',  null, Token::GROUP_NC_START, 2],
            ['=',  null, Token::LOOKAHEAD, 2],
            ['!',  null, Token::NEGATIVE_LOOKAHEAD, 2],
            ['<',  '=',  Token::LOOKBEHIND, 3],
            ['<',  '!',  Token::NEGATIVE_LOOKBEHIND, 3],
        ];

        foreach ($groupTypes as [$s, $t, $type, $skip]) {
            if ($second === $s && ($t === null || $third === $t)) {
                for ($i = 0; $i < $skip; $i++) {
                    $this->advance();
                }
                return new Token($type, $type, $pos);
            }
        }

        throw new RegexParseException('Unsupported group type (named groups not supported)', $this->input, $pos);
    }

    private function matchQuantifier(string $char, int $pos): ?Token
    {
        if ($char === ')') {
            $this->advance();
            return new Token(Token::GROUP_END, ')', $pos);
        }

        if ($char === '|') {
            throw new RegexParseException('Alternation (|) is not supported', $this->input, $pos);
        }

        if (\in_array($char, ['+', '*', '?'], true)) {
            $this->advance();
            $lazy = $this->peek() === '?';
            if ($lazy) {
                $this->advance();
            }

            $bounds = ['+' => [1, null], '*' => [0, null], '?' => [0, 1]];
            [$min, $max] = $bounds[$char];

            return new Token(Token::QUANTIFIER, $char, $pos, [
                'min' => $min,
                'max' => $max,
                'lazy' => $lazy,
            ]);
        }

        if ($char === '{') {
            return $this->readBraceQuantifier($pos);
        }

        return null;
    }

    private function readBraceQuantifier(int $pos): Token
    {
        $this->advance();

        $min = $this->readNumber();
        if ($min === null) {
            throw new RegexParseException('Invalid quantifier: expected number', $this->input, $pos);
        }

        $max = $min;
        if ($this->peek() === ',') {
            $this->advance();
            $max = $this->readNumber(); // null means unbounded
        }

        if ($this->peek() !== '}') {
            throw new RegexParseException('Invalid quantifier: expected }', $this->input, $this->pos);
        }
        $this->advance();

        $lazy = $this->peek() === '?';
        if ($lazy) {
            $this->advance();
        }

        return new Token(Token::QUANTIFIER, 'brace', $pos, [
            'min' => $min,
            'max' => $max,
            'lazy' => $lazy,
        ]);
    }

    private function readNumber(): ?int
    {
        $num = '';
        while ($this->peek() !== null && ctype_digit($this->peek())) {
            $num .= $this->advance();
        }
        return $num === '' ? null : (int) $num;
    }

    private function matchLiteral(string $char, int $pos): Token
    {
        $this->advance();
        return new Token(Token::LITERAL, $char, $pos);
    }

    private function extractDelimitersAndFlags(): void
    {
        $regex = $this->input;

        if (\strlen($regex) < 2) {
            throw new RegexParseException('Invalid regex: too short', $regex, 0);
        }

        $delimiter = $regex[0];
        if (!\in_array($delimiter, self::VALID_DELIMITERS, true)) {
            throw new RegexParseException("Invalid delimiter: {$delimiter}", $regex, 0);
        }

        $lastPos = strrpos($regex, $delimiter, 1);
        if ($lastPos === false) {
            throw new RegexParseException('Missing closing delimiter', $regex, \strlen($regex));
        }

        $this->pattern = substr($regex, 1, $lastPos - 1);

        $flagStr = substr($regex, $lastPos + 1);
        for ($i = 0; $i < \strlen($flagStr); $i++) {
            $flag = $flagStr[$i];
            if (!\in_array($flag, self::VALID_FLAGS, true)) {
                throw new RegexParseException("Unknown flag: {$flag}", $this->input, $lastPos + 1 + $i);
            }
            $this->flags[] = $flag;
        }
    }

    private function peek(int $offset = 0): ?string
    {
        $p = $this->pos + $offset;
        return $p < \strlen($this->pattern) ? $this->pattern[$p] : null;
    }

    private function advance(): ?string
    {
        return $this->eof() ? null : $this->pattern[$this->pos++];
    }

    private function eof(): bool
    {
        return $this->pos >= \strlen($this->pattern);
    }
}
