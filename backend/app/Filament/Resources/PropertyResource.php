<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyResource\Pages;
use App\Filament\Resources\PropertyResource\RelationManagers;
use App\Jobs\MatchRequirementsToProperty;
use App\Models\Property;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * The marketplace's core moderation surface.
 *
 * Approval flow: pending -> active. "Approve" calls Property::markActive()
 * (status=active + stamps published_at once) then dispatches
 * MatchRequirementsToProperty so buyers with matching open requirements are
 * notified off the request cycle — identical to the API approval path.
 *
 * Access: admins manage every listing; agents are scoped (see getEloquentQuery)
 * to listings they own or are assigned to, and cannot run moderation actions
 * (those are gated on auth()->user()->isAdmin()).
 */
class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Listings';

    protected static ?int $navigationSort = 1;

    /** Surface the pending-approval backlog as a navigation badge. */
    public static function getNavigationBadge(): ?string
    {
        // Agents don't moderate, so the badge only matters for admins.
        if (! auth()->user()?->isAdmin()) {
            return null;
        }

        $count = static::getModel()::where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identity')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    // Slug auto-fills from title on the API side; editable here
                    // for SEO tweaks. Left blank, the HasSlug trait fills it.
                    Forms\Components\TextInput::make('slug')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Leave blank to auto-generate from the title.')
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('description')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Ownership & Taxonomy')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Owner')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    // Agent assignment is admin-only — agents must not reassign
                    // listings away from themselves.
                    Forms\Components\Select::make('agent_id')
                        ->label('Assigned agent')
                        ->relationship('agent', 'name')
                        ->searchable()
                        ->preload()
                        ->disabled(fn (): bool => ! auth()->user()?->isAdmin()),
                    Forms\Components\Select::make('category_id')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('city_id')
                        ->relationship('city', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    // Areas belong to the chosen city; the dependent select
                    // filters by city_id and resets when the city changes.
                    Forms\Components\Select::make('area_id')
                        ->relationship(
                            'area',
                            'name',
                            fn (Builder $query, Forms\Get $get) => $query->where('city_id', $get('city_id'))
                        )
                        ->searchable()
                        ->preload(),
                ]),

            Forms\Components\Section::make('Deal')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('transaction_type')
                        ->options(['buy' => 'Buy', 'rent' => 'Rent'])
                        ->required(),
                    Forms\Components\TextInput::make('price')
                        ->numeric()
                        ->required()
                        ->default(0)
                        ->prefix('Rs.'),
                    Forms\Components\TextInput::make('price_unit')
                        ->datalist(['total', 'per month', 'per year'])
                        ->default('total'),
                    Forms\Components\Toggle::make('price_negotiable')
                        ->inline(false),
                ]),

            Forms\Components\Section::make('Size & Specs')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('area_size')->numeric(),
                    Forms\Components\TextInput::make('area_unit')
                        ->datalist(['aana', 'ropani', 'sqft', 'sqm'])
                        ->default('aana'),
                    Forms\Components\TextInput::make('road_width')
                        ->numeric()
                        ->suffix('ft'),
                    Forms\Components\TextInput::make('bedrooms')->numeric()->minValue(0),
                    Forms\Components\TextInput::make('bathrooms')->numeric()->minValue(0),
                    Forms\Components\TextInput::make('floors')->numeric()->minValue(0),
                    Forms\Components\TextInput::make('parking')->numeric()->minValue(0),
                    Forms\Components\TextInput::make('facing')
                        ->datalist(['East', 'West', 'North', 'South', 'North-East', 'North-West', 'South-East', 'South-West']),
                ]),

            Forms\Components\Section::make('Location')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('address')->columnSpan(3),
                    Forms\Components\TextInput::make('latitude')->numeric(),
                    Forms\Components\TextInput::make('longitude')->numeric(),
                ]),

            Forms\Components\Section::make('Moderation')
                ->columns(2)
                ->schema([
                    // Status is read-mostly here; the lifecycle is driven by the
                    // table actions (Approve/Reject/Mark Sold/Rented) so the
                    // side effects (published_at, matcher) always run.
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending'  => 'Pending',
                            'active'   => 'Active',
                            'sold'     => 'Sold',
                            'rented'   => 'Rented',
                            'rejected' => 'Rejected',
                        ])
                        ->required()
                        ->disabled(fn (): bool => ! auth()->user()?->isAdmin())
                        ->helperText('Prefer the row actions — they stamp published_at and run the matcher.'),
                    Forms\Components\TextInput::make('rejection_reason')
                        ->maxLength(255)
                        ->visible(fn (Forms\Get $get): bool => $get('status') === 'rejected'),
                ]),

            Forms\Components\Section::make('Homepage Placement')
                ->description('Boolean flags that surface a listing in the homepage sections.')
                ->columns(3)
                ->schema([
                    Forms\Components\Toggle::make('is_featured'),
                    Forms\Components\Toggle::make('is_exclusive'),
                    Forms\Components\Toggle::make('is_emerging'),
                    Forms\Components\Toggle::make('is_open_house')->live(),
                    Forms\Components\Toggle::make('is_by_owner'),
                    Forms\Components\DatePicker::make('open_house_date')
                        ->visible(fn (Forms\Get $get): bool => (bool) $get('is_open_house')),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('primaryImage.url')
                    ->label('')
                    ->circular(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(40)
                    ->description(fn (Property $r): ?string => $r->city?->name)
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_type')
                    ->badge()
                    ->colors(['info' => 'buy', 'gray' => 'rent']),
                Tables\Columns\TextColumn::make('category.name')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('NPR')
                    ->sortable(),
                // Status badge mirrors the lifecycle colour scheme used app-wide.
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'active',
                        'info'    => 'sold',
                        'primary' => 'rented',
                        'danger'  => 'rejected',
                    ]),
                Tables\Columns\IconColumn::make('is_featured')->boolean()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_exclusive')->boolean()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('agent.name')
                    ->label('Agent')
                    ->toggleable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'  => 'Pending',
                        'active'   => 'Active',
                        'sold'     => 'Sold',
                        'rented'   => 'Rented',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('city')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->options(['buy' => 'Buy', 'rent' => 'Rent']),
                // One toggle filter per homepage flag.
                Tables\Filters\TernaryFilter::make('is_featured'),
                Tables\Filters\TernaryFilter::make('is_exclusive'),
                Tables\Filters\TernaryFilter::make('is_emerging'),
                Tables\Filters\TernaryFilter::make('is_open_house'),
                Tables\Filters\TernaryFilter::make('is_by_owner'),
            ])
            ->actions([
                // ── Approve: pending -> active + matcher ──────────────────
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Property $r): bool => auth()->user()?->isAdmin() && $r->status === 'pending')
                    ->action(fn (Property $r) => static::approve($r)),

                // ── Reject: capture a reason ──────────────────────────────
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Property $r): bool => auth()->user()?->isAdmin() && in_array($r->status, ['pending', 'active'], true))
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Reason')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (Property $r, array $data): void {
                        $r->update(['status' => 'rejected', 'rejection_reason' => $data['rejection_reason']]);
                        Notification::make()->title('Listing rejected')->warning()->send();
                    }),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('markSold')
                        ->label('Mark Sold')
                        ->icon('heroicon-o-banknotes')
                        ->visible(fn (Property $r): bool => auth()->user()?->isAdmin() && $r->status === 'active')
                        ->action(fn (Property $r) => $r->update(['status' => 'sold'])),
                    Tables\Actions\Action::make('markRented')
                        ->label('Mark Rented')
                        ->icon('heroicon-o-key')
                        ->visible(fn (Property $r): bool => auth()->user()?->isAdmin() && $r->status === 'active')
                        ->action(fn (Property $r) => $r->update(['status' => 'rented'])),
                ])->label('Status')->visible(fn (): bool => (bool) auth()->user()?->isAdmin()),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk approve runs the same markActive + matcher per row.
                    Tables\Actions\BulkAction::make('approve')
                        ->label('Approve selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (): bool => (bool) auth()->user()?->isAdmin())
                        ->action(function (Collection $records): void {
                            $records->each(fn (Property $r) => $r->status === 'pending' ? static::approve($r, notify: false) : null);
                            Notification::make()->title('Approved selected listings')->success()->send();
                        }),

                    // Bulk flag toggles — one set/unset action per homepage flag.
                    ...static::flagBulkActions(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Approve a single listing: publish it and fan out matches. Shared by the
     * row action and bulk action so the side effects never drift apart.
     */
    protected static function approve(Property $property, bool $notify = true): void
    {
        $property->markActive();                              // status=active + published_at
        MatchRequirementsToProperty::dispatch($property->id); // notify matching buyers

        if ($notify) {
            Notification::make()->title('Listing approved & published')->success()->send();
        }
    }

    /**
     * Build a set/unset bulk action for each homepage flag from Property::FLAGS,
     * so adding a flag to the model automatically exposes its bulk toggles.
     *
     * @return array<int, Tables\Actions\BulkAction>
     */
    protected static function flagBulkActions(): array
    {
        $actions = [];

        foreach (Property::FLAGS as $flag) {
            $label = ucwords(str_replace(['is_', '_'], ['', ' '], $flag));

            foreach ([true => 'Set', false => 'Unset'] as $value => $verb) {
                $actions[] = Tables\Actions\BulkAction::make("{$verb}_{$flag}")
                    ->label("{$verb} {$label}")
                    ->icon($value ? 'heroicon-o-star' : 'heroicon-o-x-mark')
                    ->visible(fn (): bool => (bool) auth()->user()?->isAdmin())
                    ->deselectRecordsAfterCompletion()
                    ->action(fn (Collection $records) => $records->each->update([$flag => $value]));
            }
        }

        return $actions;
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ImagesRelationManager::class,
            RelationManagers\AmenitiesRelationManager::class,
        ];
    }

    /**
     * Agent scoping: agents only see listings they own or are assigned to.
     * Admins bypass (Gate::before) and see the full table. Eager-load the
     * relations the table reads to avoid N+1 queries.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['city', 'category', 'agent', 'primaryImage']);

        $user = auth()->user();

        if ($user && ! $user->isAdmin()) {
            $query->where(fn (Builder $q) => $q
                ->where('user_id', $user->id)
                ->orWhere('agent_id', $user->id));
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit'   => Pages\EditProperty::route('/{record}/edit'),
        ];
    }
}
