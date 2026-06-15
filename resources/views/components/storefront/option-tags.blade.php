@props(['options' => []])
@if(!empty($options))
    <div {{ $attributes->merge(['class' => 'mt-1 flex flex-wrap gap-x-3 gap-y-0.5']) }}>
        @foreach($options as $opt)
            <span class="text-xs text-ink-muted">
                <span class="text-ink-faint">{{ $opt['attribute_name'] }}:</span>
                {{ $opt['value_name'] }}
            </span>
        @endforeach
    </div>
@endif
