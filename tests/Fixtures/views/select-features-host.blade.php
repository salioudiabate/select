<div>
    <x-select::select
        wire:model="requiredCountryId"
        :options="$countries"
        option-group="group"
        label="Country"
        id="grouped-select"
    />

    <x-select::select
        wire:model="userId"
        :options="$usersWithAvatar"
        option-avatar="avatar"
        option-description="bio"
        id="rich-select"
    />

    <x-select::select
        wire:model="newTagId"
        :options="$tags"
        searchable
        creatable
        create-method="createTag"
        id="creatable-select"
    />

    <x-select::select
        wire:model="preloadCountryId"
        async
        search-method="searchCountries"
        :selected-label="null"
        :preload="false"
        id="no-preload-select"
    />

    <x-select::multi
        wire:model="tagIds"
        :options="$countries"
        option-group="group"
        id="grouped-multi"
    />

    <x-select::multi
        wire:model="tagIds"
        :options="$tags"
        creatable
        create-method="createTag"
        id="creatable-multi"
    />
</div>
