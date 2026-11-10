import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { loadSelect, makeComponent } from './support/loadSelect.js';

const OPTIONS = [
  { value: '1', label: 'Alice' },
  { value: '2', label: 'Bob' },
];

// A test that calls vi.useFakeTimers() and fails before its own
// vi.useRealTimers() cleanup would otherwise leave fake timers active for
// every test that runs after it in this file — this guarantees a reset
// regardless of how the previous test exited.
afterEach(() => {
  vi.useRealTimers();
});

describe('select(): initialization', () => {
  it('resolves the initial selectedOption from selectedValue against the preloaded options', () => {
    const { select } = loadSelect();
    const component = makeComponent(select, { options: OPTIONS, selectedValue: '2' });
    component.init();

    expect(component.selectedOption).toEqual({ value: '2', label: 'Bob' });
  });

  it('leaves selectedOption null when selectedValue matches nothing', () => {
    const { select } = loadSelect();
    const component = makeComponent(select, { options: OPTIONS, selectedValue: '999' });
    component.init();

    expect(component.selectedOption).toBeNull();
  });

  it('does not try to resolve selectedOption locally in async mode', () => {
    const { select } = loadSelect();
    const component = makeComponent(select, { options: [], async: true, selectedValue: '2' });
    component.init();

    expect(component.selectedOption).toBeNull();
  });

  it('re-resolves selectedOption whenever selectedValue changes externally', () => {
    const { select } = loadSelect();
    const component = makeComponent(select, { options: OPTIONS, selectedValue: null });
    component.init();

    component.__triggerWatch('selectedValue', '1');

    expect(component.selectedOption).toEqual({ value: '1', label: 'Alice' });
  });

  it('clears selectedOption when selectedValue is watched back to empty', () => {
    const { select } = loadSelect();
    const component = makeComponent(select, { options: OPTIONS, selectedValue: '1' });
    component.init();

    component.__triggerWatch('selectedValue', '');

    expect(component.selectedOption).toBeNull();
  });
});

describe('select(): selecting and clearing', () => {
  it('setSelectedOption() updates selectedOption/selectedValue and closes the dropdown', () => {
    const { select } = loadSelect({ livewire: { dispatch: vi.fn() } });
    const component = makeComponent(select, { options: OPTIONS });
    component.init();
    component.isOpen = true;

    component.setSelectedOption(OPTIONS[1]);

    expect(component.selectedOption).toEqual(OPTIONS[1]);
    expect(component.selectedValue).toBe('2');
    expect(component.isOpen).toBe(false);
  });

  it('clearSelection() resets selectedOption/selectedValue', () => {
    const { select } = loadSelect({ livewire: { dispatch: vi.fn() } });
    const component = makeComponent(select, { options: OPTIONS, selectedValue: '1' });
    component.init();

    component.clearSelection();

    expect(component.selectedOption).toBeNull();
    expect(component.selectedValue).toBe('');
  });

  it('selectedLabelText() prefers the resolved option, falling back to the given async label', () => {
    const { select } = loadSelect();
    const component = makeComponent(select, { options: OPTIONS, selectedValue: '1' });
    component.init();

    expect(component.selectedLabelText('fallback')).toBe('Alice');
    expect(component.selectedLabelText(null)).toBe('Alice');

    component.selectedOption = null;
    expect(component.selectedLabelText('Async Label')).toBe('Async Label');
    expect(component.selectedLabelText(null)).toBeNull();
  });
});

describe('select(): client-side search', () => {
  it('onSearchInput() filters the preloaded options locally when not async', () => {
    const { select } = loadSelect();
    const component = makeComponent(select, { options: OPTIONS });
    component.init();

    component.onSearchInput('ali');

    expect(component.options).toEqual([{ value: '1', label: 'Alice' }]);
  });

  it('setSelectedOption() restores the full option list and clears the query', () => {
    const { select } = loadSelect();
    const component = makeComponent(select, { options: OPTIONS });
    component.init();
    component.onSearchInput('ali');

    component.setSelectedOption(OPTIONS[0]);

    expect(component.options).toEqual(OPTIONS);
    expect(component.searchQuery).toBe('');
  });
});

