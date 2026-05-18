<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Orders';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Status')->schema([
                Forms\Components\Select::make('status')
                    ->options([
                        Order::STATUS_NEW => 'New',
                        Order::STATUS_CONTACTED => 'Contacted',
                        Order::STATUS_PAID => 'Paid',
                        Order::STATUS_FULFILLED => 'Fulfilled',
                        Order::STATUS_CANCELLED => 'Cancelled',
                    ])
                    ->required(),

                Forms\Components\Textarea::make('admin_notes')
                    ->rows(4)
                    ->helperText('Internal notes — never shown to the customer.'),
            ]),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Order')->schema([
                Infolists\Components\Grid::make(3)->schema([
                    Infolists\Components\TextEntry::make('order_number')->label('Number'),
                    Infolists\Components\TextEntry::make('status')->badge(),
                    Infolists\Components\TextEntry::make('created_at')->dateTime()->label('Placed at'),
                ]),
            ]),

            Infolists\Components\Section::make('Customer')->schema([
                Infolists\Components\KeyValueEntry::make('customer_snapshot')
                    ->columnSpanFull(),
            ]),

            Infolists\Components\Section::make('Totals')->schema([
                Infolists\Components\Grid::make(4)->schema([
                    Infolists\Components\TextEntry::make('subtotal_retail')->money('GEL')->label('Retail subtotal'),
                    Infolists\Components\TextEntry::make('discount_total')->money('GEL'),
                    Infolists\Components\TextEntry::make('subtotal_charged')->money('GEL')->label('Charged subtotal'),
                    Infolists\Components\TextEntry::make('total')->money('GEL')->weight('bold'),
                ]),
            ]),

            Infolists\Components\Section::make('Notes')->schema([
                Infolists\Components\TextEntry::make('notes')->label('Customer notes')->placeholder('—'),
                Infolists\Components\TextEntry::make('admin_notes')->placeholder('—'),
            ])->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Number')
                    ->searchable()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->placeholder('Guest')
                    ->searchable(),

                Tables\Columns\TextColumn::make('customerGroup.name')
                    ->label('Group')
                    ->badge()
                    ->placeholder('Retail'),

                Tables\Columns\TextColumn::make('total')
                    ->money('GEL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        Order::STATUS_NEW => 'warning',
                        Order::STATUS_CONTACTED => 'info',
                        Order::STATUS_PAID => 'success',
                        Order::STATUS_FULFILLED => 'success',
                        Order::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Order::STATUS_NEW => 'New',
                        Order::STATUS_CONTACTED => 'Contacted',
                        Order::STATUS_PAID => 'Paid',
                        Order::STATUS_FULFILLED => 'Fulfilled',
                        Order::STATUS_CANCELLED => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
