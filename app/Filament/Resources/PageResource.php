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

                Forms\Components\Textarea::make('body_ka')
                    ->label(__('Body (Georgian) — supports plain text + line breaks'))
                    ->rows(10)
                    ->columnSpanFull(),
            ]),

            Forms\Components\Section::make(__('Contact details'))
                ->description(__('Only used by the contact page. Leave empty for regular content pages.'))
                ->collapsible()
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('contact_phone')
                            ->label(__('Phone'))
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('contact_email')
                            ->label(__('Email'))
                            ->email()
                            ->maxLength(255),
                    ]),

                    Forms\Components\Repeater::make('contact_locations')
                        ->label(__('Locations'))
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label(__('Branch name'))
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('address')
                                ->label(__('Address'))
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('phone')
                                ->label(__('Phone'))
                                ->tel()
                                ->maxLength(255),
                            Forms\Components\Textarea::make('embed_url')
                                ->label(__('Google Maps embed URL (src from the iframe)'))
                                ->rows(2)
                                ->helperText(__('On maps.google.com: Share → Embed a map → copy the src="..." value from the iframe.')),
                        ])
                        ->columns(2)
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
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
