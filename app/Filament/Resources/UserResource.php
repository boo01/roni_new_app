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

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $navigationGroup = 'Customers';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Account')->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                ]),

                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $context) => $context === 'create')
                        ->helperText('Leave blank on edit to keep current password.'),

                    Forms\Components\Select::make('customer_group_id')
                        ->label('Customer group')
                        ->relationship('customerGroup', 'name')
                        ->searchable()
                        ->preload()
                        ->placeholder('(retail / B2C)')
                        ->helperText('Choose a group for B2B / company customers; leave empty for retail.'),
                ]),
            ]),

            Forms\Components\Section::make('Company details')->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('company_name')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('company_tax_id')
                        ->label('Tax ID')
                        ->maxLength(255),
                ]),
            ])->collapsible(),

            Forms\Components\Section::make('Contact')->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('address')
                        ->maxLength(255),
                ]),
            ])->collapsible(),

            Forms\Components\Section::make('Status')->schema([
                Forms\Components\Toggle::make('is_active')
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
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('customerGroup.name')
                    ->label('Group')
                    ->placeholder('Retail')
                    ->badge()
                    ->color(fn ($state) => $state ? 'primary' : 'gray'),

                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company')
                    ->placeholder('—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_group_id')
                    ->relationship('customerGroup', 'name')
                    ->label('Group'),

                Tables\Filters\Filter::make('b2b')
                    ->label('B2B only')
                    ->query(fn ($query) => $query->whereNotNull('customer_group_id')),

                Tables\Filters\Filter::make('b2c')
                    ->label('B2C only')
                    ->query(fn ($query) => $query->whereNull('customer_group_id')),

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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
