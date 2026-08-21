<?php

declare(strict_types=1);

use Illuminate\Support\Str;

it('embeds config(select.color_scheme) and the dropdown event names', function () {
    config(['select.color_scheme' => 'dark']);

    $html = (string) view('select::components.root')->render();
    $jsConfig = json_decode(Str::before(Str::after($html, 'window.__SELECT_CONFIG__ = '), ';'), true);

    expect($jsConfig)->toBe([
        'colorScheme' => 'dark',
        'dropdownEvents' => ['opened' => 'modal-overlay-opened', 'closed' => 'modal-overlay-closed'],
    ]);
});

it('links the served CSS and JS assets when config(select.assets.serve) is true', function () {
    $html = (string) view('select::components.root')->render();

    expect($html)
        ->toContain('href="'.route('select.assets.css').'"')
        ->toContain('src="'.route('select.assets.js').'"');
});
