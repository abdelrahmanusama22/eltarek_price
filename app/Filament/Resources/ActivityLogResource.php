<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use Spatie\Activitylog\Models\Activity;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use App\Models\Brand;
use App\Models\Car;
use App\Models\PriceEntry;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Activity Logs';
    protected static ?string $slug = 'activity-logs';
    protected static \UnitEnum|string|null $navigationGroup = 'System Management';

    public static function canViewAny(): bool
    {
        return auth()->user()->can('ViewAny:Activity');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Changed By')
                    ->default('System')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Model')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject_id')
                    ->label('Record ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default   => 'gray',
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Inline diff summary — shows changed fields without requiring a View click.
                Tables\Columns\TextColumn::make('id')
                    ->label('Changes Summary')
                    ->wrap()
                    ->html()
                    ->formatStateUsing(function ($record) {
                        $event = $record->event;

                        // Normalize attribute_changes — Spatie casts it as Collection
                        $raw = $record->attribute_changes;
                        if ($raw instanceof \Illuminate\Support\Collection) {
                            $changes = $raw->toArray();
                        } elseif (is_array($raw)) {
                            $changes = $raw;
                        } elseif (is_string($raw)) {
                            $changes = json_decode($raw, true) ?? [];
                        } else {
                            $changes = [];
                        }

                        $old   = $changes['old']        ?? [];
                        $attrs = $changes['attributes'] ?? [];

                        $renderVal = function ($v): string {
                            if (is_null($v))   return '<em class="text-gray-400">null</em>';
                            if (is_bool($v))   return $v ? 'true' : 'false';
                            if (is_array($v))  return json_encode($v);
                            $s = (string) $v;
                            return strlen($s) > 40 ? substr($s, 0, 40) . '…' : $s;
                        };

                        if ($event === 'created') {
                            $count = count($attrs);
                            if ($count === 0) return '<span class="text-gray-400 text-xs italic">New record (no attributes captured)</span>';
                            $preview = collect($attrs)->take(3)->map(fn ($v, $k) => '<span class="font-mono text-xs text-blue-600">' . e($k) . '</span> = ' . e($renderVal($v)))->implode(', ');
                            $more = $count > 3 ? ' <span class="text-gray-400 text-xs">+' . ($count - 3) . ' more</span>' : '';
                            return '<span class="text-green-700 font-semibold text-xs mr-1">✦ Created:</span>' . $preview . $more;
                        }

                        if ($event === 'deleted') {
                            $count = count($old);
                            if ($count === 0) return '<span class="text-red-600 font-semibold text-xs">✦ Deleted</span>';
                            return '<span class="text-red-600 font-semibold text-xs">✦ Deleted</span> <span class="text-gray-400 text-xs">(' . $count . ' attributes removed)</span>';
                        }

                        if ($event === 'updated') {
                            // Only show keys that actually changed
                            $diffs = collect(array_keys(array_merge($old, $attrs)))
                                ->unique()
                                ->filter(fn ($k) => array_key_exists($k, $old) && array_key_exists($k, $attrs) && $old[$k] !== $attrs[$k])
                                ->values();

                            if ($diffs->isEmpty()) {
                                // Fallback: show all keys if nothing detected as different
                                $diffs = collect(array_keys(array_merge($old, $attrs)))->unique()->values();
                            }

                            if ($diffs->isEmpty()) {
                                return '<span class="text-gray-400 text-xs italic">Updated (no changes captured)</span>';
                            }

                            $total   = $diffs->count();
                            $preview = $diffs->take(2)->map(function ($k) use ($old, $attrs, $renderVal) {
                                $o = array_key_exists($k, $old)   ? e($renderVal($old[$k]))   : '<em class="text-gray-400">—</em>';
                                $n = array_key_exists($k, $attrs) ? e($renderVal($attrs[$k])) : '<em class="text-gray-400">—</em>';
                                return '<span class="font-mono text-xs text-blue-600">' . e($k) . '</span>: '
                                     . '<span class="text-red-500 line-through">' . $o . '</span> → '
                                     . '<span class="text-green-700 font-semibold">' . $n . '</span>';
                            })->implode('<span class="text-gray-300 mx-1">|</span>');

                            $more = $total > 2 ? ' <span class="text-gray-400 text-xs">+' . ($total - 2) . ' more</span>' : '';

                            return $preview . $more;
                        }

                        return '<span class="text-gray-400 text-xs italic">—</span>';
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                    ]),
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Model Type')
                    ->options([
                        Brand::class      => 'Brand',
                        Car::class        => 'Car',
                        PriceEntry::class => 'Price Entry',
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Activity Details')
                    ->schema([
                        TextEntry::make('causer.name')->label('Changed By')->default('System'),
                        TextEntry::make('subject_type')->label('Model')->formatStateUsing(fn ($state) => class_basename($state ?? '')),
                        TextEntry::make('subject_id')->label('Record ID'),
                        TextEntry::make('event')->label('Event')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'created' => 'success',
                                'updated' => 'warning',
                                'deleted' => 'danger',
                                default   => 'gray',
                            }),
                        TextEntry::make('created_at')->dateTime()->label('Timestamp'),
                    ])->columns(2),

                Section::make('Changes')
                    ->schema([
                        TextEntry::make('attribute_changes')
                            ->label('')
                            ->html()
                            ->columnSpanFull()
                            ->formatStateUsing(function ($record) {
                                $event = $record->event;

                                // attribute_changes is cast as Collection by Spatie — is_array() always returns false.
                                // Normalize to a plain PHP array regardless of what type arrives.
                                $raw = $record->attribute_changes;
                                if ($raw instanceof \Illuminate\Support\Collection) {
                                    $changes = $raw->toArray();
                                } elseif (is_array($raw)) {
                                    $changes = $raw;
                                } elseif (is_string($raw)) {
                                    $changes = json_decode($raw, true) ?? [];
                                } else {
                                    $changes = [];
                                }

                                $old   = $changes['old']        ?? [];
                                $attrs = $changes['attributes'] ?? [];

                                // Union of all keys across both sets
                                $keys = collect(array_keys($old + $attrs))->unique()->sort()->values();

                                if ($keys->isEmpty()) {
                                    return '<p class="text-gray-400 italic text-sm py-2">No attribute changes recorded.</p>';
                                }

                                $formatValue = function ($v): string {
                                    if (is_null($v))   return '<span class="text-gray-400 italic">null</span>';
                                    if ($v === true)   return '<span class="text-green-600 font-semibold">true</span>';
                                    if ($v === false)  return '<span class="text-red-600 font-semibold">false</span>';
                                    if (is_array($v))  return '<code class="text-xs bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded break-all">' . e(json_encode($v, JSON_UNESCAPED_UNICODE)) . '</code>';
                                    return '<span class="break-words">' . e((string) $v) . '</span>';
                                };

                                $showOld = in_array($event, ['updated', 'deleted']);
                                $showNew = in_array($event, ['updated', 'created']);

                                $headerCols  = '<th class="text-left py-2 px-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 w-1/4">Attribute</th>';
                                if ($showOld) $headerCols .= '<th class="text-left py-2 px-3 text-xs font-semibold uppercase tracking-wider text-red-500 bg-gray-50 dark:bg-gray-800">Old Value</th>';
                                if ($showNew) $headerCols .= '<th class="text-left py-2 px-3 text-xs font-semibold uppercase tracking-wider text-green-600 bg-gray-50 dark:bg-gray-800">New Value</th>';

                                $rows = $keys->map(function ($key) use ($old, $attrs, $showOld, $showNew, $formatValue, $event) {
                                    $oldVal   = array_key_exists($key, $old)   ? $old[$key]   : null;
                                    $newVal   = array_key_exists($key, $attrs) ? $attrs[$key] : null;
                                    $changed  = $event === 'updated' && $oldVal !== $newVal;
                                    $rowBg    = $changed ? 'bg-amber-50 dark:bg-amber-900/10' : 'bg-white dark:bg-gray-900';

                                    $cells  = '<td class="py-2 px-3 font-mono text-xs font-semibold text-blue-700 dark:text-blue-400 border-r border-gray-100 dark:border-gray-700 align-top">' . e($key) . '</td>';
                                    if ($showOld) $cells .= '<td class="py-2 px-3 text-sm align-top">' . $formatValue($oldVal) . '</td>';
                                    if ($showNew) $cells .= '<td class="py-2 px-3 text-sm align-top">' . $formatValue($newVal) . '</td>';

                                    return "<tr class=\"border-b border-gray-100 dark:border-gray-700 {$rowBg}\">{$cells}</tr>";
                                })->implode('');

                                return <<<HTML
                                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm mt-1">
                                    <table class="w-full text-sm border-collapse">
                                        <thead><tr>{$headerCols}</tr></thead>
                                        <tbody>{$rows}</tbody>
                                    </table>
                                </div>
                                HTML;
                            }),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (!$user) {
            return $query->whereNull('id');
        }

        // Super Admins see everything
        if ($user->isSuperAdmin()) {
            return $query;
        }

        // Brand Managers see logs for their assigned brands
        if ($user->isBrandManager()) {
            $brandIds = $user->brands->pluck('id')->toArray();

            if (empty($brandIds)) {
                // No brands assigned — show only their own actions
                return $query->where('causer_id', $user->id)
                             ->where('causer_type', \App\Models\User::class);
            }

            return $query->where(function (Builder $q) use ($brandIds) {
                // 1. Logs where the subject IS a Brand they manage
                $q->orWhere(function ($sub) use ($brandIds) {
                    $sub->where('subject_type', Brand::class)
                        ->whereIn('subject_id', $brandIds);
                });

                // 2. Logs where the subject is a Car belonging to their brands
                //    Data lives in attribute_changes column (not properties)
                $q->orWhere(function ($sub) use ($brandIds) {
                    $sub->where('subject_type', Car::class)
                        ->where(function ($inner) use ($brandIds) {
                            foreach ($brandIds as $id) {
                                $inner->orWhereJsonContains('attribute_changes->attributes->brand_id', (int) $id)
                                      ->orWhereJsonContains('attribute_changes->old->brand_id', (int) $id);
                            }
                        });
                });

                // 3. Logs where the subject is a PriceEntry belonging to their brands
                $q->orWhere(function ($sub) use ($brandIds) {
                    $sub->where('subject_type', PriceEntry::class)
                        ->where(function ($inner) use ($brandIds) {
                            foreach ($brandIds as $id) {
                                $inner->orWhereJsonContains('attribute_changes->attributes->brand_id', (int) $id)
                                      ->orWhereJsonContains('attribute_changes->old->brand_id', (int) $id);
                            }
                        });
                });
            });
        }

        // Failsafe: unrecognized role sees nothing
        return $query->whereNull('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view'  => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
}
