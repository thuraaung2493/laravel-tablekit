(function (root) {
  'use strict';
  var instances = Object.create(null);
  var pageVersion = null;

  function each(nodes, callback) {
    Array.prototype.forEach.call(nodes, callback);
  }

  function resolveVersion(expected) {
    var globalVersion = root.DataTable && root.DataTable.version;
    var jqueryVersion = root.jQuery && root.jQuery.fn.dataTable && root.jQuery.fn.dataTable.version;
    if (globalVersion && jqueryVersion && globalVersion !== jqueryVersion) {
      throw new Error('Conflicting DataTables engines are loaded on this page.');
    }
    var actual = globalVersion || jqueryVersion;
    if (!actual || !/^[123]\.\d+\.\d+$/.test(actual) || (actual.split('.')[0] === '1' && Number(actual.split('.')[1]) < 10)) {
      throw new Error('Load DataTables 1.10+, 2.x or 3.x before initializing TableKit.');
    }
    var major = actual.split('.')[0];
    if (expected !== 'auto' && expected !== major && expected !== actual) {
      throw new Error('TableKit expects DataTables ' + expected + ', but ' + actual + ' is loaded.');
    }
    if (pageVersion && pageVersion !== actual) {
      throw new Error('All tables on a page must share one DataTables engine.');
    }
    return { actual: actual, major: major };
  }

  function encode(data, prefix, result) {
    result = result || [];
    Object.keys(data).forEach(function (key) {
      var name = prefix ? prefix + '[' + key + ']' : key;
      var value = data[key];
      if (value !== null && typeof value === 'object') encode(value, name, result);
      else result.push(encodeURIComponent(name) + '=' + encodeURIComponent(value == null ? '' : value));
    });
    return result.join('&');
  }

  function create(shell) {
    var config = JSON.parse(shell.getAttribute('data-tk-config'));
    if (instances[config.id]) {
      if (instances[config.id].element !== shell) throw new Error('Duplicate TableKit ID: ' + config.id);
      return instances[config.id];
    }
    var version = resolveVersion(String(config.version));
    var adapter = root.TableKitAdapters && root.TableKitAdapters[version.major];
    if (!adapter) throw new Error('Load the TableKit adapter for DataTables ' + version.major + '.');
    var node = document.getElementById(config.id);
    var error = shell.querySelector('[data-tk-error]');
    var status = shell.querySelector('[data-tk-status]');
    var pending = null;
    var timer = null;
    var api;
    var destroyed = false;
    var filterValues = config.filters || {};
    var selected = [];
    var pageToken = '';

    function updateFilterCount() {
      var count = Object.keys(filterValues).filter(function (key) {
        var value = filterValues[key];
        if (value && typeof value === 'object') return Boolean(value.from || value.to);
        return value !== null && value !== '';
      }).length;
      each(shell.querySelectorAll('[data-tk-filter-count]'), function (output) { output.textContent = String(count); });
    }

    function updateSelection() {
      each(shell.querySelectorAll('[data-tk-selected-count]'), function (output) { output.textContent = String(selected.length); });
      each(shell.querySelectorAll('[data-tk-selection]'), function (bar) { bar.hidden = !selected.length; });
      var checkboxes = shell.querySelectorAll('[data-tk-select]');
      each(checkboxes, function (input) { input.checked = selected.indexOf(input.value) !== -1; });
      each(shell.querySelectorAll('[data-tk-select-page]'), function (input) {
        input.checked = checkboxes.length > 0 && selected.length === checkboxes.length;
        input.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
      });
      each(shell.querySelectorAll('[data-tk-bulk] button'), function (button) { button.disabled = !selected.length; });
    }

    function clearSelection() {
      selected = [];
      updateSelection();
    }

    function busy(value) {
      shell.setAttribute('aria-busy', value ? 'true' : 'false');
      status.textContent = value ? 'Loading records…' : '';
    }

    var options = {
      serverSide: true,
      processing: false,
      searching: config.settings.searchable,
      pageLength: config.settings.page_length,
      order: [],
      columns: config.columns.map(function (column) {
        // Titles come from escaped Blade HTML, not DataTables' HTML title setter.
        var copy = {};
        Object.keys(column).forEach(function (key) {
          if (key !== 'title' && column[key] !== null) copy[key] = column[key];
        });
        return copy;
      }),
      language: { emptyTable: 'No records available.', zeroRecords: 'No matching records.' },
      ajax: function (data, callback) {
        if (pending) pending.abort();
        busy(true);
        error.hidden = true;
        clearSelection();
        pageToken = '';
        data.filters = filterValues;
        var xhr = new XMLHttpRequest();
        pending = xhr;
        var separator = config.url.indexOf('?') === -1 ? '?' : '&';
        xhr.open('GET', config.url + separator + encode(data), true);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.timeout = 30000;
        function failed() {
          if (destroyed || pending !== xhr) return;
          pending = null;
          busy(false);
          error.textContent = 'Unable to load records. Check your connection or permissions and retry.';
          error.hidden = false;
          callback({ draw: Number(data.draw), recordsTotal: 0, recordsFiltered: 0, data: [] });
        }
        xhr.onload = function () {
          if (destroyed || pending !== xhr) return;
          if (xhr.status < 200 || xhr.status >= 300) return failed();
          var response;
          try { response = JSON.parse(xhr.responseText); } catch (e) { return failed(); }
          if (!Array.isArray(response.data) || typeof response.recordsTotal !== 'number'
              || typeof response.recordsFiltered !== 'number' || typeof response.draw !== 'number') return failed();
          pending = null;
          busy(false);
          pageToken = response.pageToken || '';
          callback(response);
          updateSelection();
        };
        xhr.onerror = failed;
        xhr.ontimeout = failed;
        xhr.send();
      }
    };
    var defaultSort = config.settings.default_sort;
    if (defaultSort) {
      config.columns.forEach(function (column, index) {
        if (column.data === defaultSort[0]) options.order = [[index, defaultSort[1]]];
      });
    }
    api = adapter(node, options);
    pageVersion = version.actual;

    function onInput(event) {
      if (!event.target.hasAttribute('data-tk-search')) return;
      var value = event.target.value;
      each(shell.querySelectorAll('[data-tk-search]'), function (input) { input.value = value; });
      clearTimeout(timer);
      timer = setTimeout(function () { api.search(value).draw(); }, config.settings.search_delay);
    }

    function onChange(event) {
      if (event.target.hasAttribute('data-tk-filter')) {
        var key = event.target.getAttribute('data-tk-filter');
        var bound = event.target.getAttribute('data-tk-bound');
        if (bound) {
          if (!filterValues[key] || typeof filterValues[key] !== 'object') filterValues[key] = {};
          filterValues[key][bound] = event.target.value;
        } else filterValues[key] = event.target.value;
        updateFilterCount();
        api.ajax.reload();
        return;
      }
      if (event.target.hasAttribute('data-tk-select-page')) {
        selected = [];
        if (event.target.checked) each(shell.querySelectorAll('[data-tk-select]'), function (input) { selected.push(input.value); });
        updateSelection();
        return;
      }
      if (event.target.hasAttribute('data-tk-select')) {
        selected = selected.filter(function (id) { return id !== event.target.value; });
        if (event.target.checked) selected.push(event.target.value);
        updateSelection();
        return;
      }
      if (!event.target.hasAttribute('data-tk-length')) return;
      var length = Number(event.target.value);
      each(shell.querySelectorAll('[data-tk-length]'), function (input) { input.value = String(length); });
      api.page.len(length).draw();
    }

    function resetFilters() {
      Object.keys(filterValues).forEach(function (key) { filterValues[key] = ''; });
      each(shell.querySelectorAll('[data-tk-filter]'), function (input) { input.value = ''; });
      updateFilterCount();
      api.ajax.reload();
    }

    var activeToggle = null;

    function setPanelOpen(open, trigger) {
      var panel = shell.querySelector('.tk-filters-panel');
      if (panel) {
        panel.hidden = !open;
        panel.classList.toggle('tk-open', open);
        if (open && shell.classList.contains('tk-filters-collapsible')) {
          activeToggle = trigger || activeToggle;
          panel.style.left = '';
          panel.style.right = '';
          if (activeToggle) {
            var body = shell.querySelector('.tk-body').getBoundingClientRect();
            var button = activeToggle.getBoundingClientRect();
            var width = panel.getBoundingClientRect().width;
            panel.style.left = Math.max(0, Math.min(button.left - body.left, body.width - width)) + 'px';
            panel.style.right = 'auto';
          }
        }
      }
      each(shell.querySelectorAll('[data-tk-toggle]'), function (button) {
        button.setAttribute('aria-expanded', String(open));
      });
    }

    function confirmAction(message, callback) {
      var dialog = document.createElement('dialog');
      if (typeof dialog.showModal !== 'function') {
        if (root.confirm(message)) callback();
        return;
      }
      dialog.className = 'tk-confirm';
      dialog.setAttribute('aria-label', 'Confirm action');
      var title = document.createElement('h2');
      title.textContent = 'Confirm action';
      var text = document.createElement('p');
      text.textContent = message;
      var actions = document.createElement('div');
      actions.className = 'tk-confirm-actions';
      var cancel = document.createElement('button');
      cancel.type = 'button';
      cancel.textContent = 'Cancel';
      var confirm = document.createElement('button');
      confirm.type = 'button';
      confirm.className = 'tk-confirm-primary';
      confirm.textContent = 'Confirm';
      cancel.addEventListener('click', function () { dialog.close('cancel'); });
      confirm.addEventListener('click', function () { dialog.close('confirm'); });
      dialog.addEventListener('close', function () {
        var approved = dialog.returnValue === 'confirm';
        dialog.parentNode.removeChild(dialog);
        if (approved) callback();
      });
      actions.appendChild(cancel);
      actions.appendChild(confirm);
      dialog.appendChild(title);
      dialog.appendChild(text);
      dialog.appendChild(actions);
      document.body.appendChild(dialog);
      dialog.showModal();
      cancel.focus();
    }

    function onClick(event) {
      var target = event.target;
      while (target && target !== shell) {
        if (target.hasAttribute('data-tk-toggle')) {
          var open = target.getAttribute('aria-expanded') !== 'true';
          setPanelOpen(open, target);
          return;
        }
        if (target.hasAttribute('data-tk-reset')) { resetFilters(); return; }
        if (target.tagName === 'A' && target.hasAttribute('data-tk-confirm')) {
          event.preventDefault();
          var link = target;
          confirmAction(link.getAttribute('data-tk-confirm'), function () { root.location.assign(link.href); });
          return;
        }
        target = target.parentElement;
      }
    }

    function hiddenInput(form, name, value) {
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      input.value = value;
      input.setAttribute('data-tk-submitted', '');
      form.appendChild(input);
    }

    function onSubmit(event) {
      var form = event.target;
      if (form.hasAttribute('data-tk-bulk')) {
        each(form.querySelectorAll('[data-tk-submitted]'), function (input) { input.parentNode.removeChild(input); });
        if (!selected.length || !pageToken) { event.preventDefault(); return; }
        selected.forEach(function (id) { hiddenInput(form, 'ids[]', id); });
        hiddenInput(form, 'page_token', pageToken);
      }
      if (form.hasAttribute('data-tk-confirm')) {
        event.preventDefault();
        confirmAction(form.getAttribute('data-tk-confirm'), function () { form.submit(); });
      }
    }

    function onMenuToggle(event) {
      var details = event.target;
      if (details.tagName !== 'DETAILS' || !details.open) return;
      var menu = details.querySelector('.tk-action-menu');
      var summary = details.querySelector('summary');
      if (!menu || !summary) return;
      var button = summary.getBoundingClientRect();
      var box = menu.getBoundingClientRect();
      menu.style.right = 'auto';
      menu.style.left = Math.max(8, Math.min(button.right - box.width, root.innerWidth - box.width - 8)) + 'px';
      menu.style.top = (button.bottom + box.height > root.innerHeight ? Math.max(8, button.top - box.height) : button.bottom) + 'px';
    }

    function closeMenusOnScroll() {
      each(shell.querySelectorAll('.tk-row-actions details[open]'), function (details) { details.open = false; });
    }

    function onResize() {
      var panel = shell.querySelector('.tk-filters-panel');
      if (panel && !panel.hidden && shell.classList.contains('tk-filters-collapsible')) setPanelOpen(true);
      closeMenusOnScroll();
    }

    function onDocumentClick(event) {
      if (!shell.classList.contains('tk-filters-collapsible')) return;
      var panel = shell.querySelector('.tk-filters-panel');
      if (!panel || panel.hidden || panel.contains(event.target)) return;
      var target = event.target;
      while (target && target !== shell) {
        if (target.hasAttribute && target.hasAttribute('data-tk-toggle')) return;
        target = target.parentElement;
      }
      setPanelOpen(false);
    }

    function onKeyDown(event) {
      if (event.key !== 'Escape' || !shell.classList.contains('tk-filters-collapsible')) return;
      var panel = shell.querySelector('.tk-filters-panel');
      if (!panel || panel.hidden) return;
      setPanelOpen(false);
      var toggle = shell.querySelector('[data-tk-toggle]');
      if (toggle) toggle.focus();
    }

    var sidebarMedia = null;
    function syncSidebar(event) {
      var panel = shell.querySelector('.tk-filters-panel');
      var open = !event.matches;
      panel.hidden = !open;
      panel.classList.toggle('tk-open', open);
      each(shell.querySelectorAll('[data-tk-toggle]'), function (button) {
        button.setAttribute('aria-expanded', String(open));
      });
    }
    if (shell.classList.contains('tk-filters-sidebar') && shell.querySelector('.tk-filters-panel') && root.matchMedia) {
      sidebarMedia = root.matchMedia('(max-width: 640px)');
      syncSidebar(sidebarMedia);
      sidebarMedia.addListener(syncSidebar);
    }
    updateFilterCount();
    updateSelection();
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('scroll', closeMenusOnScroll, true);
    root.addEventListener('resize', onResize);
    shell.addEventListener('toggle', onMenuToggle, true);
    document.addEventListener('keydown', onKeyDown);
    shell.addEventListener('click', onClick);
    shell.addEventListener('submit', onSubmit);
    shell.addEventListener('input', onInput);
    shell.addEventListener('change', onChange);
    var instance = {
      element: shell,
      dataTable: api,
      reload: function () { api.ajax.reload(null, false); },
      search: function (value) {
        clearTimeout(timer);
        each(shell.querySelectorAll('[data-tk-search]'), function (input) { input.value = value; });
        api.search(value).draw();
      },
      resetFilters: resetFilters,
      setFilter: function (key, value) {
        if (!Object.prototype.hasOwnProperty.call(filterValues, key)) throw new Error('Unknown TableKit filter.');
        filterValues[key] = value;
        each(shell.querySelectorAll('[data-tk-filter]'), function (input) {
          if (input.getAttribute('data-tk-filter') !== key) return;
          var bound = input.getAttribute('data-tk-bound');
          input.value = bound ? (value && value[bound] || '') : (value || '');
        });
        updateFilterCount();
        api.ajax.reload();
      },
      getSelectedIds: function () { return selected.slice(); },
      clearSelection: clearSelection,
      destroy: function () {
        destroyed = true;
        if (pending) pending.abort();
        clearTimeout(timer);
        document.removeEventListener('click', onDocumentClick);
        document.removeEventListener('scroll', closeMenusOnScroll, true);
        root.removeEventListener('resize', onResize);
        shell.removeEventListener('toggle', onMenuToggle, true);
        document.removeEventListener('keydown', onKeyDown);
        shell.removeEventListener('click', onClick);
        shell.removeEventListener('submit', onSubmit);
        shell.removeEventListener('input', onInput);
        shell.removeEventListener('change', onChange);
        if (sidebarMedia) sidebarMedia.removeListener(syncSidebar);
        busy(false);
        api.destroy();
        delete instances[config.id];
      }
    };
    instances[config.id] = instance;
    return instance;
  }

  root.TableKit = {
    get: function (id) { return instances[id]; },
    resolveVersion: resolveVersion,
    init: function (scope) {
      each((scope || document).querySelectorAll('[data-tk-root]'), function (shell) {
        try { create(shell); }
        catch (error) {
          var output = shell.querySelector('[data-tk-error]');
          if (output) { output.textContent = error.message; output.hidden = false; }
          if (root.console) root.console.error(error);
        }
      });
    }
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { root.TableKit.init(); });
  else root.TableKit.init();
})(window);
