<?php

namespace App\Filament\Widgets;

use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Top-of-dashboard KPIs: active listings, the pending-approval backlog,
 * this week's new enquiries, and total users. Admin-only — the numbers are
 * marketplace-wide, not agent-scoped.
 */
class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Active listings', Property::where('status', 'active')->count())
                ->description('Live on the marketplace')
                ->color('success')
                ->icon('heroicon-o-building-office-2'),

            Stat::make('Pending approvals', Property::where('status', 'pending')->count())
                ->description('Awaiting moderation')
                ->color('warning')
                ->icon('heroicon-o-clock'),

            Stat::make('New inquiries (7d)', PropertyInquiry::where('created_at', '>=', now()->subWeek())->count())
                ->description('In the last 7 days')
                ->color('info')
                ->icon('heroicon-o-inbox-arrow-down'),

            Stat::make('Total users', User::count())
                ->description('Registered accounts')
                ->color('gray')
                ->icon('heroicon-o-users'),
        ];
    }
}
