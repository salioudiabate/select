@props([
    'options' => [],
    'optionLabel' => null,
    'optionValue' => null,
    'optionGroup' => null,
    'optionAvatar' => null,
    'optionDescription' => null,
    'searchable' => true,
    'creatable' => false,
    'createMethod' => null,
    'placeholder' => null,
    'searchPlaceholder' => null,
    'disabled' => false,
    'name' => null,
    'error' => null,
    'help' => null,
    'label' => null,
    'required' => false,
])

@php
    $optionLabel ??= config('select.option_label', 'label');
    $optionValue ??= config('select.option_value', 'value');
    $optionGroup ??= config('select.option_group');
    $placeholder ??= config('select.strings.placeholder', 'Sélectionner...');
    $searchPlaceholder ??= config('select.strings.search_placeholder', 'Rechercher...');
    $noResultsText = config('select.strings.no_results', 'Aucun résultat');
    $selectAllText = config('select.strings.select_all', 'Tout');
    $clearAllText = config('select.strings.clear_all', 'Aucun');
    $createText = config('select.strings.create', 'Créer');
    $searchingText = config('select.strings.searching', 'Recherche...');
    $searchable = $searchable || $creatable;

    $wireModel = $attributes->whereStartsWith('wire:model')->first();

    // Carries the `.live` modifier of wire:model over to @entangle: without it the entangled
    // value is always deferred, so `wire:model.live` would only reach the server on the next
    // request and nothing depending on the value (a cascade, a computed option list) would
    // re-render when it changes.
    $wireModelLive = collect(array_keys($attributes->whereStartsWith('wire:model')->getAttributes()))
        ->contains(fn (string $key) => str_contains($key, '.live'));
    $panelId = 'select-multi-panel-' . \Illuminate\Support\Str::slug($wireModel ?? \Illuminate\Support\Str::random(6));

    // See select.blade.php for the same auto-resolution against $errors.
    $errorField = $name ?? $wireModel;
    if ($error === null && $errorField && isset($errors) && $errors->has($errorField)) {
        $error = $errors->first($errorField);
    }
@endphp

<div {{ $attributes->except('id')->class('select-field') }}>
    @if ($label)
        <label for="{{ $panelId }}-trigger" class="select-label {{ $required ? 'select-label-required' : '' }}">{{ $label }}</label>
    @endif

    <div
        x-data="selectMulti({
            options: {{ Illuminate\Support\Js::from($options) }},
            optionLabel: {{ Illuminate\Support\Js::from($optionLabel) }},
            optionValue: {{ Illuminate\Support\Js::from($optionValue) }},
            optionGroup: {{ Illuminate\Support\Js::from($optionGroup) }},
            optionAvatar: {{ Illuminate\Support\Js::from($optionAvatar) }},
            optionDescription: {{ Illuminate\Support\Js::from($optionDescription) }},
            creatable: {{ Illuminate\Support\Js::from($creatable) }},
            createMethod: {{ Illuminate\Support\Js::from($createMethod) }},
            selectedValues: @entangle($wireModel){{ $wireModelLive ? '.live' : '' }},
        })"
        class="select-trigger-wrapper"
        x-ref="triggerWrapper"
        x-on:keydown.esc.window="closeDropdown()"
    >
        <button
            type="button"
            id="{{ $panelId }}-trigger"
            {{ $disabled ? 'disabled' : '' }}
            aria-haspopup="listbox"
            aria-controls="{{ $panelId }}"
            x-bind:aria-expanded="isOpen"
            x-on:click="toggle()"
            x-on:keydown.down.prevent="openedWithKeyboard = true"
            x-on:keydown.enter.prevent="openDropdown()"
            class="select-trigger {{ $error ? 'select-trigger-error' : '' }}"
            x-bind:class="{ 'select-trigger-open': isOpen }"
        >
            <span class="select-trigger__value select-trigger__value-summary" x-bind:class="{ 'select-trigger__value-empty': selectedValues.length === 0 }" x-text="summaryText({{ Illuminate\Support\Js::from($placeholder) }})"></span>

            <span class="select-trigger__actions">
                <span x-show="selectedValues.length > 0" x-cloak class="select-badge-count" x-text="selectedValues.length"></span>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16" class="select-chevron" x-bind:class="{ 'select-chevron-open': isOpen }" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                </svg>
            </span>
        </button>

        <template x-teleport="body">
            <div
                x-show="isOpen"
                x-cloak
                id="{{ $panelId }}"
                x-ref="dropdownPanel"
                x-bind:style="dropdownStyles"
                class="select-panel"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
            >
                @if ($searchable)
                    <div class="select-search">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="1.5" width="16" height="16" class="select-search__icon" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input
                            type="text"
                            x-ref="searchField"
                            x-model="searchQuery"
                            x-on:input="onSearchInput($el.value)"
                            class="select-search__input"
                            placeholder="{{ $searchPlaceholder }}"
                            aria-label="{{ $searchPlaceholder }}"
                        />
                    </div>
                @endif

                <div class="select-panel__header">
                    <span x-text="selectedValues.length + ' / ' + allOptions.length"></span>
                    <span class="select-panel__header-actions">
                        <button type="button" class="select-link-btn" x-on:click="selectAll()">{{ $selectAllText }}</button>
                        <button type="button" class="select-link-btn" x-on:click="clearAll()">{{ $clearAllText }}</button>
                    </span>
                </div>

                <ul class="select-options">
                    <template x-if="options.length === 0 && !canCreate()">
                        <li class="select-empty">{{ $noResultsText }}</li>
                    </template>

                    <template x-for="group in groupedOptions()" x-bind:key="group.group || '__ungrouped'">
                        <li>
                            <template x-if="group.group">
                                <div class="select-optgroup-label" x-text="group.group"></div>
                            </template>

                            <ul style="list-style: none; margin: 0; padding: 0;">
                                <template x-for="option in group.options" x-bind:key="option[optionValue]">
                                    <li class="select-option" role="option" x-on:click="toggleOption(option[optionValue])">
                                        <span class="select-option__content">
                                            <span class="select-checkbox" x-bind:class="{ 'select-checkbox-checked': isSelected(option[optionValue]) }">
                                                <svg x-show="isSelected(option[optionValue])" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="3" width="10" height="10" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                </svg>
                                            </span>
                                            <template x-if="optionAvatar && option[optionAvatar]">
                                                <img x-bind:src="option[optionAvatar]" class="select-option__avatar" alt="" />
                                            </template>
                                            <span class="select-option__text">
                                                <span x-text="option[optionLabel]"></span>
                                                <template x-if="optionDescription && option[optionDescription]">
                                                    <span class="select-option__description" x-text="option[optionDescription]"></span>
                                                </template>
                                            </span>
                                        </span>
                                    </li>
                                </template>
                            </ul>
                        </li>
                    </template>

                    <template x-if="canCreate()">
                        <li class="select-option select-option-create" role="option" x-on:click="createOption()">
                            <span x-show="!creating" x-text="{{ Illuminate\Support\Js::from($createText) }} + ' « ' + searchQuery + ' »'"></span>
                            <span x-show="creating" x-cloak>{{ $searchingText }}</span>
                        </li>
                    </template>
                </ul>
            </div>
        </template>
    </div>

    @if ($error)
        <p class="select-field-error">{{ $error }}</p>
    @elseif ($help)
        <p class="select-help">{{ $help }}</p>
    @endif
</div>
