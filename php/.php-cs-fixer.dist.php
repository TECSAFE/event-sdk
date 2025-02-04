<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'var',
        'vendor',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS:risky' => true,
        '@Symfony:risky' => true,
        'yoda_style' => false,
        'array_indentation' => true, // Richtige Einrückung für Arrays
        'trailing_comma_in_multiline' => ['elements' => ['arrays']], // Wandelt Single-Line-Arrays in Multi-Line um
        'multiline_whitespace_before_semicolons' => ['strategy' => 'new_line_for_chained_calls'], // Zeilenumbrüche vor Semikolons
    ])
    ->setFinder($finder)
    ->setUsingCache(false)
;
