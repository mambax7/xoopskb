<?php

declare(strict_types=1);

/**
 * XMF ULID - Universally Unique Lexicographically Sortable Identifier
 *
 * This is a reference implementation for the XMF library.
 * ULIDs are 128-bit identifiers that are:
 * - Lexicographically sortable
 * - Canonically encoded as 26 character string
 * - Uses Crockford's Base32 (case-insensitive, URL-safe)
 * - 1.21e+24 unique ULIDs per millisecond
 *
 * Structure:
 *  01AN4Z07BY      79KA1307SR9X4MV3
 * |----------|    |----------------|
 *  Timestamp          Randomness
 *   48 bits            80 bits
 *   10 chars           16 chars
 *
 * @package   Xmf
 * @author    XOOPS Development Team
 * @copyright 2026 XOOPS Project
 * @license   GPL-2.0-or-later
 * @link      https://xoops.org
 */

namespace Xmf;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use RuntimeException;

final class Ulid implements \Stringable, \JsonSerializable
{
    /**
     * Crockford's Base32 alphabet (excludes I, L, O, U to avoid confusion)
     */
    private const ENCODING = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /**
     * Decoding map for Base32
     */
    private const DECODING = [
        '0' => 0,  '1' => 1,  '2' => 2,  '3' => 3,  '4' => 4,
        '5' => 5,  '6' => 6,  '7' => 7,  '8' => 8,  '9' => 9,
        'A' => 10, 'B' => 11, 'C' => 12, 'D' => 13, 'E' => 14,
        'F' => 15, 'G' => 16, 'H' => 17, 'J' => 18, 'K' => 19,
        'M' => 20, 'N' => 21, 'P' => 22, 'Q' => 23, 'R' => 24,
        'S' => 25, 'T' => 26, 'V' => 27, 'W' => 28, 'X' => 29,
        'Y' => 30, 'Z' => 31,
        // Lowercase mappings
        'a' => 10, 'b' => 11, 'c' => 12, 'd' => 13, 'e' => 14,
        'f' => 15, 'g' => 16, 'h' => 17, 'j' => 18, 'k' => 19,
        'm' => 20, 'n' => 21, 'p' => 22, 'q' => 23, 'r' => 24,
        's' => 25, 't' => 26, 'v' => 27, 'w' => 28, 'x' => 29,
        'y' => 30, 'z' => 31,
        // Ambiguous character mappings
        'O' => 0, 'o' => 0,  // O -> 0
        'I' => 1, 'i' => 1,  // I -> 1
        'L' => 1, 'l' => 1,  // L -> 1
    ];

    /**
     * Maximum valid ULID string
     */
    private const MAX_ULID = '7ZZZZZZZZZZZZZZZZZZZZZZZZZ';

    /**
     * ULID string length
     */
    private const LENGTH = 26;

    /**
     * Timestamp portion length
     */
    private const TIME_LENGTH = 10;

    /**
     * Random portion length
     */
    private const RANDOM_LENGTH = 16;

    /**
     * Last generation timestamp for monotonic generation
     */
    private static ?int $lastTime = null;

    /**
     * Last random bytes for monotonic increment
     */
    private static ?string $lastRandom = null;

    /**
     * @param string $ulid The 26-character ULID string (uppercase)
     */
    private function __construct(
        private readonly string $ulid,
    ) {}

    /**
     * Generate a new ULID
     *
     * Uses monotonic generation: if multiple ULIDs are generated within
     * the same millisecond, the random component is incremented to ensure
     * proper ordering.
     *
     * @param DateTimeInterface|null $timestamp Optional timestamp (defaults to now)
     * @return self
     * @throws RuntimeException If random generation fails
     */
    public static function generate(?DateTimeInterface $timestamp = null): self
    {
        $time = $timestamp !== null
            ? (int) ($timestamp->format('Uv'))
            : (int) (microtime(true) * 1000);

        // Monotonic generation within same millisecond
        if (self::$lastTime === $time && self::$lastRandom !== null) {
            $random = self::incrementRandom(self::$lastRandom);
        } else {
            $random = self::generateRandom();
        }

        self::$lastTime = $time;
        self::$lastRandom = $random;

        $timeString = self::encodeTime($time);
        $randomString = self::encodeRandom($random);

        return new self($timeString . $randomString);
    }

