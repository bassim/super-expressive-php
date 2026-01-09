<?php

declare(strict_types=1);

namespace Bassim\SuperExpressive\Tests;

use Bassim\SuperExpressive\RegexParser;
use Bassim\SuperExpressive\RegexParseException;
use PHPUnit\Framework\TestCase;

final class RegexParserTest extends TestCase
{
    // =========================================================================
    // ANCHORS
    // =========================================================================

    public function testParseStartAnchor(): void
    {
        $result = RegexParser::parse('/^/');
        static::assertStringContainsString('startOfInput()', $result);
    }

    public function testParseEndAnchor(): void
    {
        $result = RegexParser::parse('/$/');
        static::assertStringContainsString('endOfInput()', $result);
    }

    public function testParseBothAnchors(): void
    {
        $result = RegexParser::parse('/^$/');
        static::assertStringContainsString('startOfInput()', $result);
        static::assertStringContainsString('endOfInput()', $result);
    }

    // =========================================================================
    // CHARACTER CLASSES
    // =========================================================================

    public function testParseDigit(): void
    {
        $result = RegexParser::parse('/\d/');
        static::assertStringContainsString('digit()', $result);
    }

    public function testParseNonDigit(): void
    {
        $result = RegexParser::parse('/\D/');
        static::assertStringContainsString('nonDigit()', $result);
    }

    public function testParseWord(): void
    {
        $result = RegexParser::parse('/\w/');
        static::assertStringContainsString('word()', $result);
    }

    public function testParseNonWord(): void
    {
        $result = RegexParser::parse('/\W/');
        static::assertStringContainsString('nonWord()', $result);
    }

    public function testParseWhitespace(): void
    {
        $result = RegexParser::parse('/\s/');
        static::assertStringContainsString('whitespaceChar()', $result);
    }

    public function testParseNonWhitespace(): void
    {
        $result = RegexParser::parse('/\S/');
        static::assertStringContainsString('nonWhitespaceChar()', $result);
    }

    public function testParseDot(): void
    {
        $result = RegexParser::parse('/./');
        static::assertStringContainsString('anyChar()', $result);
    }

    // =========================================================================
    // SPECIAL CHARACTERS
    // =========================================================================

    public function testParseNewline(): void
    {
        $result = RegexParser::parse('/\n/');
        static::assertStringContainsString('newline()', $result);
    }

    public function testParseTab(): void
    {
        $result = RegexParser::parse('/\t/');
        static::assertStringContainsString('tab()', $result);
    }

    public function testParseCarriageReturn(): void
    {
        $result = RegexParser::parse('/\r/');
        static::assertStringContainsString('carriageReturn()', $result);
    }

    // =========================================================================
    // QUANTIFIERS
    // =========================================================================

    public function testParseOneOrMore(): void
    {
        $result = RegexParser::parse('/\d+/');
        static::assertStringContainsString('oneOrMore()', $result);
        static::assertStringContainsString('digit()', $result);
    }

    public function testParseZeroOrMore(): void
    {
        $result = RegexParser::parse('/\d*/');
        static::assertStringContainsString('zeroOrMore()', $result);
        static::assertStringContainsString('digit()', $result);
    }

    public function testParseOptional(): void
    {
        $result = RegexParser::parse('/\d?/');
        static::assertStringContainsString('optional()', $result);
        static::assertStringContainsString('digit()', $result);
    }

    public function testParseExactly(): void
    {
        $result = RegexParser::parse('/\d{3}/');
        static::assertStringContainsString('exactly(3)', $result);
        static::assertStringContainsString('digit()', $result);
    }

    public function testParseAtLeast(): void
    {
        $result = RegexParser::parse('/\d{3,}/');
        static::assertStringContainsString('atLeast(3)', $result);
        static::assertStringContainsString('digit()', $result);
    }

    public function testParseBetween(): void
    {
        $result = RegexParser::parse('/\d{3,5}/');
        static::assertStringContainsString('between(3, 5)', $result);
        static::assertStringContainsString('digit()', $result);
    }

    // =========================================================================
    // LAZY QUANTIFIERS
    // =========================================================================

    public function testParseOneOrMoreLazy(): void
    {
        $result = RegexParser::parse('/\d+?/');
        static::assertStringContainsString('oneOrMoreLazy()', $result);
    }

    public function testParseZeroOrMoreLazy(): void
    {
        $result = RegexParser::parse('/\d*?/');
        static::assertStringContainsString('zeroOrMoreLazy()', $result);
    }

    public function testParseBetweenLazy(): void
    {
        $result = RegexParser::parse('/\d{3,5}?/');
        static::assertStringContainsString('betweenLazy(3, 5)', $result);
    }

    // =========================================================================
    // CHARACTER SETS
    // =========================================================================

    public function testParseSimpleCharSet(): void
    {
        $result = RegexParser::parse('/[abc]/');
        static::assertStringContainsString("anyOfChars('abc')", $result);
    }

    public function testParseNegatedCharSet(): void
    {
        $result = RegexParser::parse('/[^abc]/');
        static::assertStringContainsString("anythingButChars('abc')", $result);
    }

    public function testParseCharRange(): void
    {
        $result = RegexParser::parse('/[a-z]/');
        static::assertStringContainsString("range('a', 'z')", $result);
    }

    public function testParseNegatedCharRange(): void
    {
        $result = RegexParser::parse('/[^0-9]/');
        static::assertStringContainsString("anythingButRange('0', '9')", $result);
    }

