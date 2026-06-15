<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use App\Support\Slug;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('Categories');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Catalog');
    }

    public static function getModelLabel(): string
    {
        return __('category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('categories');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('name_ka')
                    ->label(__('Name (Georgian)'))
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set, $context) => $context === 'create' && $set('slug', Slug::generate($state ?? ''))),

                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText(__('URL identifier. Auto-generated from name; edit if needed.')),

                Forms\Components\Select::make('parent_id')
                    ->label(__('Parent category'))
                    ->relationship('parent', 'name_ka', fn ($query, $record) => $record ? $query->where('id', '!=', $record->id) : $query)
                    ->searchable()
                    ->preload()
                    ->placeholder(__('(top-level)')),

                Forms\Components\Textarea::make('description_ka')
                    ->label(__('Description (Georgian)'))
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('sort_order')
                        ->label(__('Sort order'))
                        ->numeric()
                        ->default(0),

                    Forms\Components\Toggle::make('is_active')
                        ->label(__('Active'))
                        ->default(true),
                ]),
            ]),

            Forms\Components\Section::make(__('Visibility'))->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\Toggle::make('visible_to_retail')
                        ->label(__('Visible to retail customers'))
                        ->default(true)
                        ->helperText(__('Hide from guests + B2C; products inside also become unreachable for them.')),
                    Forms\Components\Toggle::make('visible_to_b2b')
                        ->label(__('Visible to B2B customers'))
                        ->default(true)
                        ->helperText(__('Hide from logged-in B2B; products inside also become unreachable for them.')),
                ]),
            ]),

            Forms\Components\Section::make(__('Header navigation'))->schema([
                Forms\Components\Toggle::make('show_in_header')
                    ->label(__('Show in header menu'))
                    ->default(false)
                    ->helperText(__('Only categories with this on appear in the top nav. Others are still browsable via search and direct URL.')),
                Forms\Components\TextInput::make('header_sort_order')
                    ->label(__('Header order'))
                    ->numeric()
                    ->default(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name_ka')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('parent.name_ka')
                    ->label(__('Parent'))
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('products_count')
                    ->label(__('Products'))
                    ->counts('products')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('Sort order'))
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),

                Tables\Columns\IconColumn::make('show_in_header')
                    ->label(__('Header'))
                    ->boolean()
                    ->toggleable(),

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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
