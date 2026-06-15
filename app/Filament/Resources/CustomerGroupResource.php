<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerGroupResource\Pages;
use App\Models\CustomerGroup;
use App\Support\Slug;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerGroupResource extends Resource
{
    protected static ?string $model = CustomerGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('Customer groups');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Customers');
    }

    public static function getModelLabel(): string
    {
        return __('customer group');
    }

    public static function getPluralModelLabel(): string
    {
        return __('customer groups');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set, $context) => $context === 'create' && $set('slug', Slug::generate($state ?? ''))),

                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\TextInput::make('discount_percent')
                    ->label(__('Discount percent'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->default(0)
                    ->suffix('%')
                    ->helperText(__('Discount applied to retail price for this group, unless a per-product override exists.')),

                Forms\Components\Toggle::make('is_default_for_b2b')
                    ->label(__('Default B2B group'))
                    ->helperText(__('When admin creates a new B2B account without choosing a group, this one is assigned.')),

                Forms\Components\Textarea::make('notes')
                    ->label(__('Notes'))
                    ->rows(3)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('discount_percent')
                    ->label(__('Discount'))
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('users_count')
                    ->label(__('Customers'))
                    ->counts('users')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_default_for_b2b')
                    ->label(__('Default'))
                    ->boolean(),
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
            'index' => Pages\ListCustomerGroups::route('/'),
            'create' => Pages\CreateCustomerGroup::route('/create'),
            'edit' => Pages\EditCustomerGroup::route('/{record}/edit'),
        ];
    }
}
