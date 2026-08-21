<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Option keys
    |--------------------------------------------------------------------------
    |
    | The default keys read off each entry of an :options array — override
    | per instance via <x-select::select option-label="name" option-value="id">
    | when your data doesn't already use "label"/"value".
    |
    */
    'option_label' => 'label',
    'option_value' => 'value',

    /*
    | The key an option carries when it belongs to a group — e.g.
    | ['label' => 'Apple', 'value' => 1, 'group' => 'Fruits']. Left null by
    | default (no grouping); set globally here or per instance via
    | option-group="category". Ungrouped entries render without a header,
    | ahead of any grouped ones.
    */
    'option_group' => null,

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    |
    | Debounce, in milliseconds, before an async select (:async="true") calls
    | its search-method over the wire. Client-side search (:searchable="true"
    | without :async) filters an already-loaded array locally and ignores
    | this entirely.
    |
    */
    'search_debounce' => 300,

    /*
    |--------------------------------------------------------------------------
    | Text / copy
    |--------------------------------------------------------------------------
    |
    | Every hardcoded piece of UI chrome text, so it can be translated or
    | reworded globally without touching a single Blade file. Anything left
    | unset keeps its built-in French default. A single instance can still
    | override any of these with its own prop (:placeholder="...", etc.).
    |
    */
    'strings' => [
        'placeholder' => 'Sélectionner...',
        'search_placeholder' => 'Rechercher...',
        'no_results' => 'Aucun résultat',
        'select_all' => 'Tout',
        'clear_all' => 'Aucun',
        'searching' => 'Recherche...',
        'create' => 'Créer',
        'clear' => 'Effacer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Nested inside a Modal
    |--------------------------------------------------------------------------
    |
    | A select's dropdown panel is teleported to <body> to escape any
    | ancestor's overflow clipping, which also puts it outside a Modal's
    | focus trap the moment one is active — see github.com/salioudiabate/modal.
    | These event names match Modal's own config('modal.dropdown_events')
    | defaults exactly, so a select just works inside a Modal dialog with
    | zero configuration on either side. Only change these if you already
    | renamed Modal's own dropdown_events to match a different convention.
    |
    */
    'dropdown_events' => [
        'opened' => 'modal-overlay-opened',
        'closed' => 'modal-overlay-closed',
    ],

    /*
    |--------------------------------------------------------------------------
    | Color scheme
    |--------------------------------------------------------------------------
    |
    | null (default) follows the visitor's OS/browser preference
    | (prefers-color-scheme). Set to 'light' or 'dark' to force it site-wide
    | — or call Select.setColorScheme('dark'|'light'|'system') client-side;
    | that persists across reloads via localStorage and takes precedence.
    |
    */
    'color_scheme' => null,

    /*
    |--------------------------------------------------------------------------
    | Assets
    |--------------------------------------------------------------------------
    |
    | Served directly from the package by default (zero build-step setup).
    | Run `php artisan vendor:publish --tag=select-assets` to self-host them
    | through your own asset pipeline instead, then set `serve` to false.
    |
    */
    'assets' => [
        'serve' => true,
    ],
];
