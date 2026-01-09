<?php

declare(strict_types=1);

namespace Bassim\SuperExpressive;

final class RegexParser
{
    // Token types
    private const TOKEN_ANCHOR_START = 'ANCHOR_START';
    private const TOKEN_ANCHOR_END = 'ANCHOR_END';
    private const TOKEN_CHAR_CLASS = 'CHAR_CLASS';
    private const TOKEN_DOT = 'DOT';
    private const TOKEN_CHAR_SET_START = 'CHAR_SET_START';
    private const TOKEN_CHAR_SET_END = 'CHAR_SET_END';
    private const TOKEN_GROUP_START = 'GROUP_START';
    private const TOKEN_GROUP_NC_START = 'GROUP_NC_START';
    private const TOKEN_LOOKAHEAD = 'LOOKAHEAD';
    private const TOKEN_NEGATIVE_LOOKAHEAD = 'NEGATIVE_LOOKAHEAD';
    private const TOKEN_LOOKBEHIND = 'LOOKBEHIND';
    private const TOKEN_NEGATIVE_LOOKBEHIND = 'NEGATIVE_LOOKBEHIND';
    private const TOKEN_GROUP_END = 'GROUP_END';
    private const TOKEN_QUANTIFIER = 'QUANTIFIER';
    private const TOKEN_LITERAL = 'LITERAL';

    // Method mappings
    private const CHAR_CLASS_MAP = [
        'd' => 'digit',
        'D' => 'nonDigit',
        'w' => 'word',
        'W' => 'nonWord',
        's' => 'whitespace',
        'S' => 'nonWhitespace',
    ];

    private const METHOD_MAP = [
        'digit' => 'digit()',
        'nonDigit' => 'nonDigit()',
        'word' => 'word()',
        'nonWord' => 'nonWord()',
        'whitespace' => 'whitespaceChar()',
        'nonWhitespace' => 'nonWhitespaceChar()',
        'anyChar' => 'anyChar()',
        'startOfInput' => 'startOfInput()',
        'endOfInput' => 'endOfInput()',
        'newline' => 'newline()',
        'carriageReturn' => 'carriageReturn()',
        'tab' => 'tab()',
    ];

    private const FLAG_MAP = [
        'i' => 'caseInsensitive()',
        'm' => 'lineByLine()',
        's' => 'singleLine()',
    ];

    private string $input;
    private string $pattern;
    private int $position = 0;
    private array $tokens = [];
    private array $flags = [];

    private function __construct(string $regex)
    {
        $this->input = $regex;
    }

    public static function parse(string $regex): string
    {
        $parser = new self($regex);
        $parser->extractDelimitersAndFlags();
        $parser->tokenize();
        $ast = $parser->parseTokens();

        return $parser->generate($ast);
    }

    // =========================================================================
    // LEXER
    // =========================================================================

    private function extractDelimitersAndFlags(): void
    {
        $regex = $this->input;

        if (\strlen($regex) < 2) {
            throw new RegexParseException('Invalid regex: too short', $regex, 0);
        }

        $delimiter = $regex[0];
        if (!$this->isValidDelimiter($delimiter)) {
            throw new RegexParseException("Invalid delimiter: {$delimiter}", $regex, 0);
        }

        $lastDelimiterPos = strrpos($regex, $delimiter, 1);
        if ($lastDelimiterPos === false) {
            throw new RegexParseException('Missing closing delimiter', $regex, \strlen($regex));
        }

        $this->pattern = substr($regex, 1, $lastDelimiterPos - 1);
        $flagString = substr($regex, $lastDelimiterPos + 1);

        for ($i = 0; $i < \strlen($flagString); $i++) {
            $flag = $flagString[$i];
            if (isset(self::FLAG_MAP[$flag])) {
                $this->flags[] = $flag;
            } elseif ($flag === 'g' || $flag === 'u' || $flag === 'y') {
                $this->flags[] = $flag;
            } else {
                throw new RegexParseException("Unknown flag: {$flag}", $this->input, $lastDelimiterPos + 1 + $i);
            }
        }
    }

    private function isValidDelimiter(string $char): bool
    {
        return \in_array($char, ['/', '#', '~', '@', ';', '%', '`'], true);
    }

    private function tokenize(): void
    {
        $this->position = 0;
        $this->tokens = [];

        while (!$this->isAtEnd()) {
            $token = $this->readToken();
            if ($token !== null) {
                $this->tokens[] = $token;
            }
        }
    }

