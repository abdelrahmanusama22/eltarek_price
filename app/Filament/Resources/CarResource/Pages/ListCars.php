<?php

namespace App\Filament\Resources\CarResource\Pages;

use App\Filament\Resources\CarResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Imports\CarImporter;
use Filament\Actions\ImportAction;
use App\Filament\Exports\CarExporter;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;

class ListCars extends ListRecords
{
    protected static string $resource = CarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ImportAction::make()
            //     ->importer(CarImporter::class)
            //     ->label('Import from Excel/CSV')
            //     ->color('warning')
            //     ->icon('heroicon-o-arrow-up-tray'),

            // ExportAction::make()
            //     ->exporter(CarExporter::class)
            //     ->label('Export to Excel/CSV')
            //     ->icon('heroicon-o-arrow-down-tray')
            //     ->color('success')
            //     ->formats([
            //         ExportFormat::Csv,
            //         ExportFormat::Xlsx,
            //     ]),

            Actions\CreateAction::make(),
        ];
    }
}
