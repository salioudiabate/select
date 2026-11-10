# Changelog

All notable changes to `salioudiabate/select` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.4] - 2026-11-10

### Changed

- Same code as 1.0.3, released from a commit whose message carries no co-author line. Packagist keeps a published tag on its original commit, so the clean history ships under a new version number.

## [1.0.3] - 2026-11-10

### Fixed

- An async `<x-select::select>` can change its selection again. Selecting an option reset the list to the static options, empty in async mode, and the preload had already run, so reopening showed « Aucun résultat » until something was typed. Unfiltered results now stay listed; results filtered by a search are fetched again, unfiltered, on the next open.

## [1.0.2] - 2026-09-28

### Changed

- `<x-select::multi>` shows the selected labels in its trigger (« A, C, D », ellipsized when long) instead of a bare count once more than one option is selected; the count badge stays next to it. Falls back to the count when no selected value matches a loaded option.

## [1.0.1] - 2026-09-27

### Fixed

- `wire:model.live` (including `.live.debounce.*`) is now honored on both `<x-select::select>` and `<x-select::multi>`: the value was always entangled deferred, so a live select only reached the server on the next request and anything depending on it (a cascade, a computed option list) did not re-render when it changed. Plain `wire:model` stays deferred.

## [1.0.0] - 2026-08-21

### Added

- `<x-select::select>` — single select, unifying what were three separate, drifting implementations (simple/client-search/server-search) into one component: `:searchable` for client-side filtering, `:async` + `search-method` for server-driven search over the wire.
- `<x-select::multi>` — multi-select with checkboxes, a live count badge, and select-all/clear-all.
- A shared, self-contained zero-build-step JS/CSS runtime (`resources/js/select.js`, `resources/css/select.css`) — dropdown positioning, teleporting, outside-click/Escape/keyboard navigation, and scroll-lock behavior extracted into one place instead of copy-pasted per variant.
- Works safely inside a [Modal](https://github.com/salioudiabate/modal) dialog out of the box — the dropdown panel now teleports and reports itself via `modal-overlay-opened`/`modal-overlay-closed` on every variant (the original's plain, non-searchable select had neither, and would get clipped by a modal's own overflow).
- A soft, modern design system driven entirely by `--select-*` CSS custom properties — no Tailwind required, same visual language as the Notify and Modal sibling packages.
- Grouped options (`option-group`) — options render under a header, in order of each group's first appearance, on both `select` and `multi`, composing correctly with search.
- Rich options (`option-avatar` / `option-description`) — an avatar image and/or a muted subtext line per option, read as plain text/URL only (never raw HTML), safe even for `async` results.
- Creatable options (`creatable` + `create-method`) — add a new option on the fly from the search box, client-side only or persisted via a Livewire method, on both `select` and `multi`.
- Preloading (`preload`, default `true`) — an `async` select fetches an initial empty-query page the moment it opens, instead of staying empty until the user types; opt out per instance for a "type to search" select.
- Letter-key (typeahead) navigation on the non-searchable variant — present in the original plain select this package replaces, dropped by accident when unifying the four originals into one component, restored here.
- Validation errors auto-resolve from the current `$errors` bag by field name when `error` isn't passed explicitly, the same convenience most Blade input kits give a plain `<input>`.
- `config('select.*')`: option keys (including `option_group`), search debounce, UI copy, dropdown event names, color scheme, asset serving.
- Support for Laravel 11, 12 and 13, and Livewire 3 and 4.
