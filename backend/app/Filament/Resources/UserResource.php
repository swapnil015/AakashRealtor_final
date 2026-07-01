<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

/**
 * People management: role, active state and branch assignment. Admin-only —
 * agents have no business editing accounts, so the whole resource is hidden
 * from them (canViewAny).
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'People';

    /** Only admins manage users (Gate::before lets admins through anyway). */
    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('phone')->tel()->unique(ignoreRecord: true),
            // Password is only set when filled — keeps the existing hash on edit.
            Forms\Components\TextInput::make('password')
                ->password()
                ->revealable()
                ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->required(fn (string $operation): bool => $operation === 'create'),
            Forms\Components\Select::make('role')
                ->options(['user' => 'User', 'agent' => 'Agent', 'admin' => 'Admin'])
                ->required()
                ->native(false),
            Forms\Components\Select::make('branch_id')
                ->label('Branch')
                ->relationship('branch', 'name')
                ->searchable()
                ->preload()
                ->helperText('Assign agents to a branch.'),
            Forms\Components\Toggle::make('is_active')
                ->default(true)
                ->helperText('Inactive users cannot log in or access the panel.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('phone')->toggleable()->placeholder('—'),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->colors(['danger' => 'admin', 'warning' => 'agent', 'gray' => 'user']),
                Tables\Columns\TextColumn::make('branch.name')->placeholder('—')->toggleable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                Tables\Columns\TextColumn::make('created_at')->dateTime('M j, Y')->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options(['user' => 'User', 'agent' => 'Agent', 'admin' => 'Admin']),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                // Fast activate/deactivate without opening the edit form.
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (User $r): string => $r->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (User $r): string => $r->is_active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn (User $r): string => $r->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (User $r) => $r->update(['is_active' => ! $r->is_active])),
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
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
