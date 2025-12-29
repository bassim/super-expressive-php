<?php

declare(strict_types=1);

namespace Bassim\SuperExpressive;

assert_options(ASSERT_EXCEPTION, 1);

final class SuperExpressive
{
    private \stdClass $state;

    private \stdClass $t;

    private static array $quantifierTable = [
        'oneOrMore' => '+',
        'oneOrMoreLazy' => '+?',
        'zeroOrMore' => '*',
        'zeroOrMoreLazy' => '*?',
        'optional' => '?',
        'exactly' => '{${times}}',
        'atLeast' => '{${times},}',
        'between' => '{${times[0]},${times[1]}}',
        'betweenLazy' => '{${times[0]},${times[1]}}?',
    ];

    private static string $namedGroupRegex = '/^[a-z]+\w*$/i';

    public function __construct()
    {
        $this->t = (object) [
            'root' => $this->asType('root')(),
            'noop' => $this->asType('noop')(),
            'startOfInput' => $this->asType('startOfInput')(),
            'endOfInput' => $this->asType('endOfInput')(),
            'anyChar' => $this->asType('anyChar')(),
            'whitespaceChar' => $this->asType('whitespaceChar')(),
            'nonWhitespaceChar' => $this->asType('nonWhitespaceChar')(),
            'digit' => $this->asType('digit')(),
            'nonDigit' => $this->asType('nonDigit')(),
            'word' => $this->asType('word')(),
            'nonWord' => $this->asType('nonWord')(),
            'wordBoundary' => $this->asType('wordBoundary')(),
            'nonWordBoundary' => $this->asType('nonWordBoundary')(),
            'newline' => $this->asType('newline')(),
            'carriageReturn' => $this->asType('carriageReturn')(),
            'tab' => $this->asType('tab')(),
            'nullByte' => $this->asType('nullByte')(),
            'anyOfChars' => $this->asType('anyOfChars')(),
            'anythingButString' => $this->asType('anythingButString')(),
            'anythingButChars' => $this->asType('anythingButChars')(),
            'anythingButRange' => $this->asType('anythingButRange')(),
            'char' => $this->asType('char')(),
            'range' => $this->asType('range')(),
            'string' => $this->asType('string', ['quantifierRequiresGroup' => true])(),
            'namedBackreference' => $this->deferredType('namedBackreference'),
            'backreference' => $this->deferredType('backreference'),
            'capture' => $this->deferredType('capture', ['containsChildren' => true]),
            'subexpression' => $this->asType('subexpression', ['containsChildren' => true, 'quantifierRequiresGroup' => true])(),
            'namedCapture' => $this->deferredType('namedCapture', ['containsChildren' => true]),
            'group' => $this->deferredType('group', ['containsChildren' => true]),
            'assertAhead' => $this->deferredType('assertAhead', ['containsChildren' => true]),
            'assertNotAhead' => $this->deferredType('assertNotAhead', ['containsChildren' => true]),
            'assertBehind' => $this->deferredType('assertBehind', ['containsChildren' => true]),
            'assertNotBehind' => $this->deferredType('assertNotBehind', ['containsChildren' => true]),
            'atLeast' => $this->deferredType('atLeast', ['containsChild' => true]),
            'between' => $this->deferredType('between', ['containsChild' => true]),
            'betweenLazy' => $this->deferredType('betweenLazy', ['containsChild' => true]),
            'zeroOrMore' => $this->deferredType('zeroOrMore', ['containsChild' => true]),
            'zeroOrMoreLazy' => $this->deferredType('zeroOrMoreLazy', ['containsChild' => true]),
            'oneOrMore' => $this->deferredType('oneOrMore', ['containsChild' => true]),
            'oneOrMoreLazy' => $this->deferredType('oneOrMoreLazy', ['containsChild' => true]),
            'anyOf' => $this->deferredType('anyOf', ['containsChildren' => true]),
            'optional' => $this->deferredType('optional', ['containsChild' => true]),
            'exactly' => $this->deferredType('exactly', ['containsChild' => true]),
        ];

        $this->state = (object) [
            'hasDefinedStart' => false,
            'hasDefinedEnd' => false,
            'flags' => (object) [
                'g' => false,
                'y' => false,
                'm' => false,
                'i' => false,
                'u' => false,
                's' => false,
            ],
            'stack' => [$this->createStackFrame($this->t->root)],
            'namedGroups' => [],
            'totalCaptureGroups' => 0,
        ];
    }

