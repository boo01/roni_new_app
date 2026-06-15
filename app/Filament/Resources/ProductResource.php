<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\GroupPricesRelationManager;
use App\Models\Product;
use App\Support\Slug;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('Products');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Catalog');
    }

    public static function getModelLabel(): string
    {
        return __('product');
    }

    public static function getPluralModelLabel(): string
    {
        return __('products');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('Basics'))->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('sku')
                            ->label(__('SKU / Product code'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set, $get, $context) => $context === 'create' && ! $get('slug') && $set('slug', Slug::generate((string) $state))),

                        Forms\Components\Select::make('categories')
                            ->label(__('Categories'))
                            ->relationship('categories', 'name_ka')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText(__('Assign one or more categories; the first becomes the primary (used for breadcrumb).')),
                    ]),

                    Forms\Components\TextInput::make('name_ka')
                        ->label(__('Name (Georgian)'))
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText(__('URL identifier. Auto-derived from SKU; edit if needed.')),

                    Forms\Components\Textarea::make('description_ka')
                        ->label(__('Description (Georgian)'))
                        ->rows(4),
                ]),

                Forms\Components\Section::make(__('Filter attributes'))->schema([
                    Forms\Components\Select::make('attributeValues')
                        ->label(__('Values (color, size, brand, etc.)'))
                        ->relationship('attributeValues', 'value_ka')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->attribute->name_ka}: {$record->value_ka}")
                        ->helperText(__('Manage attribute definitions and their values from Catalog → Filters.')),
                ])->collapsed(fn ($context) => $context === 'create'),

                Forms\Components\Section::make(__('SEO'))
                    ->description(__('Leave empty to auto-generate. The share image is the first product photo.'))
                    ->collapsed()
                    ->schema([
                        Forms\Components\Placeholder::make('seo_hint')
                            ->hiddenLabel()
                            ->content(__('Leave these empty and the tags below are generated automatically from the product. The grey text shows what will be used.')),
                        Forms\Components\TextInput::make('meta_title')
                            ->label(__('Meta title'))
                            ->maxLength(255)
                            ->placeholder(fn (?Product $record) => $record?->seoMeta()['title'])
                            ->helperText(__('Defaults to the product name.')),
                        Forms\Components\Textarea::make('meta_description')
                            ->label(__('Meta description'))
                            ->rows(2)
                            ->maxLength(255)
                            ->placeholder(fn (?Product $record) => $record?->seoMeta()['description'])
                            ->helperText(__('Defaults to the start of the description.')),
                        Forms\Components\TextInput::make('meta_keywords')
                            ->label(__('Meta keywords'))
                            ->maxLength(255)
                            ->placeholder(fn (?Product $record) => $record?->seoMeta()['keywords'])
                            ->helperText(__('Comma-separated. Defaults to the name + categories.')),
                    ]),

                Forms\Components\Section::make(__('Images'))->schema([
                    Forms\Components\SpatieMediaLibraryFileUpload::make('images')
                        ->collection('images')
                        ->multiple()
                        ->reorderable()
                        ->image()
                        ->imageEditor()
                        ->maxFiles(8)
                        ->maxSize(20480)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                        ->panelLayout('grid')
                        ->helperText(__('JPG, PNG, WEBP. One file up to 20MB. Images are optimised automatically.'))
                        ->columnSpanFull(),
                ]),
            ])->columnSpan(2),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('Pricing'))->schema([
                    Forms\Components\TextInput::make('retail_price')
                        ->label(__('Retail price (GEL)'))
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₾'),
                ]),

                Forms\Components\Section::make(__('Stock'))->schema([
                    Forms\Components\Toggle::make('track_stock')
                        ->label(__('Track stock'))
                        ->default(false),

                    Forms\Components\TextInput::make('stock_quantity')
                        ->label(__('Stock quantity'))
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                ]),

                Forms\Components\Section::make(__('Visibility'))->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label(__('Active'))
                        ->default(true),

                    Forms\Components\Toggle::make('visible_to_retail')
                        ->label(__('Visible to retail'))
                        ->default(true),

                    Forms\Components\Toggle::make('visible_to_b2b')
                        ->label(__('Visible to B2B'))
                        ->default(true),

                    Forms\Components\TextInput::make('sort_order')
                        ->label(__('Sort order'))
                        ->numeric()
                        ->default(0),
                ]),
            ])->columnSpan(1),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('image')
                    ->collection('images')
                    ->conversion('thumb')
                    ->limit(1)
                    ->label('')
                    ->size(48)
                    ->square(),

                Tables\Columns\TextColumn::make('name_ka')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('sku')
                    ->label(__('SKU'))
                    ->searchable()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('categories.name_ka')
                    ->label(__('Categories'))
                    ->badge()
                    ->limit(20)
                    ->listWithLineBreaks(),

                Tables\Columns\TextColumn::make('retail_price')
                    ->label(__('Retail'))
                    ->money('GEL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label(__('Stock'))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),

                Tables\Columns\IconColumn::make('visible_to_retail')
                    ->label(__('Retail'))
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('visible_to_b2b')
                    ->label(__('B2B'))
                    ->boolean()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('categories')
                    ->relationship('categories', 'name_ka')
                    ->label(__('Category'))
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Active')),
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

    public static function getRelations(): array
    {
        return [
            GroupPricesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
