<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesResource\Pages;
use App\Models\SalesUser;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class SalesResource extends Resource
{
    protected static ?string $model = SalesUser::class;

    protected static ?string $modelLabel = 'Sales Representative';
    protected static ?string $pluralModelLabel = 'Sales Representatives';
    protected static ?string $slug = 'sales';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-presentation-chart-line';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Administration';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Schemas\Components\Section::make('Sales Representative Details')->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\TextInput::make('username')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->dehydrateStateUsing(fn (string $state) => Hash::make($state))
                    ->label(fn (string $operation) => $operation === 'create' ? 'Password' : 'New Password (leave blank to keep current)'),

                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(20),

                Forms\Components\Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),

                Forms\Components\Toggle::make('is_approved')
                    ->label('Approved')
                    ->default(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_approved')
                    ->boolean()
                    ->label('Approved')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('Approval Status')
                    ->boolean(),
            ])
            ->actions([
                Action::make('approve')
                    ->action(function (SalesUser $record) {
                        $record->update(['is_approved' => true]);
                        Notification::make()
                            ->title('Approved successfully')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->hidden(fn (SalesUser $record) => (bool) $record->is_approved)
                    ->color('success')
                    ->icon('heroicon-o-check-circle'),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSales::route('/'),
            'create' => Pages\CreateSales::route('/create'),
            'edit'   => Pages\EditSales::route('/{record}/edit'),
        ];
    }
}