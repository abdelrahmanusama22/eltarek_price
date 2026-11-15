<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BulkOfferResource\Pages;
use App\Models\Brand;
use App\Models\BulkOffer;
use App\Models\Car;
use App\Services\BulkOfferManager;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class BulkOfferResource extends Resource
{
    protected static ?string $model = BulkOffer::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-ticket';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Pricing';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationLabel(): string
    {
        return 'Manage Bulk Offers';
    }

    public static function getModelLabel(): string
    {
        return 'Bulk Offer';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Manage Bulk Offers';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Offer Identity & Calculation')
                ->description('Specify the unique machine-readable ID, customer-facing title, and calculation basis.')
                ->schema([
                    Forms\Components\TextInput::make('offer_id')
                        ->label('Offer Slug (ID)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->disabled(fn (?BulkOffer $record) => $record !== null)
                        ->dehydrated()
                        ->placeholder('e.g., ramadan_special_10_percent')
                        ->helperText('Unique key across the system. Letters, numbers, and underscores only.'),

                    Forms\Components\TextInput::make('title')
                        ->label('Offer Display Title')
                        ->required()
                        ->placeholder('e.g., 10% Ramadan Promotional Offer')
                        ->helperText('Displayed on the Sales Portal and promotional price tags.'),

                    Forms\Components\Select::make('price_basis')
                        ->label('Base Price (Depends On)')
                        ->options([
                            'max_selling_price' => 'Retail Price "5%" (Standard)',
                            'execution_price'   => 'Total Retail Price (Raw)',
                        ])
                        ->default('max_selling_price')
                        ->required()
                        ->helperText('Select which price field serves as the baseline for discount/markup.'),

                    Forms\Components\Radio::make('offer_type')
                        ->label('Offer Type')
                        ->options([
                            'fixed'      => 'Fixed Amount (EGP)',
                            'percentage' => 'Percentage (%)',
                        ])
                        ->default('fixed')
                        ->inline()
                        ->live()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('value')
                        ->label(fn (\Filament\Schemas\Components\Utilities\Get $get) =>
                            $get('offer_type') === 'percentage'
                                ? 'Discount / Markup Percentage (%)'
                                : 'Amount (EGP)'
                        )
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(fn (\Filament\Schemas\Components\Utilities\Get $get) =>
                            $get('offer_type') === 'percentage' ? 100 : PHP_INT_MAX
                        )
                        ->suffix(fn (\Filament\Schemas\Components\Utilities\Get $get) =>
                            $get('offer_type') === 'percentage' ? '%' : 'EGP'
                        ),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Offer Active')
                        ->helperText('Inactive offers remain bound but are hidden on the Sales Portal.')
                        ->default(true),

                    Forms\Components\Textarea::make('note')
                        ->label('Terms / Internal Note')
                        ->placeholder('e.g., Valid until end of month. Minimum 25% down payment required.')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(2),

            \Filament\Schemas\Components\Section::make('Target Scope & Exclusions')
                ->description('Determine which vehicles should receive this offer, and exclude specific brands or models.')
                ->schema([
                    Forms\Components\Select::make('scope')
                        ->label('Target Scope')
                        ->options([
                            'all'    => 'All Cars (System-wide)',
                            'brands' => 'Specific Brands',
                            'models' => 'Specific Models',
                        ])
                        ->default('all')
                        ->required()
                        ->live()
                        ->columnSpanFull(),

                    Forms\Components\Select::make('brands')
                        ->label('Target Brands')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(\App\Models\Brand::orderBy('name')->pluck('name', 'id'))
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('scope') === 'brands')
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('scope') === 'brands')
                        ->columnSpanFull(),

                    Forms\Components\Select::make('models')
                        ->label('Target Models')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(\App\Models\Car::select('model_name')->distinct()->whereNotNull('model_name')->orderBy('model_name')->pluck('model_name', 'model_name'))
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('scope') === 'models')
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('scope') === 'models')
                        ->columnSpanFull(),

                    Forms\Components\Select::make('exclude_brands')
                        ->label('Exclude Brands (استثناء ماركات)')
                        ->helperText('These brands will be strictly omitted from this offer.')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(\App\Models\Brand::orderBy('name')->pluck('name', 'id'))
                        ->nullable()
                        ->columnSpan(1),

                    Forms\Components\Select::make('exclude_models')
                        ->label('Exclude Models (استثناء طرازات)')
                        ->helperText('These models will be strictly omitted from this offer.')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(\App\Models\Car::select('model_name')->distinct()->whereNotNull('model_name')->orderBy('model_name')->pluck('model_name', 'model_name'))
                        ->nullable()
                        ->columnSpan(1),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Offer Title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (BulkOffer $record) => $record->offer_id)
                    ->copyable()
                    ->copyMessage('Offer ID copied to clipboard'),

                Tables\Columns\TextColumn::make('offer_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'percentage' ? 'Percentage (%)' : 'Fixed Amount (EGP)')
                    ->color(fn ($state) => $state === 'percentage' ? 'info' : 'success'),

                Tables\Columns\TextColumn::make('value')
                    ->label('Value')
                    ->sortable()
                    ->formatStateUsing(fn (BulkOffer $record) =>
                        $record->offer_type === 'percentage'
                            ? $record->value . '%'
                            : number_format((float) $record->value) . ' EGP'
                    )
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('price_basis')
                    ->label('Basis')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'max_selling_price' ? '5% Ceiling' : 'Execution')
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('formatted_scope')
                    ->label('Target Scope')
                    ->badge()
                    ->color('primary')
                    ->tooltip(function (BulkOffer $record) {
                        if ($record->scope === 'brands' && !empty($record->brands)) {
                            return Brand::whereIn('id', (array) $record->brands)->pluck('name')->implode(', ');
                        }
                        if ($record->scope === 'models' && !empty($record->models)) {
                            return implode(', ', (array) $record->models);
                        }
                        return 'Applies to all vehicles';
                    }),

                Tables\Columns\TextColumn::make('formatted_exclusions')
                    ->label('Exclusions')
                    ->badge()
                    ->color(fn ($state) => $state === 'None' ? 'gray' : 'warning')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('vehicles_count')
                    ->label('Bound Vehicles')
                    ->sortable()
                    ->badge()
                    ->icon('heroicon-m-truck')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => "{$state} Cars"),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active')
                    ->afterStateUpdated(function (BulkOffer $record, $state) {
                        BulkOfferManager::sync($record);
                        Notification::make()
                            ->title($state ? 'Offer activated & synced.' : 'Offer deactivated.')
                            ->success()
                            ->send();
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All Offers')
                    ->trueLabel('Active Offers Only')
                    ->falseLabel('Inactive Offers Only'),

                Tables\Filters\SelectFilter::make('scope')
                    ->options([
                        'all'    => 'All Cars',
                        'brands' => 'Specific Brands',
                        'models' => 'Specific Models',
                    ]),
            ])
            ->actions([
                // ── 1. View Details / Snapshot ────────────────────────────────
                Actions\Action::make('snapshot')
                    ->label('Snapshot')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->modalHeading(fn (BulkOffer $record): string => "Bound Vehicles: {$record->title}")
                    ->modalDescription(fn (BulkOffer $record): string => "Detailed snapshot of vehicles currently bound to this bulk offer ({$record->vehicles_count} vehicles).")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalWidth('5xl')
                    ->modalContent(fn (BulkOffer $record): View => view('filament.resources.bulk-offers.snapshot-modal', [
                        'bulkOffer' => $record,
                        'vehicles'  => $record->getBoundVehiclesQuery()->get(),
                    ])),

                // ── 2. Edit & Dynamically Sync ────────────────────────────────
                Actions\EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->modalHeading(fn (BulkOffer $record) => "Edit Bulk Offer: {$record->title}")
                    ->modalWidth('4xl')
                    ->after(function (BulkOffer $record) {
                        $count = BulkOfferManager::sync($record);
                        Notification::make()
                            ->title("Offer updated and dynamically synchronized across {$count} vehicles.")
                            ->success()
                            ->send();
                    }),

                // ── 3. One-Click Re-Sync ──────────────────────────────────────
                Actions\Action::make('resync')
                    ->label('Re-Sync')
                    ->icon('heroicon-m-arrow-path')
                    ->color('success')
                    ->tooltip('Re-scans vehicles and applies this offer to any newly matching cars')
                    ->requiresConfirmation()
                    ->modalHeading('Synchronize Offer Across Catalog')
                    ->modalDescription('This will re-evaluate target scope and exclusion rules, updating all matching vehicles.')
                    ->action(function (BulkOffer $record) {
                        $count = BulkOfferManager::sync($record);
                        Notification::make()
                            ->title("Successfully synchronized offer across {$count} vehicles.")
                            ->success()
                            ->send();
                    }),

                // ── 4. Delete & Safe Revert ───────────────────────────────────
                Actions\Action::make('revert_and_delete')
                    ->label('Revert & Delete')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn (BulkOffer $record) => "Delete & Revert: {$record->title}")
                    ->modalDescription(fn (BulkOffer $record) => "Are you sure? This will permanently remove this offer from all {$record->vehicles_count} bound vehicles, stripping the discount/markup and cleaning up the audit trail.")
                    ->action(function (BulkOffer $record) {
                        $revertedCount = BulkOfferManager::revert($record);
                        $record->delete();

                        Notification::make()
                            ->title("Bulk offer deleted and safely reverted from {$revertedCount} vehicles.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('revert_selected')
                        ->label('Revert & Delete Selected')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $totalReverted = 0;
                            foreach ($records as $record) {
                                $totalReverted += BulkOfferManager::revert($record);
                                $record->delete();
                            }

                            Notification::make()
                                ->title("Selected offers deleted and reverted from {$totalReverted} vehicles.")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBulkOffers::route('/'),
        ];
    }
}
