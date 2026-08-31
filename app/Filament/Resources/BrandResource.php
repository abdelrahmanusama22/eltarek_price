<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Models\Brand;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use App\Filament\Imports\BrandImporter;
use App\Filament\Exports\BrandExporter;
use Filament\Actions\ImportAction;
use Filament\Actions\ExportAction;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-building-storefront';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Catalog';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->isBrandManager()) {
            $brandIds = auth()->user()->brands()->pluck('id')->toArray();

            if (empty($brandIds)) {
                // Brand Manager with no assigned brands sees nothing
                $query->whereRaw('0 = 1');
            } else {
                $query->whereIn('id', $brandIds);
            }
        }

        return $query;
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Brand Name')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Forms\Components\FileUpload::make('logo')
                ->image()
                ->disk('public')
                ->directory('brands/logos')
                ->visibility('public')
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->square(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Brand Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('cars_count')
                    ->label('Cars')
                    ->counts('cars')
                    ->sortable(),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Managers')
                    ->counts('users')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                ImportAction::make()
                    ->importer(BrandImporter::class)
                    ->label('Import from Excel/CSV'),
                ExportAction::make()
                    ->exporter(BrandExporter::class)
                    ->label('Export to Excel'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit'   => Pages\EditBrand::route('/{record}/edit'),
        ];
    }
}
