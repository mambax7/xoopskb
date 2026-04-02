<?php

declare(strict_types=1);

/**
 * PHP-CS-Fixer Configuration for XOOPS 4.0 Modules
 *
 * @see https://cs.symfony.com/doc/rules/index.html
 */

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/Domain',
        __DIR__ . '/Application',
        __DIR__ . '/Infrastructure',
        __DIR__ . '/Presentation',
        __DIR__ . '/tests',
    ])
    ->exclude([
        'vendor',
        'cache',
        'templates_c',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        // === PHP Version & Syntax ===
        '@PHP82Migration' => true,
        '@PHP80Migration:risky' => true,
        'declare_strict_types' => true,

        // === PSR Standards ===
        '@PSR12' => true,
        '@PSR12:risky' => true,

        // === Arrays ===
        'array_syntax' => ['syntax' => 'short'],
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_trailing_comma_in_singleline' => true,
        'no_whitespace_before_comma_in_array' => true,
        'normalize_index_brace' => true,
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters', 'match'],
        ],
        'trim_array_spaces' => true,
        'whitespace_after_comma_in_array' => ['ensure_single_space' => true],

        // === Braces & Control Structures ===
        'control_structure_braces' => true,
        'control_structure_continuation_position' => true,
        'no_alternative_syntax' => true,
        'no_superfluous_elseif' => true,
        'no_useless_else' => true,
        'simplified_if_return' => true,
        'yoda_style' => false,

        // === Classes & OOP ===
        'class_attributes_separation' => [
            'elements' => [
                'const' => 'one',
                'method' => 'one',
                'property' => 'one',
                'trait_import' => 'none',
            ],
        ],
        'class_definition' => [
            'single_line' => true,
            'single_item_single_line' => true,
        ],
        'final_class' => false, // Don't force final on all classes
        'final_public_method_for_abstract_class' => true,
        'no_null_property_initialization' => true,
        'no_php4_constructor' => true,
        'no_unneeded_final_method' => true,
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'case',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public_static',
                'property_protected_static',
                'property_private_static',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public_static',
                'method_protected_static',
                'method_private_static',
                'method_public',
                'method_protected',
                'method_private',
            ],
        ],
        'ordered_interfaces' => true,
        'ordered_traits' => true,
        'protected_to_private' => true,
        'self_accessor' => true,
        'single_class_element_per_statement' => true,
        'visibility_required' => ['elements' => ['property', 'method', 'const']],

        // === Comments & PHPDoc ===
        'align_multiline_comment' => true,
        'multiline_comment_opening_closing' => true,
        'no_empty_comment' => true,
        'no_trailing_whitespace_in_comment' => true,
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_annotation_without_dot' => true,
        'phpdoc_indent' => true,
        'phpdoc_line_span' => [
            'const' => 'single',
            'property' => 'single',
            'method' => 'multi',
        ],
        'phpdoc_no_empty_return' => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_order' => ['order' => ['param', 'throws', 'return']],
        'phpdoc_scalar' => true,
        'phpdoc_separation' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_summary' => true,
        'phpdoc_to_comment' => false,
        'phpdoc_trim' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_types' => true,
        'phpdoc_types_order' => [
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'none',
        ],
        'phpdoc_var_without_name' => true,
        'single_line_comment_style' => ['comment_types' => ['hash']],

        // === Functions ===
        'function_declaration' => ['closure_function_spacing' => 'one'],
        'lambda_not_used_import' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'after_heredoc' => true,
        ],
        'no_spaces_after_function_name' => true,
        'no_unreachable_default_argument_value' => true,
        'no_useless_sprintf' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'return_type_declaration' => ['space_before' => 'none'],
        'single_line_throw' => false,
        'static_lambda' => true,
        'void_return' => true,

        // === Imports & Namespaces ===
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'no_leading_import_slash' => true,
        'no_unused_imports' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],
        'single_import_per_statement' => true,

        // === Operators ===
        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],
        'concat_space' => ['spacing' => 'one'],
        'increment_style' => ['style' => 'pre'],
        'logical_operators' => true,
        'new_with_braces' => ['anonymous_class' => false],
        'no_space_around_double_colon' => true,
        'not_operator_with_successor_space' => false,
        'object_operator_without_whitespace' => true,
        'operator_linebreak' => ['only_booleans' => true, 'position' => 'beginning'],
        'standardize_not_equals' => true,
        'ternary_operator_spaces' => true,
        'ternary_to_null_coalescing' => true,
        'unary_operator_spaces' => true,

        // === Strings ===
        'explicit_string_variable' => true,
        'heredoc_to_nowdoc' => true,
        'no_binary_string' => true,
        'single_quote' => true,
        'string_length_to_empty' => true,

        // === Type Casting ===
        'cast_spaces' => ['space' => 'single'],
        'lowercase_cast' => true,
        'modernize_types_casting' => true,
        'no_short_bool_cast' => true,
        'short_scalar_cast' => true,

        // === Whitespace ===
        'array_indentation' => true,
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,
        'blank_line_before_statement' => [
            'statements' => [
                'break',
                'continue',
                'declare',
                'return',
                'throw',
                'try',
            ],
        ],
        'blank_lines_before_namespace' => true,
        'compact_nullable_typehint' => true,
        'indentation_type' => true,
        'line_ending' => true,
        'method_chaining_indentation' => true,
        'no_blank_lines_after_class_opening' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_extra_blank_lines' => [
            'tokens' => [
                'attribute',
                'break',
                'case',
                'continue',
                'curly_brace_block',
                'default',
                'extra',
                'parenthesis_brace_block',
                'return',
                'square_brace_block',
                'switch',
                'throw',
                'use',
            ],
        ],
        'no_spaces_around_offset' => true,
        'no_trailing_whitespace' => true,
        'no_whitespace_in_blank_line' => true,
        'single_blank_line_at_eof' => true,
        'types_spaces' => ['space' => 'none'],

        // === Strict ===
        'declare_parentheses' => true,
        'strict_comparison' => true,
        'strict_param' => true,

        // === Clean Code ===
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'dir_constant' => true,
        'ereg_to_preg' => true,
        'error_suppression' => [
            'mute_deprecation_error' => false,
            'noise_remaining_usages' => true,
        ],
        'is_null' => true,
        'mb_str_functions' => true,
        'modernize_strpos' => true,
        'no_alias_functions' => ['sets' => ['@all']],
        'no_empty_statement' => true,
        'no_mixed_echo_print' => ['use' => 'echo'],
        'no_unneeded_control_parentheses' => [
            'statements' => [
                'break',
                'clone',
                'continue',
                'echo_print',
                'negative_instanceof',
                'others',
                'return',
                'switch_case',
                'yield',
                'yield_from',
            ],
        ],
        'no_unset_cast' => true,
        'no_useless_return' => true,
        'ordered_types' => ['null_adjustment' => 'always_last'],
        'php_unit_construct' => true,
        'php_unit_dedicate_assert' => true,
        'php_unit_mock_short_will_return' => true,
        'php_unit_set_up_tear_down_visibility' => true,
        'php_unit_strict' => false, // Can be too strict for some tests
        'php_unit_test_case_static_method_calls' => ['call_type' => 'this'],
        'pow_to_exponentiation' => true,
        'random_api_migration' => true,
        'regular_callable_call' => true,
        'self_static_accessor' => true,
        'set_type_to_cast' => true,
        'simplified_null_return' => true,
        'switch_continue_to_break' => true,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');
