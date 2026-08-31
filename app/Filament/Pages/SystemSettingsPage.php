<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class SystemSettingsPage extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.system-settings-page';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public static function getNavigationLabel(): string
    {
        return 'Portal Settings';
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Sales Portal Visibility Settings';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Administration';
    }

    public static function getNavigationSort(): ?int
    {
        return 99;
    }



    // -------------------------------------------------------------------------
    // Form State (bound to component properties via Livewire)
    // -------------------------------------------------------------------------

    public bool $show_official_price      = true;
    public bool $show_max_selling_price   = true;
    public bool $show_execution_price     = true;
    public bool $show_3m_protection_price = true;
    public bool $show_total_calculation   = true;
    public bool $show_availability_status = true;
    public bool $show_special_offers      = true;

    public function mount(): void
    {
        $settings = SystemSetting::getSalesPortalSettings();

        $this->show_official_price      = (bool) ($settings['show_official_price']      ?? true);
        $this->show_max_selling_price   = (bool) ($settings['show_max_selling_price']   ?? true);
        $this->show_execution_price     = (bool) ($settings['show_execution_price']     ?? true);
        $this->show_3m_protection_price = (bool) ($settings['show_3m_protection_price'] ?? true);
        $this->show_total_calculation   = (bool) ($settings['show_total_calculation']   ?? true);
        $this->show_availability_status = (bool) ($settings['show_availability_status'] ?? true);
        $this->show_special_offers      = (bool) ($settings['show_special_offers']      ?? true);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Sales Portal Visibility Toggles')
                    ->description('Control which pricing components are displayed to Sales Representatives on the portal.')
                    ->schema([
                        \Filament\Forms\Components\Toggle::make('show_official_price')
                            ->label('Show Official Price')
                            ->helperText('Display the Official Price row.')
                            ->inline(false),

                        \Filament\Forms\Components\Toggle::make('show_max_selling_price')
                            ->label('Show Max Selling Price')
                            ->helperText('Display the Max Selling Price row.')
                            ->inline(false),

                        \Filament\Forms\Components\Toggle::make('show_execution_price')
                            ->label('Show Execution Price')
                            ->helperText('Display the Execution Price row.')
                            ->inline(false),

                        \Filament\Forms\Components\Toggle::make('show_3m_protection_price')
                            ->label('Show 3M Protection Price')
                            ->helperText('Display the 3M protection breakdown row.')
                            ->inline(false),

                        \Filament\Forms\Components\Toggle::make('show_total_calculation')
                            ->label('Show Total Calculation')
                            ->helperText('Display the Total Calculation row.')
                            ->inline(false),

                        \Filament\Forms\Components\Toggle::make('show_availability_status')
                            ->label('Show Availability Status')
                            ->helperText('Display the Hold Status / Availability section.')
                            ->inline(false),

                        \Filament\Forms\Components\Toggle::make('show_special_offers')
                            ->label('Show Special Offers')
                            ->helperText('Display active installment and promotional offer cards.')
                            ->inline(false),
                    ])->columns(3),
            ])
            ->statePath('');  // Binds directly to public component properties
    }

    public function save(): void
    {
        SystemSetting::updateOrCreate(
            ['key' => 'sales_portal_settings'],
            [
                'payload' => [
                    'show_official_price'        => $this->show_official_price,
                    'show_max_selling_price'     => $this->show_max_selling_price,
                    'show_execution_price'       => $this->show_execution_price,
                    'show_3m_protection_price'   => $this->show_3m_protection_price,
                    'show_total_calculation'     => $this->show_total_calculation,
                    'show_availability_status'   => $this->show_availability_status,
                    'show_special_offers'        => $this->show_special_offers,
                ],
            ]
        );

        SystemSetting::clearSalesPortalSettingsCache();

        Notification::make()
            ->title('Settings saved successfully.')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Save Settings')
                ->submit('save'),
        ];
    }
}
