<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttributeResource\Pages;
use App\Filament\Resources\AttributeResource\RelationManagers\ValuesRelationManager;
use App\Models\Attribute;
use App\Support\Slug;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AttributeResource extends Resource
{
    protected static ?string $model = Attribute::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('Filters');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Catalog');
    }

    public static function getModelLabel(): string
    {
        return __('attribute');
    }

    public static function getPluralModelLabel(): string
    {
        return __('attributes');
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
                    ->helperText(__('Used in storefront filter URLs (e.g. ?attr[color]=red).')),

                Forms\Components\TextInput::make('sort_order')
                    ->label(__('Sort order'))
                    ->numeric()
                    ->default(0),

                Forms\Components\Fieldset::make(__('Behaviour'))->schema([
                    Forms\Components\Toggle::make('is_filterable')
                        ->label(__('Show as filter on storefront'))
                        ->helperText(__('Appears in the category sidebar so shoppers can narrow results.'))
                        ->default(true),

                    Forms\Components\Toggle::make('is_selectable')
                        ->label(__('Customer chooses on the product page'))
                        ->helperText(__('Shows a picker (e.g. Color) on each product that has values for this attribute. The choice is recorded on the order.'))
                        ->live()
                        ->default(false),

                    Forms\Components\Toggle::make('is_required')
                        ->label(__('Choice is required'))
                        ->helperText(__('Shopper must pick a value before adding to the cart.'))
                        ->default(false)
                        ->visible(fn (Forms\Get $get) => (bool) $get('is_selectable')),
                ])->columns(1),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name_ka')->label(__('Name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->searchable(),
                Tables\Columns\TextColumn::make('values_count')->label(__('Values'))->counts('values'),
                Tables\Columns\TextColumn::make('sort_order')->label(__('Sort order')),
                Tables\Columns\IconColumn::make('is_filterable')->label(__('Filter'))->boolean(),
                Tables\Columns\IconColumn::make('is_selectable')->label(__('Selectable'))->boolean(),
                Tables\Columns\IconColumn::make('is_required')->label(__('Required'))->boolean(),
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
            ValuesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttributes::route('/'),
            'create' => Pages\CreateAttribute::route('/create'),
            'edit' => Pages\EditAttribute::route('/{record}/edit'),
        ];
    }
}
