<?php

namespace App\Filament\Resources\PriceEntryResource\Pages;

use App\Filament\Resources\PriceEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Imports\PriceEntryImporter;
use Filament\Actions\ImportAction;
use Illuminate\Support\Facades\Storage;
use App\Exports\PriceEntryExcelExport;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;

class ListPriceEntries extends ListRecords
{
    protected static string $resource = PriceEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('custom_export')
                ->label('Export Dynamic Price List')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    return Excel::download(new \App\Exports\DynamicPriceListExport(), 'price_list_'.now()->format('Y-m-d').'.xlsx');
                }),
            Actions\Action::make('export_global_history')
                ->label('Export Price History')
                ->icon('heroicon-m-document-arrow-down')
                ->color('gray')
                ->modalHeading('Export Price History')
                ->modalDescription('Exports all price changes for the vehicles you manage within the selected dates.')
                ->modalSubmitActionLabel('Export')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('start_date')
                        ->label('From Date')
                        ->default(now()->toDateString())
                        ->maxDate(now())
                        ->required()
                        ->live(),
                    \Filament\Forms\Components\DatePicker::make('end_date')
                        ->label('To Date')
                        ->default(now()->toDateString())
                        ->maxDate(now())
                        ->required()
                        ->afterOrEqual('start_date'),
                ])
                ->action(function (array $data) {
                    return Excel::download(
                        new \App\Exports\GlobalPriceHistoryExport($data['start_date'], $data['end_date']),
                        'price_history_' . $data['start_date'] . '_to_' . $data['end_date'] . '.xlsx'
                    );
                }),

            Actions\Action::make('manage_bulk_offers')
                ->label('Manage Bulk Offers')
                ->icon('heroicon-m-ticket')
                ->color('info')
                ->url(\App\Filament\Resources\BulkOfferResource::getUrl('index')),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'requires_review' => Tab::make('Requires Review')
                ->modifyQueryUsing(function (Builder $query) {
                    $columnMap = [
                        'official_price'   => 'official_price',
                        'model_name'       => 'model_name',
                        'model_sales_code' => 'model_sales_code',
                        'year'             => 'year',
                        'brand_id'         => 'brand_id',
                        'crm_hold_status'  => 'hold_status',
                    ];

                    return $query->whereHas('car', function (Builder $carQuery) use ($columnMap) {
                        $carQuery->where(function ($q) use ($columnMap) {
                            foreach ($columnMap as $crmField => $priceField) {
                                $q->orWhere(function ($subQ) use ($crmField, $priceField) {
                                    if ($priceField === 'official_price') {
                                        $subQ->whereRaw("ROUND(CAST(COALESCE(cars.{$crmField}, 0) AS DECIMAL(15,2)), 2) != ROUND(CAST(COALESCE(price_entries.{$priceField}, 0) AS DECIMAL(15,2)), 2)");
                                    } elseif (in_array($priceField, ['model_name', 'model_sales_code', 'hold_status'])) {
                                        $subQ->whereRaw("TRIM(LOWER(COALESCE(cars.{$crmField}, ''))) != TRIM(LOWER(COALESCE(price_entries.{$priceField}, '')))");
                                    } else {
                                        $subQ->whereRaw("COALESCE(cars.{$crmField}, 0) != COALESCE(price_entries.{$priceField}, 0)");
                                    }
                                    
                                    // Ensure this specific field hasn't been ignored
                                    $subQ->whereNull("price_entries.ignored_crm_updates->{$priceField}");
                                });
                            }
                        });
                    });
                }),
        ];
    }
}
