<?php

declare(strict_types=1);

/**
 * XMF Slug - URL-Friendly String Generator
 *
 * This is a reference implementation for the XMF library.
 * Generates clean, URL-friendly slugs from any text input with:
 * - Full Unicode support with transliteration
 * - Multi-language support (Latin, CJK, Cyrillic, Arabic, Hebrew, Greek)
 * - Configurable separators and length limits
 * - Word-boundary aware truncation
 * - Built-in uniqueness suffix support
 *
 * @package   Xmf
 * @author    XOOPS Development Team
 * @copyright 2026 XOOPS Project
 * @license   GPL-2.0-or-later
 * @link      https://xoops.org
 */

namespace Xmf;

use InvalidArgumentException;

final class Slug implements \Stringable, \JsonSerializable
{
    /**
     * Default separator character
     */
    private const DEFAULT_SEPARATOR = '-';

    /**
     * Default maximum length
     */
    private const DEFAULT_MAX_LENGTH = 200;

    /**
     * Valid slug pattern
     */
    private const VALID_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * Transliteration map for common accented characters
     */
    private const TRANSLITERATION_MAP = [
        // Latin Extended
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'Ae', 'Å' => 'A',
        'Æ' => 'Ae', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ð' => 'D', 'Ñ' => 'N',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'Oe', 'Ø' => 'O',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'Ue', 'Ý' => 'Y', 'Þ' => 'Th',
        'ß' => 'ss',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'ae', 'å' => 'a',
        'æ' => 'ae', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'd', 'ñ' => 'n',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'oe', 'ø' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'ue', 'ý' => 'y', 'þ' => 'th',
        'ÿ' => 'y',
        // Latin Extended Additional
        'Œ' => 'Oe', 'œ' => 'oe', 'Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z',
        'ƒ' => 'f', 'Ÿ' => 'Y',
        // Polish
        'Ą' => 'A', 'ą' => 'a', 'Ć' => 'C', 'ć' => 'c', 'Ę' => 'E', 'ę' => 'e',
        'Ł' => 'L', 'ł' => 'l', 'Ń' => 'N', 'ń' => 'n', 'Ś' => 'S', 'ś' => 's',
        'Ź' => 'Z', 'ź' => 'z', 'Ż' => 'Z', 'ż' => 'z',
        // Czech/Slovak
        'Č' => 'C', 'č' => 'c', 'Ď' => 'D', 'ď' => 'd', 'Ě' => 'E', 'ě' => 'e',
        'Ň' => 'N', 'ň' => 'n', 'Ř' => 'R', 'ř' => 'r', 'Š' => 'S', 'š' => 's',
        'Ť' => 'T', 'ť' => 't', 'Ů' => 'U', 'ů' => 'u', 'Ž' => 'Z', 'ž' => 'z',
        // Turkish
        'Ğ' => 'G', 'ğ' => 'g', 'İ' => 'I', 'ı' => 'i', 'Ş' => 'S', 'ş' => 's',
        // Romanian
        'Ă' => 'A', 'ă' => 'a', 'Ș' => 'S', 'ș' => 's', 'Ț' => 'T', 'ț' => 't',
        // Icelandic
        'Ð' => 'D', 'ð' => 'd', 'Þ' => 'Th', 'þ' => 'th',
        // Cyrillic (Russian)
        'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
        'Ё' => 'Yo', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K',
        'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R',
        'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
        'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
        'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e',
        'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k',
        'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
        'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        // Ukrainian additions
        'Є' => 'Ye', 'є' => 'ye', 'І' => 'I', 'і' => 'i', 'Ї' => 'Yi', 'ї' => 'yi',
        'Ґ' => 'G', 'ґ' => 'g',
        // Greek
        'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z',
        'Η' => 'H', 'Θ' => 'Th', 'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M',
        'Ν' => 'N', 'Ξ' => 'X', 'Ο' => 'O', 'Π' => 'P', 'Ρ' => 'R', 'Σ' => 'S',
        'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'Ph', 'Χ' => 'Ch', 'Ψ' => 'Ps', 'Ω' => 'O',
        'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z',
        'η' => 'h', 'θ' => 'th', 'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm',
        'ν' => 'n', 'ξ' => 'x', 'ο' => 'o', 'π' => 'p', 'ρ' => 'r', 'σ' => 's',
        'ς' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'ph', 'χ' => 'ch', 'ψ' => 'ps',
        'ω' => 'o',
        // Currency symbols
        '€' => 'euro', '£' => 'pound', '¥' => 'yen', '$' => 'dollar', '¢' => 'cent',
        // Special characters
        '©' => 'c', '®' => 'r', '™' => 'tm', '&' => 'and', '@' => 'at',
        // Fractions
        '½' => 'half', '¼' => 'quarter', '¾' => 'three-quarters',
    ];

