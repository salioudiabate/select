@props([
    'options' => [],
    'optionLabel' => null,
    'optionValue' => null,
    'optionGroup' => null,
    'optionAvatar' => null,
    'optionDescription' => null,
    'searchable' => false,
    'async' => false,
    'searchMethod' => null,
    'preload' => true,
    'creatable' => false,
    'createMethod' => null,
    'selectedLabel' => null,
    'placeholder' => null,
    'searchPlaceholder' => null,
    'clearable' => true,
    'disabled' => false,
    'name' => null,
    'error' => null,
    'help' => null,
    'label' => null,
    'required' => false,
    'debounce' => null,
])

@php
    $optionLabel ??= config('select.option_label', 'label');
    $optionValue ??= config('select.option_value', 'value');
    $optionGroup ??= config('select.option_group');
    $placeholder ??= config('select.strings.placeholder', 'Sélectionner...');
    $searchPlaceholder ??= config('select.strings.search_placeholder', 'Rechercher...');
    $noResultsText = config('select.strings.no_results', 'Aucun résultat');
    $searchingText = config('select.strings.searching', 'Recherche...');
    $createText = config('select.strings.create', 'Créer');
    $clearText = config('select.strings.clear', 'Effacer');
    $debounceMs = $debounce ?? config('select.search_debounce', 300);
    $searchable = $searchable || $async || $creatable;

    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $panelId = 'select-panel-' . \Illuminate\Support\Str::slug($wireModel ?? \Illuminate\Support\Str::random(6));

    // Auto-resolves a validation message from the shared $errors bag (any
    // Livewire component's own failed validation) when the caller hasn't
    // passed one explicitly — the same convenience most Blade input kits
    // give plain <input>s. $name defaults to the wire:model target itself,
    // since that's the field name Livewire's own validate() reports errors
    // under unless told otherwise.
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
        x-data="select({
            options: {{ Illuminate\Support\Js::from($options) }},
            optionLabel: {{ Illuminate\Support\Js::from($optionLabel) }},
            optionValue: {{ Illuminate\Support\Js::from($optionValue) }},
            optionGroup: {{ Illuminate\Support\Js::from($optionGroup) }},
            optionAvatar: {{ Illuminate\Support\Js::from($optionAvatar) }},
            optionDescription: {{ Illuminate\Support\Js::from($optionDescription) }},
            searchable: {{ Illuminate\Support\Js::from($searchable) }},
            async: {{ Illuminate\Support\Js::from($async) }},
            searchMethod: {{ Illuminate\Support\Js::from($searchMethod) }},
            preload: {{ Illuminate\Support\Js::from($preload) }},
            creatable: {{ Illuminate\Support\Js::from($creatable) }},
            createMethod: {{ Illuminate\Support\Js::from($createMethod) }},
            clearable: {{ Illuminate\Support\Js::from($clearable) }},
            debounceMs: {{ Illuminate\Support\Js::from($debounceMs) }},
            selectedValue: @entangle($wireModel),
        })"
        class="select-trigger-wrapper"
        x-ref="triggerWrapper"
    >
        <button
            type="button"
            id="{{ $panelId }}-trigger"
            {{ $disabled ? 'disabled' : '' }}
            role="combobox"
            aria-haspopup="listbox"
            aria-controls="{{ $panelId }}"
            x-bind:aria-expanded="isOpen"
            x-on:click="toggle()"
            x-on:keydown.down.prevent="openedWithKeyboard = true"
            x-on:keydown.enter.prevent="openDropdown()"
            x-on:keydown="onTypeahead($event.key)"
            class="select-trigger {{ $error ? 'select-trigger-error' : '' }}"
            x-bind:class="{ 'select-trigger-open': isOpen }"
        >
            <span class="select-trigger__value" x-bind:class="{ 'select-trigger__value-empty': !selectedLabelText({{ Illuminate\Support\Js::from($selectedLabel) }}) }">
                <template x-if="optionAvatar && selectedOption && selectedOption[optionAvatar]">
                    <img x-bind:src="selectedOption[optionAvatar]" class="select-option__avatar" alt="" />
                </template>
                <span x-text="selectedLabelText({{ Illuminate\Support\Js::from($selectedLabel) }}) || {{ Illuminate\Support\Js::from($placeholder) }}"></span>
            </span>

            <span class="select-trigger__actions">
                <span
                    x-show="clearable && selectedLabelText({{ Illuminate\Support\Js::from($selectedLabel) }})"
                    x-cloak
                    x-on:click.stop="clearSelection()"
                    class="select-clear-btn"
                    role="button"
                    aria-label="{{ $clearText }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="14" height="14" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                    </svg>
                </span>
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
                role="listbox"
                x-on:keydown.escape.prevent="closeDropdown()"
                x-on:keydown.down.prevent="$focus.wrap().next()"
                x-on:keydown.up.prevent="$focus.wrap().previous()"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                x-trap="openedWithKeyboard"
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

                <ul class="select-options">
                    <template x-if="!loading && options.length === 0 && !canCreate()">
                        <li class="select-empty">{{ $noResultsText }}</li>
                    </template>

                    <template x-if="loading">
                        <li class="select-empty">{{ $searchingText }}</li>
                    </template>

                    <template x-for="group in groupedOptions()" x-bind:key="group.group || '__ungrouped'">
                        <li>
                            <template x-if="group.group">
                                <div class="select-optgroup-label" x-text="group.group"></div>
                            </template>

                            <ul style="list-style: none; margin: 0; padding: 0;">
                                <template x-for="option in group.options" x-bind:key="option[optionValue]">
                                    <li
                                        class="select-option"
                                        x-bind:class="{ 'select-option-active': selectedOption && selectedOption[optionValue] == option[optionValue] }"
                                        x-bind:data-option-value="option[optionValue]"
                                        role="option"
                                        tabindex="0"
                                        x-on:click="setSelectedOption(option)"
                                        x-on:keydown.enter="setSelectedOption(option)"
                                    >
                                        <span class="select-option__content">
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
                                        <svg x-show="selectedOption && selectedOption[optionValue] == option[optionValue]" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2.5" width="14" height="14" class="select-option__check" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
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
