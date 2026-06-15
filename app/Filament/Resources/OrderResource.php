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

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('Orders');
    }

    public static function getModelLabel(): string
    {
        return __('order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('orders');
    }

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return [
            Order::STATUS_NEW => __('New'),
            Order::STATUS_CONTACTED => __('Contacted'),
            Order::STATUS_PAID => __('Paid'),
            Order::STATUS_FULFILLED => __('Fulfilled'),
            Order::STATUS_CANCELLED => __('Cancelled'),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('Status'))->schema([
                Forms\Components\Select::make('status')
                    ->label(__('Status'))
                    ->options(self::statusOptions())
                    ->required(),

                Forms\Components\Textarea::make('admin_notes')
                    ->label(__('Admin notes'))
                    ->rows(4)
                    ->helperText(__('Internal notes — never shown to the customer.')),
            ]),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make(__('Order'))->schema([
                Infolists\Components\Grid::make(3)->schema([
                    Infolists\Components\TextEntry::make('order_number')->label(__('Number')),
                    Infolists\Components\TextEntry::make('status')
                        ->label(__('Status'))
                        ->badge()
                        ->formatStateUsing(fn ($state) => self::statusOptions()[$state] ?? $state),
                    Infolists\Components\TextEntry::make('created_at')->dateTime()->label(__('Placed at')),
                ]),
            ]),

            Infolists\Components\Section::make(__('Customer'))->schema([
                Infolists\Components\KeyValueEntry::make('customer_snapshot')
                    ->label(__('Customer details'))
                    ->keyLabel(__('Field'))
                    ->valueLabel(__('Value'))
                    ->columnSpanFull(),
            ]),

            Infolists\Components\Section::make(__('Products'))
                ->description(__('Items to pick and prepare for this order.'))
                ->schema([
                    Infolists\Components\ViewEntry::make('items')
                        ->hiddenLabel()
                        ->view('filament.infolists.order-items'),
                ]),

            Infolists\Components\Section::make(__('Totals'))->schema([
                Infolists\Components\Grid::make(4)->schema([
                    Infolists\Components\TextEntry::make('subtotal_retail')->money('GEL')->label(__('Retail subtotal')),
                    Infolists\Components\TextEntry::make('discount_total')->money('GEL')->label(__('Discount total')),
                    Infolists\Components\TextEntry::make('subtotal_charged')->money('GEL')->label(__('Charged subtotal')),
                    Infolists\Components\TextEntry::make('total')->money('GEL')->weight('bold')->label(__('Total')),
                ]),
            ]),

            Infolists\Components\Section::make(__('Notes'))->schema([
                Infolists\Components\TextEntry::make('notes')->label(__('Customer notes'))->placeholder('—'),
                Infolists\Components\TextEntry::make('admin_notes')->label(__('Admin notes'))->placeholder('—'),
            ])->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label(__('Number'))
                    ->searchable()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('Customer'))
                    ->placeholder(__('Guest'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('customerGroup.name')
                    ->label(__('Group'))
                    ->badge()
                    ->placeholder(__('Retail')),

                Tables\Columns\TextColumn::make('total')
                    ->label(__('Total'))
                    ->money('GEL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => self::statusOptions()[$state] ?? $state)
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
                    ->label(__('Created at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(self::statusOptions()),
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
