<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InquiryResource\Pages;
use App\Models\PropertyInquiry;
use App\Support\CsvExporter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Buyer enquiries against a specific listing. Read + status workflow
 * (new -> contacted -> closed) plus a CSV export for offline follow-up.
 *
 * Agent scoping: agents only see enquiries on listings they own or manage.
 */
class InquiryResource extends Resource
{
    protected static ?string $model = PropertyInquiry::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationGroup = 'Leads';

    protected static ?string $modelLabel = 'inquiry';

    protected static ?string $pluralModelLabel = 'inquiries';

    /** Badge the count of unhandled (new) enquiries. */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', 'new')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Enquiries are created by the public API; admins only adjust status.
            Forms\Components\Select::make('property_id')
                ->relationship('property', 'title')
                ->searchable()
                ->disabled(),
            Forms\Components\TextInput::make('name')->disabled(),
            Forms\Components\TextInput::make('phone')->disabled(),
            Forms\Components\TextInput::make('email')->disabled(),
            Forms\Components\Textarea::make('message')->disabled()->columnSpanFull(),
            Forms\Components\Select::make('status')
                ->options(['new' => 'New', 'contacted' => 'Contacted', 'closed' => 'Closed'])
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
                Tables\Columns\TextColumn::make('email')->copyable()->toggleable()->placeholder('—'),
                Tables\Columns\TextColumn::make('property.title')->limit(30)->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors(['warning' => 'new', 'info' => 'contacted', 'success' => 'closed']),
                Tables\Columns\TextColumn::make('created_at')->dateTime('M j, Y g:ia')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['new' => 'New', 'contacted' => 'Contacted', 'closed' => 'Closed']),
            ])
            ->headerActions([
                // Stream the *currently filtered* set as a CSV download.
                Tables\Actions\Action::make('export')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (): StreamedResponse => CsvExporter::stream(
                        'inquiries',
                        ['Name', 'Phone', 'Email', 'Property', 'Status', 'Received'],
                        static::getEloquentQuery()->with('property')->latest()->cursor(),
                        fn (PropertyInquiry $r): array => [
                            $r->name, $r->phone, $r->email, $r->property?->title, $r->status,
                            $r->created_at?->toDateTimeString(),
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

    /** Agents only see enquiries on properties they own or manage. */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && ! $user->isAdmin()) {
            $query->whereHas('property', fn (Builder $q) => $q
                ->where('user_id', $user->id)
                ->orWhere('agent_id', $user->id));
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInquiries::route('/'),
            'edit'  => Pages\EditInquiry::route('/{record}/edit'),
        ];
    }
}
