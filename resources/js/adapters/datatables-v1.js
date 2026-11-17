(function (root) {
  'use strict';
  root.TableKitAdapters = root.TableKitAdapters || {};
  root.TableKitAdapters[1] = function (element, options) {
    if (!root.jQuery || !root.jQuery.fn.DataTable) {
      throw new Error('DataTables 1.x requires the application to load jQuery.');
    }
    options.dom = 'rt<"tk-footer"ip>';
    return root.jQuery(element).DataTable(options);
  };
})(window);
