<?php

declare(strict_types=1);

/**
 * Unit tests for Xmf\Slug
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
use Xmf\Slug;

#[CoversClass(Slug::class)]
final class SlugTest extends TestCase
{
    // =========================================================================
    // Basic Creation Tests
    // =========================================================================

    #[Test]
    public function create_generates_slug_from_simple_text(): void
    {
        $slug = Slug::create('Hello World');

        $this->assertSame('hello-world', $slug->toString());
    }

    #[Test]
    public function create_converts_to_lowercase_by_default(): void
    {
        $slug = Slug::create('HELLO WORLD');

        $this->assertSame('hello-world', $slug->toString());
    }

    #[Test]
    public function create_replaces_spaces_with_separator(): void
    {
        $slug = Slug::create('This is a test');

        $this->assertSame('this-is-a-test', $slug->toString());
    }

    #[Test]
    public function create_removes_special_characters(): void
    {
        $slug = Slug::create('Hello! @World# $Test%');

        $this->assertSame('hello-world-test', $slug->toString());
    }

    #[Test]
    public function create_collapses_multiple_separators(): void
    {
        $slug = Slug::create('Hello   World');

        $this->assertSame('hello-world', $slug->toString());
    }

    #[Test]
    public function create_trims_separators(): void
    {
        $slug = Slug::create('  Hello World  ');

        $this->assertSame('hello-world', $slug->toString());
    }

    #[Test]
    public function create_throws_for_empty_text(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('empty');

        Slug::create('');
    }

    #[Test]
    public function create_throws_for_whitespace_only(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Slug::create('   ');
    }

    // =========================================================================
    // Options Tests
    // =========================================================================

    #[Test]
    public function create_respects_custom_separator(): void
    {
        $slug = Slug::create('Hello World', ['separator' => '_']);

        $this->assertSame('hello_world', $slug->toString());
    }

    #[Test]
    public function create_respects_max_length(): void
    {
        $slug = Slug::create('This is a very long title that should be truncated', [
            'maxLength' => 20,
        ]);

        $this->assertLessThanOrEqual(20, $slug->length());
    }

    #[Test]
    public function create_truncates_at_word_boundary(): void
    {
        $slug = Slug::create('Hello World Test Example', [
            'maxLength' => 15,
        ]);

        // Should not cut in middle of "world"
        $this->assertStringNotContainsString('worl-', $slug->toString());
    }

    #[Test]
    public function create_can_preserve_case(): void
    {
        $slug = Slug::create('Hello World', ['lowercase' => false]);

        $this->assertSame('Hello-World', $slug->toString());
    }

    // =========================================================================
    // Unicode Transliteration Tests
    // =========================================================================

    #[Test]
    #[DataProvider('accentedCharacterProvider')]
    public function create_transliterates_accented_characters(
        string $input,
        string $expected
    ): void {
        $slug = Slug::create($input);

        $this->assertSame($expected, $slug->toString());
    }

    public static function accentedCharacterProvider(): array
    {
        return [
            'french' => ['Café résumé', 'cafe-resume'],
            'german' => ['Größe', 'groesse'],
            'spanish' => ['Español', 'espanol'],
            'portuguese' => ['São Paulo', 'sao-paulo'],
            'swedish' => ['Smörgåsbord', 'smoergasbord'],
            'polish' => ['Łódź', 'lodz'],
            'czech' => ['Příliš žluťoučký', 'prilis-zlutoucky'],
        ];
    }

    #[Test]
    #[DataProvider('cyrillicProvider')]
    public function create_transliterates_cyrillic(string $input, string $expected): void
    {
        $slug = Slug::create($input);

        $this->assertSame($expected, $slug->toString());
    }

    public static function cyrillicProvider(): array
    {
        return [
            'russian hello' => ['Привет', 'privet'],
            'russian world' => ['мир', 'mir'],
            'ukrainian' => ['Київ', 'kyjiv'],
        ];
    }

    #[Test]
    #[DataProvider('greekProvider')]
    public function create_transliterates_greek(string $input, string $expected): void
    {
        $slug = Slug::create($input);

        $this->assertSame($expected, $slug->toString());
    }

    public static function greekProvider(): array
    {
        return [
            'alpha' => ['Αλφα', 'alpha'],
            'omega' => ['Ωμεγα', 'omega'],
            'philosophy' => ['φιλοσοφία', 'philosophia'],
        ];
    }

    #[Test]
    public function create_handles_chinese_with_pinyin(): void
    {
        $slug = Slug::create('你好');

        // Should transliterate to pinyin
        $this->assertSame('ni-hao', $slug->toString());
    }

    #[Test]
    public function create_handles_mixed_scripts(): void
    {
        $slug = Slug::create('PHP 8.3 新功能');

        // Should handle mix of ASCII and Chinese
        $this->assertStringContainsString('php', $slug->toString());
        $this->assertStringContainsString('8-3', $slug->toString());
    }

    // =========================================================================
    // Currency and Symbol Tests
    // =========================================================================

    #[Test]
    #[DataProvider('currencySymbolProvider')]
    public function create_converts_currency_symbols(string $input, string $expected): void
    {
        $slug = Slug::create($input);

        $this->assertSame($expected, $slug->toString());
    }

    public static function currencySymbolProvider(): array
    {
        return [
            'euro' => ['Price: €100', 'price-euro100'],
            'pound' => ['£50 off', 'pound50-off'],
            'dollar' => ['$99.99 deal', 'dollar99-99-deal'],
        ];
    }

    #[Test]
    public function create_converts_ampersand(): void
    {
        $slug = Slug::create('Rock & Roll');

        $this->assertSame('rock-and-roll', $slug->toString());
    }

    #[Test]
    public function create_converts_at_symbol(): void
    {
        $slug = Slug::create('Contact @ Us');

        $this->assertSame('contact-at-us', $slug->toString());
    }

    // =========================================================================
    // Parsing Tests
    // =========================================================================

    #[Test]
    public function fromString_accepts_valid_slug(): void
    {
        $slug = Slug::fromString('valid-slug-here');

        $this->assertSame('valid-slug-here', $slug->toString());
    }

    #[Test]
    public function fromString_throws_for_invalid_slug(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Slug::fromString('Invalid Slug!');
    }

    #[Test]
    public function fromString_throws_for_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Slug::fromString('');
    }

    // =========================================================================
    // Normalization Tests
    // =========================================================================

    #[Test]
    public function normalize_cleans_slug_string(): void
    {
        $normalized = Slug::normalize('  My--Slug--  ');

        $this->assertSame('my-slug', $normalized);
    }

    #[Test]
    public function normalize_converts_underscores_to_hyphens(): void
    {
        $normalized = Slug::normalize('my_slug_here');

        $this->assertSame('my-slug-here', $normalized);
    }

    #[Test]
    public function normalize_removes_invalid_characters(): void
    {
        $normalized = Slug::normalize('my-slug!@#$%');

        $this->assertSame('my-slug', $normalized);
    }

    // =========================================================================
    // Validation Tests
    // =========================================================================

    #[Test]
    #[DataProvider('validSlugProvider')]
    public function isValid_returns_true_for_valid_slugs(string $slug): void
    {
        $this->assertTrue(Slug::isValid($slug));
    }

    public static function validSlugProvider(): array
    {
        return [
            'simple' => ['hello'],
            'with-hyphen' => ['hello-world'],
            'with-numbers' => ['article-123'],
            'numbers-only' => ['12345'],
            'long' => ['this-is-a-very-long-slug-that-is-still-valid'],
        ];
    }

    #[Test]
    #[DataProvider('invalidSlugProvider')]
    public function isValid_returns_false_for_invalid_slugs(string $slug): void
    {
        $this->assertFalse(Slug::isValid($slug));
    }

    public static function invalidSlugProvider(): array
    {
        return [
            'empty' => [''],
            'uppercase' => ['Hello-World'],
            'spaces' => ['hello world'],
            'special chars' => ['hello!world'],
            'leading hyphen' => ['-hello'],
            'trailing hyphen' => ['hello-'],
            'double hyphen' => ['hello--world'],
            'underscore' => ['hello_world'],
        ];
    }

    // =========================================================================
    // Suffix Tests
    // =========================================================================

    #[Test]
    public function withSuffix_appends_number(): void
    {
        $slug = Slug::create('My Article');
        $withSuffix = $slug->withSuffix(2);

        $this->assertSame('my-article-2', $withSuffix->toString());
    }

    #[Test]
    public function withSuffix_creates_new_instance(): void
    {
        $original = Slug::create('My Article');
        $withSuffix = $original->withSuffix(2);

        $this->assertNotSame($original, $withSuffix);
        $this->assertSame('my-article', $original->toString());
    }

    #[Test]
    public function withSuffix_can_chain(): void
    {
        $slug = Slug::create('article');

        $this->assertSame('article-1', $slug->withSuffix(1)->toString());
        $this->assertSame('article-2', $slug->withSuffix(2)->toString());
        $this->assertSame('article-99', $slug->withSuffix(99)->toString());
    }

    #[Test]
    public function withSuffix_throws_for_zero(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Slug::create('article')->withSuffix(0);
    }

    #[Test]
    public function withSuffix_throws_for_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Slug::create('article')->withSuffix(-1);
    }

    // =========================================================================
    // Comparison Tests
    // =========================================================================

    #[Test]
    public function equals_returns_true_for_same_slug(): void
    {
        $slug1 = Slug::create('Hello World');
        $slug2 = Slug::create('hello world');

        $this->assertTrue($slug1->equals($slug2));
    }

    #[Test]
    public function equals_returns_false_for_different_slugs(): void
    {
        $slug1 = Slug::create('Hello World');
        $slug2 = Slug::create('Goodbye World');

        $this->assertFalse($slug1->equals($slug2));
    }

    // =========================================================================
    // Utility Method Tests
    // =========================================================================

    #[Test]
    public function length_returns_character_count(): void
    {
        $slug = Slug::create('hello world');

        $this->assertSame(11, $slug->length());
    }

    #[Test]
    public function startsWith_works(): void
    {
        $slug = Slug::create('hello world');

        $this->assertTrue($slug->startsWith('hello'));
        $this->assertFalse($slug->startsWith('world'));
    }

    #[Test]
    public function endsWith_works(): void
    {
        $slug = Slug::create('hello world');

        $this->assertTrue($slug->endsWith('world'));
        $this->assertFalse($slug->endsWith('hello'));
    }

    // =========================================================================
    // String Representation Tests
    // =========================================================================

    #[Test]
    public function __toString_works(): void
    {
        $slug = Slug::create('Hello World');

        $this->assertSame('hello-world', (string) $slug);
    }

    #[Test]
    public function jsonSerialize_returns_string(): void
    {
        $slug = Slug::create('Hello World');
        $json = json_encode(['slug' => $slug]);

        $this->assertSame('{"slug":"hello-world"}', $json);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    #[Test]
    public function handles_single_character(): void
    {
        $slug = Slug::create('A');

        $this->assertSame('a', $slug->toString());
    }

    #[Test]
    public function handles_numbers_only(): void
    {
        $slug = Slug::create('12345');

        $this->assertSame('12345', $slug->toString());
    }

    #[Test]
    public function handles_very_long_input(): void
    {
        $longText = str_repeat('word ', 100);
        $slug = Slug::create($longText, ['maxLength' => 50]);

        $this->assertLessThanOrEqual(50, $slug->length());
        $this->assertTrue(Slug::isValid($slug->toString()));
    }

    #[Test]
    public function create_throws_when_all_characters_removed(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Slug::create('!@#$%^&*()');
    }
}