    private function readToken(): ?array
    {
        $char = $this->peek();
        $pos = $this->position;

        // Escape sequences
        if ($char === '\\') {
            return $this->readEscapeSequence();
        }

        // Anchors
        if ($char === '^') {
            $this->advance();
            return ['type' => self::TOKEN_ANCHOR_START, 'value' => '^', 'position' => $pos];
        }

        if ($char === '$') {
            $this->advance();
            return ['type' => self::TOKEN_ANCHOR_END, 'value' => '$', 'position' => $pos];
        }

        // Dot
        if ($char === '.') {
            $this->advance();
            return ['type' => self::TOKEN_DOT, 'value' => '.', 'position' => $pos];
        }

        // Character set
        if ($char === '[') {
            return $this->readCharacterSet();
        }

        // Groups
        if ($char === '(') {
            $this->advance();
            if ($this->peek() === '?' && $this->peek(1) === ':') {
                $this->advance();
                $this->advance();
                return ['type' => self::TOKEN_GROUP_NC_START, 'value' => '(?:', 'position' => $pos];
            }
            // Lookahead: (?= and (?!
            if ($this->peek() === '?' && $this->peek(1) === '=') {
                $this->advance();
                $this->advance();
                return ['type' => self::TOKEN_LOOKAHEAD, 'value' => '(?=', 'position' => $pos];
            }
            if ($this->peek() === '?' && $this->peek(1) === '!') {
                $this->advance();
                $this->advance();
                return ['type' => self::TOKEN_NEGATIVE_LOOKAHEAD, 'value' => '(?!', 'position' => $pos];
            }
            // Lookbehind: (?<= and (?<!
            if ($this->peek() === '?' && $this->peek(1) === '<' && $this->peek(2) === '=') {
                $this->advance();
                $this->advance();
                $this->advance();
                return ['type' => self::TOKEN_LOOKBEHIND, 'value' => '(?<=', 'position' => $pos];
            }
            if ($this->peek() === '?' && $this->peek(1) === '<' && $this->peek(2) === '!') {
                $this->advance();
                $this->advance();
                $this->advance();
                return ['type' => self::TOKEN_NEGATIVE_LOOKBEHIND, 'value' => '(?<!', 'position' => $pos];
            }
            // Named groups still not supported
            if ($this->peek() === '?') {
                throw new RegexParseException(
                    'Unsupported group type (named groups not supported)',
                    $this->input,
                    $pos
                );
            }
            return ['type' => self::TOKEN_GROUP_START, 'value' => '(', 'position' => $pos];
        }

        if ($char === ')') {
            $this->advance();
            return ['type' => self::TOKEN_GROUP_END, 'value' => ')', 'position' => $pos];
        }

        // Quantifiers
        if (\in_array($char, ['+', '*', '?'], true)) {
            return $this->readQuantifier();
        }

        if ($char === '{') {
            return $this->readBraceQuantifier();
        }

        // Alternation
        if ($char === '|') {
            throw new RegexParseException(
                'Alternation (|) outside of anyOf context is not supported in v1',
                $this->input,
                $pos
            );
        }

        // Literal
        $this->advance();
        return ['type' => self::TOKEN_LITERAL, 'value' => $char, 'position' => $pos];
    }

    private function readEscapeSequence(): array
    {
        $pos = $this->position;
        $this->advance(); // consume \

        $char = $this->peek();
        if ($char === null) {
            throw new RegexParseException('Incomplete escape sequence', $this->input, $pos);
        }

        $this->advance();

        // Character classes
        if (isset(self::CHAR_CLASS_MAP[$char])) {
            return [
                'type' => self::TOKEN_CHAR_CLASS,
                'value' => self::CHAR_CLASS_MAP[$char],
                'position' => $pos,
            ];
        }

        // Special characters
        if ($char === 'n') {
            return ['type' => self::TOKEN_CHAR_CLASS, 'value' => 'newline', 'position' => $pos];
        }
        if ($char === 'r') {
            return ['type' => self::TOKEN_CHAR_CLASS, 'value' => 'carriageReturn', 'position' => $pos];
        }
        if ($char === 't') {
            return ['type' => self::TOKEN_CHAR_CLASS, 'value' => 'tab', 'position' => $pos];
        }

        // Word boundary
        if ($char === 'b') {
            return ['type' => self::TOKEN_CHAR_CLASS, 'value' => 'wordBoundary', 'position' => $pos];
        }
        if ($char === 'B') {
            return ['type' => self::TOKEN_CHAR_CLASS, 'value' => 'nonWordBoundary', 'position' => $pos];
        }

        // Backreference check
        if (ctype_digit($char)) {
            throw new RegexParseException('Backreferences are not supported in v1', $this->input, $pos);
        }

        // Escaped literal
        return ['type' => self::TOKEN_LITERAL, 'value' => $char, 'position' => $pos];
    }

