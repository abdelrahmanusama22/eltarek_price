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
            \Filament\Actions\ActionGroup::make([
                Actions\Action::make('bulk_offer_engine')
                ->label('Bulk Offer Engine')
                ->icon('heroicon-o-bolt')
                ->color('success')
                ->visible(fn () => auth()->user()?->hasRole('super_admin'))
                ->modalHeading('Apply Bulk Offer')
                ->modalDescription('Apply a specific offer to multiple vehicles simultaneously.')
                ->form([
                    \Filament\Schemas\Components\Section::make('Offer Details')->schema([
                        \Filament\Forms\Components\TextInput::make('offer_id')
                            ->label('Offer ID (slug)')
                            ->required()
                            ->placeholder('e.g. mega_sale_9_percent')
                            ->helperText('A unique machine-readable key. No spaces.'),
                        \Filament\Forms\Components\TextInput::make('title')
                            ->label('Offer Title')
                            ->required()
                            ->placeholder('e.g. 9% Installment Offer'),
                        \Filament\Forms\Components\TextInput::make('price')
                            ->label('Price (EGP)')
                            ->required()
                            ->numeric()
                            ->minValue(0),
                        \Filament\Forms\Components\Textarea::make('note')
                            ->label('Note / Terms')
                            ->rows(2)
                            ->placeholder('e.g. Requires 30% down payment'),
                    ])->columns(2),

                    \Filament\Schemas\Components\Section::make('Target Scope')->schema([
                        \Filament\Forms\Components\Select::make('scope')
                            ->label('Target Scope')
                            ->options([
                                'all'    => 'All Cars',
                                'brands' => 'Specific Brands',
                                'models' => 'Specific Models',
                            ])
                            ->default('all')
                            ->required()
                            ->live(),

                        \Filament\Forms\Components\Select::make('brands')
                            ->label('Target Brands')
                            ->multiple()
                            ->searchable()
                            ->options(\App\Models\Brand::pluck('name', 'id'))
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('scope') === 'brands')
                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('scope') === 'brands'),

                        \Filament\Forms\Components\Select::make('models')
                            ->label('Target Models')
                            ->multiple()
                            ->searchable()
                            ->options(\App\Models\Car::select('model_name')->distinct()->pluck('model_name', 'model_name'))
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('scope') === 'models')
                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('scope') === 'models'),
                    ]),
                ])
                ->action(function (array $data) {
                    $query = \App\Models\PriceEntry::query();

                    if ($data['scope'] === 'brands') {
                        $query->whereIn('brand_id', $data['brands']);
                    } elseif ($data['scope'] === 'models') {
                        $query->whereHas('car', function ($q) use ($data) {
                            $q->whereIn('model_name', $data['models']);
                        });
                    }

                    $entries = $query->get();
                    $count = 0;

                    foreach ($entries as $entry) {
                        $offers = $entry->offers ?? [];
                        
                        $newOffer = [
                            'id'        => $data['offer_id'],
                            'title'     => $data['title'],
                            'price'     => $data['price'],
                            'is_active' => true,
                            'note'      => $data['note'] ?? null,
                        ];

                        $offers[] = $newOffer;
                        $entry->update(['offers' => $offers]);
                        $count++;
                    }

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title("Offer applied successfully to {$count} vehicles.")
                        ->send();
                }),
            Actions\Action::make('bulk_edit_offer')
                ->label('Bulk Edit Offer')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->visible(fn () => auth()->user()?->hasRole('super_admin'))
                ->modalHeading('Edit Bulk Offer')
                ->modalDescription('Modify an existing offer across multiple vehicles simultaneously.')
                ->form([
                    \Filament\Schemas\Components\Section::make('Offer Details')->schema([
                        \Filament\Forms\Components\TextInput::make('target_offer_title')
                            ->label('Target Offer Title')
                            ->required()
                            ->placeholder('e.g. 9% Installment Offer')
                            ->helperText('The exact title of the offer you want to edit.'),
                        \Filament\Forms\Components\TextInput::make('title')
                            ->label('New Offer Title')
                            ->required()
                            ->placeholder('e.g. 10% Installment Offer'),
                        \Filament\Forms\Components\TextInput::make('price')
                            ->label('New Price (EGP)')
                            ->required()
                            ->numeric()
                            ->minValue(0),
                    ])->columns(1),

                    \Filament\Schemas\Components\Section::make('Target Scope')->schema([
                        \Filament\Forms\Components\Select::make('scope')
                            ->label('Target Scope')
                            ->options([
                                'all'    => 'All Cars',
                                'brands' => 'Specific Brands',
                                'models' => 'Specific Models',
                            ])
                            ->default('all')
                            ->required()
                            ->live(),

                        \Filament\Forms\Components\Select::make('brands')
                            ->label('Target Brands')
                            ->multiple()
                            ->searchable()
                            ->options(\App\Models\Brand::pluck('name', 'id'))
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('scope') === 'brands')
                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('scope') === 'brands'),

                        \Filament\Forms\Components\Select::make('models')
                            ->label('Target Models')
                            ->multiple()
                            ->searchable()
                            ->options(\App\Models\Car::select('model_name')->distinct()->pluck('model_name', 'model_name'))
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('scope') === 'models')
                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('scope') === 'models'),
                    ]),
                ])
                ->action(function (array $data) {
                    $query = \App\Models\PriceEntry::query();

                    if ($data['scope'] === 'brands') {
                        $query->whereIn('brand_id', $data['brands']);
                    } elseif ($data['scope'] === 'models') {
                        $query->whereHas('car', function ($q) use ($data) {
                            $q->whereIn('model_name', $data['models']);
                        });
                    }

                    $entries = $query->get();
                    $count = 0;

                    foreach ($entries as $entry) {
                        $offers = $entry->offers ?? [];
                        $updated = false;
                        
                        foreach ($offers as &$offer) {
                            if (isset($offer['title']) && strtolower(trim($offer['title'])) === strtolower(trim($data['target_offer_title']))) {
                                $offer['title'] = $data['title'];
                                $offer['price'] = $data['price'];
                                $updated = true;
                            }
                        }

                        if ($updated) {
                            $entry->update(['offers' => $offers]);
                            $count++;
                        }
                    }

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title("Offer updated successfully across {$count} vehicles.")
                        ->send();
                }),
            Actions\Action::make('bulk_delete_offer')
                ->label('Bulk Remove Offer')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn () => auth()->user()?->hasRole('super_admin'))
                ->modalHeading('Remove Bulk Offer')
                ->modalDescription('Delete an existing offer from multiple vehicles simultaneously.')
                ->form([
                    \Filament\Schemas\Components\Section::make('Offer Details')->schema([
                        \Filament\Forms\Components\TextInput::make('target_offer_title')
                            ->label('Offer Title to Remove')
                            ->required()
                            ->placeholder('e.g. 9% Installment Offer')
                            ->helperText('The exact title of the offer you want to remove.'),
                    ])->columns(1),

                    \Filament\Schemas\Components\Section::make('Target Scope')->schema([
                        \Filament\Forms\Components\Select::make('scope')
                            ->label('Target Scope')
                            ->options([
                                'all'    => 'All Cars',
                                'brands' => 'Specific Brands',
                                'models' => 'Specific Models',
                            ])
                            ->default('all')
                            ->required()
                            ->live(),

                        \Filament\Forms\Components\Select::make('brands')
                            ->label('Target Brands')
                            ->multiple()
                            ->searchable()
                            ->options(\App\Models\Brand::pluck('name', 'id'))
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('scope') === 'brands')
                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('scope') === 'brands'),

                        \Filament\Forms\Components\Select::make('models')
                            ->label('Target Models')
                            ->multiple()
                            ->searchable()
                            ->options(\App\Models\Car::select('model_name')->distinct()->pluck('model_name', 'model_name'))
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('scope') === 'models')
                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('scope') === 'models'),
                    ]),
                ])
                ->action(function (array $data) {
                    $query = \App\Models\PriceEntry::query();

                    if ($data['scope'] === 'brands') {
                        $query->whereIn('brand_id', $data['brands']);
                    } elseif ($data['scope'] === 'models') {
                        $query->whereHas('car', function ($q) use ($data) {
                            $q->whereIn('model_name', $data['models']);
                        });
                    }

                    $entries = $query->get();
                    $count = 0;

                    foreach ($entries as $entry) {
                        $offers = $entry->offers ?? [];
                        $initialCount = count($offers);
                        
                        $offers = array_filter($offers, function ($offer) use ($data) {
                            return !isset($offer['title']) || strtolower(trim($offer['title'])) !== strtolower(trim($data['target_offer_title']));
                        });

                        if (count($offers) < $initialCount) {
                            $entry->update(['offers' => array_values($offers)]);
                            $count++;
                        }
                    }

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title("Offer removed successfully from {$count} vehicles.")
                        ->send();
                }),
            ])
            ->label('Manage Bulk Offers')
            ->icon('heroicon-m-tag')
            ->button()
            ->color('warning'),
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
                                    // Null-safe comparison to catch mismatches even if one is NULL
                                    $subQ->whereRaw("NOT (cars.{$crmField} <=> price_entries.{$priceField})")
                                         // Ensure this specific field hasn't been ignored
                                         ->whereNull("price_entries.ignored_crm_updates->{$priceField}");
                                });
                            }
                        });
                    });
                }),
        ];
    }
}
