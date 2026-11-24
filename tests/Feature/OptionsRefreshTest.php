<?php

declare(strict_types=1);

use Livewire\Livewire;
use Salioudiabate\Select\Tests\Fixtures\Components\LiveSelectHost;

it('keys select and multi on their options, so a new list from the server re-creates them', function () {
    $initial = md5(json_encode([
        ['value' => 'a', 'label' => 'Alpha'],
        ['value' => 'b', 'label' => 'Beta'],
    ]));
    $updatedOptions = [['value' => 'c', 'label' => 'Gamma']];
    $updated = md5(json_encode($updatedOptions));

    Livewire::test(LiveSelectHost::class)
        ->assertSeeHtml('wire:key="select-panel-countryid-'.$initial.'"')
        ->assertSeeHtml('wire:key="select-multi-panel-tagids-'.$initial.'"')
        ->set('options', $updatedOptions)
        ->assertSeeHtml('wire:key="select-panel-countryid-'.$updated.'"')
        ->assertSeeHtml('wire:key="select-multi-panel-tagids-'.$updated.'"')
        ->assertDontSeeHtml($initial);
});

it('keeps the same key while the options do not change', function () {
    $host = Livewire::test(LiveSelectHost::class);
    $before = $host->html();

    $host->set('countryId', 'a');

    preg_match_all('/wire:key="(select-[^"]+)"/', $before, $keysBefore);
    preg_match_all('/wire:key="(select-[^"]+)"/', $host->html(), $keysAfter);

    expect($keysAfter[1])->toBe($keysBefore[1]);
});
