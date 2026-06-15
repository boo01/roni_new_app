<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page as FilamentPage;
use Illuminate\Support\Facades\DB;

class ManageMenu extends FilamentPage implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3';

    protected static ?int $navigationSort = 11;

    protected static string $view = 'filament.pages.manage-menu';

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('Menu');
    }

    public function getTitle(): string
    {
        return __('Header menu');
    }

    public function mount(): void
    {
        $this->form->fill(['items' => $this->loadItems()]);
    }

    /** @return array<int, array> */
    private function loadItems(): array
    {
        return MenuItem::query()
            ->where('location', 'header')
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (MenuItem $item) => $this->itemToArray($item, true))
            ->all();
    }

    private function itemToArray(MenuItem $item, bool $withChildren): array
    {
        $data = [
            'type' => $item->type,
            'category_id' => $item->category_id,
            'page_id' => $item->page_id,
            'url' => $item->url,
            'label' => $item->label,
            'target_blank' => $item->target_blank,
            'is_active' => $item->is_active,
        ];

        if ($withChildren) {
            $data['children'] = $item->children->map(fn ($c) => $this->itemToArray($c, false))->all();
        }

        return $data;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Repeater::make('items')
                    ->hiddenLabel()
                    ->schema([
                        ...self::itemFields(),
                        Forms\Components\Repeater::make('children')
                            ->label(__('Sub-items'))
                            ->schema(self::itemFields())
                            ->columns(2)
                            ->reorderable()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => self::previewLabel($state))
                            ->addActionLabel(__('Add sub-item'))
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->reorderable()
                    ->collapsed()
                    ->itemLabel(fn (array $state): ?string => self::previewLabel($state))
                    ->addActionLabel(__('Add menu item'))
                    ->defaultItems(0),
            ])
            ->statePath('data');
    }

    /** @return array<\Filament\Forms\Components\Component> */
    private static function itemFields(): array
    {
        return [
            Forms\Components\Select::make('type')
                ->label(__('Type'))
                ->options([
                    'category' => __('Category'),
                    'page' => __('Page'),
                    'link' => __('Custom link'),
                ])
                ->default('category')
                ->required()
                ->live(),

            Forms\Components\Select::make('category_id')
                ->label(__('Category'))
                ->options(fn () => Category::orderBy('name_ka')->get()->mapWithKeys(function (Category $c): array {
                    $label = $c->name_ka;
                    if (! $c->visible_to_retail) {
                        $label .= $c->visible_to_b2b ? ' — ' . __('B2B only') : ' — ' . __('Hidden');
                    }

                    return [$c->id => $label];
                }))
                ->searchable()
                ->visible(fn (Forms\Get $get) => $get('type') === 'category')
                ->required(fn (Forms\Get $get) => $get('type') === 'category'),

            Forms\Components\Select::make('page_id')
                ->label(__('Page'))
                ->options(fn () => Page::orderBy('title_ka')->pluck('title_ka', 'id'))
                ->searchable()
                ->visible(fn (Forms\Get $get) => $get('type') === 'page')
                ->required(fn (Forms\Get $get) => $get('type') === 'page'),

            Forms\Components\TextInput::make('url')
                ->label(__('URL'))
                ->placeholder('https://…')
                ->visible(fn (Forms\Get $get) => $get('type') === 'link')
                ->required(fn (Forms\Get $get) => $get('type') === 'link')
                ->maxLength(255),

            Forms\Components\TextInput::make('label')
                ->label(__('Label (optional)'))
                ->helperText(__('Leave empty to use the page/category name.'))
                ->maxLength(255),

            Forms\Components\Toggle::make('target_blank')
                ->label(__('Open in new tab'))
                ->default(false),

            Forms\Components\Toggle::make('is_active')
                ->label(__('Active'))
                ->default(true),
        ];
    }

    private static function previewLabel(array $state): string
    {
        $type = $state['type'] ?? null;
        $category = $type === 'category' ? Category::find($state['category_id'] ?? null) : null;

        $base = filled($state['label'] ?? null)
            ? $state['label']
            : match ($type) {
                'category' => $category?->name_ka,
                'page' => Page::find($state['page_id'] ?? null)?->title_ka,
                'link' => $state['url'] ?? null,
                default => null,
            };

        $base = $base ?: __('Menu item');

        // Flag items that retail visitors won't see, so the mismatch between
        // this list and the public header is obvious.
        if ($category && ! $category->visible_to_retail) {
            $base .= $category->visible_to_b2b ? ' — ' . __('B2B only') : ' — ' . __('Hidden');
        }

        return $base;
    }

    public function save(): void
    {
        $state = $this->form->getState();

        DB::transaction(function () use ($state) {
            MenuItem::where('location', 'header')->delete();

            $order = 0;
            foreach ($state['items'] ?? [] as $item) {
                $parent = MenuItem::create($this->itemAttributes($item, null, $order++));

                $childOrder = 0;
                foreach ($item['children'] ?? [] as $child) {
                    MenuItem::create($this->itemAttributes($child, $parent->id, $childOrder++));
                }
            }
        });

        Notification::make()->success()->title(__('Saved'))->send();
    }

    private function itemAttributes(array $item, ?int $parentId, int $order): array
    {
        $type = $item['type'] ?? 'link';

        return [
            'parent_id' => $parentId,
            'location' => 'header',
            'type' => $type,
            'label' => $item['label'] ?? null,
            'page_id' => $type === 'page' ? ($item['page_id'] ?? null) : null,
            'category_id' => $type === 'category' ? ($item['category_id'] ?? null) : null,
            'url' => $type === 'link' ? ($item['url'] ?? null) : null,
            'target_blank' => (bool) ($item['target_blank'] ?? false),
            'is_active' => (bool) ($item['is_active'] ?? true),
            'sort_order' => $order,
        ];
    }
}
