<?php

declare(strict_types=1);

namespace Bassim\SuperExpressive;

use Bassim\SuperExpressive\Parser\Lexer;
use Bassim\SuperExpressive\Parser\Node;
use Bassim\SuperExpressive\Parser\Nodes\CharSetNode;
use Bassim\SuperExpressive\Parser\Nodes\ContainerNode;
use Bassim\SuperExpressive\Parser\Nodes\FlagNode;
use Bassim\SuperExpressive\Parser\Nodes\LiteralNode;
use Bassim\SuperExpressive\Parser\Nodes\SimpleNode;
use Bassim\SuperExpressive\Parser\Quantifier;
use Bassim\SuperExpressive\Parser\Token;

final class RegexParser
{
    private const CONTAINER_TOKENS = [
        Token::GROUP_START => 'capture',
        Token::GROUP_NC_START => 'group',
        Token::LOOKAHEAD => 'assertAhead',
        Token::NEGATIVE_LOOKAHEAD => 'assertNotAhead',
        Token::LOOKBEHIND => 'assertBehind',
        Token::NEGATIVE_LOOKBEHIND => 'assertNotBehind',
    ];

    /** @var Token[] */
    private array $tokens;
    private int $pos = 0;

    /** @var string[] */
    private array $flags;

    private function __construct(Lexer $lexer)
    {
        $this->tokens = $lexer->tokenize();
        $this->flags = $lexer->getFlags();
    }

    public static function parse(string $regex): string
    {
        $lexer = new Lexer($regex);
        $parser = new self($lexer);
        $nodes = $parser->parseAll();

        return self::generate($nodes);
    }

    /**
     * @return Node[]
     */
    private function parseAll(): array
    {
        $nodes = [];

        // Flags first
        foreach ($this->flags as $flag) {
            if (\in_array($flag, ['i', 'm', 's'], true)) {
                $nodes[] = new FlagNode($flag);
            }
        }

        // Parse expression
        while ($this->pos < \count($this->tokens)) {
            $node = $this->parseNode();
            if ($node !== null) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    private function parseNode(): ?Node
    {
        $token = $this->current();
        if ($token === null) {
            return null;
        }

        // Handle by token type
        switch ($token->type) {
            case Token::ANCHOR_START:
                $this->pos++;
                return new SimpleNode('startOfInput');

            case Token::ANCHOR_END:
                $this->pos++;
                return new SimpleNode('endOfInput');

            case Token::CHAR_CLASS:
                $this->pos++;
                return $this->withQuantifier(new SimpleNode($token->value));

            case Token::DOT:
                $this->pos++;
                return $this->withQuantifier(new SimpleNode('anyChar'));

            case Token::LITERAL:
                return $this->parseLiterals();

            case Token::CHAR_SET:
                $this->pos++;
                $node = new CharSetNode($token->value, $token->meta['negated']);
                return $this->withQuantifier($node);

            case Token::GROUP_END:
                return null;

            case Token::QUANTIFIER:
                throw new RegexParseException(
                    'Unexpected quantifier',
                    '',
                    $token->position
                );

            default:
                // Container tokens (groups, assertions)
                if (isset(self::CONTAINER_TOKENS[$token->type])) {
                    return $this->parseContainer($token);
                }
                $this->pos++;
                return null;
        }
    }

    private function parseLiterals(): Node
    {
        $literals = '';

        while ($this->pos < \count($this->tokens)) {
            $token = $this->current();
            if (!$token->is(Token::LITERAL)) {
                break;
            }

            // Peek ahead for quantifier
            $next = $this->peek();
            if ($next !== null && $next->is(Token::QUANTIFIER)) {
                if (\strlen($literals) > 0) {
                    break; // Return accumulated string first
                }
                // Single char with quantifier
                $this->pos++;
                $node = new LiteralNode($token->value);
                return $this->withQuantifier($node);
            }

            $literals .= $token->value;
            $this->pos++;
        }

        return new LiteralNode($literals);
    }

    private function parseContainer(Token $startToken): Node
    {
        $type = self::CONTAINER_TOKENS[$startToken->type];
        $this->pos++;

        $children = [];
        while ($this->pos < \count($this->tokens)) {
            $token = $this->current();
            if ($token->is(Token::GROUP_END)) {
                $this->pos++;
                break;
            }
            $node = $this->parseNode();
            if ($node !== null) {
                $children[] = $node;
            }
        }

        $container = new ContainerNode($type, $children);

        // Assertions can't have quantifiers, groups can
        if (\in_array($type, ['group', 'capture'], true)) {
            return $this->withQuantifier($container);
        }

        return $container;
    }

    private function withQuantifier(Node $node): Node
    {
        $next = $this->current();
        if ($next === null || !$next->is(Token::QUANTIFIER)) {
            return $node;
        }

        $this->pos++;
        $quantifier = new Quantifier(
            $next->meta['min'],
            $next->meta['max'],
            $next->meta['lazy']
        );

        return $node->quantify($quantifier);
    }

    private function current(): ?Token
    {
        return $this->tokens[$this->pos] ?? null;
    }

    private function peek(): ?Token
    {
        return $this->tokens[$this->pos + 1] ?? null;
    }

    /**
     * @param Node[] $nodes
     */
    private static function generate(array $nodes): string
    {
        $lines = ['SuperExpressive::create()'];

        foreach ($nodes as $node) {
            $code = $node->toSuperExpressive();
            foreach (explode("\n", $code) as $line) {
                if ($line !== '') {
                    // Preserve existing indentation, add -> after it
                    $trimmed = ltrim($line);
                    $indent = \strlen($line) - \strlen($trimmed);
                    $lines[] = '    ' . str_repeat(' ', $indent) . '->' . $trimmed;
                }
            }
        }

        return implode("\n", $lines);
    }
}