    private function readQuantifier(): array
    {
        $pos = $this->position;
        $char = $this->advance();
        $lazy = false;

        if ($this->peek() === '?') {
            $this->advance();
            $lazy = true;
        }

        return [
            'type' => self::TOKEN_QUANTIFIER,
            'value' => $char,
            'position' => $pos,
            'lazy' => $lazy,
            'min' => $char === '+' ? 1 : 0,
            'max' => $char === '?' ? 1 : null,
        ];
    }

    private function readBraceQuantifier(): array
    {
        $pos = $this->position;
        $this->advance(); // consume {

        $numStr = '';
        while ($this->peek() !== null && ctype_digit($this->peek())) {
            $numStr .= $this->advance();
        }

        if ($numStr === '') {
            throw new RegexParseException('Invalid quantifier: expected number', $this->input, $pos);
        }

        $min = (int) $numStr;
        $max = $min;

        if ($this->peek() === ',') {
            $this->advance();
            $maxStr = '';
            while ($this->peek() !== null && ctype_digit($this->peek())) {
                $maxStr .= $this->advance();
            }
            $max = $maxStr === '' ? null : (int) $maxStr;
        }

        if ($this->peek() !== '}') {
            throw new RegexParseException('Invalid quantifier: expected }', $this->input, $this->position);
        }
        $this->advance();

        $lazy = false;
        if ($this->peek() === '?') {
            $this->advance();
            $lazy = true;
        }

        return [
            'type' => self::TOKEN_QUANTIFIER,
            'value' => 'brace',
            'position' => $pos,
            'lazy' => $lazy,
            'min' => $min,
            'max' => $max,
        ];
    }

    private function readCharacterSet(): array
    {
        $pos = $this->position;
        $this->advance(); // consume [

        $negated = false;
        if ($this->peek() === '^') {
            $negated = true;
            $this->advance();
        }

        $items = [];
        while ($this->peek() !== null && $this->peek() !== ']') {
            $char = $this->peek();

            if ($char === '\\') {
                $this->advance();
                $escaped = $this->advance();
                if ($escaped === null) {
                    throw new RegexParseException('Incomplete escape in character set', $this->input, $this->position);
                }
                $char = $this->resolveEscapeInCharSet($escaped);
            } else {
                $this->advance();
            }

            // Check for range
            if ($this->peek() === '-' && $this->peek(1) !== null && $this->peek(1) !== ']') {
                $this->advance(); // consume -
                $endChar = $this->peek();
                if ($endChar === '\\') {
                    $this->advance();
                    $endChar = $this->resolveEscapeInCharSet($this->advance());
                } else {
                    $this->advance();
                }
                $items[] = ['type' => 'range', 'from' => $char, 'to' => $endChar];
            } else {
                $items[] = ['type' => 'char', 'value' => $char];
            }
        }

        if ($this->peek() !== ']') {
            throw new RegexParseException('Unclosed character set', $this->input, $pos);
        }
        $this->advance();

        return [
            'type' => self::TOKEN_CHAR_SET_START,
            'value' => $items,
            'position' => $pos,
            'negated' => $negated,
        ];
    }

    private function resolveEscapeInCharSet(string $char): string
    {
        $map = ['n' => "\n", 'r' => "\r", 't' => "\t", '\\' => '\\', ']' => ']', '[' => '[', '-' => '-', '^' => '^'];
        return $map[$char] ?? $char;
    }

    private function peek(int $offset = 0): ?string
    {
        $pos = $this->position + $offset;
        return $pos < \strlen($this->pattern) ? $this->pattern[$pos] : null;
    }

    private function advance(): ?string
    {
        if ($this->isAtEnd()) {
            return null;
        }
        return $this->pattern[$this->position++];
    }

