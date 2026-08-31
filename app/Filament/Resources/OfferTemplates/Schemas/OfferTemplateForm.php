<?php

namespace App\Filament\Resources\OfferTemplates\Schemas;

use Filament\Schemas\Schema;

class OfferTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state))),
                \Filament\Forms\Components\TextInput::make('slug')
                    ->required()
                    ->disabled()
                    ->dehydrated()
                    ->unique(ignoreRecord: true),
                \Filament\Forms\Components\Select::make('offer_type')
                    ->options([
                        'percentage' => 'Percentage (%)',
                        'fixed' => 'Fixed Amount (EGP)'
                    ])
                    ->required(),
                \Filament\Forms\Components\TextInput::make('value')
                    ->required()
                    ->numeric(),
                \Filament\Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
