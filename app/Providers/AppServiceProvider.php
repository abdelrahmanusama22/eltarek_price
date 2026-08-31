<?php

namespace App\Providers;

use App\Models\PriceEntry;
use App\Observers\PriceEntryObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

// Filament and Excel dependencies for the Macro
use Filament\Actions\ImportAction;
use Filament\Forms\Components\FileUpload;
use Livewire\Component;
use Filament\Schemas\Components\Utilities\Set;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Validation\ValidationException;
use League\Csv\Reader as CsvReader;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Filament\Actions\Imports\ImportColumn;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Select;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
        Gate::policy(\Spatie\Activitylog\Models\Activity::class, \App\Policies\ActivityPolicy::class);

        // Register the Pricing Engine Observer.
        PriceEntry::observe(PriceEntryObserver::class);

        // Implicitly grant "super_admin" role all permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        // ---------------------------------------------------------------------
        // MACRO: ImportAction::excel()
        // Converts uploaded .xlsx files to .csv on the fly to retain native Filament mapping UI!
        // ---------------------------------------------------------------------
        $excelMacro = function () {
            /** @var ImportAction|TableImportAction $this */
            $this->schema(fn ($action): array => array_merge([
                FileUpload::make('file')
                    ->label(__('filament-actions::import.modal.form.file.label'))
                    ->placeholder(__('filament-actions::import.modal.form.file.placeholder'))
                    ->acceptedFileTypes([
                        'text/csv', 
                        'application/vnd.ms-excel', 
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/csv', 
                        'text/plain'
                    ])
                    ->rules(function () use ($action) {
                        $rules = $action->getFileValidationRules();
                        // Remove the hardcoded 'extensions:csv,txt' rule that breaks .xlsx uploads
                        return array_filter($rules, fn ($rule) => ! (is_string($rule) && str_starts_with($rule, 'extensions:')));
                    })
                    ->afterStateUpdated(function (FileUpload $component, Component $livewire, Set $set, ?TemporaryUploadedFile $state) use ($action): void {
                        if (! $state instanceof TemporaryUploadedFile) {
                            return;
                        }

                        try {
                            $livewire->validateOnly($component->getStatePath());
                        } catch (ValidationException $exception) {
                            $component->state([]);
                            throw $exception;
                        }

                        $filePath = $state->getRealPath();

                        // 1. Convert XLSX to CSV on the fly!
                        $extension = strtolower($state->getClientOriginalExtension());
                        if (in_array($extension, ['xlsx', 'xls'])) {
                            try {
                                $spreadsheet = IOFactory::load($filePath);
                                $writer = IOFactory::createWriter($spreadsheet, 'Csv');
                                $writer->save($filePath); // Overwrite in place!
                            } catch (\Exception $e) {
                                // Silent fallback to native parsing attempt
                            }
                        }

                        // 2. Resume Native Filament Logic
                        $csvStream = fopen($filePath, 'r');
                        if (! $csvStream) {
                            return;
                        }

                        $csvReader = CsvReader::from($csvStream);

                        // Call protected method getCsvDelimiter using reflection closure
                        $getCsvDelimiter = fn() => $this->getCsvDelimiter($csvReader);
                        if (filled($csvDelimiter = $getCsvDelimiter->call($action))) {
                            $csvReader->setDelimiter($csvDelimiter);
                        }

                        $csvReader->setHeaderOffset($action->getHeaderOffset() ?? 0);
                        $csvColumns = $csvReader->getHeader();
                        $lowercaseCsvColumnValues = array_map(Str::lower(...), $csvColumns);
                        $lowercaseCsvColumnKeys = array_combine($lowercaseCsvColumnValues, $csvColumns);

                        $set('columnMap', array_reduce($action->getImporter()::getColumns(), function (array $carry, ImportColumn $column) use ($lowercaseCsvColumnKeys, $lowercaseCsvColumnValues) {
                            $carry[$column->getName()] = $lowercaseCsvColumnKeys[
                                Arr::first(
                                    array_intersect($lowercaseCsvColumnValues, $column->getGuesses())
                                )
                            ] ?? null;

                            return $carry;
                        }, []));
                    })
                    ->storeFiles(false)
                    ->visibility('private')
                    ->required()
                    ->hiddenLabel(),

                Fieldset::make(__('filament-actions::import.modal.form.columns.label'))
                    ->columns(1)
                    ->inlineLabel()
                    ->schema(function (Get $get) use ($action): array {
                        $csvFile = $get('file');

                        if (! $csvFile instanceof TemporaryUploadedFile) {
                            return [];
                        }

                        $csvStream = fopen($csvFile->getRealPath(), 'r');
                        if (! $csvStream) {
                            return [];
                        }

                        $csvReader = CsvReader::from($csvStream);
                        
                        $getCsvDelimiter = fn() => $this->getCsvDelimiter($csvReader);
                        if (filled($csvDelimiter = $getCsvDelimiter->call($action))) {
                            $csvReader->setDelimiter($csvDelimiter);
                        }

                        $csvReader->setHeaderOffset($action->getHeaderOffset() ?? 0);
                        $csvColumns = $csvReader->getHeader();
                        $csvColumnOptions = array_combine($csvColumns, $csvColumns);

                        return array_map(
                            fn (ImportColumn $column): Select => $column->getSelect()->options($csvColumnOptions),
                            $action->getImporter()::getColumns(),
                        );
                    })
                    ->statePath('columnMap')
                    ->visible(fn (Get $get): bool => $get('file') instanceof TemporaryUploadedFile),
            ], $action->getImporter()::getOptionsFormComponents()));

            return $this;
        };

        ImportAction::macro('excel', $excelMacro);

        // Apply this macro globally to ALL ImportAction instances in the system!
        ImportAction::configureUsing(function (ImportAction $action) {
            $action->excel();
        });
    }
}

