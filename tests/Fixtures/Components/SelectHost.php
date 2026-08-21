<?php

declare(strict_types=1);

namespace Salioudiabate\Select\Tests\Fixtures\Components;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SelectHost extends Component
{
    public ?string $userId = null;

    public ?string $authorId = null;

    public ?string $countryId = null;

    #[Validate('required', message: 'Please pick a country.')]
    public ?string $requiredCountryId = null;

    public ?string $newTagId = null;

    /** @var array<int, string> */
    public array $tagIds = [];

    /** @var array<int, array{value: string, label: string}> */
    public array $users = [
        ['value' => '1', 'label' => 'Alice'],
        ['value' => '2', 'label' => 'Bob'],
    ];

    /** @var array<int, array{value: string, label: string}> */
    public array $tags = [
        ['value' => 'a', 'label' => 'Alpha'],
        ['value' => 'b', 'label' => 'Beta'],
    ];

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

    public int $searchAuthorsCalls = 0;

    /** @return array<int, array{value: string, label: string}> */
    public function searchAuthors(string $query): array
    {
        $this->searchAuthorsCalls++;

        return collect($this->users)
            ->filter(fn (array $user) => str_contains(strtolower($user['label']), strtolower($query)))
            ->values()
            ->all();
    }

    /** @return array{value: string, label: string} */
    public function createTag(string $label): array
    {
        $value = 'new-'.str($label)->slug();
        $this->tags[] = ['value' => $value, 'label' => $label];

        return ['value' => $value, 'label' => $label];
    }

    public function submitRequiredCountry(): void
    {
        $this->validate();
    }

    public function render(): View
    {
        return view('select-host');
    }
}
