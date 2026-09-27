<?php

declare(strict_types=1);

use Livewire\Livewire;
use Salioudiabate\Select\Tests\Fixtures\Components\LiveSelectHost;
use Salioudiabate\Select\Tests\Fixtures\Components\SelectHost;

it('entangles live when wire:model has the .live modifier, on select and multi', function () {
    $html = Livewire::test(LiveSelectHost::class)->html();

    expect($html)
        ->toContain(".entangle('countryId').live")
        ->toContain(".entangle('userId').live")
        ->toContain(".entangle('tagIds').live");
});

it('keeps the entangled value deferred without the .live modifier', function () {
    expect(Livewire::test(LiveSelectHost::class)->html())
        ->toContain(".entangle('deferredTagIds')")
        ->not->toContain(".entangle('deferredTagIds').live");

    expect(Livewire::test(SelectHost::class)->html())
        ->not->toContain(".entangle('userId').live")
        ->not->toContain(".entangle('tagIds').live");
});
