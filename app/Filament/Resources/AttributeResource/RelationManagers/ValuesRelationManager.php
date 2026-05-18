<?php

namespace App\Filament\Resources\AttributeResource\RelationManagers;

use App\Support\Slug;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'values';

    protected static ?string $title = 'Values';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('value_ka')
                ->label('Value (Georgian)')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set, $context) => $context === 'create' && $set('slug', Slug::generate($state ?? ''))),

            Forms\Components\TextInput::make('slug')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('sort_order')
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('value_ka')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('value_ka')->label('Value'),
                Tables\Columns\TextColumn::make('slug')->fontFamily('mono'),
                Tables\Columns\TextColumn::make('sort_order')->label('Order'),
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
