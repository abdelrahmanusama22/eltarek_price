<?php

namespace App\Filament\Resources\PriceEntryResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ActivityLogsRelationManager extends RelationManager
{
    /**
     * Spatie Activitylog v4+ exposes activitiesAsSubject (MorphMany) on LogsActivity trait.
     */
    protected static string $relationship = 'activitiesAsSubject';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Audit Log';
    }

    public static function getIcon(Model $ownerRecord, string $pageClass): string|\BackedEnum|null
    {
        return 'heroicon-o-document-magnifying-glass';
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Changed By')
                    ->default('System')
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

                // Single HTML column renders a compact old→new diff table.
                // Two separate columns with the same make() key caused both
                // to read from the same state value, producing empty dashes.
                Tables\Columns\TextColumn::make('id')
                    ->label('Changes')
                    ->html()
                    ->wrap()
                    ->formatStateUsing(function ($record) {
                        $event = $record->event;
                        $changes = is_array($record->attribute_changes)
                            ? $record->attribute_changes
                            : ($record->attribute_changes?->toArray() ?? []);

                        $old   = $changes['old']        ?? [];
                        $attrs = $changes['attributes'] ?? [];

                        $formatVal = function ($v): string {
                            if (is_null($v))   return '<em class="text-gray-400">null</em>';
                            if (is_bool($v))   return $v ? '<span class="text-green-600">true</span>' : '<span class="text-red-500">false</span>';
                            if (is_array($v))  return '<code class="text-xs">' . e(json_encode($v)) . '</code>';
                            $str = (string) $v;
                            return strlen($str) > 60 ? '<span title="' . e($str) . '">' . e(substr($str, 0, 60)) . '…</span>' : e($str);
                        };

                        if ($event === 'created') {
                            if (empty($attrs)) return '<span class="text-gray-400 text-xs italic">No changes captured</span>';
                            $rows = collect($attrs)
                                ->map(fn ($v, $k) => '<tr><td class="pr-2 font-mono text-xs text-blue-600 align-top whitespace-nowrap">' . e($k) . '</td><td class="text-xs text-green-700">' . $formatVal($v) . '</td></tr>')
                                ->implode('');
                            return '<table class="text-xs w-full">' . $rows . '</table>';
                        }

                        if ($event === 'updated') {
                            $keys = collect(array_keys(array_merge($old, $attrs)))->unique()->sort();
                            if ($keys->isEmpty()) return '<span class="text-gray-400 text-xs italic">No changes captured</span>';
                            $rows = $keys->map(function ($k) use ($old, $attrs, $formatVal) {
                                $o = array_key_exists($k, $old)   ? $formatVal($old[$k])   : '<em class="text-gray-400">—</em>';
                                $n = array_key_exists($k, $attrs) ? $formatVal($attrs[$k]) : '<em class="text-gray-400">—</em>';
                                return '<tr class="border-b border-gray-100"><td class="pr-2 font-mono text-xs text-blue-600 align-top whitespace-nowrap">' . e($k) . '</td><td class="text-xs text-red-500 pr-2 align-top">' . $o . '</td><td class="text-xs align-top">→</td><td class="text-xs text-green-700 pl-2 align-top">' . $n . '</td></tr>';
                            })->implode('');
                            return '<table class="text-xs w-full">' . $rows . '</table>';
                        }

                        if ($event === 'deleted') {
                            if (empty($old)) return '<span class="text-gray-400 text-xs italic">No changes captured</span>';
                            $rows = collect($old)
                                ->map(fn ($v, $k) => '<tr><td class="pr-2 font-mono text-xs text-blue-600 align-top whitespace-nowrap">' . e($k) . '</td><td class="text-xs text-red-500">' . $formatVal($v) . '</td></tr>')
                                ->implode('');
                            return '<table class="text-xs w-full">' . $rows . '</table>';
                        }

                        return '<span class="text-gray-400 text-xs italic">—</span>';
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
