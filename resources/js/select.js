/*!
 * Select — front-end runtime. No build step: this file is served directly by
 * the package (config('select.assets.serve')) or published and included as
 * a plain <script>. It registers two Alpine components, `select` and
 * `selectMulti`, used by resources/views/components/select.blade.php and
 * multi.blade.php — Livewire itself bundles Alpine, so nothing extra needs
 * to be loaded.
 */
(function () {
  'use strict';

  var COLOR_SCHEME_KEY = 'select:color-scheme';

  function applyColorScheme(scheme) {
    var root = document.documentElement;

    if (scheme === 'dark' || scheme === 'light') {
      root.setAttribute('data-select-theme', scheme);
    } else {
      root.removeAttribute('data-select-theme');
    }
  }

  function initColorScheme() {
    var stored = null;

    try {
      stored = window.localStorage.getItem(COLOR_SCHEME_KEY);
    } catch (e) {
      // localStorage unavailable (private mode, disabled) — fall back to config/system
    }

    var cfg = window.__SELECT_CONFIG__ || {};
    applyColorScheme(stored || cfg.colorScheme || 'system');
  }

  window.Select = {
    /** @param {'light'|'dark'|'system'} scheme */
    setColorScheme: function (scheme) {
      try {
        window.localStorage.setItem(COLOR_SCHEME_KEY, scheme);
      } catch (e) {
        // ignore, same as initColorScheme() above
      }

      applyColorScheme(scheme);
    },
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initColorScheme);
  } else {
    initColorScheme();
  }

  // ------------------------------------------------------------- shared --

  /** cfg.dropdownEvents falls back to the same default names Modal itself
   *  uses (config('modal.dropdown_events')), so a select works inside a
   *  Modal dialog with zero configuration on either side. */
  function dropdownEventNames() {
    var cfg = (window.__SELECT_CONFIG__ && window.__SELECT_CONFIG__.dropdownEvents) || {};

    return {
      opened: cfg.opened || 'modal-overlay-opened',
      closed: cfg.closed || 'modal-overlay-closed',
    };
  }

  function debounce(fn, wait) {
    var timer = null;

    var debounced = function () {
      var args = arguments;
      var self = this;

      clearTimeout(timer);
      timer = setTimeout(function () {
        fn.apply(self, args);
      }, wait);
    };

    debounced.cancel = function () {
      clearTimeout(timer);
    };

    return debounced;
  }

  /**
   * Groups a flat option list by groupKey (an option's own field name), in
   * order of each group's first appearance — always returns the same
   * uniform [{ group, options }] shape, with a single { group: null, ... }
   * bucket when groupKey isn't set, so the Blade templates never need to
   * branch on whether grouping is active.
   */
  function groupOptions(options, groupKey) {
    if (!groupKey) {
      return [{ group: null, options: options }];
    }

    var order = [];
    var buckets = Object.create(null);

    options.forEach(function (option) {
      var group = option[groupKey] || null;
      var key = group === null ? '\u0000ungrouped' : String(group);

      if (!buckets[key]) {
        buckets[key] = { group: group, options: [] };
        order.push(key);
      }

      buckets[key].options.push(option);
    });

    return order.map(function (key) {
      return buckets[key];
    });
  }

  /** Best-effort CSS attribute-value escaping — realistic option values
   *  (ids, slugs) never need more than this. */
  function escapeAttributeValue(value) {
    return String(value).replace(/["\\]/g, '\\$&');
  }

  function matchesQuery(option, labelKey, query) {
    return String(option[labelKey]).toLowerCase().indexOf(query.toLowerCase()) !== -1;
  }

  function existsWithLabel(options, labelKey, query) {
    var normalized = query.toLowerCase();

    return options.some(function (option) {
      return String(option[labelKey]).toLowerCase() === normalized;
    });
  }

  /**
   * Shared positioning/teleport/outside-click/scroll-lock behavior for both
   * select() and selectMulti() below — factored out once, rather than
   * copy-pasted per variant (which is exactly how the four ad-hoc select
   * components this package replaces drifted out of sync with each other:
   * one of them shipped with no teleport and a much lower z-index than the
   * rest, and would get clipped inside any overflow-hidden ancestor).
   */
  function dropdownBehavior() {
    return {
      isOpen: false,
      openedWithKeyboard: false,
      dropdownStyles: {},
      _handleOutsideClick: null,
      _preventParentScroll: null,

      updateDropdownPosition: function () {
        var rect = this.$refs.triggerWrapper.getBoundingClientRect();
        var dropdownMaxHeight = 320;
        var spaceBelow = window.innerHeight - rect.bottom;
        var spaceAbove = rect.top;

        if (spaceBelow < dropdownMaxHeight && spaceAbove > spaceBelow) {
          this.dropdownStyles = {
            position: 'fixed',
            bottom: (window.innerHeight - rect.top + 4) + 'px',
            left: rect.left + 'px',
            width: rect.width + 'px',
          };
        } else {
          this.dropdownStyles = {
            position: 'fixed',
            top: (rect.bottom + 4) + 'px',
            left: rect.left + 'px',
            width: rect.width + 'px',
          };
        }
      },

      openDropdown: function () {
        this.isOpen = true;
        Livewire.dispatch(dropdownEventNames().opened);

        var self = this;
        this.$nextTick(function () {
          self.updateDropdownPosition();
          if (self.$refs.searchField) {
            self.$refs.searchField.focus({ preventScroll: true });
          }
        });
      },

      closeDropdown: function () {
        if (this.isOpen === false) {
          return;
        }

        this.isOpen = false;
        this.openedWithKeyboard = false;
        Livewire.dispatch(dropdownEventNames().closed);
      },

      toggle: function () {
        if (this.isOpen) {
          this.closeDropdown();
        } else {
          this.openDropdown();
        }
      },

      groupedOptions: function () {
        return groupOptions(this.options, this.optionGroup);
      },

      bindGlobalListeners: function () {
        var self = this;

        this._handleOutsideClick = function (e) {
          if (!self.isOpen) return;

          var insideTrigger = self.$refs.triggerWrapper && self.$refs.triggerWrapper.contains(e.target);
          var insideDropdown = self.$refs.dropdownPanel && self.$refs.dropdownPanel.contains(e.target);

          if (!insideTrigger && !insideDropdown) {
            self.closeDropdown();
          }
        };
        document.addEventListener('mousedown', this._handleOutsideClick);

        this._preventParentScroll = function (e) {
          if (self.isOpen && self.$refs.dropdownPanel && !self.$refs.dropdownPanel.contains(e.target)) {
            e.preventDefault();
          }
        };
        window.addEventListener('wheel', this._preventParentScroll, { passive: false, capture: true });
        window.addEventListener('touchmove', this._preventParentScroll, { passive: false, capture: true });
      },

      unbindGlobalListeners: function () {
        document.removeEventListener('mousedown', this._handleOutsideClick);
        window.removeEventListener('wheel', this._preventParentScroll, { capture: true });
        window.removeEventListener('touchmove', this._preventParentScroll, { capture: true });
      },
    };
  }

  /**
   * Shared "type a new option in, add it to the list" behavior for both
   * select() and selectMulti() — createMethod (a $wire.call()) is optional:
   * without one, the typed text is used as both the value and the label of
   * a purely client-side option, useful for free-text tagging with no
   * server round-trip at all.
   */
  function creatableBehavior() {
    return {
      canCreate: function () {
        if (!this.creatable) return false;

        var query = (this.searchQuery || '').trim();
        if (!query) return false;

        return !existsWithLabel(this.options, this.optionLabel, query);
      },

      buildCreatedOption: function (query) {
        var option = {};
        option[this.optionValue] = query;
        option[this.optionLabel] = query;

        return option;
      },
    };
  }

  // -------------------------------------------------------------- select --

  /**
   * Single select: simple (searchable: false), client-filtered
   * (searchable: true), or server-driven (async: true — calls
   * searchMethod over the wire instead of filtering options locally).
   */
  function select(config) {
    config = config || {};

    var base = dropdownBehavior();
    var baseOpenDropdown = base.openDropdown;

    return Object.assign({}, base, creatableBehavior(), {
      options: config.options || [],
      allOptions: config.options || [],
      optionLabel: config.optionLabel || 'label',
      optionValue: config.optionValue || 'value',
      optionGroup: config.optionGroup || null,
      optionAvatar: config.optionAvatar || null,
      optionDescription: config.optionDescription || null,
      searchable: config.searchable === true,
      async: config.async === true,
      searchMethod: config.searchMethod || null,
      preload: config.preload !== false,
      creatable: config.creatable === true,
      createMethod: config.createMethod || null,
      clearable: config.clearable !== false,
      selectedOption: config.selectedOption || null,
      selectedValue: config.selectedValue,
      searchQuery: '',
      loading: false,
      creating: false,
      _debouncedSearch: null,
      _hasPreloaded: false,
      _typeaheadBuffer: '',
      _typeaheadTimer: null,

      init: function () {
        this.bindGlobalListeners();
        this._debouncedSearch = debounce(this._runSearch.bind(this), config.debounceMs || 300);

        var initial = this.selectedValue;
        if (initial !== null && initial !== undefined && initial !== '' && !this.async) {
          this.selectedOption = this.allOptions.find(function (o) {
            return String(o[this.optionValue]) === String(initial);
          }.bind(this)) || null;
        }

        this.$watch('selectedValue', function (value) {
          if (value === null || value === undefined || value === '') {
            this.selectedOption = null;
          } else if (!this.async && (!this.selectedOption || String(this.selectedOption[this.optionValue]) !== String(value))) {
            this.selectedOption = this.allOptions.find(function (o) {
              return String(o[this.optionValue]) === String(value);
            }.bind(this)) || null;
          }
        }.bind(this));
      },

      destroy: function () {
        this.unbindGlobalListeners();
        if (this._debouncedSearch) this._debouncedSearch.cancel();
        clearTimeout(this._typeaheadTimer);
      },

      /**
       * Preloads the first page of async results the moment the dropdown
       * opens (rather than leaving it empty until the user types), unless
       * :preload="false" opts back into the "empty until typed" behavior —
       * see the base openDropdown() this wraps, in dropdownBehavior().
       */
      openDropdown: function () {
        baseOpenDropdown.call(this);

        if (this.async && this.preload && !this._hasPreloaded) {
          this._hasPreloaded = true;
          this.loading = true;
          this._runSearch(this.searchQuery || '');
        }
      },

      selectedLabelText: function (fallbackLabel) {
        if (this.selectedOption) {
          return this.selectedOption[this.optionLabel];
        }

        return fallbackLabel || null;
      },

      setSelectedOption: function (option) {
        this.selectedOption = option;
        this.selectedValue = option[this.optionValue];
        this.closeDropdown();
        this.searchQuery = '';
        this.options = this.allOptions;
      },

      clearSelection: function () {
        this.selectedOption = null;
        this.selectedValue = '';
        this.closeDropdown();
      },

      onSearchInput: function (query) {
        this.searchQuery = query;

        if (this.async) {
          this.loading = true;
          this._debouncedSearch(query);

          return;
        }

        this.options = this.allOptions.filter(function (option) {
          return matchesQuery(option, this.optionLabel, query);
        }.bind(this));
      },

      _runSearch: function (query) {
        if (!this.searchMethod) {
          this.loading = false;

          return;
        }

        var self = this;

        this.$wire.call(this.searchMethod, query).then(function (results) {
          self.options = results || [];
          self.loading = false;
        }).catch(function () {
          self.loading = false;
        });
      },

      createOption: function () {
        var query = (this.searchQuery || '').trim();
        if (!query) return;

        if (this.createMethod) {
          this.creating = true;
          var self = this;

          this.$wire.call(this.createMethod, query).then(function (option) {
            self.allOptions = self.allOptions.concat([option]);
            self.creating = false;
            self.setSelectedOption(option);
          }).catch(function () {
            self.creating = false;
          });

          return;
        }

        var option = this.buildCreatedOption(query);
        this.allOptions = this.allOptions.concat([option]);
        this.setSelectedOption(option);
      },

      /**
       * Restores letter-jump navigation for the plain (non-searchable)
       * variant — dropped by accident when the four originals this package
       * replaces were unified, since only the searchable ones kept a
       * filter box to fall back on. A search box already gives strictly
       * better filtering, so this is a no-op whenever one is present
       * (searchable, async, or creatable all imply one).
       */
      onTypeahead: function (key) {
        if (this.searchable || !key || key.length !== 1 || !/[a-z0-9]/i.test(key)) {
          return;
        }

        var self = this;
        this._typeaheadBuffer = (this._typeaheadBuffer || '') + key.toLowerCase();
        clearTimeout(this._typeaheadTimer);
        this._typeaheadTimer = setTimeout(function () {
          self._typeaheadBuffer = '';
        }, 500);

        var buffer = this._typeaheadBuffer;
        var match = this.options.find(function (option) {
          return String(option[this.optionLabel]).toLowerCase().indexOf(buffer) === 0;
        }.bind(this));

        if (!match) return;

        this.openedWithKeyboard = true;
        this.$nextTick(function () {
          var panel = self.$refs.dropdownPanel;
          var target = panel && panel.querySelector(
            '[data-option-value="' + escapeAttributeValue(match[self.optionValue]) + '"]'
          );
          if (target) target.focus();
        });
      },
    });
  }

  // --------------------------------------------------------- selectMulti --

  /** Multi-select: always client-filtered, always shows a search field. */
  function selectMulti(config) {
    config = config || {};

    return Object.assign({}, dropdownBehavior(), creatableBehavior(), {
      options: config.options || [],
      allOptions: config.options || [],
      optionLabel: config.optionLabel || 'label',
      optionValue: config.optionValue || 'value',
      optionGroup: config.optionGroup || null,
      optionAvatar: config.optionAvatar || null,
      optionDescription: config.optionDescription || null,
      creatable: config.creatable === true,
      createMethod: config.createMethod || null,
      searchQuery: '',
      selectedValues: config.selectedValues || [],
      creating: false,

      init: function () {
        this.bindGlobalListeners();
      },

      destroy: function () {
        this.unbindGlobalListeners();
      },

      isSelected: function (value) {
        return this.selectedValues.indexOf(value) !== -1;
      },

      toggleOption: function (value) {
        if (this.isSelected(value)) {
          this.selectedValues = this.selectedValues.filter(function (v) {
            return v !== value;
          });
        } else {
          this.selectedValues = this.selectedValues.concat([value]);
        }
      },

      selectAll: function () {
        this.selectedValues = this.allOptions.map(function (o) {
          return o[this.optionValue];
        }.bind(this));
      },

      clearAll: function () {
        this.selectedValues = [];
      },

      onSearchInput: function (query) {
        this.searchQuery = query;
        this.options = this.allOptions.filter(function (option) {
          return matchesQuery(option, this.optionLabel, query);
        }.bind(this));
      },

      createOption: function () {
        var query = (this.searchQuery || '').trim();
        if (!query) return;

        if (this.createMethod) {
          this.creating = true;
          var self = this;

          this.$wire.call(this.createMethod, query).then(function (option) {
            self.allOptions = self.allOptions.concat([option]);
            self.selectedValues = self.selectedValues.concat([option[self.optionValue]]);
            self.creating = false;
          }).catch(function () {
            self.creating = false;
          });

          return;
        }

        var option = this.buildCreatedOption(query);
        this.allOptions = this.allOptions.concat([option]);
        this.selectedValues = this.selectedValues.concat([option[this.optionValue]]);
      },

      // The selected labels, comma-separated (the trigger ellipsizes a long list and the count
      // badge sits next to it) — a bare count said how many, never which. Falls back to the
      // count when no selected value matches a loaded option.
      summaryText: function (noneLabel) {
        var count = this.selectedValues.length;

        if (count === 0) return noneLabel;

        var labels = this.selectedValues
          .map(function (value) {
            var opt = this.allOptions.find(function (o) { return o[this.optionValue] === value; }.bind(this));

            return opt ? opt[this.optionLabel] : null;
          }.bind(this))
          .filter(function (label) { return label !== null && label !== undefined && label !== ''; });

        return labels.length > 0 ? labels.join(', ') : String(count);
      },
    });
  }

  // Exposed for the zero-build-step test harness (tests/js) to grab the raw
  // factories without a real Alpine runtime — never used at runtime otherwise.
  window.__selectFactory = select;
  window.__selectMultiFactory = selectMulti;

  document.addEventListener('alpine:init', function () {
    Alpine.data('select', select);
    Alpine.data('selectMulti', selectMulti);
  });
})();
