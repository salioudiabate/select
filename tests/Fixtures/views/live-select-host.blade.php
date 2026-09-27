<div>
    <x-select::select wire:model.live="countryId" :options="$options" />

    <x-select::select wire:model.live.debounce.300ms="userId" :options="$options" />

    <x-select::multi wire:model.live="tagIds" :options="$options" />

    <x-select::multi wire:model="deferredTagIds" :options="$options" />
</div>
