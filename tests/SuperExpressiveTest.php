<?php

declare(strict_types=1);

namespace Bassim\SuperExpressive\Tests;

use Bassim\SuperExpressive\SuperExpressive;
use PHPUnit\Framework\TestCase;

final class SuperExpressiveTest extends TestCase
{

    public function test_empty_string_in_string(): void
    {
        $this->expectException('AssertionError');

        $this->assertEquals('//g',
            SuperExpressive::create()
                ->allowMultipleMatches()
                ->string('')
                ->toRegexString()
        );
    }

    public function test_allow_multiple_matches(): void
    {

        $this->assertEquals('/hello/g',
            SuperExpressive::create()
                ->allowMultipleMatches()
                ->string('hello')
                ->toRegexString()
        );
    }

    public function test_line_by_line(): void
    {

        $this->assertEquals('/\^hello\$/m',
            SuperExpressive::create()
                ->lineByLine()
                ->string('^hello$')
                ->toRegexString()
        );
    }

    public function test_case_insensitive(): void
    {

        $this->assertEquals('/HELLO/i',
            SuperExpressive::create()
                ->caseInsensitive()
                ->string('HELLO')
                ->toRegexString()
        );
    }

    public function test_null_byte(): void
    {

        $this->assertEquals('/\0/',
            SuperExpressive::create()
                ->nullByte()
                ->toRegexString()
        );
    }

    public function test_single_line(): void
    {

        $this->assertEquals('/hello.world/s',
            SuperExpressive::create()
                ->singleLine()
                ->string('hello')
                ->anyChar()
                ->string('world')
                ->toRegexString()
        );
    }

    public function test_word_boundary(): void
    {

        $this->assertEquals('/\d\b/',
            SuperExpressive::create()
                ->digit()
                ->wordBoundary()
                ->toRegexString()
        );
    }

    public function test_non_word_boundary(): void
    {

        $this->assertEquals('/\d\B/',
            SuperExpressive::create()
                ->digit()
                ->nonWordBoundary()
                ->toRegexString()
        );
    }

    public function test_newline(): void
    {

        $this->assertEquals('/\n/',
            SuperExpressive::create()
                ->newline()
                ->toRegexString()
        );
    }


    public function test_anything_but_range(): void
    {

        $this->assertEquals('/[^0-9]/',
            SuperExpressive::create()
                ->anythingButRange(0, 9)
                ->toRegexString()
        );
    }

    public function test_any_of(): void
    {

        $this->assertEquals('/(?:XXX|[a-f0-9])/',
            SuperExpressive::create()
                ->anyOf()
                    ->range('a','f')
                    ->range('0','9')
                    ->string('XXX')
                ->end()
                ->toRegexString()
        );

    }

    public function test_any_of_chars(): void
    {

        $this->assertEquals('/[aeiou]/',
            SuperExpressive::create()
                ->anyOfChars('aeiou')
                ->toRegexString()
        );

    }

    public function test_anything_but_chars(): void
    {

        $this->assertEquals('/[^aeiou]/',
            SuperExpressive::create()
                ->anythingButChars('aeiou')
                ->toRegexString()
        );

    }

    public function test_anything_but_string(): void
    {

        $this->assertEquals('/(?:[^a][^e][^i][^o][^u])/',
            SuperExpressive::create()
                ->anythingButString('aeiou')
                ->toRegexString()
        );

    }

    public function test_optional(): void
    {

        $this->assertEquals('/\d?/',
            SuperExpressive::create()
                ->optional()
                ->digit()
                ->toRegexString()
        );
    }

    public function test_exactly(): void
    {

        $this->assertEquals('/\d{5}/',
            SuperExpressive::create()
                ->exactly(5)
                ->digit()
                ->toRegexString()
        );
    }

    public function test_on_or_more(): void
    {

        $this->assertEquals('/\d+/',
            SuperExpressive::create()
                ->oneOrMore()
                ->digit()
                ->toRegexString()
        );

    }

    public function test_start_of_input(): void
    {

        $this->assertEquals('/^/',
            SuperExpressive::create()
                ->startOfInput()
                ->toRegexString()
        );
    }

    public function test_end_of_input(): void
    {

        $this->assertEquals('/$/',
            SuperExpressive::create()
                ->endOfInput()
                ->toRegexString()
        );
    }

    public function test_one_or_more_lazy(): void
    {

        $this->assertEquals('/\w+?/',
            SuperExpressive::create()
                ->oneOrMoreLazy()->word()
                ->toRegexString()
        );
    }

    public function test_zero_or_more(): void
    {

        $this->assertEquals('/\w*/',
            SuperExpressive::create()
                ->zeroOrMore()->word()
                ->toRegexString()
        );
    }

    public function test_zero_or_more_lazy(): void
    {

        $this->assertEquals('/\w*?/',
            SuperExpressive::create()
                ->zeroOrMoreLazy()->word()
                ->toRegexString()
        );
    }

    public function test_between(): void
    {

        $this->assertEquals('/\w{4,7}/',
            SuperExpressive::create()
                ->between(4,7)->word()
                ->toRegexString()
        );
    }

    public function test_between_lazy(): void
    {
        $this->assertEquals('/\w{4,7}?/',
            SuperExpressive::create()
                ->betweenLazy(4,7)->word()
                ->toRegexString()
        );
    }

    public function test_not_ahead(): void
    {
        $this->assertEquals('/(?![a-f])[0-9]/',
            SuperExpressive::create()
                ->assertNotAhead()
                    ->range('a','f')
                ->end()
                ->range('0','9')
                ->toRegexString()
        );
    }

    public function test_ahead(): void
    {

        $this->assertEquals('/(?=[a-f])[0-9]/',
            SuperExpressive::create()
                ->assertAhead()
                ->range('a','f')
                ->end()
                ->range('0','9')
                ->toRegexString()
        );
    }

    public function test_group(): void
    {

        $this->assertEquals('/(?:hello \w\!)/',
            SuperExpressive::create()
                ->group()
                    ->string('hello ')
                    ->word()
                    ->char('!')
                ->end()
                ->toRegexString()
        );
    }

    public function test_capture(): void
    {

        $this->assertEquals('/(hello \w\!)/',
            SuperExpressive::create()
                ->capture()
                ->string('hello ')
                ->word()
                ->char('!')
                ->end()
                ->toRegexString()
        );
    }

    public function test_named_capture(): void
    {

        $this->assertEquals('/(?<this_is_the_name>hello \w\!)/',
            SuperExpressive::create()
                ->namedCapture('this_is_the_name')
                ->string('hello ')
                ->word()
                ->char('!')
                ->end()
                ->toRegexString()
        );
    }


//    public function test_sub_expression(): void
//    {
//        $fiveDigits = SuperExpressive::create()->exactly(5)->digit();
//
//        $this->assertEquals('/[a-z]+.{3,}\d{5}/',
//            SuperExpressive::create()
//                ->oneOrMore()->range('a','z')
//                ->atLeast(3)->anyChar()
//                ->subexpression($fiveDigits)
//                ->toRegexString()
//        );
//    }

//    public function test_regex_string(): void
//    {
//
//        $this->assertEquals('/^(?:0x)?([A-Fa-f0-9]{4})$/gm',
//            SuperExpressive::create()
//                ->allowMultipleMatches()
//                ->lineByLine()
//                ->startOfInput()
//                ->optional()->string('0x')
//                ->capture()
//                ->exactly(4)->anyOf()
//                ->range('A','F')
//                ->range('a','f')
//                ->range('0','9')
//                ->end()
//                ->end()
//                ->endOfInput()
//                ->toRegexString()
//        );
//
//    }

}