    public function anyChar(): self
    {
        return $this->matchElement($this->t->anyChar);
    }

    public function whitespaceChar(): self
    {
        return $this->matchElement($this->t->whitespaceChar);
    }

    public function nonWhitespaceChar(): self
    {
        return $this->matchElement($this->t->nonWhitespaceChar);
    }

    public function digit(): self
    {
        return $this->matchElement($this->t->digit);
    }

    public function nonDigit(): self
    {
        return $this->matchElement($this->t->nonDigit);
    }

    public function word(): self
    {
        return $this->matchElement($this->t->word);
    }

    public function nonWord(): self
    {
        return $this->matchElement($this->t->nonWord);
    }

    public function wordBoundary(): self
    {
        return $this->matchElement($this->t->wordBoundary);
    }

    public function nonWordBoundary(): self
    {
        return $this->matchElement($this->t->nonWordBoundary);
    }

    public function newline(): self
    {
        return $this->matchElement($this->t->newline);
    }

    public function carriageReturn(): self
    {
        return $this->matchElement($this->t->carriageReturn);
    }

    public function tab(): self
    {
        return $this->matchElement($this->t->tab);
    }

    public function nullByte(): self
    {
        return $this->matchElement($this->t->nullByte);
    }

    public function toRegexString(): string
    {
        [$pattern, $flags] = $this->getRegexPatternAndFlags();

        return sprintf('/%s/%s', $pattern, $flags);
    }

    public function anyOf(): self
    {
        return $this->frameCreatingElement($this->t->anyOf);
    }

    public function group(): self
    {
        return $this->frameCreatingElement($this->t->group);
    }

    public function assertAhead(): self
    {
        return $this->frameCreatingElement($this->t->assertAhead);
    }

    public function assertNotAhead(): self
    {
        return $this->frameCreatingElement($this->t->assertNotAhead);
    }

    public function assertBehind(): self
    {
        return $this->frameCreatingElement($this->t->assertBehind);
    }

    public function assertNotBehind(): self
    {
        return $this->frameCreatingElement($this->t->assertNotBehind);
    }

    public function allowMultipleMatches(): self
    {
        $this->state->flags->g = true;

        return $this;
    }

    public function lineByLine(): self
    {
        $this->state->flags->m = true;

        return $this;
    }

    public function caseInsensitive(): self
    {
        $this->state->flags->i = true;

        return $this;
    }

    public function sticky(): self
    {
        $this->state->flags->y = true;

        return $this;
    }

    public function unicode(): self
    {
        $this->state->flags->u = true;

        return $this;
    }

    public function singleLine(): self
    {
        $this->state->flags->s = true;

        return $this;
    }

    public function optional(): self
    {
        return $this->quantifierElement('optional');
    }

    public function zeroOrMore(): self
    {
        return $this->quantifierElement('zeroOrMore');
    }

    public function zeroOrMoreLazy(): self
    {
        return $this->quantifierElement('zeroOrMoreLazy');
    }

    public function oneOrMore(): self
    {
        return $this->quantifierElement('oneOrMore');
    }

    public function oneOrMoreLazy(): self
    {
        return $this->quantifierElement('oneOrMoreLazy');
    }

    public function exactly(int $n): self
    {
        $this->assert($n > 0, strtr('n must be a positive integer (got ${n})', ['${n}' => $n]));

        $currentFrame = $this->getCurrentFrame();

        $quantifier = clone $this->t->exactly;
        $quantifier->value = $n;
        $quantifier->times = $n;

        $currentFrame->quantifier = $quantifier;

        return $this;
    }