describe('select(): async search', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('debounces $wire.call(searchMethod, query) and populates options with the result', async () => {
    const call = vi.fn().mockResolvedValue([{ value: '9', label: 'Zed' }]);
    const { select } = loadSelect();
    const component = makeComponent(select, { async: true, searchMethod: 'search', debounceMs: 300 });
    component.$wire.call = call;
    component.init();

    component.onSearchInput('z');
    expect(component.loading).toBe(true);
    expect(call).not.toHaveBeenCalled(); // still debouncing

    await vi.advanceTimersByTimeAsync(300);

    expect(call).toHaveBeenCalledWith('search', 'z');
    expect(component.options).toEqual([{ value: '9', label: 'Zed' }]);
    expect(component.loading).toBe(false);
  });

  it('only fires the last call when typing faster than the debounce window', async () => {
    const call = vi.fn().mockResolvedValue([]);
    const { select } = loadSelect();
    const component = makeComponent(select, { async: true, searchMethod: 'search', debounceMs: 300 });
    component.$wire.call = call;
    component.init();

    component.onSearchInput('a');
    await vi.advanceTimersByTimeAsync(100);
    component.onSearchInput('al');
    await vi.advanceTimersByTimeAsync(100);
    component.onSearchInput('ali');
    await vi.advanceTimersByTimeAsync(300);

    expect(call).toHaveBeenCalledTimes(1);
    expect(call).toHaveBeenCalledWith('search', 'ali');
  });

  it('stops loading even if the wire call rejects', async () => {
    const { select } = loadSelect();
    const component = makeComponent(select, { async: true, searchMethod: 'search', debounceMs: 50 });
    component.$wire.call = vi.fn().mockRejectedValue(new Error('boom'));
    component.init();

    component.onSearchInput('x');
    await vi.advanceTimersByTimeAsync(50);
    await Promise.resolve(); // let the .catch() microtask settle

    expect(component.loading).toBe(false);
  });
});

describe('select(): open/close dispatch the configured overlay events', () => {
  it('dispatches the default Modal-compatible event names', () => {
    const { select } = loadSelect({ livewire: { dispatch: vi.fn() } });
    const component = makeComponent(select, {});
    component.init();

    component.openDropdown();
    expect(window.Livewire.dispatch).toHaveBeenCalledWith('modal-overlay-opened');

    component.closeDropdown();
    expect(window.Livewire.dispatch).toHaveBeenCalledWith('modal-overlay-closed');
  });

  it('uses config(select.dropdown_events) when customized', () => {
    const { select } = loadSelect({
      config: { dropdownEvents: { opened: 'select-search-opened', closed: 'select-search-closed' } },
      livewire: { dispatch: vi.fn() },
    });
    const component = makeComponent(select, {});
    component.init();

    component.openDropdown();
    expect(window.Livewire.dispatch).toHaveBeenCalledWith('select-search-opened');
  });

  it('closeDropdown() is a no-op (no duplicate dispatch) when already closed', () => {
    const { select } = loadSelect({ livewire: { dispatch: vi.fn() } });
    const component = makeComponent(select, {});
    component.init();

    component.closeDropdown();

    expect(window.Livewire.dispatch).not.toHaveBeenCalled();
  });
});

describe('selectMulti()', () => {
  it('toggleOption() adds and removes values', () => {
    const { selectMulti } = loadSelect();
    const component = makeComponent(selectMulti, { options: OPTIONS, selectedValues: [] });
    component.init();

    component.toggleOption('1');
    expect(component.selectedValues).toEqual(['1']);

    component.toggleOption('2');
    expect(component.selectedValues).toEqual(['1', '2']);

    component.toggleOption('1');
    expect(component.selectedValues).toEqual(['2']);
  });

  it('isSelected() reflects the current selection', () => {
    const { selectMulti } = loadSelect();
    const component = makeComponent(selectMulti, { options: OPTIONS, selectedValues: ['1'] });
    component.init();

    expect(component.isSelected('1')).toBe(true);
    expect(component.isSelected('2')).toBe(false);
  });

  it('selectAll() selects every loaded option and clearAll() empties the selection', () => {
    const { selectMulti } = loadSelect();
    const component = makeComponent(selectMulti, { options: OPTIONS, selectedValues: [] });
    component.init();

    component.selectAll();
    expect(component.selectedValues).toEqual(['1', '2']);

    component.clearAll();
    expect(component.selectedValues).toEqual([]);
  });

  it('onSearchInput() filters the preloaded options locally', () => {
    const { selectMulti } = loadSelect();
    const component = makeComponent(selectMulti, { options: OPTIONS, selectedValues: [] });
    component.init();

    component.onSearchInput('bo');

    expect(component.options).toEqual([{ value: '2', label: 'Bob' }]);
  });

  it('summaryText() lists the selected labels, not just how many', () => {
    const { selectMulti } = loadSelect();
    const component = makeComponent(selectMulti, { options: OPTIONS, selectedValues: [] });
    component.init();

    expect(component.summaryText('None selected')).toBe('None selected');

    component.selectedValues = ['1'];
    expect(component.summaryText('None selected')).toBe('Alice');

    component.selectedValues = ['1', '2'];
    expect(component.summaryText('None selected')).toBe('Alice, Bob');
  });

  it('summaryText() falls back to the count when no selected value matches a loaded option', () => {
    const { selectMulti } = loadSelect();
    const component = makeComponent(selectMulti, { options: OPTIONS, selectedValues: [] });
    component.init();

    component.selectedValues = ['unknown-1', 'unknown-2'];
    expect(component.summaryText('None selected')).toBe('2');
  });
});