    private function isAtEnd(): bool
    {
        return $this->position >= \strlen($this->pattern);
    }

    // =========================================================================
    // PARSER
    // =========================================================================

    private int $tokenIndex = 0;

    private function parseTokens(): array
    {
        $this->tokenIndex = 0;
        $children = [];

        // Add flags first
        foreach ($this->flags as $flag) {
            if (isset(self::FLAG_MAP[$flag])) {
                $children[] = ['type' => 'flag', 'value' => $flag];
            }
        }

        // Parse expression
        while ($this->tokenIndex < \count($this->tokens)) {
            $node = $this->parseAtom();
            if ($node !== null) {
                $children[] = $node;
            }
        }

        return ['type' => 'root', 'children' => $children];
    }

    private function parseAtom(): ?array
    {
        if ($this->tokenIndex >= \count($this->tokens)) {
            return null;
        }

        $token = $this->tokens[$this->tokenIndex];

        switch ($token['type']) {
            case self::TOKEN_ANCHOR_START:
                $this->tokenIndex++;
                return ['type' => 'startOfInput'];

            case self::TOKEN_ANCHOR_END:
                $this->tokenIndex++;
                return ['type' => 'endOfInput'];

            case self::TOKEN_CHAR_CLASS:
                $this->tokenIndex++;
                $node = ['type' => $token['value']];
                return $this->applyQuantifierIfPresent($node);

            case self::TOKEN_DOT:
                $this->tokenIndex++;
                $node = ['type' => 'anyChar'];
                return $this->applyQuantifierIfPresent($node);

            case self::TOKEN_LITERAL:
                return $this->parseLiterals();

            case self::TOKEN_CHAR_SET_START:
                $this->tokenIndex++;
                $node = $this->buildCharSetNode($token);
                return $this->applyQuantifierIfPresent($node);

            case self::TOKEN_GROUP_START:
            case self::TOKEN_GROUP_NC_START:
                return $this->parseGroup($token['type'] === self::TOKEN_GROUP_START);

            case self::TOKEN_LOOKAHEAD:
                return $this->parseAssertion('assertAhead');

            case self::TOKEN_NEGATIVE_LOOKAHEAD:
                return $this->parseAssertion('assertNotAhead');

            case self::TOKEN_LOOKBEHIND:
                return $this->parseAssertion('assertBehind');

            case self::TOKEN_NEGATIVE_LOOKBEHIND:
                return $this->parseAssertion('assertNotBehind');

            case self::TOKEN_GROUP_END:
                return null;

            case self::TOKEN_QUANTIFIER:
                throw new RegexParseException(
                    'Unexpected quantifier without preceding element',
                    $this->input,
                    $token['position']
                );

            default:
                $this->tokenIndex++;
                return null;
        }
    }

    private function parseLiterals(): array
    {
        $literals = '';
        $startPos = $this->tokenIndex;

        // Collect consecutive literals
        while ($this->tokenIndex < \count($this->tokens)) {
            $token = $this->tokens[$this->tokenIndex];
            if ($token['type'] !== self::TOKEN_LITERAL) {
                break;
            }

            // Check if next token is a quantifier
            $nextToken = $this->tokens[$this->tokenIndex + 1] ?? null;
            if ($nextToken && $nextToken['type'] === self::TOKEN_QUANTIFIER) {
                // If we have accumulated literals, return them first
                if (\strlen($literals) > 0) {
                    break;
                }
                // Otherwise, this single char gets quantified
                $this->tokenIndex++;
                $node = ['type' => 'char', 'value' => $token['value']];
                return $this->applyQuantifierIfPresent($node);
            }

            $literals .= $token['value'];
            $this->tokenIndex++;
        }

        if (\strlen($literals) === 1) {
            return ['type' => 'char', 'value' => $literals];
        }

        return ['type' => 'string', 'value' => $literals];
    }

