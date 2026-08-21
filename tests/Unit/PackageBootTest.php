<?php

declare(strict_types=1);

it('boots the service provider and loads the config', function () {
    expect(config('select.option_label'))->toBe('label')
        ->and(config('select.option_value'))->toBe('value')
        ->and(config('select.search_debounce'))->toBe(300);
});

it('defaults its dropdown event names to the exact same strings Modal listens for, so the two work together with zero configuration', function () {
    expect(config('select.dropdown_events.opened'))->toBe('modal-overlay-opened')
        ->and(config('select.dropdown_events.closed'))->toBe('modal-overlay-closed');
});
