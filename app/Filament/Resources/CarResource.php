<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CarResource\Pages;
use App\Models\Car;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Imports\CarImporter;
use App\Filament\Exports\CarExporter;
use Filament\Actions\ImportAction;
use Filament\Actions\ExportAction;

class CarResource extends Resource
{
    protected static ?string $model = Car::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-truck';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Catalog';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    // -------------------------------------------------------------------------
    // Multi-Tenancy Scoping (PRD Section 4.1)
    // Brand Managers see ONLY cars belonging to their authorized brands.
    // Super Admins see everything.
    // -------------------------------------------------------------------------

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->isBrandManager()) {
            $brandIds = auth()->user()->brands()->pluck('id')->toArray();

            if (empty($brandIds)) {
                // Brand Manager with no assigned brands sees nothing
                $query->whereRaw('0 = 1');
            } else {
                $query->whereIn('brand_id', $brandIds);
            }
        }

        return $query;
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Schemas\Components\Section::make('Car Identity')->schema([
                Forms\Components\TextInput::make('crm_id')
                    ->label('CRM Car ID')
                    ->nullable()
                    ->unique(ignoreRecord: true)
                    ->helperText('External CRM ID. Leave blank if not synced yet.'),

                Forms\Components\Select::make('brand_id')
                    ->label('Brand')
                    ->relationship(
                        name: 'brand',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (Builder $query) {
                            if (auth()->user()?->isBrandManager()) {
                                $query->whereIn('id', auth()->user()->brands->pluck('id'));
                            }
                        }
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->native(false),

                Forms\Components\TextInput::make('model_name')
                    ->label('Model Name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('category')
                    ->label('Category')
                    ->nullable()
                    ->maxLength(255),

                Forms\Components\TextInput::make('year')
                    ->label('Year')
                    ->required()
                    ->numeric()
                    ->minValue(1900)
                    ->maxValue(2100),

                Forms\Components\TextInput::make('model_sales_code')
                    ->label('Model Sales Code')
                    ->required()
                    ->maxLength(500),

                Forms\Components\TextInput::make('official_price')
                    ->label('Official Price (EGP)')
                    ->required()
                    ->numeric()
                    ->prefix('EGP')
                    ->minValue(0),

                Forms\Components\TextInput::make('execution_price')
                    ->label('Execution Price (EGP)')
                    ->numeric()
                    ->prefix('EGP')
                    ->minValue(0),

                Forms\Components\Select::make('crm_hold_status')
                    ->label('CRM Hold Status')
                    ->options([
                        'NO'           => 'No (Available)',
                        'YES'          => 'Yes (Hidden)',
                        'Wishing List' => 'Wishing List (Visible but Prices Hidden)',
                        'STOP'         => 'STOP — Do Not Sell',
                    ])
                    ->default('NO')
                    ->required()
                    ->native(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Internal ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('crm_id')
                    ->label('CRM ID')
                    ->placeholder('Not Synced Yet')
                    ->badge()
                    ->color(fn (?string $state): string => $state === null ? 'warning' : 'success')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Brand')
                    ->badge()
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('model_name')
                    ->label('Model')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('model_sales_code')
                    ->label('Sales Code')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('year')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('category')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('official_price')
                    ->label('Official Price')
                    ->money('EGP')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\SelectColumn::make('crm_hold_status')
                    ->label('CRM Hold')
                    ->options([
                        'NO'           => 'No (Available)',
                        'YES'          => 'Yes (Hidden)',
                        'Wishing List' => 'Wishing List',
                        'STOP'         => 'STOP',
                    ])
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('has_price_entry')
                    ->label('Has Price Entry')
                    ->boolean()
                    ->getStateUsing(fn (Car $record) => $record->priceEntry !== null)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('brand')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('crm_hold_status')
                    ->options([
                        'NO'           => 'No (Available)',
                        'YES'          => 'Yes (Hidden)',
                        'Wishing List' => 'Wishing List',
                        'STOP'         => 'STOP',
                    ]),
            ])
            ->headerActions([
                ImportAction::make()
                    ->importer(CarImporter::class)
                    ->label('Import from Excel/CSV'),
                ExportAction::make()
                    ->exporter(CarExporter::class)
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
            'index'  => Pages\ListCars::route('/'),
            'create' => Pages\CreateCar::route('/create'),
            'edit'   => Pages\EditCar::route('/{record}/edit'),
        ];
    }
}
