<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use App\Support\Slug;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('Pages');
    }

    public static function getModelLabel(): string
    {
        return __('page');
    }

    public static function getPluralModelLabel(): string
    {
        return __('pages');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('title_ka')
                    ->label(__('Title (Georgian)'))
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set, $context) => $context === 'create' && $set('slug', Slug::generate($state ?? ''))),

                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->helperText(__('URL identifier. About page should be "about", contact page should be "contact".')),

                Forms\Components\RichEditor::make('body_ka')
                    ->label(__('Body (Georgian)'))
                    ->toolbarButtons([
                        'bold', 'italic', 'underline', 'strike', 'link',
                        'h2', 'h3', 'bulletList', 'orderedList', 'blockquote',
                        'attachFiles', 'undo', 'redo',
                    ])
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('pages')
                    ->fileAttachmentsVisibility('public')
                    ->columnSpanFull(),
            ]),

            Forms\Components\Section::make(__('Settings'))->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\Toggle::make('is_published')
                        ->label(__('Published'))
                        ->default(true),

                    Forms\Components\TextInput::make('sort_order')
                        ->label(__('Sort order'))
                        ->numeric()
                        ->default(0),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('title_ka')->label(__('Title'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->fontFamily('mono')->searchable(),
                Tables\Columns\IconColumn::make('is_published')->label(__('Published'))->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->label(__('Updated at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
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
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
