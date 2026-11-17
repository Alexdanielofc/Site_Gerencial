<?php

use App\Support\Money;

// ── parseToDecimal ──────────────────────────────────────────────────────────

it('parses Brazilian format with thousands separator', function () {
    expect(Money::parseToDecimal('1.234,56'))->toBe('1234.56');
});

it('parses Brazilian format without thousands separator', function () {
    expect(Money::parseToDecimal('1234,56'))->toBe('1234.56');
});

it('parses decimal with dot separator', function () {
    expect(Money::parseToDecimal('1234.56'))->toBe('1234.56');
});

it('parses decimal with single digit after dot', function () {
    expect(Money::parseToDecimal('1234.5'))->toBe('1234.5');
});

it('parses plain integer string', function () {
    expect(Money::parseToDecimal('1234'))->toBe('1234');
});

it('parses thousands-only dot separators', function () {
    expect(Money::parseToDecimal('1.234'))->toBe('1234');
});

it('parses with R$ prefix', function () {
    expect(Money::parseToDecimal('R$ 1.234,56'))->toBe('1234.56');
});

it('parses zero', function () {
    expect(Money::parseToDecimal('0'))->toBe('0');
    expect(Money::parseToDecimal('0,00'))->toBe('0.00');
    expect(Money::parseToDecimal('0.00'))->toBe('0.00');
});

it('returns null for empty string', function () {
    expect(Money::parseToDecimal(''))->toBeNull();
    expect(Money::parseToDecimal('   '))->toBeNull();
});

it('returns null for null value', function () {
    expect(Money::parseToDecimal(null))->toBeNull();
});

it('returns null for negative values', function () {
    expect(Money::parseToDecimal('-100'))->toBeNull();
    expect(Money::parseToDecimal('-1,00'))->toBeNull();
});

it('returns null for text', function () {
    expect(Money::parseToDecimal('abc'))->toBeNull();
    expect(Money::parseToDecimal('12.34.56'))->toBeNull();
});

// ── format ──────────────────────────────────────────────────────────────────

it('formats cents to Brazilian Real', function () {
    expect(Money::format(123456))->toBe('R$ 1.234,56');
    expect(Money::format(100))->toBe('R$ 1,00');
    expect(Money::format(0))->toBe('R$ 0,00');
});

it('formats null as dash', function () {
    expect(Money::format(null))->toBe('—');
});

// ── percentOf ───────────────────────────────────────────────────────────────

it('calculates percentage correctly', function () {
    expect(Money::percentOf(5000, 10000))->toBe(50.0);
    expect(Money::percentOf(10000, 10000))->toBe(100.0);
    expect(Money::percentOf(0, 10000))->toBe(0.0);
});

it('returns null when total is zero', function () {
    expect(Money::percentOf(5000, 0))->toBeNull();
    expect(Money::percentOf(0, 0))->toBeNull();
});

it('returns null when total is null', function () {
    expect(Money::percentOf(5000, null))->toBeNull();
});

// ── formatPercent ───────────────────────────────────────────────────────────

it('formats percent with one decimal place and comma', function () {
    expect(Money::formatPercent(50.0))->toBe('50,0%');
    expect(Money::formatPercent(100.0))->toBe('100,0%');
    expect(Money::formatPercent(33.333))->toBe('33,3%');
});

it('formats null percent as dash', function () {
    expect(Money::formatPercent(null))->toBe('—');
});
