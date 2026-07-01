<?php

use App\Services\Tools\EmiCalculator;
use App\Services\Tools\LandUnitConverter;
use App\Services\Tools\NepaliDateConverter;

/* ── EMI calculator ────────────────────────────────────────────────── */

it('computes a known EMI correctly', function () {
    // 10,00,000 @ 10% for 12 months -> ~87,915.89/month.
    $r = EmiCalculator::calculate(1_000_000, 10, 12);

    expect($r['emi'])->toBeGreaterThan(87_900)->toBeLessThan(87_930)
        ->and($r['total_payable'])->toBeGreaterThan(1_000_000)
        ->and($r['schedule'])->toHaveCount(12);
});

it('amortizes to a zero balance on the final installment', function () {
    $r = EmiCalculator::calculate(500_000, 12, 24);
    $last = end($r['schedule']);

    expect($last['balance'])->toBe(0.0)->or->toBe(0);
});

it('handles a zero-interest loan', function () {
    $r = EmiCalculator::calculate(120000, 0, 12, false);
    expect($r['emi'])->toBe(10000.0)->and($r['total_interest'])->toBe(0.0);
});

/* ── Land unit converter ───────────────────────────────────────────── */

it('converts 1 ropani to 16 aana', function () {
    expect(round(LandUnitConverter::convert(1, 'ropani', 'aana')))->toBe(16.0);
});

it('converts 1 aana to 4 paisa', function () {
    expect(round(LandUnitConverter::convert(1, 'aana', 'paisa')))->toBe(4.0);
});

it('breaks an area into the ropani system', function () {
    $b = LandUnitConverter::breakdown(1, 'ropani');
    expect($b['ropani_system']['ropani'])->toBe(1)
        ->and($b['ropani_system']['aana'])->toBe(0);
});

/* ── BS ⇄ AD date converter ────────────────────────────────────────── */

it('round-trips a BS date through AD and back', function () {
    $ad = NepaliDateConverter::bsToAd(2080, 1, 1);     // 1 Baishakh 2080
    $bs = NepaliDateConverter::adToBs($ad['ad']);

    expect($bs['formatted'])->toBe('2080-01-01');
});

it('maps the BS anchor to its known AD date', function () {
    $r = NepaliDateConverter::bsToAd(2000, 1, 1);
    expect($r['ad'])->toBe('1943-04-14');
});
