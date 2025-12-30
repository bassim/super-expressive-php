[![Latest Version on Packagist](https://img.shields.io/packagist/v/bassim/super-expressive-php.svg?style=for-the-badge)](https://packagist.org/packages/super-expressive-php)
[![License](https://img.shields.io/github/license/bassim/super-expressive-php?style=for-the-badge)](https://github.com/super-expressive-php/blob/master/LICENSE.md)
![Postcardware](https://img.shields.io/badge/Postcardware-%F0%9F%92%8C-197593?style=for-the-badge)

[![PHP from Packagist](https://img.shields.io/packagist/php-v/bassim/super-expressive-php?style=flat-square)](https://packagist.org/packages/super-expressive-php)
[![Build Status](https://img.shields.io/github/workflow/status/bassim/super-expressive-php/run-tests?label=tests&style=flat-square)](https://github.com/super-expressive-php/actions?query=workflow%3Arun-tests)
[![Coverage Status](https://coveralls.io/repos/github/bassim/super-expressive-php/badge.svg?branch=master)](https://coveralls.io/github/bassim/super-expressive-php?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/bassim/super-expressive-php.svg?style=flat-square)](https://packagist.org/packages/super-expressive-php)

# Super Expressive PHP

**Super Expressive PHP** is a PHP library that allows you to build regular expressions in almost natural language - with no extra dependencies, and a lightweight code footprint.

This library is a port of https://github.com/francisrstokes/super-expressive

## Why?

Regex is a very powerful tool, but its terse and cryptic vocabulary can make constructing and communicating them with others a challenge. Even developers who understand them well can have trouble reading their own back just a few months later! In addition, they can't be easily created and manipulated in a programmatic way - closing off an entire avenue of dynamic text processing.

That's where **Super Expressive PHP** comes in. It provides a programmatic and human readable way to create regular expressions. It's API uses the [fluent builder pattern](https://en.wikipedia.org/wiki/Fluent_interface), and is completely immutable. It's built to be discoverable and predictable:

- properties and methods describe what they do in plain English
- order matters! quantifiers are specified before the thing they change, just like in English (e.g. `SuperExpressive::create()->exactly(5)->digit()`)
- if you make a mistake, you'll know how to fix it. SuperExpressive will guide you towards a fix if your expression is invalid
- [subexpressions](https://github.com/francisrstokes/super-expressive#subexpressionexpr-opts) can be used to create meaningful, reusable components

SuperExpressive turns those complex and unwieldy regexes that appear in code reviews into something that can be read, understood, and **properly reviewed** by your peers - and maintained by anyone!

For the complete API documentation, visit https://github.com/francisrstokes/super-expressive

## Example

The following example recognises and captures the value of a 16-bit hexadecimal number like `0xC0D3`.

```php
$myRegex = SuperExpressive::create()
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
  ->toRegexString();

// Produces the following regular expression:
/^(?:0x)?([A-Fa-f0-9]{4})$/
```

## Installation

You can install the package via composer:

```bash
composer require bassim/super-expressive-php
```
