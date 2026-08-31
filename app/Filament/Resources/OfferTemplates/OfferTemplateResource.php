<?php

namespace App\Filament\Resources\OfferTemplates;

use App\Filament\Resources\OfferTemplates\Pages\CreateOfferTemplate;
use App\Filament\Resources\OfferTemplates\Pages\EditOfferTemplate;
use App\Filament\Resources\OfferTemplates\Pages\ListOfferTemplates;
use App\Filament\Resources\OfferTemplates\Schemas\OfferTemplateForm;
use App\Filament\Resources\OfferTemplates\Tables\OfferTemplatesTable;
use App\Models\OfferTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OfferTemplateResource extends Resource
{
    protected static ?string $model = OfferTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return OfferTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OfferTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOfferTemplates::route('/'),
            'create' => CreateOfferTemplate::route('/create'),
            'edit' => EditOfferTemplate::route('/{record}/edit'),
        ];
    }
}
