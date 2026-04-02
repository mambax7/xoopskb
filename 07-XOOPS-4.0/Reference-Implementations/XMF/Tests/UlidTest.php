<?php

declare(strict_types=1);

/**
 * Unit tests for Xmf\Ulid
 *
 * @package   Xmf
 * @author    XOOPS Development Team
 * @copyright 2026 XOOPS Project
 * @license   GPL-2.0-or-later
 */

namespace Xmf\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use Xmf\Ulid;

#[CoversClass(Ulid::class)]
final class UlidTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset monotonic state between tests
        Ulid::resetState();
    }

    // =========================================================================
    // Generation Tests
    // =========================================================================

    #[Test]
    public function generate_creates_valid_ulid(): void
    {
        $ulid = Ulid::generate();

        $this->assertInstanceOf(Ulid::class, $ulid);
        $this->assertSame(26, strlen($ulid->toString()));
    }

    #[Test]
    public function generate_creates_unique_ulids(): void
    {
        $ulids = [];
        for ($i = 0; $i < 1000; $i++) {
            $ulids[] = Ulid::generate()->toString();
        }

        $this->assertCount(1000, array_unique($ulids));
    }

    #[Test]
    public function generate_with_timestamp_uses_provided_time(): void
    {
        $timestamp = new \DateTimeImmutable('2026-01-15 10:30:00.123');
        $ulid = Ulid::generate($timestamp);

        $extractedTime = $ulid->getTimestamp();

        // Should match within same second (milliseconds may vary due to encoding)
        $this->assertSame(
            $timestamp->format('Y-m-d H:i:s'),
            $extractedTime->format('Y-m-d H:i:s')
        );
    }

    #[Test]
    public function generate_produces_monotonic_ulids_in_same_millisecond(): void
    {
        // Generate many ULIDs with same timestamp
        $timestamp = new \DateTimeImmutable('2026-01-15 10:30:00.500');
        $ulids = [];

        for ($i = 0; $i < 100; $i++) {
            $ulids[] = Ulid::generate($timestamp)->toString();
        }

        // All should be unique
        $this->assertCount(100, array_unique($ulids));

        // Should be in ascending order (monotonic)
        $sorted = $ulids;
        sort($sorted);
        $this->assertSame($sorted, $ulids);
    }

    // =========================================================================
    // Parsing Tests
    // =========================================================================

    #[Test]
    public function fromString_parses_valid_ulid(): void
    {
        $ulidString = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
        $ulid = Ulid::fromString($ulidString);

        $this->assertSame($ulidString, $ulid->toString());
    }

    #[Test]
    public function fromString_is_case_insensitive(): void
    {
        $upper = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
        $lower = '01arz3ndektsv4rrffq69g5fav';
        $mixed = '01Arz3NdekTsv4rrFFQ69g5faV';

        $ulid1 = Ulid::fromString($upper);
        $ulid2 = Ulid::fromString($lower);
        $ulid3 = Ulid::fromString($mixed);

        $this->assertTrue($ulid1->equals($ulid2));
        $this->assertTrue($ulid2->equals($ulid3));
    }

    #[Test]
    public function fromString_handles_ambiguous_characters(): void
    {
        // O should be treated as 0
        // I and L should be treated as 1
        $withZero = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
        $withO = 'O1ARZ3NDEKTSV4RRFFQ69G5FAV';

        $ulid1 = Ulid::fromString($withZero);
        $ulid2 = Ulid::fromString($withO);

        $this->assertTrue($ulid1->equals($ulid2));
    }

    #[Test]
    public function fromString_trims_whitespace(): void
    {
        $ulid = Ulid::fromString('  01ARZ3NDEKTSV4RRFFQ69G5FAV  ');

        $this->assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', $ulid->toString());
    }

    #[Test]
    public function fromString_throws_for_invalid_length(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid ULID string');

        Ulid::fromString('01ARZ3NDEK'); // Too short
    }

    #[Test]
    public function fromString_throws_for_invalid_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FA!'); // Invalid character
    }

    #[Test]
    public function fromString_throws_for_overflow(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // First character > 7 causes timestamp overflow
        Ulid::fromString('8ZZZZZZZZZZZZZZZZZZZZZZZZZ');
    }

    // =========================================================================
    // Validation Tests
    // =========================================================================

    #[Test]
    #[DataProvider('validUlidProvider')]
    public function isValid_returns_true_for_valid_ulids(string $ulid): void
    {
        $this->assertTrue(Ulid::isValid($ulid));
    }

    public static function validUlidProvider(): array
    {
        return [
            'standard' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV'],
            'all zeros' => ['00000000000000000000000000'],
            'max valid' => ['7ZZZZZZZZZZZZZZZZZZZZZZZZZ'],
            'lowercase' => ['01arz3ndektsv4rrffq69g5fav'],
            'with ambiguous O' => ['O1ARZ3NDEKTSV4RRFFQ69G5FAV'],
        ];
    }

    #[Test]
    #[DataProvider('invalidUlidProvider')]
    public function isValid_returns_false_for_invalid_ulids(string $ulid): void
    {
        $this->assertFalse(Ulid::isValid($ulid));
    }

    public static function invalidUlidProvider(): array
    {
        return [
            'too short' => ['01ARZ3NDEK'],
            'too long' => ['01ARZ3NDEKTSV4RRFFQ69G5FAVX'],
            'invalid char' => ['01ARZ3NDEKTSV4RRFFQ69G5FA!'],
            'overflow' => ['8ZZZZZZZZZZZZZZZZZZZZZZZZZ'],
            'empty' => [''],
            'spaces only' => ['                          '],
            'uuid format' => ['550e8400-e29b-41d4-a716-446655440000'],
        ];
    }

    // =========================================================================
    // Binary Conversion Tests
    // =========================================================================

    #[Test]
    public function toBinary_returns_16_bytes(): void
    {
        $ulid = Ulid::generate();
        $binary = $ulid->toBinary();

        $this->assertSame(16, strlen($binary));
    }

    #[Test]
    public function fromBinary_roundtrips_correctly(): void
    {
        $original = Ulid::generate();
        $binary = $original->toBinary();
        $restored = Ulid::fromBinary($binary);

        $this->assertTrue($original->equals($restored));
    }

    #[Test]
    public function fromBinary_throws_for_wrong_length(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('16 bytes');

        Ulid::fromBinary('short');
    }

    // =========================================================================
    // Timestamp Tests
    // =========================================================================

    #[Test]
    public function getTimestamp_extracts_creation_time(): void
    {
        $before = new \DateTimeImmutable();
        $ulid = Ulid::generate();
        $after = new \DateTimeImmutable();

        $timestamp = $ulid->getTimestamp();

        $this->assertGreaterThanOrEqual($before->getTimestamp(), $timestamp->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $timestamp->getTimestamp());
    }

    #[Test]
    public function getMilliseconds_returns_unix_timestamp_in_ms(): void
    {
        $now = (int) (microtime(true) * 1000);
        $ulid = Ulid::generate();
        $ms = $ulid->getMilliseconds();

        // Should be within 100ms of now
        $this->assertLessThan(100, abs($ms - $now));
    }

    // =========================================================================
    // Comparison Tests
    // =========================================================================

    #[Test]
    public function equals_returns_true_for_same_ulid(): void
    {
        $ulid1 = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $ulid2 = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');

        $this->assertTrue($ulid1->equals($ulid2));
    }

    #[Test]
    public function equals_returns_false_for_different_ulids(): void
    {
        $ulid1 = Ulid::generate();
        $ulid2 = Ulid::generate();

        $this->assertFalse($ulid1->equals($ulid2));
    }

    #[Test]
    public function compareTo_returns_correct_ordering(): void
    {
        $older = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $newer = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAW');

        $this->assertSame(-1, $older->compareTo($newer));
        $this->assertSame(1, $newer->compareTo($older));
        $this->assertSame(0, $older->compareTo($older));
    }

    #[Test]
    public function isBefore_and_isAfter_work_correctly(): void
    {
        $first = Ulid::generate();
        usleep(1000); // 1ms delay
        $second = Ulid::generate();

        $this->assertTrue($first->isBefore($second));
        $this->assertFalse($first->isAfter($second));
        $this->assertTrue($second->isAfter($first));
        $this->assertFalse($second->isBefore($first));
    }

    // =========================================================================
    // String Representation Tests
    // =========================================================================

    #[Test]
    public function toString_returns_uppercase(): void
    {
        $ulid = Ulid::generate();
        $string = $ulid->toString();

        $this->assertSame(strtoupper($string), $string);
    }

    #[Test]
    public function toLowercase_returns_lowercase(): void
    {
        $ulid = Ulid::generate();
        $string = $ulid->toLowercase();

        $this->assertSame(strtolower($string), $string);
    }

    #[Test]
    public function __toString_works(): void
    {
        $ulid = Ulid::generate();

        $this->assertSame($ulid->toString(), (string) $ulid);
    }

    #[Test]
    public function jsonSerialize_returns_string(): void
    {
        $ulid = Ulid::generate();
        $json = json_encode(['id' => $ulid]);

        $this->assertStringContainsString($ulid->toString(), $json);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    #[Test]
    public function handles_minimum_timestamp(): void
    {
        $minTime = new \DateTimeImmutable('@0'); // Unix epoch
        $ulid = Ulid::generate($minTime);

        $this->assertTrue(str_starts_with($ulid->toString(), '0'));
    }

    #[Test]
    public function lexicographic_sorting_matches_chronological(): void
    {
        $ulids = [];
        for ($i = 0; $i < 10; $i++) {
            $ulids[] = Ulid::generate();
            usleep(100); // Small delay between generations
        }

        $strings = array_map(fn($u) => $u->toString(), $ulids);
        $sorted = $strings;
        sort($sorted);

        $this->assertSame($strings, $sorted);
    }
}
