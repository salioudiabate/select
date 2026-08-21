<div>
    <x-select::select
        wire:model="userId"
        :options="$users"
        label="User"
        help="Pick a teammate."
        searchable
        required
    />

    <x-select::select
        wire:model="authorId"
        async
        search-method="searchAuthors"
        :selected-label="null"
        placeholder="Choisir un auteur"
    />

    <x-select::select
        wire:model="userId"
        :options="$users"
        option-label="label"
        option-value="value"
        :clearable="false"
        error="This field is required."
    />

    <x-select::multi
        wire:model="tagIds"
        :options="$tags"
        label="Tags"
        help="Pick as many as you like."
    />

    <x-select::multi
        wire:model="tagIds"
        :options="$tags"
        :searchable="false"
    />
</div>
