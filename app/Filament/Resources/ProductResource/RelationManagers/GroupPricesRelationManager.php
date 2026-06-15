<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class GroupPricesRelationManager extends RelationManager
{
    protected static string $relationship = 'groupPrices';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Per-group price overrides');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('customer_group_id')
                ->label(__('Customer group'))
                ->relationship('customerGroup', 'name')
                ->required()
                ->searchable()
                ->preload(),

            Forms\Components\TextInput::make('price')
                ->label(__('Price (GEL)'))
                ->required()
                ->numeric()
                ->minValue(0)
                ->prefix('₾')
                ->helperText(__("Replaces the group's percent discount for this product only.")),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modelLabel(__('price override'))
            ->pluralModelLabel(__('price overrides'))
            ->columns([
                Tables\Columns\TextColumn::make('customerGroup.name')
                    ->label(__('Group')),

                Tables\Columns\TextColumn::make('price')
                    ->label(__('Price'))
                    ->money('GEL'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('Y-m-d H:i')
                    ->label(__('Updated')),
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