    public function atLeast(int $n): self
    {
        $this->assert($n > 0, strtr('n must be a positive integer (got ${n})', ['${n}' => $n]));
        $currentFrame = $this->getCurrentFrame();

        $quantifier = clone $this->t->atLeast;
        $quantifier->value = $n;
        $quantifier->times = $n;

        $currentFrame->quantifier = $quantifier;

        return $this;
    }

    public function between(int $x, int $y): self
    {
        $this->assert($x >= 0, strtr('x must be an integer (got ${x})', ['${x}' => $x]));
        $this->assert($y > 0, strtr('y must be an integer greater than 0 (got ${y})', ['${y}' => $y]));
        $this->assert($x < $y, strtr('x must be less than y (x = ${x}, y = ${y})', ['${x}' => $x, '${y}' => $y]));

        $currentFrame = $this->getCurrentFrame();

        if ($currentFrame->quantifier) {
            throw new \RuntimeException(strtr('cannot quantify regular expression with "between" because it\'s already being quantified with "${currentFrame.quantifier.type}"', ['${currentFrame.quantifier.type}' => $currentFrame->quantifier->type]));
        }

        $n = clone $this->t->between;
        $n->times[0] = $x;
        $n->times[1] = $y;
        $currentFrame->quantifier = $n;

        return $this;
    }

    public function betweenLazy(int $x, int $y): self
    {
        $this->assert($x >= 0, strtr('x must be an integer (got ${x})', ['${x}' => $x]));
        $this->assert($y > 0, strtr('y must be an integer greater than 0 (got ${y})', ['${y}' => $y]));
        $this->assert($x < $y, strtr('x must be less than y (x = ${x}, y = ${y})', ['${x}' => $x, '${y}' => $y]));

        $currentFrame = $this->getCurrentFrame();

        if ($currentFrame->quantifier) {
            throw new \RuntimeException(strtr('cannot quantify regular expression with "betweenLazy" because it\'s already being quantified with "${currentFrame.quantifier.type}"', ['${currentFrame.quantifier.type}' => $currentFrame->quantifier->type]));
        }

        $n = clone $this->t->betweenLazy;
        $n->times[0] = $x;
        $n->times[1] = $y;
        $currentFrame->quantifier = $n;

        return $this;
    }

    public function startOfInput(): self
    {
        $this->assert(!$this->state->hasDefinedStart, 'This regex already has a defined start of input');
        $this->assert(!$this->state->hasDefinedEnd, 'Cannot define the start of input after the end of input');

        $this->state->hasDefinedStart = true;
        $currentElementArray = &$this->getCurrentElementArray();
        $currentElementArray[] = $this->t->startOfInput;

        return $this;
    }

    public function endOfInput(): self
    {
        $this->assert(!$this->state->hasDefinedEnd, 'This regex already has a defined end of input');

        $this->state->hasDefinedEnd = true;
        $currentElementArray = &$this->getCurrentElementArray();
        $currentElementArray[] = $this->t->endOfInput;

        return $this;
    }

    public function anyOfChars(string $string): self
    {
        $n = clone $this->t->anyOfChars;
        $n->value = $this->escapeSpecial($string);

        $currentFrame = $this->getCurrentFrame();

        $currentFrame->elements[] = $this->applyQuantifier($n);

        return $this;
    }

    public function end(): self
    {
        $this->assert(\count($this->state->stack) > 1, 'Cannot call end while building the root expression.');

        $oldFrame = array_pop($this->state->stack);
        $currentFrame = $this->getCurrentFrame();
        $element = clone $currentFrame;
        $element->type = $oldFrame->type;
        $element->value = $oldFrame->elements;

        if (property_exists($oldFrame, 'metadata')) {
            $element->metadata = $oldFrame->metadata;
        } else {
            $element->metadata = null;
        }
        $currentFrame->elements[] = $this->applyQuantifier($element);

        return $this;
    }

