<?php

namespace App\Filament\Resources\BulkOfferResource\Pages;

use App\Filament\Resources\BulkOfferResource;
use App\Models\BulkOffer;
use App\Services\BulkOfferManager;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListBulkOffers extends ListRecords
{
    protected static string $resource = BulkOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create New Bulk Offer')
                ->icon('heroicon-o-plus')
                ->modalHeading('Create & Apply New Bulk Offer')
                ->modalWidth('4xl')
                ->after(function (BulkOffer $record) {
                    $count = BulkOfferManager::apply($record);
                    Notification::make()
                        ->title("Bulk offer '{$record->title}' created and applied to {$count} vehicles.")
                        ->success()
                        ->send();
                }),

            Actions\Action::make('go_to_pricing')
                ->label('Vehicle Price Entries')
                ->icon('heroicon-o-currency-dollar')
                ->color('gray')
                ->url(\App\Filament\Resources\PriceEntryResource::getUrl('index')),
        ];
    }
}
