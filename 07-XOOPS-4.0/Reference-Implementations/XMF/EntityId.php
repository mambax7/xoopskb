<?php

declare(strict_types=1);

/**
 * XMF EntityId - Base trait for ULID-based entity identifiers
 *
 * This trait provides common ULID-based ID functionality that can be
 * used by all entity ID value objects, reducing code duplication.
 *
 * @package   Xmf
 * @author    XOOPS Development Team
 * @copyright 2026 XOOPS Project
 * @license   GPL-2.0-or-later
 * @link      https://xoops.org
 */

namespace Xmf;

/**
 * Trait for ULID-based entity identifiers
 *
 * Usage:
 * ```php
 * final readonly class ArticleId
 * {
 *     use EntityId;
 *
 *     protected static function exceptionClass(): string
 *     {
 *         return InvalidArticleId::class;
 *     }
 * }
 * ```
 */
trait EntityId
{
    private readonly Ulid $value;

    /**
     * Private constructor - use factory methods
     */
    private function __construct(Ulid $value)
    {
        $this->value = $value;
    }

    /**
     * Generate a new unique identifier
     */
    public static function generate(): self
    {
        return new self(Ulid::generate());
    }

    /**
     * Create from string representation
     *
     * @param string $id ULID string (26 characters)
     * @return self
     * @throws \InvalidArgumentException If string is not a valid ULID
     */
    public static function fromString(string $id): self
    {
        if (!Ulid::isValid($id)) {
            $exceptionClass = static::exceptionClass();
            throw new $exceptionClass(
                sprintf('Invalid %s: %s', static::entityName(), $id)
            );
        }

        return new self(Ulid::fromString($id));
    }

    /**
     * Create from binary representation
     *
     * @param string $binary 16-byte binary string
     * @return self
     */
    public static function fromBinary(string $binary): self
    {
        return new self(Ulid::fromBinary($binary));
    }

    /**
     * Get string representation (26 characters, uppercase)
     */
    public function toString(): string
    {
        return $this->value->toString();
    }

    /**
     * Get binary representation (16 bytes)
     */
    public function toBinary(): string
    {
        return $this->value->toBinary();
    }

    /**
     * Get the timestamp when this ID was created
     */
    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->value->getTimestamp();
    }

    /**
     * Get the Unix timestamp in milliseconds
     */
    public function getMilliseconds(): int
    {
        return $this->value->getMilliseconds();
    }

    /**
     * Compare with another ID of the same type
     */
    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }

    /**
     * Compare for ordering (lexicographic, which is chronological for ULIDs)
     *
     * @return int -1 if this < other, 0 if equal, 1 if this > other
     */
    public function compareTo(self $other): int
    {
        return $this->value->compareTo($other->value);
    }

    /**
     * Check if this ID was created before another
     */
    public function isBefore(self $other): bool
    {
        return $this->value->isBefore($other->value);
    }

    /**
     * Check if this ID was created after another
     */
    public function isAfter(self $other): bool
    {
        return $this->value->isAfter($other->value);
    }

    /**
     * Get string representation
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * JSON serialization
     */
    public function jsonSerialize(): string
    {
        return $this->toString();
    }

    /**
     * Get the exception class to throw for invalid IDs
     *
     * Override this method to provide a custom exception class.
     * Default returns InvalidArgumentException.
     */
    protected static function exceptionClass(): string
    {
        return \InvalidArgumentException::class;
    }

    /**
     * Get the entity name for error messages
     *
     * Override this method to provide a custom entity name.
     * Default extracts from class name (e.g., ArticleId -> "article ID").
     */
    protected static function entityName(): string
    {
        $className = (new \ReflectionClass(static::class))->getShortName();

        // Convert "ArticleId" to "article ID"
        $name = preg_replace('/Id$/', ' ID', $className);
        $name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $name);

        return strtolower($name);
    }
}