    public function anythingButString(string $str): self
    {
        $this->assert('' !== $str, 'str must have least one character');

        $n = clone $this->t->anythingButString;
        $n->value = $this->escapeSpecial($str);

        $currentFrame = $this->getCurrentFrame();

        $currentFrame->elements[] = $this->applyQuantifier($n);

        return $this;
    }

    public function anythingButChars(string $chars): self
    {
        $this->assert('' !== $chars, 'chars must have at least one character');

        $n = clone $this->t->anythingButChars;
        $n->value = $this->escapeSpecial($chars);

        $currentFrame = $this->getCurrentFrame();

        $currentFrame->elements[] = $this->applyQuantifier($n);

        return $this;
    }

    public function anythingButRange($strA, $strB): self
    {
        $this->assert(1 === \strlen((string) $strA), strtr('a must be a single character or number (got ${strA})', ['${strA}' => $strA]));
        $this->assert(1 === \strlen((string) $strB), strtr('b must be a single character or number (got ${strB})', ['${strB}' => $strB]));
        $this->assert(\ord((string) $strA) < \ord((string) $strB), strtr('a must have a smaller character value than b (a = ${strA.charCodeAt(0)}, b = ${strB.charCodeAt(0)})', ['${strA.charCodeAt(0)}' => \ord((string) $strA), '${strB.charCodeAt(0)}' => \ord((string) $strB)]));

        $n = $this->t->anythingButRange;
        $n->value = [$strA, $strB];

        $currentFrame = $this->getCurrentFrame();
        $currentFrame->elements[] = $this->applyQuantifier($n);

        return $this;
    }

    public function string(string $string): self
    {
        $this->assert('' !== $string, '$string cannot be an empty string');

        $n = clone $this->t->string;
        $elementValue = \strlen($string) > 1 ? $this->escapeSpecial($string) : $string;
        $n->value = $elementValue;

        $currentFrame = $this->getCurrentFrame();

        $currentFrame->elements[] = $this->applyQuantifier($n);

        return $this;
    }

    public function char(string $c): self
    {
        $this->assert(1 === \strlen($c), strtr('char() can only be called with a single character (got ${c})', ['${c}' => $c]));

        $n = clone $this->t->char;
        $elementValue = $this->escapeSpecial($c);
        $n->value = $elementValue;
        $currentFrame = $this->getCurrentFrame();

        $currentFrame->elements[] = $this->applyQuantifier($n);

        return $this;
    }

    public function range(string $strA, string $strB): self
    {
        $this->assert(1 === \strlen($strA), strtr('a must be a single character or number (got ${strA})', ['${strA}' => $strA]));
        $this->assert(1 === \strlen($strB), strtr('b must be a single character or number (got ${strB})', ['${strB}' => $strB]));
        $this->assert(\ord($strA) < \ord($strB), strtr('a must have a smaller character value than b (a = ${strA.charCodeAt(0)}, b = ${strB.charCodeAt(0)})', ['${strA.charCodeAt(0)}' => \ord($strA), '${strB.charCodeAt(0)}' => \ord($strB)]));

        $n = clone $this->t->range;
        $n->value = [$strA, $strB];

        $currentFrame = $this->getCurrentFrame();
        $currentFrame->elements[] = $this->applyQuantifier($n);

        return $this;
    }

    public function subexpression(self $expr, array $opts = []): self
    {
        $this->assert(
            1 === \count($expr->state->stack),
            strtr('Cannot call subexpression with a not yet fully specified regex object.'.
                '\n(Try adding a .end() call to match the "${expr[getCurrentFrame]().type.type}" on the subexpression)\n', ['${expr[getCurrentFrame]().type.type}' => $expr->getCurrentFrame()->type])
        );

        $options = $this->applySubexpressionDefaults($opts);

        $exprNext = clone $expr;
        $additionalCaptureGroups = 0;

        $exprFrame = $exprNext->getCurrentFrame();
        $that = $this;
        $cb = static function ($e) use ($options, $that, &$additionalCaptureGroups) {
            return $that->mergeSubexpression($e, $options, $that, $additionalCaptureGroups);
        };
        $exprFrame->elements = array_map($cb, $exprFrame->elements);

        $this->state->totalCaptureGroups += $additionalCaptureGroups;

        if (!$options->ignoreFlags) {
            foreach ($exprNext->state->flags as $key => $value) {
                if (false === $this->state->flags->{$key}) {
                    $this->state->flags->{$key} = $value;
                }
            }
        }

        $currentFrame = $this->getCurrentFrame();

        $e = $this->t->subexpression;
        $e->value = $exprFrame->elements;
        $currentFrame->elements[] = $this->applyQuantifier($e);

        return $this;
    }

