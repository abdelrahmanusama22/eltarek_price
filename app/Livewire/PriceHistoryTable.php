<?php

namespace App\Livewire;

use App\Exports\PriceHistoryExport;
use App\Models\PriceEntry;
use App\Support\PriceHistory;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;

class PriceHistoryTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public int $recordId;

    public function mount(int $recordId): void
    {
        $this->recordId = $recordId;
    }

    protected function getRecord(): PriceEntry
    {
        // Resolved through the resource's scoped query so brand scoping still applies.
        return \App\Filament\Resources\PriceEntryResource::getEloquentQuery()
            ->with('car.brand')
            ->findOrFail($this->recordId);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $record = $this->getRecord();

                return Activity::query()
                    ->where('subject_type', $record->getMorphClass())
                    // History follows the vehicle, not the row id: it survives hard-delete + re-upload.
                    ->where(function (Builder $q) use ($record) {
                        $q->where('subject_id', $record->getKey());

                        if (filled($record->model_sales_code)) {
                            $q->orWhere('properties->model_sales_code', $record->model_sales_code);
                        }

                        if (filled($record->crm_id)) {
                            $q->orWhere('properties->crm_id', (string) $record->crm_id);
                        }
                    })
                    ->with('causer')
                    // Only saves that touched at least one price field.
                    ->where(function (Builder $q) {
                        foreach (array_keys(PriceHistory::FIELDS) as $field) {
                            $q->orWhere('attribute_changes', 'like', '%"' . $field . '"%');
                        }
                    });
            })

            ->columns([
                TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('Y-m-d h:i:s A')
                    ->sortable(),

                TextColumn::make('causer.name')
                    ->label('User')
                    ->default('System')
                    ->description(fn (Activity $record): ?string => $record->causer?->email),

                TextColumn::make('field')
                    ->label('Field / Modification')
                    ->state(fn (Activity $record): string => $this->cells($record, fn ($c) => e($c['label'])))
                    ->html(),

                TextColumn::make('recorded_price')
                    ->label('Recorded Price')
                    ->state(fn (Activity $record): string => $this->cells(
                        $record,
                        fn ($c) => '<strong>' . PriceHistory::money($c['price']) . ' EGP</strong>'
                    ))
                    ->html()
                    ->alignEnd(),
            ])

            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->striped()
            ->filters([
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('from')->label('From Date'),
                        DatePicker::make('until')->label('To Date'),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'From ' . \Carbon\Carbon::parse($data['from'])->toFormattedDateString();
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Until ' . \Carbon\Carbon::parse($data['until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->headerActions([
                Action::make('export_excel')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        // getFilteredTableQuery() already contains the active date filter.
                        $query = $this->getFilteredTableQuery();

                        return Excel::download(
                            new PriceHistoryExport($query),
                            'price_history_' . $this->recordId . '_' . now()->format('Y-m-d_His') . '.xlsx'
                        );
                    }),
            ])
            ->emptyStateHeading('No price changes found')
            ->emptyStateDescription('Try widening the date range.')
            ->emptyStateIcon('heroicon-o-clock');
    }

    /**
     * Renders one line per recorded price field, so multi-field saves stay aligned across columns.
     */
    protected function cells(Activity $record, \Closure $render): string
    {
        return collect(PriceHistory::recordedPrices($record))
            ->map(fn (array $item): string => '<div style="padding:2px 0;white-space:nowrap">' . $render($item) . '</div>')
            ->implode('');
    }


    public function render(): View
    {
        return view('livewire.price-history-table', [
            'record' => $this->getRecord(),
        ]);
    }
}
