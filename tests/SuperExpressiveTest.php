<?php

declare(strict_types=1);

namespace Bassim\SuperExpressive\Tests;

use Bassim\SuperExpressive\SuperExpressive;
use PHPUnit\Framework\TestCase;

final class SuperExpressiveTest extends TestCase
{
    public function testErrorEmptyStringInString(): void
    {
        $this->expectException('AssertionError');

        static::assertSame(
            '//g',
            SuperExpressive::create()
                ->allowMultipleMatches()
                ->string('')
                ->toRegexString()
        );
    }

    public function testErrorCharWithMultipleChars(): void
    {
        try {
            SuperExpressive::create()->char('hello');
        } catch (\AssertionError $assertionError) {
            static::assertSame('char() can only be called with a single character (got hello)', $assertionError->getMessage());
        }
    }

    public function testErrorEndWhenCalledWithNoStack(): void
    {
        try {
            SuperExpressive::create()->end();
        } catch (\AssertionError $assertionError) {
            static::assertSame('Cannot call end while building the root expression.', $assertionError->getMessage());
        }
    }

    public function testErrorWrongRange(): void
    {
        try {
            SuperExpressive::create()->range('z', 'a');
        } catch (\AssertionError $assertionError) {
            static::assertSame('a must have a smaller character value than b (a = 122, b = 97)', $assertionError->getMessage());
        }

        try {
            SuperExpressive::create()->range('1', '0');
        } catch (\AssertionError $assertionError) {
            static::assertSame('a must have a smaller character value than b (a = 49, b = 48)', $assertionError->getMessage());
        }
    }

    public function testErrorDoubleBetween(): void
    {
        $this->expectException('RuntimeException');
        static::assertSame(
            '',
            SuperExpressive::create()
                ->between(0, 1)
                ->between(2, 3)
                ->toRegexString()
        );
    }

    public function testAllowMultipleMatches(): void
    {
        static::assertSame(
            '/hello/g',
            SuperExpressive::create()
                ->allowMultipleMatches()
                ->string('hello')
                ->toRegexString()
        );
    }

    public function testLineByLine(): void
    {
        static::assertSame(
            '/\^hello\$/m',
            SuperExpressive::create()
                ->lineByLine()
                ->string('^hello$')
                ->toRegexString()
        );
    }

    public function testCaseInsensitive(): void
    {
        static::assertSame(
            '/HELLO/i',
            SuperExpressive::create()
                ->caseInsensitive()
                ->string('HELLO')
                ->toRegexString()
        );
    }

    public function testNullByte(): void
    {
        static::assertSame(
            '/\0/',
            SuperExpressive::create()
                ->nullByte()
                ->toRegexString()
        );
    }

    public function testSingleLine(): void
    {
        static::assertSame(
            '/hello.world/s',
            SuperExpressive::create()
                ->singleLine()
                ->string('hello')
                ->anyChar()
                ->string('world')
                ->toRegexString()
        );
    }

    public function testWordBoundary(): void
    {
        static::assertSame(
            '/\d\b/',
            SuperExpressive::create()
                ->digit()
                ->wordBoundary()
                ->toRegexString()
        );
    }

    public function testNonWordBoundary(): void
    {
        static::assertSame(
            '/\d\B/',
            SuperExpressive::create()
                ->digit()
                ->nonWordBoundary()
                ->toRegexString()
        );
    }

    public function testNewline(): void
    {
        static::assertSame(
            '/\n/',
            SuperExpressive::create()
                ->newline()
                ->toRegexString()
        );
    }

    public function testWhitespaceChar(): void
    {
        static::assertSame(
            '/\s/',
            SuperExpressive::create()
                ->whitespaceChar()
                ->toRegexString()
        );
    }

    public function testNonWhitespaceChar(): void
    {
        static::assertSame(
            '/\S/',
            SuperExpressive::create()
                ->nonWhitespaceChar()
                ->toRegexString()
        );
    }

    public function testAnythingButRange(): void
    {
        static::assertSame(
            '/[^0-9]/',
            SuperExpressive::create()
                ->anythingButRange(0, 9)
                ->toRegexString()
        );
    }