    public function capture(): self
    {
        $newFrame = $this->createStackFrame($this->t->capture);
        $this->state->stack[] = $newFrame;
        ++$this->state->totalCaptureGroups;

        return $this;
    }

    public function namedCapture(string $name): self
    {
        $newFrame = $this->createStackFrame($this->t->namedCapture);
        $newFrame->metadata = $name;
        $this->trackNamedGroup($name);
        $this->state->stack[] = $newFrame;
        ++$this->state->totalCaptureGroups;

        return $this;
    }

    public function backreference(int $index): self
    {
        $this->assert(
            $index > 0 && $index <= $this->state->totalCaptureGroups,
            strtr('invalid index ${index}. There are ${this.state.totalCaptureGroups} capture groups on this SuperExpression', ['${index}' => $index])
        );

        $e = $this->t->backreference;
        $e->metadata = $index;

        return $this->matchElement($e);
    }

    public function namedBackreference(string $name): self
    {
        $this->assert(
            \in_array($name, $this->state->namedGroups, true),
            strtr('no capture group called "${name}" exists (create one with .namedCapture())', ['${name}' => $name])
        );

        $e = $this->t->namedBackreference;
        $e->metadata = $name;

        return $this->matchElement($e);
    }

    public static function create(): self
    {
        return new self();
    }

    private function trackNamedGroup(string $name): void
    {
        $this->assert('' !== $name, 'name must be at least one character');
        $this->assert(!\in_array($name, $this->state->namedGroups, true), strtr('cannot use ${name} again for a capture group', ['${name}' => $name]));
        $this->assert(preg_match(self::$namedGroupRegex, $name) > 0, strtr('name "${name}" is not valid (only letters, numbers, and underscores)', ['${name}' => $name]));
        $this->state->namedGroups[] = $name;
    }

    private function getRegexPatternAndFlags(): array
    {
        $this->assert(
            1 === \count($this->state->stack),
            strtr('Cannot compute the value of a not yet fully specified regex object.'.
                '\n(Try adding a .end() call to match the "${this[getCurrentFrame]().type.type}")\n', ['${this[getCurrentFrame]().type.type}' => $this->getCurrentFrame()->type])
        );

        $callbackPattern = static function ($n) {
            return self::evaluate($n);
        };
        $callbackFlags = static function ($k, $v) {
            if ($v) {
                return $k;
            }
        };
        $ea = $this->getCurrentElementArray();
        $pattern = implode('', array_map($callbackPattern, $ea));
        $flags = implode('', array_map($callbackFlags, array_keys((array) $this->state->flags), (array) $this->state->flags));

        return [$pattern, $flags];
    }

