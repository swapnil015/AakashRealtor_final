<?php

namespace App\Filament\Widgets;

use App\Models\Property;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

/**
 * Bar chart of active listings per city (top 10). Admin-only.
 */
class ListingsByCityChart extends ChartWidget
{
    protected static ?string $heading = 'Active listings by city';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    protected function getData(): array
    {
        // Group active listings by city, biggest first, capped at 10 bars.
        $rows = Property::query()
            ->where('properties.status', 'active')
            ->join('cities', 'cities.id', '=', 'properties.city_id')
            ->select('cities.name', DB::raw('count(*) as total'))
            ->groupBy('cities.name')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'name');

        return [
            'datasets' => [[
                'label'           => 'Listings',
                'data'            => $rows->values()->all(),
                'backgroundColor' => '#C9A227',
            ]],
            'labels' => $rows->keys()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
