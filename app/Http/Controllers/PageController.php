<?php

namespace App\Http\Controllers;

use App\Models\Page;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $view = match ($page->slug) {
            'contact' => 'pages.contact',
            default => 'pages.page',
        };

        return view($view, compact('page'));
    }
}