    private static function evaluate(\stdClass $el): string
    {
        switch ($el->type) {
            case 'anyChar':
                return '.';

            case 'whitespaceChar':
                return '\\s';

            case 'nonWhitespaceChar':
                return '\\S';

            case 'digit':
                return '\\d';

            case 'nonDigit':
                return '\\D';

            case 'word':
                return '\\w';

            case 'nonWord':
                return '\\W';

            case 'wordBoundary':
                return '\\b';

            case 'nonWordBoundary':
                return '\\B';

            case 'startOfInput':
                return '^';

            case 'endOfInput':
                return '$';

            case 'newline':
                return '\\n';

            case 'carriageReturn':
                return '\\r';

            case 'tab':
                return '\\t';

            case 'nullByte':
                return '\\0';

            case 'string':
                return $el->value;

            case 'char':
                return $el->value;

            case 'range':
                return strtr('[${el.value[0]}-${el.value[1]}]', ['${el.value[0]}' => $el->value[0], '${el.value[1]}' => $el->value[1]]);

            case 'anythingButRange':
                return strtr('[^${el.value[0]}-${el.value[1]}]', ['${el.value[0]}' => $el->value[0], '${el.value[1]}' => $el->value[1]]);

            case 'anyOfChars':
                return strtr('[${el.value}]', ['${el.value}' => $el->value]);

            case 'anythingButChars':
                return strtr('[^${el.value}]', ['${el.value}' => $el->value]);

            case 'namedBackreference':
                return strtr('\\k<${el.metadata}>', ['${el.metadata}' => $el->metadata]);

            case 'backreference':
                return strtr('\\${el.metadata}', ['${el.metadata}' => $el->metadata]);

            case 'subexpression':
                return implode('', array_map(static function ($e) {
                    return self::evaluate($e);
                }, $el->value));

            case 'optional':
            case 'zeroOrMore':
            case 'zeroOrMoreLazy':
            case 'oneOrMore':
            case 'oneOrMoreLazy':
                $inner = self::evaluate($el->value);
                $withGroup = property_exists($el->value, 'quantifierRequiresGroup') && $el->value->quantifierRequiresGroup ? strtr('(?:${inner})', ['${inner}' => $inner]) : $inner;
                $symbol = self::$quantifierTable[$el->type];

                return strtr('${withGroup}${symbol}', ['${withGroup}' => $withGroup, '${symbol}' => $symbol]);

            case 'betweenLazy':
            case 'between':
            case 'atLeast':
            case 'exactly':
                $inner = self::evaluate($el->value);
                $withGroup = property_exists($el->value, 'quantifierRequiresGroup') && $el->value->quantifierRequiresGroup ? strtr('(?:${inner})', ['${inner}' => $inner]) : $inner;

                if (\is_array($el->times) && \count($el->times) > 1) {
                    return strtr('${withGroup}'.strtr(self::$quantifierTable[$el->type], ['${times[0]}' => $el->times[0], '${times[1]}' => $el->times[1]]), ['${withGroup}' => $withGroup]);
                }

                return strtr('${withGroup}'.strtr(self::$quantifierTable[$el->type], ['${times}' => $el->times]), ['${withGroup}' => $withGroup]);

            case 'anythingButString':
                $chars = str_split($el->value);
                $callback = static function ($c) {
                    return strtr('[^${c}]', ['${c}' => $c]);
                };
                $chars = array_map($callback, $chars);
                $chars = implode('', $chars);

                return strtr('(?:${chars})', ['${chars}' => $chars]);

            case 'assertAhead':
                $evaluated = implode('', array_map(static function ($e) {
                    return self::evaluate($e);
                }, $el->value));

                return strtr('(?=${evaluated})', ['${evaluated}' => $evaluated]);

            case 'assertNotAhead':
                $evaluated = implode('', array_map(static function ($e) {
                    return self::evaluate($e);
                }, $el->value));

                return strtr('(?!${evaluated})', ['${evaluated}' => $evaluated]);

            case 'assertBehind':
                $evaluated = implode('', array_map(static function ($e) {
                    return self::evaluate($e);
                }, $el->value));

                return strtr('(?<=${evaluated})', ['${evaluated}' => $evaluated]);

            case 'assertNotBehind':
                $evaluated = implode('', array_map(static function ($e) {
                    return self::evaluate($e);
                }, $el->value));

                return strtr('(?<!${evaluated})', ['${evaluated}' => $evaluated]);

            case 'anyOf':
                [$fused, $rest] = self::fuseElements($el->value);

                if (\count($rest) < 1) {
                    return strtr('[${fused}]', ['${fused}' => $fused]);
                }

                $evaluatedRest = array_map(function ($e) {
                    return self::evaluate($e);
                }, $rest);
                $separator = (\count($evaluatedRest) > 0 && \strlen($fused) > 0) ? '|' : '';

                return '(?:'.implode('|', $evaluatedRest).$separator.('' !== $fused ? '['.$fused.']' : '').')';

            case 'capture':
                $evaluated = implode('', array_map(static function ($e) {
                    return self::evaluate($e);
                }, $el->value));

                return strtr('(${evaluated})', ['${evaluated}' => $evaluated]);

            case 'namedCapture':
                $evaluated = implode('', array_map(static function ($e) {
                    return self::evaluate($e);
                }, $el->value));

                return strtr('(?<${el.metadata}>${evaluated})', ['${el.metadata}' => $el->metadata, '${evaluated}' => $evaluated]);

            case 'group':
                $evaluated = implode('', array_map(static function ($e) {
                    return self::evaluate($e);
                }, $el->value));

                return strtr('(?:${evaluated})', ['${evaluated}' => $evaluated]);

            default:
                throw new \RuntimeException('Can\'t process unsupported element type: '.$el->type.'');
        }
    }

