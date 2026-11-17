(function (root) {
  'use strict';
  root.TableKitAdapters = root.TableKitAdapters || {};
  root.TableKitAdapters[3] = function (element, options) {
    options.layout = { topStart: null, topEnd: null, bottomStart: 'info', bottomEnd: 'paging' };
    return new root.DataTable(element, options);
  };
})(window);