    private function buildCharSetNode(array $token): array
    {
        $items = $token['value'];
        $negated = $token['negated'];

        // Optimize: simple chars only
        $allChars = true;
        $chars = '';
        foreach ($items as $item) {
            if ($item['type'] === 'char') {
                $chars .= $item['value'];
            } else {
                $allChars = false;
                break;
            }
        }

        if ($allChars && \strlen($chars) > 0) {
            return [
                'type' => $negated ? 'anythingButChars' : 'anyOfChars',
                'value' => $chars,
            ];
        }

        // Single range
        if (\count($items) === 1 && $items[0]['type'] === 'range') {
            if ($negated) {
                return [
                    'type' => 'anythingButRange',
                    'from' => $items[0]['from'],
                    'to' => $items[0]['to'],
                ];
            }
            return [
                'type' => 'range',
                'from' => $items[0]['from'],
                'to' => $items[0]['to'],
            ];
        }

        // Complex: use anyOf
        return [
            'type' => 'charSet',
            'items' => $items,
            'negated' => $negated,
        ];
    }

    private function parseGroup(bool $capturing): array
    {
        $this->tokenIndex++; // consume group start

        $children = [];
        while ($this->tokenIndex < \count($this->tokens)) {
            $token = $this->tokens[$this->tokenIndex];
            if ($token['type'] === self::TOKEN_GROUP_END) {
                $this->tokenIndex++;
                break;
            }
            $node = $this->parseAtom();
            if ($node !== null) {
                $children[] = $node;
            }
        }

        $node = [
            'type' => $capturing ? 'capture' : 'group',
            'children' => $children,
        ];

        return $this->applyQuantifierIfPresent($node);
    }

    private function parseAssertion(string $type): array
    {
        $this->tokenIndex++; // consume assertion start

        $children = [];
        while ($this->tokenIndex < \count($this->tokens)) {
            $token = $this->tokens[$this->tokenIndex];
            if ($token['type'] === self::TOKEN_GROUP_END) {
                $this->tokenIndex++;
                break;
            }
            $node = $this->parseAtom();
            if ($node !== null) {
                $children[] = $node;
            }
        }

        // Assertions cannot have quantifiers
        return [
            'type' => $type,
            'children' => $children,
        ];
    }

    private function applyQuantifierIfPresent(array $node): array
    {
        if ($this->tokenIndex >= \count($this->tokens)) {
            return $node;
        }

        $token = $this->tokens[$this->tokenIndex];
        if ($token['type'] !== self::TOKEN_QUANTIFIER) {
            return $node;
        }

        $this->tokenIndex++;
        $node['quantifier'] = [
            'min' => $token['min'],
            'max' => $token['max'],
            'lazy' => $token['lazy'],
        ];

        return $node;
    }

    // =========================================================================
    // GENERATOR
    // =========================================================================