    private function frameCreatingElement(\stdClass $typeFn): self
    {
        $newFrame = clone $this->createStackFrame($typeFn);
        $this->state->stack[] = $newFrame;

        return $this;
    }

    private function matchElement(\stdClass $typeFn): self
    {
        $currentElementArray = &$this->getCurrentElementArray();
        $currentElementArray[] = $this->applyQuantifier($typeFn);

        return $this;
    }

    private function applyQuantifier(\stdClass $element): \stdClass
    {
        $currentFrame = $this->getCurrentFrame();

        if (null !== $currentFrame->quantifier) {
            $wrapped = clone $currentFrame->quantifier;
            $wrapped->value = $element;
            $currentFrame->quantifier = null;

            return $wrapped;
        }

        return $element;
    }

    private function getCurrentFrame(): \stdClass
    {
        return $this->state->stack[\count($this->state->stack) - 1];
    }

    private function &getCurrentElementArray(): array
    {
        return $this->getCurrentFrame()->elements;
    }

    private function escapeSpecial(string $string): string
    {
        return preg_quote($string, '/');
    }

    private function asType(string $type, array $options = null): \Closure
    {
        $f = static function () use ($type, $options) {
            return null !== $options ? (object) array_merge(['type' => $type], $options) : (object) ['type' => $type];
        };

        return $f;
    }

    private function deferredType(string $string, array $options = null): \stdClass
    {
        $typeFn = $this->asType($string, $options);

        return $typeFn();
    }

    private function createStackFrame(\stdClass $typeFn): \stdClass
    {
        return (object) [
            'type' => $typeFn->type,
            'quantifier' => null,
            'elements' => [],
        ];
    }

    private function quantifierElement(string $typeFnName): self
    {
        $currentFrame = $this->getCurrentFrame();

        if (null !== $currentFrame->quantifier) {
            throw new \RuntimeException(strtr('cannot quantify regular expression with "${typeFnName}" because it\'s already being quantified with "${currentFrame.quantifier.type}"', ['${typeFnName}' => $typeFnName, '${currentFrame.quantifier.type}' => $currentFrame->quantifier->type]));
        }

        $currentFrame->quantifier = $this->t->{$typeFnName};

        return $this;
    }

