import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const SELECT_JS_PATH = path.resolve(__dirname, '../../../resources/js/select.js');
const SOURCE = readFileSync(SELECT_JS_PATH, 'utf8');

/**
 * select.js is a plain IIFE (no build step, no ES module — see the README)
 * that exposes window.Select (the color-scheme API) and
 * window.__selectFactory/__selectMultiFactory (the Alpine.data() factories,
 * exposed purely so they can be grabbed here without a real Alpine
 * runtime). Every test gets a clean slate by resetting the relevant
 * globals, then re-evaluating the script — exactly what a fresh <script>
 * tag on a fresh page load would do.
 */
export function loadSelect({ config = {}, livewire } = {}) {
  document.documentElement.removeAttribute('data-select-theme');

  delete window.Select;
  delete window.__selectFactory;
  delete window.__selectMultiFactory;
  delete window.Livewire;

  window.__SELECT_CONFIG__ = { colorScheme: null, dropdownEvents: {}, ...config };

  // Indirect eval forces global scope — window/document below resolve to
  // this test's jsdom globals, exactly like a real <script> tag would,
  // rather than this module's own local scope.
  (0, eval)(SOURCE);

  // Applied *after* the eval above (which wipes window.Livewire as part of
  // its "fresh page load" reset) — passing it as an option here, instead of
  // letting call sites set window.Livewire themselves before calling this,
  // avoids that reset silently swallowing the stub.
  if (livewire !== undefined) window.Livewire = livewire;

  return { select: window.__selectFactory, selectMulti: window.__selectMultiFactory };
}

/**
 * Builds a plain select()/selectMulti() instance wired up with fakeable
 * Alpine magic properties ($wire, $el, $refs, $nextTick, $watch) — enough
 * surface for every method in resources/js/select.js to run exactly as it
 * would inside a real Alpine component, without needing Alpine itself.
 */
export function makeComponent(factory, config) {
  const component = factory(config);
  const watchers = {};

  component.$wire = {
    call: () => Promise.resolve([]),
  };

  // Attached to the document (not just an in-memory node) — jsdom, like a
  // real browser, only updates document.activeElement for a focus() call
  // on an element that's actually in the tree, which the typeahead tests
  // rely on.
  const dropdownPanel = document.createElement('div');
  document.body.appendChild(dropdownPanel);

  component.$refs = {
    triggerWrapper: makeRect(),
    dropdownPanel: dropdownPanel,
    searchField: document.createElement('input'),
  };
  component.$nextTick = (cb) => cb();
  component.$watch = (prop, cb) => {
    watchers[prop] = cb;
  };

  component.__triggerWatch = (prop, value) => watchers[prop] && watchers[prop](value);

  return component;
}

function makeRect() {
  const el = document.createElement('div');
  el.getBoundingClientRect = () => ({ top: 100, bottom: 130, left: 20, width: 200, height: 30 });

  return el;
}
