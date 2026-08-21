<?php

declare(strict_types=1);

use Livewire\Livewire;
use Salioudiabate\Select\Tests\Fixtures\Components\SelectFeaturesHost;

it('flows option-group into x-data so options render grouped', function () {
    $html = Livewire::test(SelectFeaturesHost::class)->html();

    expect($html)->toContain("optionGroup: 'group'");
});

it('flows option-avatar and option-description into x-data for rich option rendering', function () {
    $html = Livewire::test(SelectFeaturesHost::class)->html();

    expect($html)
        ->toContain("optionAvatar: 'avatar'")
        ->toContain("optionDescription: 'bio'");
});

it('defaults option-group/avatar/description to null when not given', function () {
    $html = Livewire::test(SelectFeaturesHost::class)->html();

    // the creatable-select instance sets none of these
    expect(substr_count($html, 'optionGroup: null'))->toBeGreaterThanOrEqual(1);
});

it('flows creatable + create-method into x-data, and implies searchable', function () {
    $html = Livewire::test(SelectFeaturesHost::class)->html();

    expect($html)
        ->toContain('creatable: true')
        ->toContain("createMethod: 'createTag'")
        ->toContain('select-search__input'); // creatable implies searchable
});

it('flows preload: false into x-data for an async select that opts out', function () {
    $html = Livewire::test(SelectFeaturesHost::class)->html();

    expect($html)->toContain('preload: false');
});

it('defaults preload to true otherwise', function () {
    // the other async select in this suite doesn't set :preload at all
    Livewire::test(SelectFeaturesHost::class)
        ->assertSeeHtml('preload: true');
});

it('auto-resolves the error message from the validation error bag using the wire:model field name', function () {
    $test = Livewire::test(SelectFeaturesHost::class)->call('submit');

    expect($test->html())
        ->toContain('Please pick a country.')
        ->toContain('select-trigger-error');
});

it('does not show an error before validation has run', function () {
    $html = Livewire::test(SelectFeaturesHost::class)->html();

    expect($html)->not->toContain('Please pick a country.');
});

it('the createTag() Livewire action works like any other public method', function () {
    Livewire::test(SelectFeaturesHost::class)
        ->call('createTag', 'Gamma')
        ->assertReturned(['value' => 'new-gamma', 'label' => 'Gamma']);
});
