(function (root) {
  'use strict';
  root.TableKitAdapters = root.TableKitAdapters || {};
  root.TableKitAdapters[2] = function (element, options) {
    if (!root.jQuery || !root.jQuery.fn.DataTable) {
      throw new Error('DataTables 2.x requires the application to load jQuery.');
    }
    options.layout = { topStart: null, topEnd: null, bottomStart: 'info', bottomEnd: 'paging' };
    return root.jQuery(element).DataTable(options);
  };
})(window);