    public function testAnyOf(): void
    {
        static::assertSame(
            '/(?:XXX|[a-f0-9])/',
            SuperExpressive::create()
                ->anyOf()
                ->range('a', 'f')
                ->range('0', '9')
                ->string('XXX')
                ->end()
                ->toRegexString()
        );
    }

    public function testAnyOfChars(): void
    {
        static::assertSame(
            '/[aeiou]/',
            SuperExpressive::create()
                ->anyOfChars('aeiou')
                ->toRegexString()
        );
    }

    public function testAnythingButChars(): void
    {
        static::assertSame(
            '/[^aeiou]/',
            SuperExpressive::create()
                ->anythingButChars('aeiou')
                ->toRegexString()
        );
    }

    public function testAnythingButString(): void
    {
        static::assertSame(
            '/(?:[^a][^e][^i][^o][^u])/',
            SuperExpressive::create()
                ->anythingButString('aeiou')
                ->toRegexString()
        );
    }

    public function testOptional(): void
    {
        static::assertSame(
            '/\d?/',
            SuperExpressive::create()
                ->optional()
                ->digit()
                ->toRegexString()
        );
    }

    public function testExactly(): void
    {
        static::assertSame(
            '/\d{5}/',
            SuperExpressive::create()
                ->exactly(5)
                ->digit()
                ->toRegexString()
        );
    }

    public function testOnOrMore(): void
    {
        static::assertSame(
            '/\d+/',
            SuperExpressive::create()
                ->oneOrMore()
                ->digit()
                ->toRegexString()
        );
    }

    public function testStartOfInput(): void
    {
        static::assertSame(
            '/^/',
            SuperExpressive::create()
                ->startOfInput()
                ->toRegexString()
        );
    }

    public function testEndOfInput(): void
    {
        static::assertSame(
            '/$/',
            SuperExpressive::create()
                ->endOfInput()
                ->toRegexString()
        );
    }

    public function testOneOrMoreLazy(): void
    {
        static::assertSame(
            '/\w+?/',
            SuperExpressive::create()
                ->oneOrMoreLazy()->word()
                ->toRegexString()
        );
    }

    public function testZeroOrMore(): void
    {
        static::assertSame(
            '/\w*/',
            SuperExpressive::create()
                ->zeroOrMore()->word()
                ->toRegexString()
        );
    }

    public function testZeroOrMoreLazy(): void
    {
        static::assertSame(
            '/\w*?/',
            SuperExpressive::create()
                ->zeroOrMoreLazy()->word()
                ->toRegexString()
        );
    }

    public function testBetween(): void
    {
        static::assertSame(
            '/\w{4,7}/',
            SuperExpressive::create()
                ->between(4, 7)->word()
                ->toRegexString()
        );
    }

    public function testBetweenLazy(): void
    {
        static::assertSame(
            '/\w{4,7}?/',
            SuperExpressive::create()
                ->betweenLazy(4, 7)->word()
                ->toRegexString()
        );
    }

    public function testNotAhead(): void
    {
        static::assertSame(
            '/(?![a-f])[0-9]/',
            SuperExpressive::create()
                ->assertNotAhead()
                ->range('a', 'f')
                ->end()
                ->range('0', '9')
                ->toRegexString()
        );
    }

    public function testAhead(): void
    {
        static::assertSame(
            '/(?=[a-f])[0-9]/',
            SuperExpressive::create()
                ->assertAhead()
                ->range('a', 'f')
                ->end()
                ->range('0', '9')
                ->toRegexString()
        );
    }

    public function testGroup(): void
    {
        static::assertSame(
            '/(?:hello \w\!)/',
            SuperExpressive::create()
                ->group()
                ->string('hello ')
                ->word()
                ->char('!')
                ->end()
                ->toRegexString()
        );
    }

    public function testCapture(): void
    {
        static::assertSame(
            '/(hello \w\!)/',
            SuperExpressive::create()
                ->capture()
                ->string('hello ')
                ->word()
                ->char('!')
                ->end()
                ->toRegexString()
        );
    }

