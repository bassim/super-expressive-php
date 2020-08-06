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

    public function __construct()
    {
        $this->t = (object)[
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
            'string' => $this->asType('string')(),

//            namedBackreference: name => deferredType('namedBackreference', { name }),
//            backreference: index => deferredType('backreference', { index }),
//            capture: deferredType('capture', { containsChildren: true }),
//            subexpression: asType('subexpression', { containsChildren: true, quantifierRequiresGroup: true }),
//            namedCapture: name => deferredType('namedCapture', { name, containsChildren: true }),
//            group: deferredType('group', { containsChildren: true }),
//            assertAhead: deferredType('assertAhead', { containsChildren: true }),
//            assertNotAhead: deferredType('assertNotAhead', { containsChildren: true }),
            'atLeast' => $this->deferredType('atLeast', ['containsChild' => true]),
//            between: (x, y) => deferredType('between', { times: [x, y], containsChild: true }),
//            betweenLazy: (x, y) => deferredType('betweenLazy', { times: [x, y], containsChild: true }),
//            zeroOrMore: deferredType('zeroOrMore', { containsChild: true }),
//            zeroOrMoreLazy: deferredType('zeroOrMoreLazy', { containsChild: true }),
            'oneOrMore' => $this->deferredType('oneOrMore', ['containsChild' => true]),
            //oneOrMoreLazy: deferredType('oneOrMoreLazy', { containsChild: true }),

            'anyOf' => $this->deferredType('anyOf', ['containsChildren' => true]),
            'optional' => $this->deferredType('optional', ['containsChild' => true]),
            'exactly' => $this->deferredType('exactly', ['containsChild' => true]),
        ];

        $this->state = (object)[
            'hasDefinedStart' => false,
            'hasDefinedEnd' => false,
            'flags' => (object)[
                'g' => false,
                'y' => false,
                'm' => false,
                'i' => false,
                'u' => false,
                's' => false
            ],
            'stack' => [$this->createStackFrame($this->t->root)],
            'namedGroups' => [],
            'totalCaptureGroups' => 0,
        ];
    }

    private static function isFusable(): \Closure
    {
        return function ($element) {
            return in_array($element->type, ['range', 'char', 'anyOfChars']);
        };
    }

    private static function fuseElements($elements)
    {
        [$fusables, $rest] = self::partition(self::isFusable(), $elements);

        $callbackFused = static function ($n) {
            if (in_array($n->type, ['char', 'anyOfChars'])) {
                return $n->value;
            }
            return strtr('${el.value[0]}-${el.value[1]}', ['${el.value[0]}' => $n->value[0], '${el.value[1]}' => $n->value[1]]);
        };
        $fused = implode('', array_map($callbackFused, $fusables));

        return [$fused, $rest];
    }

    private static function partition(\Closure $pred, $elements): array
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
        return $this->matchElement($this->t->notWhitespaceChar);
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

    public function anyOf(): self
    {
        return $this->frameCreatingElement($this->t->anyOf);
    }

    public function toRegexString(): string
    {
        list($pattern, $flags) = $this->getRegexPatternAndFlags();

        return sprintf('/%s/%s', $pattern, $flags);
    }

    private function frameCreatingElement(\stdClass $typeFn): self
    {
        $newFrame = clone $this->createStackFrame($typeFn);
        $this->state->stack[] = $newFrame;
        return $this;
    }

    private function getRegexPatternAndFlags(): array
    {
        $callbackPattern = static function ($n) {
            return SuperExpressive::evaluate($n);
        };
        $callbackFlags = static function ($k, $v) {
            if ($v) {
                return $k;
            }
        };
        $ea = $this->getCurrentElementArray();
        $pattern = implode('', array_map($callbackPattern, $ea));
        $flags = implode('', array_map($callbackFlags, array_keys((array)$this->state->flags), (array)$this->state->flags));

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
                return strtr('${withGroup}' . strtr(self::$quantifierTable[$el->type], ['${times}' => $el->times]), ['${withGroup}' => $withGroup]);

            case 'anythingButString':
                $chars = str_split($el->value);
                $callback = static function ($c) {
                    return strtr('[^${c}]', ['${c}' => $c]);
                };
                $chars = array_map($callback, $chars);
                $chars = implode('', $chars);
                //.map(c => `[^${c}]`).join('');
                return strtr('(?:${chars})', ['${chars}' => $chars]);

//
//      case 'assertAhead': {
//    const evaluated = el.value.map(SuperExpressive[evaluate]).join('');
//    return `(?=${evaluated})`;
//}
//
//      case 'assertNotAhead': {
//    const evaluated = el.value.map(SuperExpressive[evaluate]).join('');
//    return `(?!${evaluated})`;
//}
//
            case 'anyOf':

                [$fused, $rest] = self::fuseElements($el->value);
                if (count($rest) < 1) {
                    return strtr('[${fused}]', ['${fused}' => $fused]);
                }

                $evaluatedRest = array_map(function ($e) {
                    return self::evaluate($e);
                }, $rest);// $rest.map(SuperExpressive[evaluate]);
                $separator = (count($evaluatedRest) > 0 && strlen($fused) > 0) ? '|' : '';
                return '(?:' . implode('|', $evaluatedRest) . $separator . ('' !== $fused ? '[' . $fused . ']' : '') . ')';
//      case 'capture': {
//    const evaluated = el.value.map(SuperExpressive[evaluate]);
//    return `(${evaluated.join('')})`;
//}
//
//      case 'namedCapture': {
//    const evaluated = el.value.map(SuperExpressive[evaluate]);
//    return `(?<${el.metadata}>${evaluated.join('')})`;
//}
//
//      case 'group': {
//    const evaluated = el.value.map(SuperExpressive[evaluate]);
//    return `(?:${evaluated.join('')})`;
//}

            default:
                throw new \RuntimeException('Can\'t process unsupported element type: ' . $el->type . '');

        }
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

    public function string(string $string): self
    {
        //assert($string !== '', '$string cannot be an empty string');
        if ($string === '') {
            throw new \AssertionError('$string cannot be an empty string');
        }

        $n = clone $this->t->string;
        $elementValue = strlen($string) > 1 ? $this->escapeSpecial($string) : $string;
        $n->value = $elementValue;

        $currentFrame = $this->getCurrentFrame();

        $currentFrame->elements[] = $this->applyQuantifier($n);
        return $this;
    }

    public function range(string $strA, string $strB): self
    {
        //TODO asserts

        $n = clone $this->t->range;
        $n->value = [$strA, $strB];

        $currentFrame = $this->getCurrentFrame();
        $currentFrame->elements[] = $this->applyQuantifier($n);


        return $this;
    }

    public function anythingButRange($a, $b): self
    {

        //TODO asserts
        $n = $this->t->anythingButRange;
        $n->value = [$a, $b];

        $currentFrame = $this->getCurrentFrame();
        $currentFrame->elements[] = $this->applyQuantifier($n);
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

    public function end(): self
    {
        $oldFrame = array_pop($this->state->stack);
        $currentFrame = $this->getCurrentFrame();
        $element = clone $currentFrame;
        $element->type = $oldFrame->type;
        $element->value = $oldFrame->elements;

        $currentFrame->elements[] = $this->applyQuantifier($element);

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
        return $this->state->stack[count($this->state->stack) - 1];
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
        $f = function () use ($type, $options) {
            return (object)['type' => $type, 'options' => $options];
        };
        return $f;
    }

    private function deferredType(string $string, array $options = null): \stdClass
    {
        $typeFn = $this->asType($string, $options);
        return ($typeFn)();
    }

    private function createStackFrame(\stdClass $typeFn): \stdClass
    {
        return (object)[
            'type' => $typeFn->type,
            'quantifier' => null,
            'elements' => []
        ];
    }

    private function quantifierElement(string $typeFnName): self
    {
        $currentFrame = $this->getCurrentFrame();
        if (null !== $currentFrame->quantifier) {
            throw new \RuntimeException('cannot quantify regular expression with "${typeFnName}" because it\'s already being quantified with "${currentFrame.quantifier.type}"');
        }

        $currentFrame->quantifier = $this->t->$typeFnName;

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

    public function anythingButChars(string $string): self
    {
        $n = clone $this->t->anythingButChars;
        $n->value = $this->escapeSpecial($string);

        $currentFrame = $this->getCurrentFrame();

        $currentFrame->elements[] = $this->applyQuantifier($n);

        return $this;

    }

    public function anythingButString(string $string): self
    {
        $n = clone $this->t->anythingButString;
        $n->value = $this->escapeSpecial($string);

        $currentFrame = $this->getCurrentFrame();

        $currentFrame->elements[] = $this->applyQuantifier($n);

        return $this;
    }

    public function exactly(int $int): self
    {
        $currentFrame = $this->getCurrentFrame();

        $n = clone $this->t->exactly;
        $n->value = $int;
        $n->times = $int;

        $currentFrame->quantifier = $n;

        return $this;
    }

    public function atLeast(int $int): self
    {
        $currentFrame = $this->getCurrentFrame();

        $n = clone $this->t->atLeast;
        $n->value = $int;
        $n->times = $int;

        $currentFrame->quantifier = $n;

        return $this;
    }

    /*
       subexpression(expr, opts = {}) {
    assert(expr instanceof SuperExpressive, `expr must be a SuperExpressive instance`);
    assert(
      expr.state.stack.length === 1,
      'Cannot call subexpression with a not yet fully specified regex object.' +
      `\n(Try adding a .end() call to match the "${expr[getCurrentFrame]().type.type}" on the subexpression)\n`
    );


    const options = applySubexpressionDefaults(opts);

    const exprNext = expr[clone]();
    const next = this[clone]();
    let additionalCaptureGroups = 0;

    const exprFrame = exprNext[getCurrentFrame]();
    exprFrame.elements = exprFrame.elements.map(e =>
      SuperExpressive[mergeSubexpression](
        e,
        options,
        next,
        () => additionalCaptureGroups++
      )
    );

    next.state.totalCaptureGroups += additionalCaptureGroups;

    if (!options.ignoreFlags) {
      Object.entries(exprNext.state.flags).forEach(([flagName, enabled]) => {
        next.state.flags[flagName] = enabled || next.state.flags[flagName];
      });
    }

    const currentFrame = next[getCurrentFrame]();
    currentFrame.elements.push(next[applyQuantifier](t.subexpression(exprFrame.elements)));

    return next;
  }
     */
    public function subexpression(SuperExpressive $expr, $opts = []): self
    {
        $options = $this->applySubexpressionDefaults($opts);
        $exprNext = clone $expr;
        $additionalCaptureGroups = 0;


        return $this;
    }

    public function startOfInput(): self
    {
        return $this;
    }

    public function capture(): self
    {
        return $this;
    }

    public function endOfInput(): self
    {
        return $this;
    }

    private function applySubexpressionDefaults(array ...$expr)
    {
        //  const out = { ...expr };
//  out.namespace = ('namespace' in out) ? out.namespace : '';
//  out.ignoreFlags = ('ignoreFlags' in out) ? out.ignoreFlags : true;
//  out.ignoreStartAndEnd = ('ignoreStartAndEnd' in out) ? out.ignoreStartAndEnd : true;
//
//  assert(typeof out.namespace === 'string', 'namespace must be a string');
//  assert(typeof out.ignoreFlags === 'boolean', 'ignoreFlags must be a boolean');
//  assert(typeof out.ignoreStartAndEnd === 'boolean', 'ignoreStartAndEnd must be a boolean');
//
//  return out;
        $out = (object)$expr;
        $out->namespace = '';
        $out->ignoreFlags = true;
        $out->ignoreStartAndEnd = true;
        //todo pick up args
        return $out;
    }


}
