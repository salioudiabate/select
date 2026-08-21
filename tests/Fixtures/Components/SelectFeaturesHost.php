<?php

declare(strict_types=1);

namespace Salioudiabate\Select\Tests\Fixtures\Components;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SelectFeaturesHost extends Component
{
    #[Validate('required', message: 'Please pick a country.')]
    public ?string $requiredCountryId = null;

    public ?string $userId = null;

    public ?string $newTagId = null;

    /** @var array<int, string> */
    public array $tagIds = [];

    public ?string $preloadCountryId = null;

    /** @var array<int, array{value: string, label: string, group: string}> */
    public array $countries = [
        ['value' => 'fr', 'label' => 'France', 'group' => 'Europe'],
        ['value' => 'de', 'label' => 'Germany', 'group' => 'Europe'],
        ['value' => 'ci', 'label' => 'Ivory Coast', 'group' => 'Africa'],
        ['value' => 'sn', 'label' => 'Senegal', 'group' => 'Africa'],
    ];

    /** @var array<int, array{value: string, label: string, avatar: string, bio: string}> */
    public array $usersWithAvatar = [
        ['value' => '1', 'label' => 'Alice', 'avatar' => '/avatars/alice.png', 'bio' => 'Product'],
        ['value' => '2', 'label' => 'Bob', 'avatar' => '/avatars/bob.png', 'bio' => 'Engineering'],
    ];

    /** @var array<int, array{value: string, label: string}> */
    public array $tags = [
        ['value' => 'a', 'label' => 'Alpha'],
        ['value' => 'b', 'label' => 'Beta'],
    ];

    public function submit(): void
    {
        $this->validate();
    }

    /** @return array{value: string, label: string} */
    public function createTag(string $label): array
    {
        $value = 'new-'.str($label)->slug();
        $this->tags[] = ['value' => $value, 'label' => $label];

        return ['value' => $value, 'label' => $label];
    }

    public function searchCountries(string $query): array
    {
        return collect($this->countries)
            ->filter(fn (array $c) => str_contains(strtolower($c['label']), strtolower($query)))
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('select-features-host');
    }
}
