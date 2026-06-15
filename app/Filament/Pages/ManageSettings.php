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
        $settings = SiteSetting::current();

        $this->form->fill([
            'logo' => $settings->logo,
            'meta_title' => $settings->meta_title,
            'meta_description' => $settings->meta_description,
            'contact_phone' => $settings->contact_phone,
            'contact_email' => $settings->contact_email,
            'whatsapp' => $settings->whatsapp,
            'social_links' => $settings->social_links ?? [],
            'locations' => $settings->locations ?? [],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Branding'))
                    ->description(__('Logo shown in the storefront header.'))
                    ->schema([
                        Forms\Components\FileUpload::make('logo')
                            ->label(__('Logo'))
                            ->image()
                            ->disk('public')
                            ->directory('branding')
                            ->visibility('public')
                            ->imageEditor()
                            ->maxSize(2048)
                            ->helperText(__('PNG or SVG with transparent background works best. Leave empty to show the text logo.')),
                    ]),

                Forms\Components\Section::make(__('SEO'))
                    ->description(__('Used in the browser tab and by search engines / social shares.'))
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->label(__('Meta title'))
                            ->maxLength(255)
                            ->helperText(__('Site name shown in the browser tab and search results.')),
                        Forms\Components\Textarea::make('meta_description')
                            ->label(__('Meta description'))
                            ->rows(2)
                            ->maxLength(255)
                            ->helperText(__('Short summary (up to ~160 characters) shown in search results.')),
                    ]),

                Forms\Components\Section::make(__('Contact details'))
                    ->description(__('Shown on the contact page and in the footer.'))
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('contact_phone')->label(__('Phone'))->tel()->maxLength(255),
                            Forms\Components\TextInput::make('contact_email')->label(__('Email'))->email()->maxLength(255),
                            Forms\Components\TextInput::make('whatsapp')->label(__('WhatsApp'))->tel()->maxLength(255)
                                ->helperText(__('Number with country code, e.g. 995599…')),
                        ]),
                    ]),

                Forms\Components\Section::make(__('Social links'))
                    ->description(__('Add a URL and the icon appears in the footer. Leave empty to hide it.'))
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('social_links.facebook')->label('Facebook')->url()->placeholder('https://facebook.com/…'),
                            Forms\Components\TextInput::make('social_links.instagram')->label('Instagram')->url()->placeholder('https://instagram.com/…'),
                            Forms\Components\TextInput::make('social_links.youtube')->label('YouTube')->url()->placeholder('https://youtube.com/…'),
                            Forms\Components\TextInput::make('social_links.tiktok')->label('TikTok')->url()->placeholder('https://tiktok.com/@…'),
                            Forms\Components\TextInput::make('social_links.telegram')->label('Telegram')->url()->placeholder('https://t.me/…'),
                            Forms\Components\TextInput::make('social_links.linkedin')->label('LinkedIn')->url()->placeholder('https://linkedin.com/…'),
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