    public function testParseComplexCharSet(): void
    {
        $result = RegexParser::parse('/[a-zA-Z0-9]/');
        static::assertStringContainsString('anyOf()', $result);
        static::assertStringContainsString("range('a', 'z')", $result);
        static::assertStringContainsString("range('A', 'Z')", $result);
        static::assertStringContainsString("range('0', '9')", $result);
        static::assertStringContainsString('end()', $result);
    }

    // =========================================================================
    // GROUPS
    // =========================================================================

    public function testParseNonCapturingGroup(): void
    {
        $result = RegexParser::parse('/(?:abc)/');
        static::assertStringContainsString('group()', $result);
        static::assertStringContainsString("string('abc')", $result);
        static::assertStringContainsString('end()', $result);
    }

    public function testParseCapturingGroup(): void
    {
        $result = RegexParser::parse('/(abc)/');
        static::assertStringContainsString('capture()', $result);
        static::assertStringContainsString("string('abc')", $result);
        static::assertStringContainsString('end()', $result);
    }

    public function testParseGroupWithQuantifier(): void
    {
        $result = RegexParser::parse('/(?:abc)+/');
        static::assertStringContainsString('oneOrMore()->group()', $result);
    }

    // =========================================================================
    // LITERALS
    // =========================================================================

    public function testParseLiteralString(): void
    {
        $result = RegexParser::parse('/hello/');
        static::assertStringContainsString("string('hello')", $result);
    }

    public function testParseSingleChar(): void
    {
        $result = RegexParser::parse('/a/');
        static::assertStringContainsString("char('a')", $result);
    }

    public function testParseSingleCharWithQuantifier(): void
    {
        $result = RegexParser::parse('/a+/');
        static::assertStringContainsString('oneOrMore()', $result);
        static::assertStringContainsString("char('a')", $result);
    }

    public function testParseEscapedSpecialChars(): void
    {
        $result = RegexParser::parse('/\./');
        static::assertStringContainsString("char('.')", $result);
    }

    // =========================================================================
    // FLAGS
    // =========================================================================

    public function testParseCaseInsensitiveFlag(): void
    {
        $result = RegexParser::parse('/abc/i');
        static::assertStringContainsString('caseInsensitive()', $result);
    }

    public function testParseMultilineFlag(): void
    {
        $result = RegexParser::parse('/abc/m');
        static::assertStringContainsString('lineByLine()', $result);
    }

    public function testParseSingleLineFlag(): void
    {
        $result = RegexParser::parse('/abc/s');
        static::assertStringContainsString('singleLine()', $result);
    }

    public function testParseMultipleFlags(): void
    {
        $result = RegexParser::parse('/abc/ims');
        static::assertStringContainsString('caseInsensitive()', $result);
        static::assertStringContainsString('lineByLine()', $result);
        static::assertStringContainsString('singleLine()', $result);
    }

    // =========================================================================
    // COMPLEX PATTERNS
    // =========================================================================

    public function testParseEmailLikePattern(): void
    {
        $result = RegexParser::parse('/^\w+@\w+\.\w+$/');
        static::assertStringContainsString('startOfInput()', $result);
        static::assertStringContainsString('oneOrMore()->word()', $result);
        static::assertStringContainsString("char('@')", $result);
        static::assertStringContainsString("char('.')", $result);
        static::assertStringContainsString('endOfInput()', $result);
    }

    public function testParsePhonePattern(): void
    {
        $result = RegexParser::parse('/^\d{3}-\d{4}$/');
        static::assertStringContainsString('startOfInput()', $result);
        static::assertStringContainsString('exactly(3)->digit()', $result);
        static::assertStringContainsString("char('-')", $result);
        static::assertStringContainsString('exactly(4)->digit()', $result);
        static::assertStringContainsString('endOfInput()', $result);
    }

    // =========================================================================
    // ERROR HANDLING
    // =========================================================================

    public function testErrorOnInvalidDelimiter(): void
    {
        $this->expectException(RegexParseException::class);
        RegexParser::parse('abc');
    }

    public function testErrorOnMissingClosingDelimiter(): void
    {
        $this->expectException(RegexParseException::class);
        RegexParser::parse('/abc');
    }

    public function testErrorOnUnclosedCharacterSet(): void
    {
        $this->expectException(RegexParseException::class);
        RegexParser::parse('/[abc/');
    }

    public function testErrorOnLookahead(): void
    {
        $this->expectException(RegexParseException::class);
        $this->expectExceptionMessage('lookahead');
        RegexParser::parse('/(?=abc)/');
    }

    public function testErrorOnBackreference(): void
    {
        $this->expectException(RegexParseException::class);
        $this->expectExceptionMessage('Backreference');
        RegexParser::parse('/(\w)\1/');
    }

    public function testErrorOnAlternation(): void
    {
        $this->expectException(RegexParseException::class);
        $this->expectExceptionMessage('Alternation');
        RegexParser::parse('/a|b/');
    }

    // =========================================================================
    // ROUND-TRIP TESTS
    // =========================================================================

    /**
     * @dataProvider roundTripProvider
     */
    public function testRoundTrip(string $regex): void
    {
        $code = RegexParser::parse($regex);
        $generatedRegex = eval('use Bassim\SuperExpressive\SuperExpressive; return ' . $code . '->toRegexString();');

        static::assertEquals($regex, $generatedRegex, "Round-trip failed for: {$regex}\nGenerated code:\n{$code}");
    }

    public function roundTripProvider(): array
    {
        return [
            'simple digit' => ['/\d/'],
            'digit with quantifier' => ['/\d+/'],
            'anchored pattern' => ['/^\d+$/'],
            'character class' => ['/[a-z]/'],
            'escaped dot' => ['/\./'],
            'simple group' => ['/(?:abc)/'],
        ];
    }
}
