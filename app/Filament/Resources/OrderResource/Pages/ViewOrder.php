<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('invoice')
                ->label(__('Print / Invoice'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('order.invoice', $this->record->order_number))
                ->openUrlInNewTab(),

            Actions\EditAction::make(),
        ];
    }
}
