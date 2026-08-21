<?php

declare(strict_types=1);

use Livewire\Livewire;
use Salioudiabate\Select\Tests\Fixtures\Components\SelectHost;

it('embeds the options, config and entangled value in x-data', function () {
    $html = Livewire::test(SelectHost::class)->html();

    expect($html)
        ->toContain('x-data="selectMulti({')
        ->toContain('Alpha')
        ->toContain('Beta')
        ->toContain(".entangle('tagIds')");
});

it('is searchable by default and can be turned off per instance', function () {
    $html = Livewire::test(SelectHost::class)->html();

    // fixture view: 2 searchable/async single-selects (1 search input each) + a
    // multi with searchable defaulted to true (1 more) + a multi with
    // :searchable="false" (0) = 3 total.
    expect(substr_count($html, 'select-search__input'))->toBe(3)
        // trailing quote to avoid also matching the "select-panel__header-actions" class
        ->and(substr_count($html, 'select-panel__header"'))->toBe(2); // both multi instances render their count/actions header regardless of searchable
});

it('renders the select-all and clear-all actions with the configured strings', function () {
    config(['select.strings.select_all' => 'Sélectionner tout', 'select.strings.clear_all' => 'Tout effacer']);

    $html = Livewire::test(SelectHost::class)->html();

    expect($html)
        ->toContain('Sélectionner tout')
        ->toContain('Tout effacer');
});

it('renders help text and a required label', function () {
    $html = Livewire::test(SelectHost::class)->html();

    expect($html)
        ->toContain('Pick as many as you like.')
        ->toContain('Tags');
});