    /**
     * Create a ULID from a string representation
     *
     * @param string $ulid The ULID string (case-insensitive)
     * @return self
     * @throws InvalidArgumentException If the string is not a valid ULID
     */
    public static function fromString(string $ulid): self
    {
        $ulid = strtoupper(trim($ulid));

        if (!self::isValid($ulid)) {
            throw new InvalidArgumentException(
                sprintf('Invalid ULID string: "%s"', $ulid)
            );
        }

        // Normalize ambiguous characters
        $ulid = strtr($ulid, [
            'O' => '0', 'o' => '0',
            'I' => '1', 'i' => '1',
            'L' => '1', 'l' => '1',
        ]);

        return new self(strtoupper($ulid));
    }

    /**
     * Create a ULID from binary representation (16 bytes)
     *
     * @param string $bytes 16-byte binary string
     * @return self
     * @throws InvalidArgumentException If bytes are not exactly 16 bytes
     */
    public static function fromBinary(string $bytes): self
    {
        if (strlen($bytes) !== 16) {
            throw new InvalidArgumentException(
                sprintf('Binary ULID must be exactly 16 bytes, %d given', strlen($bytes))
            );
        }

        // Convert 16 bytes to 26 character Base32 string
        $ulid = self::encodeBinaryToBase32($bytes);

        return new self($ulid);
    }

    /**
     * Validate a ULID string
     *
     * @param string $ulid The string to validate
     * @return bool True if valid ULID format
     */
    public static function isValid(string $ulid): bool
    {
        $ulid = strtoupper(trim($ulid));

        if (strlen($ulid) !== self::LENGTH) {
            return false;
        }

        // Check all characters are valid Base32
        for ($i = 0; $i < self::LENGTH; $i++) {
            if (!isset(self::DECODING[$ulid[$i]])) {
                return false;
            }
        }

        // Check timestamp portion doesn't overflow
        // First character must be 0-7 (max timestamp is 7ZZZZZZZZZ)
        if ($ulid[0] > '7') {
            return false;
        }

        return true;
    }

    /**
     * Get the string representation
     *
     * @return string 26-character uppercase ULID string
     */
    public function toString(): string
    {
        return $this->ulid;
    }

    /**
     * Get lowercase string representation
     *
     * @return string 26-character lowercase ULID string
     */
    public function toLowercase(): string
    {
        return strtolower($this->ulid);
    }

    /**
     * Get binary representation (16 bytes)
     *
     * @return string 16-byte binary string
     */
    public function toBinary(): string
    {
        return self::decodeBase32ToBinary($this->ulid);
    }

    /**
     * Extract the timestamp from this ULID
     *
     * @return DateTimeImmutable The timestamp when this ULID was created
     */
    public function getTimestamp(): DateTimeImmutable
    {
        $timePart = substr($this->ulid, 0, self::TIME_LENGTH);
        $milliseconds = self::decodeTime($timePart);

        $seconds = intdiv($milliseconds, 1000);
        $microseconds = ($milliseconds % 1000) * 1000;

        return DateTimeImmutable::createFromFormat(
            'U u',
            sprintf('%d %06d', $seconds, $microseconds)
        ) ?: new DateTimeImmutable();
    }

    /**
     * Get the millisecond timestamp
     *
     * @return int Unix timestamp in milliseconds
     */
    public function getMilliseconds(): int
    {
        $timePart = substr($this->ulid, 0, self::TIME_LENGTH);
        return self::decodeTime($timePart);
    }

    /**
     * Check equality with another ULID
     *
     * @param self $other The ULID to compare with
     * @return bool True if ULIDs are equal
     */
    public function equals(self $other): bool
    {
        return $this->ulid === $other->ulid;
    }

    /**
     * Compare with another ULID for ordering
     *
     * @param self $other The ULID to compare with
     * @return int -1 if this < other, 0 if equal, 1 if this > other
     */
    public function compareTo(self $other): int
    {
        return $this->ulid <=> $other->ulid;
    }