    /**
     * Pinyin map for common Chinese characters (simplified)
     * This is a subset - full implementation would use a library
     */
    private const PINYIN_MAP = [
        '你' => 'ni', '好' => 'hao', '世' => 'shi', '界' => 'jie',
        '中' => 'zhong', '国' => 'guo', '人' => 'ren', '大' => 'da',
        '学' => 'xue', '习' => 'xi', '文' => 'wen', '字' => 'zi',
        '新' => 'xin', '功' => 'gong', '能' => 'neng', '日' => 'ri',
        '本' => 'ben', '语' => 'yu', '我' => 'wo', '是' => 'shi',
        '的' => 'de', '一' => 'yi', '不' => 'bu', '了' => 'le',
        '在' => 'zai', '有' => 'you', '这' => 'zhe', '个' => 'ge',
        '上' => 'shang', '下' => 'xia', '来' => 'lai', '去' => 'qu',
    ];

    /**
     * @param string $slug The normalized slug string
     */
    private function __construct(
        private readonly string $slug,
    ) {}

    /**
     * Create a slug from text
     *
     * @param string $text The text to convert to a slug
     * @param array{
     *     separator?: string,
     *     maxLength?: int,
     *     lowercase?: bool,
     * } $options Configuration options
     * @return self
     */
    public static function create(string $text, array $options = []): self
    {
        $separator = $options['separator'] ?? self::DEFAULT_SEPARATOR;
        $maxLength = $options['maxLength'] ?? self::DEFAULT_MAX_LENGTH;
        $lowercase = $options['lowercase'] ?? true;

        // Trim whitespace
        $text = trim($text);

        if ($text === '') {
            throw new InvalidArgumentException('Cannot create slug from empty text');
        }

        // Transliterate Unicode characters
        $text = self::transliterate($text);

        // Convert to lowercase if requested
        if ($lowercase) {
            $text = mb_strtolower($text, 'UTF-8');
        }

        // Replace non-alphanumeric characters with separator
        $text = preg_replace('/[^a-zA-Z0-9]+/', $separator, $text);

        // Remove leading/trailing separators
        $text = trim($text, $separator);

        // Collapse multiple separators
        $text = preg_replace('/' . preg_quote($separator, '/') . '+/', $separator, $text);

        // Truncate to max length at word boundary
        if (mb_strlen($text) > $maxLength) {
            $text = self::truncateAtWordBoundary($text, $maxLength, $separator);
        }

        if ($text === '') {
            throw new InvalidArgumentException('Text produced empty slug after processing');
        }

        return new self($text);
    }

    /**
     * Create a Slug from an existing slug string
     *
     * @param string $slug The slug string
     * @return self
     * @throws InvalidArgumentException If string is not a valid slug
     */
    public static function fromString(string $slug): self
    {
        $slug = trim($slug);

        if (!self::isValid($slug)) {
            throw new InvalidArgumentException(
                sprintf('Invalid slug format: "%s"', $slug)
            );
        }

        return new self($slug);
    }

