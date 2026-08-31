<?php

namespace App\Filament\Resources;

use App\Filament\Exports\PriceEntryExporter;
use App\Filament\Imports\PriceEntryImporter;
use App\Filament\Resources\PriceEntryResource\Pages;
use App\Filament\Resources\PriceEntryResource\RelationManagers\ActivityLogsRelationManager;
use App\Models\Car;
use App\Models\PriceEntry;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Schemas;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PriceEntryResource extends Resource
{
    protected static ?string $model = PriceEntry::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-currency-dollar';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Pricing';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    // -------------------------------------------------------------------------
    // Super Admins have unrestricted access.
    // -------------------------------------------------------------------------

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // 1. Brand Manager Scope
        if (auth()->user()?->isBrandManager()) {
            $brandIds = auth()->user()->brands()->pluck('id')->toArray();

            if (empty($brandIds)) {
                // Brand Manager with no assigned brands sees nothing
                $query->whereRaw('0 = 1');
            } else {
                $query->whereIn('brand_id', $brandIds);
            }
        }

        // 2. Hide 'STOP' entries for everyone except the super_admin
        // (Adjust 'super_admin' if your top-level role name is slightly different)
        if (! auth()->user()?->hasRole('super_admin')) {
            $query->where('hold_status', '!=', 'STOP');
        }

        return $query;
    }

    // -------------------------------------------------------------------------
    // Form Definition
    // -------------------------------------------------------------------------

    public static function form(Schema $form): Schema
    {
        return $form->schema([

            // ── Core Pricing Section ──────────────────────────────────────────
            Schemas\Components\Section::make('Price Entry')
                ->description('Enter official and execution prices. The engine will automatically compute max selling price and 3M protection price on save.')
                ->schema([
                    Forms\Components\Select::make('brand_id')
                        ->label('Brand')
                        ->relationship('brand', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\TextInput::make('model_name')
                        ->label('Model Name')
                        ->maxLength(255)
                        ->required(),

                    Forms\Components\TextInput::make('model_sales_code')
                        ->label('Sales Code')
                        ->maxLength(255)
                        ->required(),

                    Forms\Components\TextInput::make('year')
                        ->label('Year')
                        ->numeric()
                        ->minValue(1900)
                        ->maxValue(2100)
                        ->nullable(),

                ])->columns(2),

            // ── Price Inputs ──────────────────────────────────────────────────
            Schemas\Components\Section::make('Pricing Inputs')
                ->schema([
                    Forms\Components\Select::make('pricing_strategy')
                        ->label('Overprice Strategy')
                        ->options([
                            'standard' => 'Standard (Up to 5% on Retail, rest on 3M)',
                            'all_3m'   => 'All Overprice on 3M (Retail = Official)',
                        ])
                        ->default('standard')
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::updateComputedPrices($get, $set)),

                    Forms\Components\TextInput::make('official_price')
                        ->label('Official Price (EGP)')
                        ->required()
                        ->numeric()
                        ->prefix('EGP')
                        ->minValue(0)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::updateComputedPrices($get, $set))
                        ->helperText('Sourced from the CRM. Used as the 5% ceiling base.'),

                    Forms\Components\TextInput::make('execution_price')
                        ->label('total retail price')
                        ->required()
                        ->numeric()
                        ->prefix('EGP')
                        ->minValue(0)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::updateComputedPrices($get, $set))
                        ->helperText('Enter the actual selling price. The engine will split it on save.'),

                ])->columns(3),

            // ── Computed Fields (Read-only) ───────────────────────────────────
            Schemas\Components\Section::make('Computed Outputs (Auto-Calculated)')
                ->description('These fields are computed automatically by the Pricing Engine when you save.')
                ->schema([

                    Forms\Components\TextInput::make('max_selling_price')
                        ->label('retail price "5%"')
                        ->prefix('EGP')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('= MIN(execution_price, official_price × 1.05)'),

                    Forms\Components\TextInput::make('protection_3m_price')
                        ->label('3M Protection Price')
                        ->prefix('EGP')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('= MAX(0, execution_price − official_price × 1.05)'),

                ])->columns(2),

            // ── Offers Builder ────────────────────────────────────────────────
            Schemas\Components\Section::make('Dynamic Offers')
                ->description('Build installment or promotional offers for this car. Only active offers appear on the Sales Portal.')
                ->schema([
                    Forms\Components\Repeater::make('offers')
                        ->schema([
                            Forms\Components\Select::make('offer_template_id')
                                ->label('Offer Template')
                                ->options(\App\Models\OfferTemplate::where('is_active', true)->pluck('name', 'id'))
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $template = \App\Models\OfferTemplate::find($state);
                                        if ($template) {
                                            $set('id', $template->slug);
                                            $set('title', $template->name);
                                            $set('offer_type', $template->offer_type);
                                            $set('value', $template->value);
                                        }
                                    }
                                }),
                            Forms\Components\TextInput::make('id')
                                ->label('Offer ID (slug)')
                                ->required()
                                ->readOnly()
                                ->dehydrated()
                                ->placeholder('e.g. offer_9_percent')
                                ->helperText('A unique machine-readable key. No spaces. (Auto-filled from template)'),

                            Forms\Components\TextInput::make('title')
                                ->label('Title')
                                ->required()
                                ->readOnly()
                                ->placeholder('e.g. 9% Installment Offer'),

                            Forms\Components\Select::make('offer_type')
                                ->options([
                                    'percentage' => 'Percentage (%)',
                                    'fixed' => 'Fixed Amount (EGP)'
                                ])
                                ->required()
                                ->live(),
                            Forms\Components\TextInput::make('value')
                                ->label('Value')
                                ->numeric()
                                ->required()
                                ->live(),
                            Forms\Components\Placeholder::make('calculated_price')
                                ->label('Final Offer Price')
                                ->content(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                    $execPrice = floatval($get('../../execution_price'));
                                    $val = floatval($get('value'));
                                    $type = $get('offer_type') ?? 'fixed';
                                    
                                    if ($type === 'percentage') {
                                        $final = $execPrice + ($execPrice * ($val / 100));
                                    } else {
                                        $final = $execPrice + $val;
                                    }
                                    
                                    return number_format($final, 2) . ' EGP';
                                }),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Active')
                                ->default(true)
                                ->inline(false),

                            Forms\Components\Textarea::make('note')
                                ->label('Note / Terms')
                                ->rows(2)
                                ->placeholder('e.g. Requires 30% down payment')
                                ->columnSpanFull(),
                        ])
                        ->columns(4)
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                        ->collapsible()
                        ->defaultItems(0)
                        ->addActionLabel('Add Offer'),
                ]),

            // ── Hold Status ───────────────────────────────────────────────────
            Schemas\Components\Section::make('Availability')
                ->schema([
                    Forms\Components\Select::make('hold_status')
                        ->label('Hold Status')
                        ->options([
                            'NO'           => 'No (Available)',
                            'YES'          => 'Yes (Hidden)',
                            'Wishing List' => 'Wishing List (Visible but Prices Hidden)',
                            'STOP'         => 'STOP — Do Not Sell',
                        ])
                        ->default('NO')
                        ->required()
                        ->native(false),
                ]),

            // ── Additional Info ───────────────────────────────────────────────
            Schemas\Components\Section::make('Additional Information')
                ->schema([
                    Forms\Components\Textarea::make('additional_info')
                        ->label('Custom Notes')
                        ->rows(3)
                        ->placeholder('Enter any additional notes for this car...')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('warranty_info')
                        ->label('Warranty')
                        ->placeholder('e.g., 7 Years or 150,000 KM')
                        ->columnSpanFull(),
                   Forms\Components\FileUpload::make('brochure_pdf')
    ->label('Brochure (PDF)')
    ->acceptedFileTypes(['application/pdf'])
    ->disk('public')
    ->directory('price_entries/brochures')
    ->visibility('public')
    ->preserveFilenames()
    ->maxSize(51200) // التعديل هنا: السماح برفع ملفات حتى 50 ميجابايت
    ->columnSpanFull(),
                ]),

            // ── Available Colors ──────────────────────────────────────────────
            Schemas\Components\Section::make('Available Colors')
                ->schema([
                    Forms\Components\Repeater::make('available_colors')
                        ->schema(function () {
                            $colorMap = [
                                'ابيض' => '#FFFFFF',
                                'ابيض لؤلؤى' => '#F3E5AB',
                                'احمر' => '#FF0000',
                                'احمر دارك' => '#8B0000',
                                'اخضر' => '#008000',
                                'ازرق' => '#0000FF',
                                'ازرق بيبسى' => '#004B93',
                                'ازرق زهرى' => '#6495ED',
                                'اسود' => '#000000',
                                'اسود كاربون' => '#040404',
                                'اصفر' => '#FFFF00',
                                'برتقالى' => '#FFA500',
                                'باذنجانى' => '#4B0082',
                                'بترولى' => '#005F69',
                                'بترولى غامق' => '#003F46',
                                'بترولى فاتح' => '#00808C',
                                'بنى' => '#A52A2A',
                                'بنى غامق' => '#5C4033',
                                'بنى فاتح' => '#D2B48C',
                                'برونزى' => '#CD7F32',
                                'موكا' => '#493D26',
                                'بلاتينيوم' => '#E5E4E2',
                                'بيج' => '#F5F5DC',
                                'جرافيتى بلو' => '#2B3542',
                                'جرانيتى' => '#676767',
                                'ذهبى' => '#FFD700',
                                'ذهبى غامق' => '#B8860B',
                                'ذهبى فاتح' => '#EEE8AA',
                                'رصاصى' => '#808080',
                                'رمادى' => '#A9A9A9',
                                'زهرى' => '#FFC0CB',
                                'زيتونى' => '#808000',
                                'زيتى' => '#556B2F',
                                'زيتى فاتح' => '#8FBC8F',
                                'سماوى' => '#87CEEB',
                                'شامبين' => '#F7E7CE',
                                'فانتوم جراى' => '#444C5C',
                                'فضى' => '#C0C0C0',
                                'فضى فاتح' => '#D3D3D3',
                                'فضى مدخن' => '#708090',
                                'فضى ميتالك' => '#AAA9AD',
                                'فيرانى' => '#4F4F4F',
                                'فيرانى غامق' => '#2F4F4F',
                                'فيرانى فاتح' => '#778899',
                                'كحلى' => '#000080',
                                'كحلى غامق' => '#000033',
                                'كحلى فاتح' => '#4169E1',
                                'موف' => '#E0B0FF',
                                'مون ستون جراى' => '#73777B',
                                'نبيتى' => '#800000',
                                'نبيتى غامق' => '#4A0404',
                                'نحاسى' => '#B87333',
                                'بستاج' => '#93C572',
                                'بكينى' => '#F4C2C2',
                                'تركواز' => '#40E0D0',
                                'اسمنتى' => '#A2A2A2',
                                'فستقى' => '#93C572',
                                'رمادى فاتح' => '#D3D3D3',
                                'رمادى غامق' => '#696969',
                                'فضى غامق' => '#8C92AC',
                                'رملى' => '#F4A460',
                                'فيرانى مط' => '#4F4F4F',
                                'كريمى' => '#FFFDD0',
                                'فسدقى مط' => '#93C572',
                            ];

                            return [
                                Forms\Components\Select::make('color_name')
                                    ->label('Color Name/Description')
                                    ->options(array_combine(array_keys($colorMap), array_keys($colorMap)))
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(fn ($state, Set $set) => $set('color_code', $colorMap[$state] ?? null))
                                    ->required()
                                    ->columnSpan(4),
                                Forms\Components\ColorPicker::make('color_code')
                                    ->label('Color Hex Code')
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('interior_color')
                                    ->label('Interior Color')
                                    ->placeholder('e.g. Black, Beige')
                                    ->nullable()
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('additional_price')
                                    ->label('Price Adjustment')
                                    ->numeric()
                                    ->prefix('±')
                                    ->placeholder('Use - for discount, + for extra')
                                    ->suffix('EGP')
                                    ->nullable()
                                    ->columnSpan(5),
                                Forms\Components\Textarea::make('notes')
                                    ->label('ملاحظات / إقرار (Notes)')
                                    ->placeholder('Enter any specific issues, scratches, or required declarations for this color...')
                                    ->rows(2)
                                    ->nullable()
                                    ->columnSpanFull(),
                            ];
                        })
                        ->columns(12)
                        ->itemLabel(fn (array $state): ?string => $state['color_name'] ?? null)
                        ->collapsible()
                        ->addActionLabel('Add Color')
                ]),
        ]);
    }

    // -------------------------------------------------------------------------
    // Table Definition
    // -------------------------------------------------------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('car.id')
                    ->label('CRM ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('car.model_name')
                    ->label('Model')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('car.model_sales_code')
                    ->label('Sales Code')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Brand')
                    ->badge()
                    ->sortable()
                    ->toggleable(),

               Tables\Columns\TextInputColumn::make('official_price')
    ->label('Official Price (EGP)')
    ->type('number')
    ->sortable()
    ->rules(['required', 'numeric', 'min:0'])
    ->toggleable(),

Tables\Columns\TextInputColumn::make('execution_price')
    ->label('total retail price')
    ->type('number')
    ->sortable()
    ->rules(['required', 'numeric', 'min:0'])
    ->toggleable(),

                Tables\Columns\TextColumn::make('max_selling_price')
                    ->label('retail price "5%"')
                    ->money('EGP')
                    ->sortable()
                    ->color('success')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('protection_3m_price')
                    ->label('3M Protection')
                    ->money('EGP')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\SelectColumn::make('hold_status')
                    ->label('Hold')
                    ->options([
                        'NO'           => 'No (Available)',
                        'YES'          => 'Yes (Hidden)',
                        'Wishing List' => 'Wishing List',
                        'STOP'         => 'STOP',
                    ])
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('offers_count')
                    ->label('Has Offers')
                    ->getStateUsing(fn (PriceEntry $record) => ! empty($record->offers))
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('brand')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('hold_status')
                    ->options([
                        'NO'           => 'No (Available)',
                        'YES'          => 'Yes (Hidden)',
                        'Wishing List' => 'Wishing List',
                        'STOP'         => 'STOP',
                    ]),
            ])
            ->headerActions([
                ImportAction::make()
                    ->importer(PriceEntryImporter::class)
                    ->label('Import from Excel/CSV')
                    ->excel(),

                \Filament\Actions\Action::make('export_excel')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->action(function (\Filament\Resources\Pages\ListRecords $livewire) {
                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Exports\PriceEntriesExport($livewire->getFilteredTableQuery()),
                            'price_entries.xlsx'
                        );
                    }),
            ])
            ->actions([
                Actions\Action::make('manage_colors')
                    ->label('Colors')
                    ->icon('heroicon-m-swatch')
                    ->color('info')
                    ->fillForm(fn (PriceEntry $record): array => ['available_colors' => $record->available_colors ?? []])
                    ->form([
                        Forms\Components\Repeater::make('available_colors')
                            ->schema(function () {
                                $colorMap = [
                                    'ابيض' => '#FFFFFF',
                                    'ابيض لؤلؤى' => '#F3E5AB',
                                    'احمر' => '#FF0000',
                                    'احمر دارك' => '#8B0000',
                                    'اخضر' => '#008000',
                                    'ازرق' => '#0000FF',
                                    'ازرق بيبسى' => '#004B93',
                                    'ازرق زهرى' => '#6495ED',
                                    'اسود' => '#000000',
                                    'اسود كاربون' => '#040404',
                                    'اصفر' => '#FFFF00',
                                    'برتقالى' => '#FFA500',
                                    'باذنجانى' => '#4B0082',
                                    'بترولى' => '#005F69',
                                    'بترولى غامق' => '#003F46',
                                    'بترولى فاتح' => '#00808C',
                                    'بنى' => '#A52A2A',
                                    'بنى غامق' => '#5C4033',
                                    'بنى فاتح' => '#D2B48C',
                                    'برونزى' => '#CD7F32',
                                    'موكا' => '#493D26',
                                    'بلاتينيوم' => '#E5E4E2',
                                    'بيج' => '#F5F5DC',
                                    'جرافيتى بلو' => '#2B3542',
                                    'جرانيتى' => '#676767',
                                    'ذهبى' => '#FFD700',
                                    'ذهبى غامق' => '#B8860B',
                                    'ذهبى فاتح' => '#EEE8AA',
                                    'رصاصى' => '#808080',
                                    'رمادى' => '#A9A9A9',
                                    'زهرى' => '#FFC0CB',
                                    'زيتونى' => '#808000',
                                    'زيتى' => '#556B2F',
                                    'زيتى فاتح' => '#8FBC8F',
                                    'سماوى' => '#87CEEB',
                                    'شامبين' => '#F7E7CE',
                                    'فانتوم جراى' => '#444C5C',
                                    'فضى' => '#C0C0C0',
                                    'فضى فاتح' => '#D3D3D3',
                                    'فضى مدخن' => '#708090',
                                    'فضى ميتالك' => '#AAA9AD',
                                    'فيرانى' => '#4F4F4F',
                                    'فيرانى غامق' => '#2F4F4F',
                                    'فيرانى فاتح' => '#778899',
                                    'كحلى' => '#000080',
                                    'كحلى غامق' => '#000033',
                                    'كحلى فاتح' => '#4169E1',
                                    'موف' => '#E0B0FF',
                                    'مون ستون جراى' => '#73777B',
                                    'نبيتى' => '#800000',
                                    'نبيتى غامق' => '#4A0404',
                                    'نحاسى' => '#B87333',
                                    'بستاج' => '#93C572',
                                    'بكينى' => '#F4C2C2',
                                    'تركواز' => '#40E0D0',
                                    'اسمنتى' => '#A2A2A2',
                                    'فستقى' => '#93C572',
                                    'رمادى فاتح' => '#D3D3D3',
                                    'رمادى غامق' => '#696969',
                                    'فضى غامق' => '#8C92AC',
                                    'رملى' => '#F4A460',
                                    'فيرانى مط' => '#4F4F4F',
                                    'كريمى' => '#FFFDD0',
                                    'فسدقى مط' => '#93C572',
                                ];
                                return [
                                    Forms\Components\Select::make('color_name')
                                        ->label('Color Name/Description')
                                        ->options(array_combine(array_keys($colorMap), array_keys($colorMap)))
                                        ->searchable()
                                        ->live()
                                        ->afterStateUpdated(fn ($state, \Filament\Schemas\Components\Utilities\Set $set) => $set('color_code', $colorMap[$state] ?? null))
                                        ->required()
                                        ->columnSpan(4),
                                    Forms\Components\ColorPicker::make('color_code')
                                        ->label('Color Hex Code')
                                        ->required()
                                        ->columnSpan(2),
                                    Forms\Components\TextInput::make('interior_color')
                                        ->label('Interior Color')
                                        ->placeholder('e.g. Black, Beige')
                                        ->nullable()
                                        ->columnSpan(3),
                                    Forms\Components\TextInput::make('additional_price')
                                        ->label('Price Adjustment')
                                        ->numeric()
                                        ->prefix('±')
                                        ->placeholder('Use - for discount, + for extra')
                                        ->suffix('EGP')
                                        ->nullable()
                                        ->columnSpan(3),
                                    Forms\Components\Textarea::make('notes')
                                        ->label('ملاحظات / إقرار (Notes)')
                                        ->placeholder('Enter any specific issues, scratches, or required declarations for this color...')
                                        ->rows(2)
                                        ->nullable()
                                        ->columnSpanFull(),
                                ];
                            })
                            ->columns(12)
                            ->itemLabel(fn (array $state): ?string => $state['color_name'] ?? null)
                            ->collapsible()
                            ->addActionLabel('Add Color')
                    ])
                    ->action(function (PriceEntry $record, array $data): void {
                        $record->update(['available_colors' => $data['available_colors']]);
                    }),

                Actions\Action::make('manage_offers')
                    ->label('Offers')
                    ->icon('heroicon-m-ticket')
                    ->color('warning')
                    ->fillForm(fn (PriceEntry $record): array => ['offers' => $record->offers ?? []])
                    ->form([
                        Forms\Components\Repeater::make('offers')
                            ->schema([
                                Forms\Components\Select::make('offer_template_id')
                                    ->label('Offer Template')
                                    ->options(\App\Models\OfferTemplate::where('is_active', true)->pluck('name', 'id'))
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $template = \App\Models\OfferTemplate::find($state);
                                            if ($template) {
                                                $set('id', $template->slug);
                                                $set('title', $template->name);
                                                $set('offer_type', $template->offer_type);
                                                $set('value', $template->value);
                                            }
                                        }
                                    }),
                                Forms\Components\TextInput::make('id')
                                    ->label('Offer ID (slug)')
                                    ->required()
                                    ->readOnly()
                                    ->dehydrated()
                                    ->placeholder('e.g. offer_9_percent')
                                    ->helperText('A unique machine-readable key. No spaces. (Auto-filled from template)'),
                                Forms\Components\TextInput::make('title')
                                    ->label('Title')
                                    ->required()
                                    ->placeholder('e.g. 9% Installment Offer'),
                                Forms\Components\Select::make('offer_type')
                                    ->options([
                                        'percentage' => 'Percentage (%)',
                                        'fixed' => 'Fixed Amount (EGP)'
                                    ])
                                    ->required()
                                    ->live(),
                                Forms\Components\TextInput::make('value')
                                    ->label('Value')
                                    ->numeric()
                                    ->required()
                                    ->live(),
                                Forms\Components\Placeholder::make('calculated_price')
                                    ->label('Final Offer Price')
                                    ->content(function (\Filament\Schemas\Components\Utilities\Get $get, ?\App\Models\PriceEntry $record) {
                                        $execPrice = $record ? floatval($record->execution_price) : 0;
                                        $val = floatval($get('value'));
                                        $type = $get('offer_type') ?? 'fixed';
                                        
                                        if ($type === 'percentage') {
                                            $final = $execPrice + ($execPrice * ($val / 100));
                                        } else {
                                            $final = $execPrice + $val;
                                        }
                                        
                                        return number_format($final, 2) . ' EGP';
                                    }),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->inline(false),
                                Forms\Components\Textarea::make('note')
                                    ->label('Note / Terms')
                                    ->rows(2)
                                    ->placeholder('e.g. Requires 30% down payment')
                                    ->columnSpanFull(),
                            ])
                            ->columns(4)
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->collapsible()
                            ->defaultItems(0)
                            ->addActionLabel('Add Offer')
                    ])
                    ->action(function (PriceEntry $record, array $data): void {
                        $record->update(['offers' => $data['offers']]);
                    }),

                Actions\Action::make('resolveConflict')
                    ->label('Resolve Conflict')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(function (PriceEntry $record) {
                        if (!auth()->user()->can('resolve_conflict_price_entry')) {
                            return false;
                        }

                        $columnMap = [
                            'official_price'   => 'official_price',
                            'model_name'       => 'model_name',
                            'model_sales_code' => 'model_sales_code',
                            'year'             => 'year',
                            'brand_id'         => 'brand_id',
                            'crm_hold_status'  => 'hold_status',
                        ];
                        $ignored = $record->ignored_crm_updates ?? [];
                        foreach ($columnMap as $crmField => $priceField) {
                            if ($record->car->$crmField != $record->$priceField && ($ignored[$priceField] ?? null) != $record->car->$crmField) {
                                return true;
                            }
                        }
                        return false;
                    })
                    ->modalHeading('Resolve Mismatches')
                    ->form(function (PriceEntry $record) {
                        $columnMap = [
                            'official_price'   => 'official_price',
                            'model_name'       => 'model_name',
                            'model_sales_code' => 'model_sales_code',
                            'year'             => 'year',
                            'brand_id'         => 'brand_id',
                            'crm_hold_status'  => 'hold_status',
                        ];
                        $ignored = $record->ignored_crm_updates ?? [];
                        $mismatches = [];
                        foreach ($columnMap as $crmField => $priceField) {
                            if ($record->car->$crmField != $record->$priceField && ($ignored[$priceField] ?? null) != $record->car->$crmField) {
                                $mismatches[] = ucfirst(str_replace('_', ' ', $priceField)) . ": " . ($record->$priceField ?? 'None') . " -> " . ($record->car->$crmField ?? 'None');
                            }
                        }
                        return [
                            Forms\Components\Placeholder::make('mismatch_info')
                                ->label('Discrepancies Detected')
                                ->content(implode(" | ", $mismatches)),
                            Forms\Components\Radio::make('decision')
                                ->label('Decision')
                                ->options([
                                    'approve'   => 'Option A: Approve & Sync All (Matches CRM)',
                                    'ignore'    => 'Option B: Ignore CRM Update(s)',
                                    'overwrite' => 'Option C: Overwrite CRM (Force Local Price)',
                                ])
                                ->required(),
                        ];
                    })
                    ->action(function (array $data, PriceEntry $record) {
                        $columnMap = [
                            'official_price'   => 'official_price',
                            'model_name'       => 'model_name',
                            'model_sales_code' => 'model_sales_code',
                            'year'             => 'year',
                            'brand_id'         => 'brand_id',
                            'crm_hold_status'  => 'hold_status',
                        ];
                        $ignored = $record->ignored_crm_updates ?? [];
                        
                        if ($data['decision'] === 'approve') {
                            $updates = [];
                            foreach ($columnMap as $crmField => $priceField) {
                                $updates[$priceField] = $record->car->$crmField;
                            }
                            $record->update($updates);
                            $record->update(['ignored_crm_updates' => null]);
                            
                        } elseif ($data['decision'] === 'ignore') {
                            foreach ($columnMap as $crmField => $priceField) {
                                if ($record->car->$crmField != $record->$priceField) {
                                    $ignored[$priceField] = $record->car->$crmField;
                                }
                            }
                            $record->update(['ignored_crm_updates' => $ignored]);
                            
                        } elseif ($data['decision'] === 'overwrite') {
                            $car = $record->car;
                            foreach ($columnMap as $crmField => $priceField) {
                                // Only push non-null values to the car (car fields are NOT NULL)
                                if (!is_null($record->{$priceField})) {
                                    $car->{$crmField} = $record->{$priceField};
                                } else {
                                    // If price field is null, we can't overwrite CRM. We must accept the CRM value into our PriceEntry
                                    // or ignore it. For now, let's sync the CRM value down so they match and don't loop forever.
                                    $record->update([$priceField => $car->{$crmField}]);
                                }
                            }
                            $car->save();
                            $record->update(['ignored_crm_updates' => null]);
                        }
                    })
                    ->after(function ($livewire) {
                        // Redirect back to the exact same URL (including query parameters like ?tab=...)
                        // using wire:navigate for a seamless, instant SPA refresh.
                        $livewire->redirect(request()->header('Referer'), navigate: true);
                    }),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    \Filament\Actions\ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\PriceEntryExporter::class)
                        ->label('Export (Import Template)'),
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function updateComputedPrices(Get $get, Set $set): void
    {
        $official = (float) $get('official_price');
        $execution = (float) $get('execution_price');
        $strategy = $get('pricing_strategy') ?? 'standard';
        
        if ($official > 0 && $execution > 0) {
            if ($strategy === 'all_3m') {
                $maxSelling = min($execution, $official);
            } else {
                $maxSelling = min($execution, $official * 1.05);
            }
            
            $protection3M = max(0, $execution - $maxSelling);
            
            $set('max_selling_price', $maxSelling);
            $set('protection_3m_price', $protection3M);
        } else {
            $set('max_selling_price', null);
            $set('protection_3m_price', null);
        }
    }

    public static function getRelations(): array
    {
        return [
            ActivityLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPriceEntries::route('/'),
            'create' => Pages\CreatePriceEntry::route('/create'),
            'edit'   => Pages\EditPriceEntry::route('/{record}/edit'),
        ];
    }
}