    /**
     * Check if this ULID is older than another
     *
     * @param self $other The ULID to compare with
     * @return bool True if this ULID was created before the other
     */
    public function isBefore(self $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    /**
     * Check if this ULID is newer than another
     *
     * @param self $other The ULID to compare with
     * @return bool True if this ULID was created after the other
     */
    public function isAfter(self $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->ulid;
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->ulid;
    }

    /**
     * Encode timestamp to 10-character Base32 string
     *
     * @param int $milliseconds Unix timestamp in milliseconds
     * @return string 10-character Base32 encoded timestamp
     */
    private static function encodeTime(int $milliseconds): string
    {
        $chars = '';
        for ($i = self::TIME_LENGTH - 1; $i >= 0; $i--) {
            $chars = self::ENCODING[$milliseconds & 0x1F] . $chars;
            $milliseconds >>= 5;
        }
        return $chars;
    }

    /**
     * Decode 10-character Base32 timestamp to milliseconds
     *
     * @param string $encoded 10-character timestamp portion
     * @return int Unix timestamp in milliseconds
     */
    private static function decodeTime(string $encoded): int
    {
        $time = 0;
        for ($i = 0; $i < self::TIME_LENGTH; $i++) {
            $time = ($time << 5) | self::DECODING[$encoded[$i]];
        }
        return $time;
    }

    /**
     * Generate 10 random bytes for the random portion
     *
     * @return string 10 random bytes
     * @throws RuntimeException If random generation fails
     */
    private static function generateRandom(): string
    {
        $bytes = random_bytes(10);
        if ($bytes === false) {
            throw new RuntimeException('Failed to generate random bytes');
        }
        return $bytes;
    }

    /**
     * Increment random bytes for monotonic generation
     *
     * @param string $random Current random bytes
     * @return string Incremented random bytes
     * @throws RuntimeException If overflow occurs
     */
    private static function incrementRandom(string $random): string
    {
        $bytes = array_values(unpack('C*', $random));

        for ($i = count($bytes) - 1; $i >= 0; $i--) {
            if ($bytes[$i] < 255) {
                $bytes[$i]++;
                break;
            }
            $bytes[$i] = 0;
            if ($i === 0) {
                // Overflow - generate new random
                return self::generateRandom();
            }
        }

        return pack('C*', ...$bytes);
    }

    /**
     * Encode 10 random bytes to 16-character Base32 string
     *
     * @param string $bytes 10 random bytes
     * @return string 16-character Base32 encoded random portion
     */
    private static function encodeRandom(string $bytes): string
    {
        $values = array_values(unpack('C*', $bytes));
        $encoded = '';

        // Encode 10 bytes (80 bits) to 16 Base32 characters (80 bits)
        // Process 5 bytes at a time (40 bits = 8 Base32 chars)
        for ($i = 0; $i < 10; $i += 5) {
            $n = ($values[$i] << 32)
               | ($values[$i + 1] << 24)
               | ($values[$i + 2] << 16)
               | ($values[$i + 3] << 8)
               | $values[$i + 4];

            for ($j = 7; $j >= 0; $j--) {
                $encoded .= self::ENCODING[($n >> ($j * 5)) & 0x1F];
            }
        }

        return $encoded;
    }

    /**
     * Encode 16 bytes to 26-character Base32 string
     *
     * @param string $bytes 16 bytes
     * @return string 26-character ULID
     */
    private static function encodeBinaryToBase32(string $bytes): string
    {
        $values = array_values(unpack('C*', $bytes));

        // First 6 bytes encode to first 10 characters (timestamp)
        $timePart = '';
        $timeBytes = array_slice($values, 0, 6);
        $timeInt = 0;
        foreach ($timeBytes as $byte) {
            $timeInt = ($timeInt << 8) | $byte;
        }
        for ($i = 9; $i >= 0; $i--) {
            $timePart = self::ENCODING[($timeInt >> ($i * 5)) & 0x1F] . $timePart;
        }

        // Last 10 bytes encode to last 16 characters (random)
        $randomPart = self::encodeRandom(substr($bytes, 6, 10));

        return $timePart . $randomPart;
    }

    /**
     * Decode 26-character Base32 to 16 bytes
     *
     * @param string $ulid 26-character ULID
     * @return string 16 bytes
     */
    private static function decodeBase32ToBinary(string $ulid): string
    {
        // Decode timestamp (10 chars to 6 bytes)
        $timeInt = self::decodeTime(substr($ulid, 0, 10));
        $timeBytes = '';
        for ($i = 5; $i >= 0; $i--) {
            $timeBytes = chr(($timeInt >> ($i * 8)) & 0xFF) . $timeBytes;
        }

        // Decode random (16 chars to 10 bytes)
        $randomPart = substr($ulid, 10, 16);
        $randomBytes = '';

        for ($i = 0; $i < 16; $i += 8) {
            $n = 0;
            for ($j = 0; $j < 8; $j++) {
                $n = ($n << 5) | self::DECODING[$randomPart[$i + $j]];
            }
            for ($k = 4; $k >= 0; $k--) {
                $randomBytes .= chr(($n >> ($k * 8)) & 0xFF);
            }
        }

        return $timeBytes . $randomBytes;
    }

    /**
     * Reset the monotonic state (useful for testing)
     *
     * @internal
     */
    public static function resetState(): void
    {
        self::$lastTime = null;
        self::$lastRandom = null;
    }
}