describe('groupedOptions()', () => {
  const GROUPED = [
    { value: 'fr', label: 'France', group: 'Europe' },
    { value: 'ci', label: 'Ivory Coast', group: 'Africa' },
    { value: 'de', label: 'Germany', group: 'Europe' },
    { value: 'xx', label: 'Unclassified' },
  ];

  it('returns a single null-group bucket when optionGroup is not set', () => {
    const { select } = loadSelect();
    const component = makeComponent(select, { options: OPTIONS });
    component.init();

    expect(component.groupedOptions()).toEqual([{ group: null, options: OPTIONS }]);
  });

  it('buckets options by group, in order of each group\'s first appearance, preserving item order within a group', () => {
    const { select } = loadSelect();
    const component = makeComponent(select, { options: GROUPED, optionGroup: 'group' });
    component.init();

    expect(component.groupedOptions()).toEqual([
      { group: 'Europe', options: [GROUPED[0], GROUPED[2]] },
      { group: 'Africa', options: [GROUPED[1]] },
      { group: null, options: [GROUPED[3]] },
    ]);
  });

  it('re-groups whatever the *currently filtered* options are, so search + grouping compose correctly', () => {
    const { select } = loadSelect();
    const component = makeComponent(select, { options: GROUPED, optionGroup: 'group' });
    component.init();

    component.onSearchInput('franc');

    expect(component.groupedOptions()).toEqual([{ group: 'Europe', options: [GROUPED[0]] }]);
  });

  it('also works on selectMulti()', () => {
    const { selectMulti } = loadSelect();
    const component = makeComponent(selectMulti, { options: GROUPED, optionGroup: 'group', selectedValues: [] });
    component.init();

    expect(component.groupedOptions().map((g) => g.group)).toEqual(['Europe', 'Africa', null]);
  });
});

