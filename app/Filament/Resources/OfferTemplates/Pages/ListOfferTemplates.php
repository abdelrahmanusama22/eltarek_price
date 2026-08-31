<?php

namespace App\Filament\Resources\OfferTemplates\Pages;

use App\Filament\Resources\OfferTemplates\OfferTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOfferTemplates extends ListRecords
{
    protected static string $resource = OfferTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
