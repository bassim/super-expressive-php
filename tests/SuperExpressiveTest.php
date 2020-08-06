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
        $superExpressive = new SuperExpressive();
        $this->assertEquals('//g',
            $superExpressive
                ->allowMultipleMatches()
                ->string('')
                ->toRegexString()
        );
    }

    public function test_allow_multiple_matches(): void
    {
        $superExpressive = new SuperExpressive();
        $this->assertEquals('/hello/g',
            $superExpressive
                ->allowMultipleMatches()
                ->string('hello')
                ->toRegexString()
        );
    }

    public function test_line_by_line(): void
    {
        $superExpressive = new SuperExpressive();
        $this->assertEquals('/\^hello\$/m',
            $superExpressive
                ->lineByLine()
                ->string('^hello$')
                ->toRegexString()
        );
    }

    public function test_case_insensitive(): void
    {
        $superExpressive = new SuperExpressive();
        $this->assertEquals('/HELLO/i',
            $superExpressive
                ->caseInsensitive()
                ->string('HELLO')
                ->toRegexString()
        );
    }

    public function test_null_byte(): void
    {
        $superExpressive = new SuperExpressive();
        $this->assertEquals('/\0/',
            $superExpressive
                ->nullByte()
                ->toRegexString()
        );
    }

    public function test_single_line(): void
    {
        $superExpressive = new SuperExpressive();
        $this->assertEquals('/hello.world/s',
            $superExpressive
                ->singleLine()
                ->string('hello')
                ->anyChar()
                ->string('world')
                ->toRegexString()
        );
    }

    public function test_word_boundary(): void
    {
        $superExpressive = new SuperExpressive();
        $this->assertEquals('/\d\b/',
            $superExpressive
                ->digit()
                ->wordBoundary()
                ->toRegexString()
        );
    }

    public function test_non_word_boundary(): void
    {
        $superExpressive = new SuperExpressive();
        $this->assertEquals('/\d\B/',
            $superExpressive
                ->digit()
                ->nonWordBoundary()
                ->toRegexString()
        );
    }

    public function test_newline(): void
    {
        $superExpressive = new SuperExpressive();
        $this->assertEquals('/\n/',
            $superExpressive
                ->newline()
                ->toRegexString()
        );
    }


    public function test_anything_but_range(): void
    {
        $superExpressive = new SuperExpressive();
        $this->assertEquals('/[^0-9]/',
            $superExpressive
                ->anythingButRange(0, 9)
                ->toRegexString()
        );
    }

    public function test_any_of(): void
    {
        $superExpressive = new SuperExpressive();
        $this->assertEquals('/(?:XXX|[a-f0-9])/',
            $superExpressive
                ->anyOf()
                    ->range('a','f')
                    ->range('0','9')
                    ->string('XXX')
                ->end()
                ->toRegexString());

    }

    public function test_any_of_chars(): void
    {
        $superExpressive = new SuperExpressive();
        $this->assertEquals('/[aeiou]/',
            $superExpressive
                ->anyOfChars('aeiou')
                ->toRegexString());

    }

    public function test_anything_but_chars(): void
    {
        $superExpressive = new SuperExpressive();
        $this->assertEquals('/[^aeiou]/',
            $superExpressive
                ->anythingButChars('aeiou')
                ->toRegexString());

    }

    public function test_anything_but_string(): void
    {
        $superExpressive = new SuperExpressive();
        $this->assertEquals('/(?:[^a][^e][^i][^o][^u])/',
            $superExpressive
                ->anythingButString('aeiou')
                ->toRegexString());

    }

    public function test_optional(): void
    {
        $superExpressive = new SuperExpressive();
        $this->assertEquals('/\d?/',
            $superExpressive
                ->optional()
                ->digit()
                ->toRegexString()
        );
    }

    public function test_exactly(): void
    {
        $superExpressive = new SuperExpressive();
        $this->assertEquals('/\d{5}/',
            $superExpressive
                ->exactly(5)
                ->digit()
                ->toRegexString()
        );
    }

    public function test_on_or_more(): void
    {
        /*
         * SuperExpressive()
  .oneOrMore.digit
  .toRegex();
// ->
/\d+/
         */
        $superExpressive = new SuperExpressive();
        $this->assertEquals('/\d+/',
            $superExpressive
                ->oneOrMore()
                ->digit()
                ->toRegexString()
        );

    }
//
//    public function test_sub_expression(): void
//    {
//        $fiveDigits = (new SuperExpressive())->exactly(5)->digit();
//        $superExpressive = new SuperExpressive();
//        $this->assertEquals('/[a-z]+.{3,}\d{5}/',
//            $superExpressive
//                ->oneOrMore()->range('a','z')
//                ->atLeast(3)->anyChar()
//                ->subexpression($fiveDigits)
//                ->toRegexString()
//        );
//    }


//    public function test_regex_string(): void
//    {
//        $superExpressive = new SuperExpressive();
//        $this->assertEquals('/^(?:0x)?([A-Fa-f0-9]{4})$/gm',
//            $superExpressive
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
