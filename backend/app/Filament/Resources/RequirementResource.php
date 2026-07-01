<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequirementResource\Pages;
use App\Models\Requirement;
use App\Support\CsvExporter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Buyer "didn't find a property?" requests. The matcher job pairs new active
 * listings against open requirements; admins work the queue here, flipping a
 * requirement to "fulfilled" once handled, and can export the list as CSV.
 *
 * Admin-only — requirements are a global demand signal, not agent-scoped.
 */
class RequirementResource extends Resource
{
    protected static ?string $model = Requirement::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Leads';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->disabled(),
            Forms\Components\TextInput::make('phone')->disabled(),
            Forms\Components\TextInput::make('email')->disabled(),
            Forms\Components\Select::make('category_id')->relationship('category', 'name')->disabled(),
            Forms\Components\Select::make('city_id')->relationship('city', 'name')->disabled(),
            Forms\Components\TextInput::make('transaction_type')->disabled(),
            Forms\Components\TextInput::make('min_budget')->disabled(),
            Forms\Components\TextInput::make('max_budget')->disabled(),
            Forms\Components\Textarea::make('message')->disabled()->columnSpanFull(),
            Forms\Components\Select::make('status')
                ->options(['open' => 'Open', 'fulfilled' => 'Fulfilled'])
                ->required()
                ->native(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('phone')->copyable(),
                Tables\Columns\TextColumn::make('transaction_type')->badge(),
                Tables\Columns\TextColumn::make('category.name')->placeholder('—'),
                Tables\Columns\TextColumn::make('city.name')->placeholder('—'),
                Tables\Columns\TextColumn::make('min_budget')->money('NPR')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('max_budget')->money('NPR')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors(['warning' => 'open', 'success' => 'fulfilled']),
                Tables\Columns\TextColumn::make('created_at')->dateTime('M j, Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['open' => 'Open', 'fulfilled' => 'Fulfilled']),
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->options(['buy' => 'Buy', 'rent' => 'Rent']),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (): StreamedResponse => CsvExporter::stream(
                        'requirements',
                        ['Name', 'Phone', 'Email', 'Type', 'Category', 'City', 'Min', 'Max', 'Status', 'Created'],
                        static::getEloquentQuery()->with(['category', 'city'])->latest()->cursor(),
                        fn (Requirement $r): array => [
                            $r->name, $r->phone, $r->email, $r->transaction_type,
                            $r->category?->name, $r->city?->name, $r->min_budget, $r->max_budget,
                            $r->status, $r->created_at?->toDateTimeString(),
                        ],
                    )),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequirements::route('/'),
            'edit'  => Pages\EditRequirement::route('/{record}/edit'),
        ];
    }
}
