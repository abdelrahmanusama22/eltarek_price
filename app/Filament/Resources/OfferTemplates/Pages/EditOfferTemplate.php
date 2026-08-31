<?php

namespace App\Filament\Resources\OfferTemplates\Pages;

use App\Filament\Resources\OfferTemplates\OfferTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOfferTemplate extends EditRecord
{
    protected static string $resource = OfferTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
