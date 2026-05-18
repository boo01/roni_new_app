<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class GroupPricesRelationManager extends RelationManager
{
    protected static string $relationship = 'groupPrices';

    protected static ?string $title = 'Per-group price overrides';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('customer_group_id')
                ->label('Customer group')
                ->relationship('customerGroup', 'name')
                ->required()
                ->searchable()
                ->preload(),

            Forms\Components\TextInput::make('price')
                ->label('Price (GEL)')
                ->required()
                ->numeric()
                ->minValue(0)
                ->prefix('₾')
                ->helperText('Replaces the group\'s percent discount for this product only.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('customerGroup.name')
                    ->label('Group'),

                Tables\Columns\TextColumn::make('price')
                    ->money('GEL'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('Y-m-d H:i')
                    ->label('Updated'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
