@php $current = app()->getLocale(); @endphp
<div class="flex items-center gap-x-1" style="margin-inline-end: 0.25rem;">
    @foreach(['ka' => 'ქარ', 'en' => 'ENG'] as $locale => $label)
        <a href="{{ route('admin.locale', $locale) }}"
           @class([
               'fi-btn rounded-lg px-2 py-1 text-xs font-semibold transition',
               'bg-gray-100 text-gray-950 dark:bg-white/10 dark:text-white' => $current === $locale,
               'text-gray-500 hover:text-gray-950 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-white dark:hover:bg-white/5' => $current !== $locale,
           ])>
            {{ $label }}
        </a>
    @endforeach
</div>
