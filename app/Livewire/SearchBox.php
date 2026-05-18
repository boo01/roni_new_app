<?php

namespace App\Livewire;

use App\Models\Product;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

class SearchBox extends Component
{
    #[Url(as: 'q', except: '')]
    public string $query = '';

    public bool $open = false;

    public function updatedQuery(): void
    {
        $this->open = mb_strlen(trim($this->query), 'UTF-8') >= 3;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function results(): Collection
    {
        $q = trim($this->query);
        if (mb_strlen($q, 'UTF-8') < 3) {
            return collect();
        }

        $like = '%' . $q . '%';

        return Product::query()
            ->where('is_active', true)
            ->where(function ($w) use ($like) {
                $w->where('name_ka', 'like', $like)
                    ->orWhere('sku', 'like', $like);
            })
            ->with(['media', 'groupPrices'])
            ->orderByRaw('CASE WHEN sku LIKE ? THEN 0 ELSE 1 END', [$like])
            ->orderBy('name_ka')
            ->take(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.search-box', [
            'results' => $this->results(),
        ]);
    }
}