    /**
     * Normalize a slug string (clean up formatting issues)
     *
     * @param string $slug The slug to normalize
     * @return string Normalized slug
     */
    public static function normalize(string $slug): string
    {
        // Trim whitespace
        $slug = trim($slug);

        // Convert to lowercase
        $slug = mb_strtolower($slug, 'UTF-8');

        // Replace spaces and underscores with hyphens
        $slug = preg_replace('/[\s_]+/', '-', $slug);

        // Remove non-slug characters
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug);

        // Collapse multiple hyphens
        $slug = preg_replace('/-+/', '-', $slug);

        // Remove leading/trailing hyphens
        return trim($slug, '-');
    }

    /**
     * Validate a slug string
     *
     * @param string $slug The string to validate
     * @return bool True if valid slug format
     */
    public static function isValid(string $slug): bool
    {
        if ($slug === '') {
            return false;
        }

        return preg_match(self::VALID_PATTERN, $slug) === 1;
    }

    /**
     * Get the string representation
     *
     * @return string The slug string
     */
    public function toString(): string
    {
        return $this->slug;
    }

    /**
     * Create a new slug with a numeric suffix for uniqueness
     *
     * @param int $suffix The numeric suffix to append
     * @param string $separator Separator between slug and suffix
     * @return self New Slug instance with suffix
     */
    public function withSuffix(int $suffix, string $separator = '-'): self
    {
        if ($suffix < 1) {
            throw new InvalidArgumentException('Suffix must be a positive integer');
        }

        return new self($this->slug . $separator . $suffix);
    }

    /**
     * Check equality with another Slug
     *
     * @param self $other The Slug to compare with
     * @return bool True if slugs are equal
     */
    public function equals(self $other): bool
    {
        return $this->slug === $other->slug;
    }

    /**
     * Get the length of the slug
     *
     * @return int Character count
     */
    public function length(): int
    {
        return strlen($this->slug);
    }

    /**
     * Check if slug starts with a given prefix
     *
     * @param string $prefix The prefix to check
     * @return bool True if slug starts with prefix
     */
    public function startsWith(string $prefix): bool
    {
        return str_starts_with($this->slug, $prefix);
    }

    /**
     * Check if slug ends with a given suffix
     *
     * @param string $suffix The suffix to check
     * @return bool True if slug ends with suffix
     */
    public function endsWith(string $suffix): bool
    {
        return str_ends_with($this->slug, $suffix);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->slug;
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->slug;
    }

    /**
     * Transliterate Unicode text to ASCII
     *
     * @param string $text The text to transliterate
     * @return string ASCII text
     */
    private static function transliterate(string $text): string
    {
        // First, apply our custom transliteration map
        $text = strtr($text, self::TRANSLITERATION_MAP);

        // Apply Pinyin for Chinese characters
        $text = strtr($text, self::PINYIN_MAP);

        // Use intl transliterator if available for remaining characters
        if (class_exists('Transliterator')) {
            $transliterator = \Transliterator::create(
                'Any-Latin; Latin-ASCII; [\u0080-\u7fff] remove'
            );
            if ($transliterator !== null) {
                $text = $transliterator->transliterate($text) ?: $text;
            }
        } else {
            // Fallback: use iconv
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }

        return $text;
    }

    /**
     * Truncate text at word boundary
     *
     * @param string $text The text to truncate
     * @param int $maxLength Maximum length
     * @param string $separator Word separator
     * @return string Truncated text
     */
    private static function truncateAtWordBoundary(
        string $text,
        int $maxLength,
        string $separator
    ): string {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        // Find the last separator before max length
        $truncated = mb_substr($text, 0, $maxLength);
        $lastSeparator = mb_strrpos($truncated, $separator);

        if ($lastSeparator !== false && $lastSeparator > $maxLength * 0.5) {
            // Only truncate at separator if it's in the second half
            return mb_substr($truncated, 0, $lastSeparator);
        }

        // Otherwise, just hard truncate and remove trailing separator
        return rtrim($truncated, $separator);
    }
}