describe('select(): typeahead on the plain (non-searchable) variant', () => {
  function panelWithOptions(component, options) {
    component.$refs.dropdownPanel.innerHTML = options
      .map((o) => `<div data-option-value="${o.value}" tabindex="0"></div>`)
      .join('');
  }

  it('opens the dropdown and focuses the first option whose label starts with the typed letter', () => {
    const { select } = loadSelect();
    const component = makeComponent(select, { options: OPTIONS, searchable: false });
    component.init();
    panelWithOptions(component, OPTIONS);

    component.onTypeahead('b');

    expect(component.openedWithKeyboard).toBe(true);
    expect(document.activeElement.getAttribute('data-option-value')).toBe('2'); // Bob
  });

  it('accumulates consecutive keystrokes into one buffer', () => {
    const { select } = loadSelect();
    const options = [{ value: '1', label: 'Alice' }, { value: '2', label: 'Alan' }];
    const component = makeComponent(select, { options, searchable: false });
    component.init();
    panelWithOptions(component, options);

    component.onTypeahead('a');
    component.onTypeahead('l');
    component.onTypeahead('i');

    expect(document.activeElement.getAttribute('data-option-value')).toBe('1'); // "ali" only matches Alice
  });

  it('is a no-op when the select is searchable (the search box already filters)', () => {
    const { select } = loadSelect();
    const component = makeComponent(select, { options: OPTIONS, searchable: true });
    component.init();
    panelWithOptions(component, OPTIONS);

    component.onTypeahead('b');

    expect(component.openedWithKeyboard).toBe(false);
  });

  it('ignores non-single-character keys (arrows, Enter, Escape...)', () => {
    const { select } = loadSelect();
    const component = makeComponent(select, { options: OPTIONS, searchable: false });
    component.init();
    panelWithOptions(component, OPTIONS);

    component.onTypeahead('ArrowDown');
    component.onTypeahead('Enter');

    expect(component.openedWithKeyboard).toBe(false);
  });

  it('does nothing when no option matches the buffer', () => {
    const { select } = loadSelect();
    const component = makeComponent(select, { options: OPTIONS, searchable: false });
    component.init();
    panelWithOptions(component, OPTIONS);

    component.onTypeahead('z');

    expect(component.openedWithKeyboard).toBe(false);
  });

  it('resets the buffer after the typeahead window elapses', () => {
    vi.useFakeTimers();
    const { select } = loadSelect();
    const options = [{ value: '1', label: 'Bob' }, { value: '2', label: 'Ann' }];
    const component = makeComponent(select, { options, searchable: false });
    component.init();
    panelWithOptions(component, options);

    component.onTypeahead('b');
    vi.advanceTimersByTime(600); // past the 500ms reset window
    component.onTypeahead('a'); // fresh buffer: "a", not "ba"

    expect(document.activeElement.getAttribute('data-option-value')).toBe('2'); // Ann
    vi.useRealTimers();
  });
});

describe('select(): preload on open (async)', () => {
  it('runs one initial empty-query search the first time the dropdown opens, by default', async () => {
    const call = vi.fn().mockResolvedValue([{ value: '9', label: 'Zed' }]);
    const { select } = loadSelect({ livewire: { dispatch: vi.fn() } });
    const component = makeComponent(select, { async: true, searchMethod: 'search' });
    component.$wire.call = call;
    component.init();

    component.openDropdown();
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(call).toHaveBeenCalledWith('search', '');
    expect(component.options).toEqual([{ value: '9', label: 'Zed' }]);
  });

  it('does not preload again on a later open', async () => {
    const call = vi.fn().mockResolvedValue([]);
    const { select } = loadSelect({ livewire: { dispatch: vi.fn() } });
    const component = makeComponent(select, { async: true, searchMethod: 'search' });
    component.$wire.call = call;
    component.init();

    component.openDropdown();
    await new Promise((resolve) => setTimeout(resolve, 0));
    call.mockClear();

    component.closeDropdown();
    component.openDropdown();
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(call).not.toHaveBeenCalled();
  });

  it('still lists the preloaded options after a selection, so the choice can be changed', async () => {
    const results = [{ value: '9', label: 'Zed' }, { value: '8', label: 'Yan' }];
    const { select } = loadSelect({ livewire: { dispatch: vi.fn() } });
    const component = makeComponent(select, { async: true, searchMethod: 'search' });
    component.$wire.call = vi.fn().mockResolvedValue(results);
    component.init();

    component.openDropdown();
    await new Promise((resolve) => setTimeout(resolve, 0));
    component.setSelectedOption(results[0]);
    component.openDropdown();

    expect(component.selectedValue).toBe('9');
    expect(component.options).toEqual(results);
  });

  it('fetches the unfiltered list again on open after selecting from a search', async () => {
    const call = vi.fn().mockResolvedValue([{ value: '9', label: 'Zed' }]);
    const { select } = loadSelect({ livewire: { dispatch: vi.fn() } });
    const component = makeComponent(select, { async: true, searchMethod: 'search' });
    component.$wire.call = call;
    component.init();

    component.openDropdown();
    await new Promise((resolve) => setTimeout(resolve, 0));
    component.searchQuery = 'ze';
    component.setSelectedOption({ value: '9', label: 'Zed' });
    call.mockClear();

    component.openDropdown();
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(call).toHaveBeenCalledWith('search', '');
    expect(component.options).toEqual([{ value: '9', label: 'Zed' }]);
  });

  it('does not preload when :preload="false"', async () => {
    const call = vi.fn().mockResolvedValue([]);
    const { select } = loadSelect({ livewire: { dispatch: vi.fn() } });
    const component = makeComponent(select, { async: true, searchMethod: 'search', preload: false });
    component.$wire.call = call;
    component.init();

    component.openDropdown();
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(call).not.toHaveBeenCalled();
  });

  it('does not preload for a non-async select', () => {
    const { select } = loadSelect({ livewire: { dispatch: vi.fn() } });
    const component = makeComponent(select, { options: OPTIONS });
    const call = vi.fn();
    component.$wire.call = call;
    component.init();

    component.openDropdown();

    expect(call).not.toHaveBeenCalled();
  });
});

