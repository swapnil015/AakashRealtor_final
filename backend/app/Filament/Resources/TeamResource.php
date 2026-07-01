<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages;
use App\Models\Team;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Public-facing team members ("Our Team" page). `socials` is a JSON map of
 * platform => URL. Admin-only.
 */
class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Company';

    protected static ?string $modelLabel = 'team member';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('position'),
            Forms\Components\FileUpload::make('photo')
                ->image()
                ->directory('team'),
            Forms\Components\Select::make('branch_id')
                ->relationship('branch', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
            // socials is a JSON map; a key-value repeater keeps it editable.
            Forms\Components\KeyValue::make('socials')
                ->keyLabel('Platform')
                ->valueLabel('URL')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')->circular(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('position')->placeholder('—'),
                Tables\Columns\TextColumn::make('branch.name')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order')
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
            'index'  => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit'   => Pages\EditTeam::route('/{record}/edit'),
        ];
    }
}
