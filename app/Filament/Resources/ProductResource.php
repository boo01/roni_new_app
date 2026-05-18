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

    protected static ?string $navigationLabel = 'Products';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Basics')->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU / Product code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set, $get, $context) => $context === 'create' && ! $get('slug') && $set('slug', Slug::generate((string) $state))),

                        Forms\Components\Select::make('categories')
                            ->label('Categories')
                            ->relationship('categories', 'name_ka')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Assign one or more categories; the first becomes the primary (used for breadcrumb).'),
                    ]),

                    Forms\Components\TextInput::make('name_ka')
                        ->label('Name (Georgian)')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('URL identifier. Auto-derived from SKU; edit if needed.'),

                    Forms\Components\Textarea::make('description_ka')
                        ->label('Description (Georgian)')
                        ->rows(4),
                ]),

                Forms\Components\Section::make('Images')->schema([
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
                        ->helperText('JPG, PNG, WEBP. ერთი ფაილი 20MB-მდე. ფოტოები ოპტიმიზდება ავტომატურად.')
                        ->columnSpanFull(),
                ]),
            ])->columnSpan(2),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Pricing')->schema([
                    Forms\Components\TextInput::make('retail_price')
                        ->label('Retail price (GEL)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₾'),
                ]),

                Forms\Components\Section::make('Stock')->schema([
                    Forms\Components\Toggle::make('track_stock')
                        ->default(false),

                    Forms\Components\TextInput::make('stock_quantity')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                ]),

                Forms\Components\Section::make('Visibility')->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->default(true),

                    Forms\Components\TextInput::make('sort_order')
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
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('categories.name_ka')
                    ->label('Categories')
                    ->badge()
                    ->limit(20)
                    ->listWithLineBreaks(),

                Tables\Columns\TextColumn::make('retail_price')
                    ->label('Retail')
                    ->money('GEL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('categories')
                    ->relationship('categories', 'name_ka')
                    ->label('Category')
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active'),
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
