<?php

namespace App\Filament\Widgets;

use App\Models\Property;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

/**
 * Doughnut chart of active listings per category. Admin-only.
 */
class ListingsByCategoryChart extends ChartWidget
{
    protected static ?string $heading = 'Active listings by category';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    protected function getData(): array
    {
        $rows = Property::query()
            ->where('properties.status', 'active')
            ->join('categories', 'categories.id', '=', 'properties.category_id')
            ->select('categories.name', DB::raw('count(*) as total'))
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->pluck('total', 'name');

        return [
            'datasets' => [[
                'label' => 'Listings',
                'data'  => $rows->values()->all(),
                // A spread of the brand gold plus neutral supporting tones.
                'backgroundColor' => ['#C9A227', '#8C6D1F', '#E0C766', '#6B7280', '#374151', '#A16207', '#D4A373'],
            ]],
            'labels' => $rows->keys()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
