<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogResource\Pages;
use App\Models\Blog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Blog posts. Rich-text body, cover image upload, and a published_at timestamp
 * that gates public visibility (Blog::scopePublished). Both admins and agents
 * may write posts; agents see only their own (getEloquentQuery).
 */
class BlogResource extends Resource
{
    protected static ?string $model = Blog::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = 'Content';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('slug')
                ->unique(ignoreRecord: true)
                ->helperText('Leave blank to auto-generate.')
                ->columnSpanFull(),
            Forms\Components\Textarea::make('excerpt')
                ->rows(2)
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\FileUpload::make('cover_image')
                ->image()
                ->directory('blog')
                ->columnSpanFull(),
            // Rich text editor for the post body.
            Forms\Components\RichEditor::make('body')
                ->columnSpanFull(),
            Forms\Components\Select::make('user_id')
                ->label('Author')
                ->relationship('author', 'name')
                ->searchable()
                ->preload()
                ->default(fn () => auth()->id()),
            // Null = draft; a past timestamp = live. Defaults to now for convenience.
            Forms\Components\DateTimePicker::make('published_at')
                ->helperText('Leave blank to keep as a draft.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')->label('Cover'),
                Tables\Columns\TextColumn::make('title')->searchable()->limit(40)->sortable(),
                Tables\Columns\TextColumn::make('author.name')->placeholder('—')->toggleable(),
                Tables\Columns\IconColumn::make('published_at')
                    ->label('Published')
                    ->boolean()
                    ->getStateUsing(fn (Blog $r): bool => $r->published_at !== null && $r->published_at->isPast()),
                Tables\Columns\TextColumn::make('published_at')->dateTime('M j, Y')->placeholder('Draft')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** Agents only manage their own posts; admins see all. */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && ! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBlogs::route('/'),
            'create' => Pages\CreateBlog::route('/create'),
            'edit'   => Pages\EditBlog::route('/{record}/edit'),
        ];
    }
}