describe('select(): creatable', () => {
  it('canCreate() is false when creatable is off, the query is empty, or it already matches an option', () => {
    const { select } = loadSelect();
    const component = makeComponent(select, { options: OPTIONS, creatable: false });
    component.init();

    component.searchQuery = 'Zed';
    expect(component.canCreate()).toBe(false); // creatable off

    component.creatable = true;
    component.searchQuery = '';
    expect(component.canCreate()).toBe(false); // empty query

    component.searchQuery = 'alice'; // case-insensitive exact match
    expect(component.canCreate()).toBe(false);

    component.searchQuery = 'Zed';
    expect(component.canCreate()).toBe(true);
  });

  it('createOption() with no create-method builds a client-only option from the typed text and selects it', () => {
    const { select } = loadSelect({ livewire: { dispatch: vi.fn() } });
    const component = makeComponent(select, { options: OPTIONS, creatable: true });
    component.init();
    component.searchQuery = 'Zed';

    component.createOption();

    expect(component.selectedOption).toEqual({ value: 'Zed', label: 'Zed' });
    expect(component.selectedValue).toBe('Zed');
    expect(component.allOptions).toContainEqual({ value: 'Zed', label: 'Zed' });
  });

  it('createOption() with a create-method calls $wire.call(), then adds and selects the server-returned option', async () => {
    const created = { value: '99', label: 'Zed' };
    const call = vi.fn().mockResolvedValue(created);
    const { select } = loadSelect({ livewire: { dispatch: vi.fn() } });
    const component = makeComponent(select, { options: OPTIONS, creatable: true, createMethod: 'store' });
    component.$wire.call = call;
    component.init();
    component.searchQuery = 'Zed';

    component.createOption();
    expect(component.creating).toBe(true);

    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(call).toHaveBeenCalledWith('store', 'Zed');
    expect(component.selectedOption).toEqual(created);
    expect(component.creating).toBe(false);
  });

  it('createOption() does nothing for a blank/whitespace-only query', () => {
    const { select } = loadSelect();
    const component = makeComponent(select, { options: OPTIONS, creatable: true });
    component.init();
    component.searchQuery = '   ';

    component.createOption();

    expect(component.selectedOption).toBeNull();
    expect(component.allOptions).toEqual(OPTIONS);
  });
});

describe('selectMulti(): creatable', () => {
  it('createOption() with no create-method adds the typed text to selectedValues', () => {
    const { selectMulti } = loadSelect();
    const component = makeComponent(selectMulti, { options: OPTIONS, selectedValues: [], creatable: true });
    component.init();
    component.searchQuery = 'Zed';

    component.createOption();

    expect(component.selectedValues).toEqual(['Zed']);
    expect(component.allOptions).toContainEqual({ value: 'Zed', label: 'Zed' });
  });

  it('createOption() with a create-method adds the server-returned option to the selection', async () => {
    const created = { value: '99', label: 'Zed' };
    const call = vi.fn().mockResolvedValue(created);
    const { selectMulti } = loadSelect();
    const component = makeComponent(selectMulti, { options: OPTIONS, selectedValues: ['1'], creatable: true, createMethod: 'store' });
    component.$wire.call = call;
    component.init();
    component.searchQuery = 'Zed';

    component.createOption();
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(component.selectedValues).toEqual(['1', '99']);
  });
});

describe('window.Select.setColorScheme()', () => {
  beforeEach(() => {
    loadSelect();
  });

  it('sets data-select-theme for an explicit scheme and clears it for "system"', () => {
    window.Select.setColorScheme('dark');
    expect(document.documentElement.getAttribute('data-select-theme')).toBe('dark');

    window.Select.setColorScheme('system');
    expect(document.documentElement.hasAttribute('data-select-theme')).toBe(false);
  });
});
