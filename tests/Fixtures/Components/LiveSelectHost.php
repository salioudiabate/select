<?php

declare(strict_types=1);

namespace Salioudiabate\Select\Tests\Fixtures\Components;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class LiveSelectHost extends Component
{
    public ?string $countryId = null;

    public ?string $userId = null;

    /** @var array<int, string> */
    public array $tagIds = [];

    /** @var array<int, string> */
    public array $deferredTagIds = [];

    /** @var array<int, array{value: string, label: string}> */
    public array $options = [
        ['value' => 'a', 'label' => 'Alpha'],
        ['value' => 'b', 'label' => 'Beta'],
    ];

    public function render(): View
    {
        return view('live-select-host');
    }
}
