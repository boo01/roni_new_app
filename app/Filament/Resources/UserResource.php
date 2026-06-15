<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('Customers');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Customers');
    }

    public static function getModelLabel(): string
    {
        return __('customer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('customers');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('Account'))->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('Name'))
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label(__('Email'))
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                ]),

                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('password')
                        ->label(__('Password'))
                        ->password()
                        ->revealable()
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $context) => $context === 'create')
                        ->helperText(__('Leave blank on edit to keep current password.')),

                    Forms\Components\Select::make('customer_group_id')
                        ->label(__('Customer group'))
                        ->relationship('customerGroup', 'name')
                        ->searchable()
                        ->preload()
                        ->placeholder(__('(retail / B2C)'))
                        ->helperText(__('Choose a group for B2B / company customers; leave empty for retail.')),
                ]),
            ]),

            Forms\Components\Section::make(__('Company details'))->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('company_name')
                        ->label(__('Company name'))
                        ->maxLength(255),

                    Forms\Components\TextInput::make('company_tax_id')
                        ->label(__('Tax ID'))
                        ->maxLength(255),
                ]),
            ])->collapsible(),

            Forms\Components\Section::make(__('Contact'))->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('phone')
                        ->label(__('Phone'))
                        ->tel()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('address')
                        ->label(__('Address'))
                        ->maxLength(255),
                ]),
            ])->collapsible(),

            Forms\Components\Section::make(__('Status'))->schema([
                Forms\Components\Toggle::make('is_active')
                    ->label(__('Active'))
                    ->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('customerGroup.name')
                    ->label(__('Group'))
                    ->placeholder(__('Retail'))
                    ->badge()
                    ->color(fn ($state) => $state ? 'primary' : 'gray'),

                Tables\Columns\TextColumn::make('company_name')
                    ->label(__('Company'))
                    ->placeholder('—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label(__('Phone'))
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created at'))
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_group_id')
                    ->relationship('customerGroup', 'name')
                    ->label(__('Group')),

                Tables\Filters\Filter::make('b2b')
                    ->label(__('B2B only'))
                    ->query(fn ($query) => $query->whereNotNull('customer_group_id')),

                Tables\Filters\Filter::make('b2c')
                    ->label(__('B2C only'))
                    ->query(fn ($query) => $query->whereNull('customer_group_id')),

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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
