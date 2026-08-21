@php
    // Built up-front, as a plain variable: @json() with a nested expression
    // inline can truncate mid-expression during Blade compilation — a bare
    // variable reference is unambiguous.
    $selectJsConfig = [
        'colorScheme' => config('select.color_scheme'),
        'dropdownEvents' => [
            'opened' => config('select.dropdown_events.opened', 'modal-overlay-opened'),
            'closed' => config('select.dropdown_events.closed', 'modal-overlay-closed'),
        ],
    ];
@endphp

<script>
    window.__SELECT_CONFIG__ = @json($selectJsConfig);
</script>

@if (config('select.assets.serve', true) && \Illuminate\Support\Facades\Route::has('select.assets.css'))
    <link rel="stylesheet" href="{{ route('select.assets.css') }}">
@endif

@if (config('select.assets.serve', true) && \Illuminate\Support\Facades\Route::has('select.assets.js'))
    <script defer src="{{ route('select.assets.js') }}"></script>
@endif
