<?php

namespace App\Services\Tools;

/**
 * Nepali land-unit converter ("Naptol").
 *
 * Two traditional systems plus metric. Everything is converted through a
 * square-metre base using exact constants.
 *
 * Ropani system (hills / Kathmandu valley):
 *   1 ropani = 16 aana = 64 paisa = 256 daam = 508.72 m²
 *   1 aana   = 4 paisa = 16 daam  = 31.795 m²
 *
 * Bigha system (Terai):
 *   1 bigha  = 20 kattha = 6772.63 m²
 *   1 kattha = 20 dhur   = 338.63 m²
 *   1 dhur   = 16.93 m²
 *
 * Metric / imperial:
 *   1 m²     = 10.7639 sq ft
 */
class LandUnitConverter
{
    /** Square metres per 1 unit. */
    public const TO_SQM = [
        // Ropani system
        'ropani' => 508.7376,
        'aana'   => 31.79610,
        'paisa'  => 7.949025,
        'daam'   => 1.987256,
        // Bigha system
        'bigha'  => 6772.6308,
        'kattha' => 338.63154,
        'dhur'   => 16.931577,
        // Metric / imperial
        'sqm'    => 1.0,
        'sqft'   => 0.09290304,
    ];

    public static function units(): array
    {
        return array_keys(self::TO_SQM);
    }

    /**
     * Convert a value from one unit to another.
     */
    public static function convert(float $value, string $from, string $to): float
    {
        $from = strtolower(trim($from));
        $to = strtolower(trim($to));

        if (! isset(self::TO_SQM[$from], self::TO_SQM[$to])) {
            throw new \InvalidArgumentException("Unknown land unit: {$from} or {$to}.");
        }

        $sqm = $value * self::TO_SQM[$from];

        return round($sqm / self::TO_SQM[$to], 6);
    }

    /**
     * Break an area down into the full ropani-system breakdown
     * (ropani / aana / paisa / daam), plus metric equivalents — exactly the
     * card the reference site shows.
     */
    public static function breakdown(float $value, string $from): array
    {
        $sqm = $value * (self::TO_SQM[strtolower($from)]
            ?? throw new \InvalidArgumentException("Unknown unit: {$from}"));

        $remaining = $sqm;
        $parts = [];
        foreach (['ropani', 'aana', 'paisa', 'daam'] as $unit) {
            $whole = (int) floor($remaining / self::TO_SQM[$unit]);
            $parts[$unit] = $whole;
            $remaining -= $whole * self::TO_SQM[$unit];
        }

        return [
            'input'        => ['value' => $value, 'unit' => strtolower($from)],
            'ropani_system'=> $parts, // e.g. {ropani:2, aana:5, paisa:1, daam:0}
            'sqm'          => round($sqm, 4),
            'sqft'         => round($sqm / self::TO_SQM['sqft'], 4),
        ];
    }
}