    private function generate(array $ast): string
    {
        $lines = ['SuperExpressive::create()'];

        foreach ($ast['children'] as $node) {
            $generated = $this->generateNode($node, 1);
            if (\is_array($generated)) {
                foreach ($generated as $line) {
                    $lines[] = $line;
                }
            } else {
                $lines[] = $generated;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @return string|array
     */
    private function generateNode(array $node, int $indent)
    {
        $prefix = str_repeat('    ', $indent) . '->';

        switch ($node['type']) {
            case 'flag':
                return $prefix . self::FLAG_MAP[$node['value']];

            case 'startOfInput':
            case 'endOfInput':
            case 'digit':
            case 'nonDigit':
            case 'word':
            case 'nonWord':
            case 'whitespace':
            case 'nonWhitespace':
            case 'anyChar':
            case 'newline':
            case 'carriageReturn':
            case 'tab':
                $method = self::METHOD_MAP[$node['type']];
                return $this->wrapWithQuantifier($prefix, $method, $node);

            case 'wordBoundary':
                return $prefix . 'wordBoundary()';

            case 'nonWordBoundary':
                return $prefix . 'nonWordBoundary()';

            case 'char':
                $escaped = $this->escapePhpString($node['value']);
                $method = "char('{$escaped}')";
                return $this->wrapWithQuantifier($prefix, $method, $node);

            case 'string':
                $escaped = $this->escapePhpString($node['value']);
                return $prefix . "string('{$escaped}')";

            case 'anyOfChars':
                $escaped = $this->escapePhpString($node['value']);
                $method = "anyOfChars('{$escaped}')";
                return $this->wrapWithQuantifier($prefix, $method, $node);

            case 'anythingButChars':
                $escaped = $this->escapePhpString($node['value']);
                $method = "anythingButChars('{$escaped}')";
                return $this->wrapWithQuantifier($prefix, $method, $node);

            case 'range':
                $method = "range('{$node['from']}', '{$node['to']}')";
                return $this->wrapWithQuantifier($prefix, $method, $node);

            case 'anythingButRange':
                $method = "anythingButRange('{$node['from']}', '{$node['to']}')";
                return $this->wrapWithQuantifier($prefix, $method, $node);

            case 'charSet':
                return $this->generateComplexCharSet($node, $indent);

            case 'group':
            case 'capture':
                return $this->generateGroup($node, $indent);

            case 'assertAhead':
            case 'assertNotAhead':
            case 'assertBehind':
            case 'assertNotBehind':
                return $this->generateAssertion($node, $indent);

            default:
                return $prefix . "/* unsupported: {$node['type']} */";
        }
    }

    private function wrapWithQuantifier(string $prefix, string $method, array $node): string
    {
        if (!isset($node['quantifier'])) {
            return $prefix . $method;
        }

        $q = $node['quantifier'];
        $quantifierMethod = $this->getQuantifierMethod($q);

        return $prefix . $quantifierMethod . '->' . $method;
    }

    private function getQuantifierMethod(array $q): string
    {
        $min = $q['min'];
        $max = $q['max'];
        $lazy = $q['lazy'];

        if ($min === 0 && $max === 1) {
            return 'optional()';
        }

        if ($min === 0 && $max === null) {
            return $lazy ? 'zeroOrMoreLazy()' : 'zeroOrMore()';
        }

        if ($min === 1 && $max === null) {
            return $lazy ? 'oneOrMoreLazy()' : 'oneOrMore()';
        }

        if ($min === $max) {
            return "exactly({$min})";
        }

        if ($max === null) {
            return "atLeast({$min})";
        }

        return $lazy ? "betweenLazy({$min}, {$max})" : "between({$min}, {$max})";
    }

    private function generateComplexCharSet(array $node, int $indent): array
    {
        $prefix = str_repeat('    ', $indent) . '->';
        $innerPrefix = str_repeat('    ', $indent + 1) . '->';

        $lines = [];

        $quantifier = isset($node['quantifier']) ? $this->getQuantifierMethod($node['quantifier']) . '->' : '';
        $lines[] = $prefix . $quantifier . 'anyOf()';

        foreach ($node['items'] as $item) {
            if ($item['type'] === 'char') {
                $escaped = $this->escapePhpString($item['value']);
                $lines[] = $innerPrefix . "char('{$escaped}')";
            } else {
                $lines[] = $innerPrefix . "range('{$item['from']}', '{$item['to']}')";
            }
        }

        $lines[] = $innerPrefix . 'end()';

        return $lines;
    }

    private function generateGroup(array $node, int $indent): array
    {
        $prefix = str_repeat('    ', $indent) . '->';
        $innerIndent = $indent + 1;

        $lines = [];

        $quantifier = isset($node['quantifier']) ? $this->getQuantifierMethod($node['quantifier']) . '->' : '';
        $groupMethod = $node['type'] === 'capture' ? 'capture()' : 'group()';
        $lines[] = $prefix . $quantifier . $groupMethod;

        foreach ($node['children'] as $child) {
            $generated = $this->generateNode($child, $innerIndent);
            if (\is_array($generated)) {
                foreach ($generated as $line) {
                    $lines[] = $line;
                }
            } else {
                $lines[] = $generated;
            }
        }

        $lines[] = str_repeat('    ', $innerIndent) . '->end()';

        return $lines;
    }

    private function generateAssertion(array $node, int $indent): array
    {
        $prefix = str_repeat('    ', $indent) . '->';
        $innerIndent = $indent + 1;

        $methodMap = [
            'assertAhead' => 'assertAhead()',
            'assertNotAhead' => 'assertNotAhead()',
            'assertBehind' => 'assertBehind()',
            'assertNotBehind' => 'assertNotBehind()',
        ];

        $lines = [];
        $lines[] = $prefix . $methodMap[$node['type']];

        foreach ($node['children'] as $child) {
            $generated = $this->generateNode($child, $innerIndent);
            if (\is_array($generated)) {
                foreach ($generated as $line) {
                    $lines[] = $line;
                }
            } else {
                $lines[] = $generated;
            }
        }

        $lines[] = str_repeat('    ', $innerIndent) . '->end()';

        return $lines;
    }

    private function escapePhpString(string $str): string
    {
        return addcslashes($str, "'\\");
    }
}
