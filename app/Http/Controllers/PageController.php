<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\SiteSetting;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        if ($page->slug === 'contact') {
            return view('pages.contact', [
                'page' => $page,
                'settings' => SiteSetting::current(),
            ]);
        }

        return view('pages.page', compact('page'));
    }
}