    public function testNamedCapture(): void
    {
        static::assertSame(
            '/(?<this_is_the_name>hello \w\!)/',
            SuperExpressive::create()
                ->namedCapture('this_is_the_name')
                ->string('hello ')
                ->word()
                ->char('!')
                ->end()
                ->toRegexString()
        );
    }

    public function testBackReference(): void
    {
        static::assertSame(
            '/(hello \w\!)\1/',
            SuperExpressive::create()
                ->capture()
                ->string('hello ')
                ->word()
                ->char('!')
                ->end()
                ->backreference(1)
                ->toRegexString()
        );
    }

    public function testNamedBackReference(): void
    {
        static::assertSame(
            '/(?<this_is_the_name>hello \w\!)\k<this_is_the_name>/',
            SuperExpressive::create()
                ->namedCapture('this_is_the_name')
                ->string('hello ')
                ->word()
                ->char('!')
                ->end()
                ->namedBackreference('this_is_the_name')
                ->toRegexString()
        );
    }

    public function testSimpleSubexpression(): void
    {
        $simpleSubExpression = SuperExpressive::create()
            ->string('hello')
            ->anyChar()
            ->string('world')
        ;

        static::assertSame(
            '/^\d{3,}hello.world[0-9]$/',
            SuperExpressive::create()
                ->startOfInput()
                ->atLeast(3)->digit()
                ->subexpression($simpleSubExpression)
                ->range('0', '9')
                ->endOfInput()
                ->toRegexString()
        );
    }

    public function testSubExpression(): void
    {
        $fiveDigits = SuperExpressive::create()->exactly(5)->digit();

        static::assertSame(
            '/[a-z]+.{3,}\d{5}/',
            SuperExpressive::create()
                ->oneOrMore()->range('a', 'z')
                ->atLeast(3)->anyChar()
                ->subexpression($fiveDigits)
                ->toRegexString()
        );
    }

    public function testSubExpressionFlags(): void
    {
        $flagsSubExpression = SuperExpressive::create()
            ->allowMultipleMatches()
            ->unicode()
            ->lineByLine()
            ->caseInsensitive()
            ->string('hello')
            ->anyChar()
            ->string('world')
        ;

        static::assertSame(
            '/^\d{3,}hello.world[0-9]$/gymiu',
            SuperExpressive::create()
                ->sticky()
                ->startOfInput()
                ->atLeast(3)->digit()
                ->subexpression($flagsSubExpression, ['ignoreFlags' => false])
                ->range('0', '9')
                ->endOfInput()
                ->toRegexString()
        );

        static::assertSame(
            '/^\d{3,}hello.world[0-9]$/y',
            SuperExpressive::create()
                ->sticky()
                ->startOfInput()
                ->atLeast(3)->digit()
                ->subexpression($flagsSubExpression)
                ->range('0', '9')
                ->endOfInput()
                ->toRegexString()
        );
    }

    public function testRegexString(): void
    {
        static::assertSame(
            '/^(?:0x)?([A-Fa-f0-9]{4})$/gm',
            SuperExpressive::create()
                ->allowMultipleMatches()
                ->lineByLine()
                ->startOfInput()
                ->optional()->string('0x')
                ->capture()
                ->exactly(4)->anyOf()
                ->range('A', 'F')
                ->range('a', 'f')
                ->range('0', '9')
                ->end()
                ->end()
                ->endOfInput()
                ->toRegexString()
        );
    }

    public function testAssertBehind(): void
    {
        static::assertSame(
            '/(?<=hello )[a-z]/',
            SuperExpressive::create()
                ->assertBehind()
                ->string('hello ')
                ->end()
                ->range('a', 'z')
                ->toRegexString()
        );
    }

    public function testNotAssertBehind(): void
    {
        static::assertSame(
            '/(?<!hello )[a-z]/',
            SuperExpressive::create()
                ->assertNotBehind()
                ->string('hello ')
                ->end()
                ->range('a', 'z')
                ->toRegexString()
        );
    }
}
