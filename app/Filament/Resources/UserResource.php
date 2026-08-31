<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Illuminate\Support\Facades\Hash;
use Filament\Schemas\Components\Utilities\Get;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Administration';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }


    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Schemas\Components\Section::make('User Details')->schema([
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

                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->required()
                    ->native(false)
                    ->live(),

                Forms\Components\Select::make('brands')
                    ->relationship('brands', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Assigned Brands')
                    ->helperText('Select which brands this user can access (primarily for Brand Managers).'),
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

                Tables\Columns\TextColumn::make('username')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin'   => 'danger',
                        'Brand Manager' => 'warning',
                        'sales'         => 'success',
                        default         => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'super_admin'   => 'Super Admin',
                        'Brand Manager' => 'Brand Manager',
                        'sales'         => 'Sales Rep',
                        default         => str($state)->title(),
                    }),

                Tables\Columns\TextColumn::make('brands.name')
                    ->label('Authorized Brands')
                    ->badge()
                    ->separator(','),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name'),
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
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
