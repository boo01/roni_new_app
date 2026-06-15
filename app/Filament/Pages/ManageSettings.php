<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('Settings');
    }

    public function getTitle(): string
    {
        return __('Settings');
    }

    public function mount(): void
    {
        $this->form->fill(SiteSetting::current()->only(['contact_phone', 'contact_email', 'locations']));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Contact details'))
                    ->description(__('Shown on the contact page and used across the storefront.'))
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('contact_phone')->label(__('Phone'))->tel()->maxLength(255),
                            Forms\Components\TextInput::make('contact_email')->label(__('Email'))->email()->maxLength(255),
                        ]),
                    ]),

                Forms\Components\Section::make(__('Locations'))
                    ->description(__('Branches shown on the contact page. Add as many as you need.'))
                    ->schema([
                        Forms\Components\Repeater::make('locations')
                            ->hiddenLabel()
                            ->schema([
                                Forms\Components\TextInput::make('name')->label(__('Branch name'))->required()->maxLength(255),
                                Forms\Components\TextInput::make('address')->label(__('Address'))->required()->maxLength(255),
                                Forms\Components\TextInput::make('phone')->label(__('Phone'))->tel()->maxLength(255),
                                Forms\Components\Textarea::make('embed_url')
                                    ->label(__('Google Maps embed URL (src from the iframe)'))
                                    ->rows(2)
                                    ->helperText(__('On maps.google.com: Share → Embed a map → copy the src="..." value from the iframe.')),
                            ])
                            ->columns(2)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->addActionLabel(__('Add location'))
                            ->defaultItems(0),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        SiteSetting::current()->update($this->form->getState());

        Notification::make()->success()->title(__('Saved'))->send();
    }
}
