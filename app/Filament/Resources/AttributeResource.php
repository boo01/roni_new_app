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

    protected static ?string $navigationLabel = 'Filters';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('name_ka')
                    ->label('Name (Georgian)')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set, $context) => $context === 'create' && $set('slug', Slug::generate($state ?? ''))),

                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Used in storefront filter URLs (e.g. ?attr[color]=red).'),

                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0),

                    Forms\Components\Toggle::make('is_filterable')
                        ->label('Show as filter on storefront')
                        ->default(true),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name_ka')->label('Name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->searchable(),
                Tables\Columns\TextColumn::make('values_count')->label('Values')->counts('values'),
                Tables\Columns\TextColumn::make('sort_order')->label('Order'),
                Tables\Columns\IconColumn::make('is_filterable')->label('Filter')->boolean(),
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