    private function mergeSubexpression(\stdClass $el, \stdClass $options, self $parent, int $incrementCaptureGroups): \stdClass
    {
        $nextEl = clone $el;

        if ('backreference' === $nextEl->type) {
            $nextEl->index += $parent->state->totalCaptureGroups;
        }

        if ('capture' === $nextEl->type) {
            ++$incrementCaptureGroups;
        }

        if ('namedCapture' === $nextEl->type) {
            $groupName = $options->namespace
                ? '${options.namespace}${nextEl.name}'
                : $nextEl->name;

            $parent->trackNamedGroup($groupName);
            $nextEl->name = $groupName;
        }

        if ('namedBackreference' === $nextEl->type) {
            $nextEl->name = $options->namespace
                ? strtr('${options.namespace}${nextEl.name}', ['${options.namespace}' => $options->namespace, '${nextEl.name}' => $nextEl->name])
                : $nextEl->name;
        }

        if (property_exists($nextEl, 'containsChild') && $nextEl->containsChild) {
            $nextEl->value = self::mergeSubexpression($nextEl->value, $options, $parent, $incrementCaptureGroups);
        } elseif (property_exists($nextEl, 'containsChildren') && $nextEl->containsChildren) {
            $nextEl->value = array_map(static function ($e) use ($options, $parent, $incrementCaptureGroups) {
                return self::mergeSubexpression($e, $options, $parent, $incrementCaptureGroups);
            }, $nextEl->value);
        }

        if ('startOfInput' === $nextEl->type) {
            if ($options->ignoreStartAndEnd) {
                return $this->t->noop;
            }

            $this->assert(
                !$parent->state->hasDefinedStart,
                'The parent regex already has a defined start of input. '.
                'You can ignore a subexpressions startOfInput/endOfInput markers with the ignoreStartAndEnd option'
            );

            $this->assert(
                !$parent->state->hasDefinedEnd,
                'The parent regex already has a defined end of input. '.
                'You can ignore a subexpressions startOfInput/endOfInput markers with the ignoreStartAndEnd option'
            );

            $parent->state->hasDefinedEnd = true;
        }

        if ('endOfInput' === $nextEl->type) {
            if ($options->ignoreStartAndEnd) {
                return $this->t->noop;
            }

            $this->assert(
                !$parent->state->hasDefinedEnd,
                'The parent regex already has a defined start of input. '.
                'You can ignore a subexpressions startOfInput/endOfInput markers with the ignoreStartAndEnd option'
            );

            $parent->state->hasDefinedEnd = true;
        }

        return $nextEl;
    }

    private function applySubexpressionDefaults(array $expr): \stdClass
    {
        $out = (object) $expr;
        $out->namespace = \array_key_exists('namespace', $expr) ? $expr['namespace'] : '';
        $out->ignoreFlags = \array_key_exists('ignoreFlags', $expr) ? $expr['ignoreFlags'] : true;
        $out->ignoreStartAndEnd = \array_key_exists('ignoreStartAndEnd', $expr) ? $expr['ignoreStartAndEnd'] : true;

        $this->assert(\is_string($out->namespace), 'namespace must be a string');
        $this->assert(\is_bool($out->ignoreFlags), 'ignoreFlags must be a boolean');
        $this->assert(\is_bool($out->ignoreStartAndEnd), 'ignoreStartAndEnd must be a boolean');

        return $out;
    }

    private static function isFusable(): \Closure
    {
        return static function ($element) {
            return \in_array($element->type, ['range', 'char', 'anyOfChars'], true);
        };
    }

    private static function fuseElements(array $elements): array
    {
        [$fusables, $rest] = self::partition(self::isFusable(), $elements);

        $callbackFused = static function ($n) {
            if (\in_array($n->type, ['char', 'anyOfChars'], true)) {
                return $n->value;
            }

            return strtr('${el.value[0]}-${el.value[1]}', ['${el.value[0]}' => $n->value[0], '${el.value[1]}' => $n->value[1]]);
        };
        $fused = implode('', array_map($callbackFused, $fusables));

        return [$fused, $rest];
    }

    private static function partition(\Closure $pred, array $elements): array
    {
        $fusables = [];
        $rest = [];

        foreach ($elements as $element) {
            if ($pred($element)) {
                $fusables[] = $element;
            } else {
                $rest[] = $element;
            }
        }

        return [$fusables, $rest];
    }

    private function assert(bool $param, string $string): ?\AssertionError
    {
        if (!$param) {
            throw new \AssertionError($string);
        }

        return null;
    }
}
