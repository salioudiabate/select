<?php

declare(strict_types=1);

use Livewire\Livewire;
use Salioudiabate\Select\Tests\Fixtures\Components\SelectHost;

it('embeds the options, config and entangled value in x-data', function () {
    $html = Livewire::test(SelectHost::class)->html();

    expect($html)
        ->toContain('x-data="select({')
        ->toContain("optionLabel: 'label'")
        ->toContain("optionValue: 'value'")
        ->toContain('Alice')
        ->toContain('Bob')
        ->toContain(".entangle('userId')");
});

it('defaults to non-searchable, non-async, clearable', function () {
    $html = Livewire::test(SelectHost::class)->html();

    // the first select in the fixture view: searchable, not async, clearable (default)
    expect($html)
        ->toContain('searchable: true')
        ->toContain('async: false')
        ->toContain('clearable: true');
});

it('renders a search input only when searchable or async', function () {
    $host = Livewire::test(SelectHost::class);

    expect(substr_count($host->html(), 'select-search__input'))->toBeGreaterThanOrEqual(2); // searchable select + async select
});

it('async implies searchable and carries the search-method name', function () {
    $html = Livewire::test(SelectHost::class)->html();

    expect($html)
        ->toContain('async: true')
        ->toContain("searchMethod: 'searchAuthors'")
        ->toContain(".entangle('authorId')");
});

it('flows custom option-label/option-value and clearable: false through to x-data', function () {
    $html = Livewire::test(SelectHost::class)->html();

    expect($html)->toContain('clearable: false');
});

it('renders the error message instead of the help text, with the error trigger class', function () {
    $html = Livewire::test(SelectHost::class)->html();

    expect($html)
        ->toContain('This field is required.')
        ->toContain('select-trigger-error')
        ->toContain('select-field-error');
});

it('renders help text when there is no error', function () {
    $html = Livewire::test(SelectHost::class)->html();

    expect($html)
        ->toContain('Pick a teammate.')
        ->toContain('select-help');
});

it('marks a required label with the required class', function () {
    $html = Livewire::test(SelectHost::class)->html();

    expect($html)->toContain('select-label-required');
});

it('falls back to config(select.strings.placeholder) when no placeholder is given', function () {
    config(['select.strings.placeholder' => 'Choisis...']);

    $html = Livewire::test(SelectHost::class)->html();

    expect($html)->toContain('Choisis...');
});

it('lets a per-instance placeholder override the config default', function () {
    $html = Livewire::test(SelectHost::class)->html();

    expect($html)->toContain('Choisir un auteur');
});
