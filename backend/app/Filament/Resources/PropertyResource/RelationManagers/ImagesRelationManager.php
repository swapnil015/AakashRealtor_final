<?php

namespace App\Filament\Resources\PropertyResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Manage a listing's gallery: upload, reorder (drag handle on sort_order), and
 * flag the primary image. Uploaded files land on the configured filesystem; the
 * stored `path` is what the API resolves to a URL.
 */
class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $recordTitleAttribute = 'path';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('path')
                ->label('Image')
                ->image()
                ->directory('properties')
                ->required(),
            Forms\Components\Toggle::make('is_primary')
                ->helperText('Only one image should be primary; it leads the gallery.'),
            Forms\Components\TextInput::make('sort_order')
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // Drag-to-reorder writes the sort_order column directly.
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('url')
                    ->label('Preview')
                    ->getStateUsing(fn ($record) => $record->url ?? $record->path),
                Tables\Columns\IconColumn::make('is_primary')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Quick "make primary" toggle — demote the rest in the process.
                Tables\Actions\Action::make('setPrimary')
                    ->label('Set primary')
                    ->icon('heroicon-o-star')
                    ->hidden(fn ($record): bool => (bool) $record->is_primary)
                    ->action(function ($record): void {
                        $record->property->images()->update(['is_primary' => false]);
                        $record->update(['is_primary' => true]);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
