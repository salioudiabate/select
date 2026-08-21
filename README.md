# Select

[![Tests](https://github.com/salioudiabate/select/actions/workflows/tests.yml/badge.svg)](https://github.com/salioudiabate/select/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/salioudiabate/select.svg)](https://packagist.org/packages/salioudiabate/select)
[![Total Downloads](https://img.shields.io/packagist/dt/salioudiabate/select.svg)](https://packagist.org/packages/salioudiabate/select)
[![License](https://img.shields.io/packagist/l/salioudiabate/select.svg)](LICENSE.md)

A soft, modern select/combobox kit for Livewire. One component for simple, searchable and server-driven (async) single selects, one for multi-select — one design system, zero build step, safe to render inside a [Modal](https://github.com/salioudiabate/modal) dialog out of the box.

```blade
<x-select::select wire:model="userId" :options="$users" label="Assigned to" searchable />

<x-select::select wire:model="countryId" :options="$countries" async search-method="searchCountries" :selected-label="$country?->name" />

<x-select::multi wire:model="tagIds" :options="$tags" label="Tags" />
```

Companion package to [salioudiabate/notify](https://github.com/salioudiabate/notify) and [salioudiabate/modal](https://github.com/salioudiabate/modal) — same design language, same conventions, meant to be used together.

## Why not the four ad-hoc select components most Livewire apps accumulate?

This package started as a review of four separate, hand-rolled select components in a real production app: a plain select, a client-searchable one, a server-searched one, and a multi-select — each its own Alpine blob, each copy-pasting (and slowly drifting from) the same positioning/teleport/outside-click logic. The plain select never got the teleport-and-dynamic-positioning fix the other three received, so — unlike its siblings — it would silently get clipped inside any `overflow: hidden` ancestor, a modal included. It did, however, have letter-key navigation none of the others did — carried forward here onto every non-searchable select, instead of lost in the merge.

Select folds all four into two components (`select` and `multi`) sharing one JS runtime, so that fix (and any future one) applies everywhere at once, and styles with a CSS-custom-property design system instead of hardcoded utility classes, so it doesn't require Tailwind and re-themes with a handful of CSS lines.

## Requirements

- PHP ^8.3
- Laravel 11, 12 or 13
- Livewire 3 or 4

## Installation

```bash
composer require salioudiabate/select
```

Zero build step: no `npm install`, no Vite entry. The JS/CSS are served directly by the package — nothing to add to your layout beyond using the components. To self-host them through your own asset pipeline instead:

```bash
php artisan vendor:publish --tag=select-assets
```

then set `'assets' => ['serve' => false]` in `config/select.php` and include `public/vendor/select/select.{js,css}` yourself.

Publish the config file if you want to change any default:

```bash
php artisan vendor:publish --tag=select-config
```

## `<x-select::select>` — single select

```blade
<x-select::select
    wire:model="userId"
    :options="$users"
    label="Assigné à"
    help="Visible uniquement par l'équipe."
    searchable
    clearable
/>
```

Each entry of `:options` is an associative array (or array-accessible object) with a `label`/`value` pair by default — override the keys per instance (`option-label="name"` `option-value="id"`) or globally via `config('select.option_label'/'option_value')`.

| Prop | Default | |
|---|---|---|
| `options` | `[]` | The array/Collection of options. |
| `option-label` / `option-value` | `config('select.option_label'/'option_value')` | Which keys to read off each option. |
| `option-group` | `config('select.option_group')` (`null`) | Groups options under a header — see "Grouped options" below. |
| `option-avatar` / `option-description` | `null` | Renders an avatar image and/or a subtext line per option — see "Rich options" below. |
| `searchable` | `false` | Renders a search field and filters `options` client-side as you type. |
| `async` | `false` | Implies `searchable` — see below. Filters server-side instead of locally. |
| `search-method` | `null` | Required when `async`. A public method on the Livewire component hosting this select, called as `searchMethod($query)`, returning an array shaped like `options`. |
| `preload` | `true` | Only used by `async`: fetches an initial empty-query page the moment the dropdown opens, instead of staying empty until the user types. Set `false` for a "type to search" select that should show nothing at first. |
| `creatable` / `create-method` | `false` / `null` | Implies `searchable` — see "Creatable" below. |
| `selected-label` | `null` | Required when `async` (there's no local option list to resolve it from) — the currently selected option's label, resolved server-side. Optional otherwise: derived automatically from `options` + the bound value. |
| `clearable` | `true` | Shows a clear (×) button once something is selected. |
| `placeholder` / `search-placeholder` | `config('select.strings.*')` | |
| `debounce` | `config('select.search_debounce')` (300ms) | Only used by `async`. |
| `name` | `null` | Field name to look up in the validation `$errors` bag when `error` isn't given explicitly — defaults to the `wire:model` target itself. |
| `label` / `help` / `error` / `required` | `null` / `null` / `null` / `false` | Standard field chrome — `error` renders in place of `help` and switches the trigger to its error style; auto-resolved from `$errors` when omitted (see "Validation errors" below). |
| `disabled` | `false` | |

The non-searchable variant supports letter-key navigation (type "b" to jump to the first option starting with "b", same as a native `<select>`) — a search box supersedes it, so it's skipped automatically whenever one is present (`searchable`, `async` or `creatable`).

### Async (server-driven) search

```php
class EditPost extends Component
{
    public ?int $authorId = null;

    public function searchAuthors(string $query): array
    {
        return User::query()
            ->where('name', 'like', "%{$query}%")
            ->limit(20)
            ->get(['id', 'name'])
            ->map(fn ($user) => ['value' => $user->id, 'label' => $user->name])
            ->all();
    }

    public function render(): View
    {
        return view('livewire.edit-post', [
            'author' => $this->authorId ? User::find($this->authorId) : null,
        ]);
    }
}
```

```blade
<x-select::select
    wire:model="authorId"
    async
    search-method="searchAuthors"
    :selected-label="$author?->name"
    label="Auteur"
/>
```

`search-method` is called on whichever Livewire component is rendering the Blade view — no separate component or route to wire up.

### Grouped options

Give any option a `group` key (or whatever `option-group` points to) and the dropdown renders it under a header, in order of each group's first appearance — ungrouped entries render first, with no header:

```blade
<x-select::select wire:model="countryId" :options="$countries" option-group="region" label="Country" />
```

```php
$countries = [
    ['value' => 'fr', 'label' => 'France', 'region' => 'Europe'],
    ['value' => 'ci', 'label' => 'Ivory Coast', 'region' => 'Africa'],
    // ...
];
```

Works on `<x-select::multi>` too. Grouping composes with search: filtering narrows each group's contents rather than replacing the grouped view with a flat list.

### Rich options (avatar + description)

```blade
<x-select::select
    wire:model="assigneeId"
    :options="$teammates"
    option-avatar="avatar_url"
    option-description="email"
    searchable
/>
```

`option-avatar` renders a small round image per option (and next to the selected value in the trigger); `option-description` renders a muted subtext line under the label. Both are opt-in per instance and read as plain text/URL — never raw HTML — so option data coming back from an `async` search can't inject markup.

### Creatable — add an option on the fly

```blade
<x-select::select wire:model="tagId" :options="$tags" searchable creatable create-method="createTag" />
```

```php
public function createTag(string $label): array
{
    $tag = Tag::create(['name' => $label]);

    return ['value' => $tag->id, 'label' => $tag->name];
}
```

When what's typed doesn't match any existing option, a "Créer « … »" row appears at the bottom of the list. Without `create-method`, clicking it just uses the typed text as both the value and the label — no server round-trip, useful for plain free-text tagging. With `create-method`, it calls that method over the wire and uses its return value (so you can persist the new row and hand back a real id). Works the same way on `<x-select::multi>`, adding the created option to the current selection instead of replacing it.

### Validation errors

An `error` you don't pass explicitly is resolved automatically from the current `$errors` bag, keyed by `name` (or the `wire:model` target itself when `name` is omitted) — the same convenience most Blade input kits already give a plain `<input>`:

```php
class EditPost extends Component
{
    #[Validate('required')]
    public ?int $categoryId = null;

    public function save(): void { $this->validate(); /* ... */ }
}
```

```blade
<x-select::select wire:model="categoryId" :options="$categories" label="Category" />
```

No `error="..."` needed — it shows up on its own the moment `$this->validate()` fails.

## `<x-select::multi>` — multi-select

```blade
<x-select::multi wire:model="tagIds" :options="$tags" label="Tags" />
```

Always searchable (client-side); pass `:searchable="false"` to hide the search field for a short list. `wire:model` binds to an array of values. Supports the same `option-label`/`option-value`/`option-group`/`option-avatar`/`option-description`/`creatable`/`create-method`/`placeholder`/`search-placeholder`/`name`/`label`/`help`/`error`/`disabled` props as `<x-select::select>` (grouping, rich options, creatable and auto-resolved validation errors all work the same way — see above), minus `async`/`clearable`/`selected-label`/`preload` (multi-select has no single "clear" state — use the built-in "Aucun" action instead, and is always client-filtered).

## Working inside a Modal

Both components already dispatch `modal-overlay-opened`/`modal-overlay-closed` — the exact event names [Modal](https://github.com/salioudiabate/modal) listens for by default (`config('modal.dropdown_events')`) — so a select just works inside a `ModalComponent`'s view, with the dropdown panel teleported above the dialog and the modal's focus trap correctly suspended while it's open. Nothing extra to configure on either side, unless you already renamed one side's event names — then set the matching `config('select.dropdown_events')` / `config('modal.dropdown_events')` to line back up.

## Customization

**Colors, radii, shadows.** Every visual token is a CSS custom property (`--select-*`) in `select.css`:

```css
:root {
  --select-accent: #7c3aed;
  --select-r-lg: 8px;
}
```

**Color scheme.** Follows `prefers-color-scheme` by default. Force it via `config('select.color_scheme')`, or at runtime — persists via `localStorage`:

```js
Select.setColorScheme('dark'); // 'light' | 'dark' | 'system'
```

Using Notify and/or Modal alongside Select? Each package's `setColorScheme()` is independent on purpose (so any one of them works standalone) — call all the ones you've installed from your own theme toggle to flip them together:

```js
function setSiteColorScheme(scheme) {
    if (window.Notify) Notify.setColorScheme(scheme);
    if (window.Modal) Modal.setColorScheme(scheme);
    if (window.Select) Select.setColorScheme(scheme);
}
```

**Any piece of markup.**

```bash
php artisan vendor:publish --tag=select-views
```

edits land in `resources/views/vendor/select/components/{select,multi}.blade.php` and take precedence over the package's own copies automatically.

**Every config default** — option keys, search debounce, UI copy, dropdown event names, color scheme, asset serving — lives in one published file (`config/select.php`).

## Testing your own usage

```php
use Livewire\Livewire;

Livewire::test(EditPost::class)
    ->set('authorId', $user->id)
    ->assertSet('authorId', $user->id);
```

`search-method` is a plain public method — test it directly like any other Livewire action:

```php
Livewire::test(EditPost::class)
    ->call('searchAuthors', 'jane')
    ->assertReturned(fn ($results) => collect($results)->pluck('label')->contains('Jane Doe'));
```

## Security

See [SECURITY.md](SECURITY.md). Report vulnerabilities privately rather than via a public issue.

## License

MIT. See [LICENSE.md](LICENSE.md).
