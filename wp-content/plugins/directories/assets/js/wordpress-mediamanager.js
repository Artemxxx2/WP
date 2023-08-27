'use strict';

(function($) {
  DRTS.WordPress = DRTS.WordPress || {};
  DRTS.WordPress.mediamanager = DRTS.WordPress.mediamanager || function(o) {
    var options = $.extend({}, {
      selector: '',
      maxNumFiles: 0,
      maxNumFileExceededError: '',
      fileNotAllowedError: '',
      sortable: true,
      sortableHandle: false,
      deleteConfirm: 'Are you sure?'
    }, o);

    var $button = $(options.selector);
    if (!$button.length) return;

    var frames = {},
      table = $button.closest('.drts-form-type-wp-media-manager').find('.drts-wp-upload-current table');
    $button.on('click', function(e) {
      e.preventDefault();
      var $this = $(this),
        name = $this.data('input-name'),
        mtypes;

      if (!frames[name]) {
        mtypes = $this.data('mime-types').split(',');

        frames[name] = wp.media({
          frame: 'post',
          multiple: true,
          library: {
            type: mtypes
          }
        });
        frames[name].on('insert', function(e) {
          var json, row, imgsrc;
          frames[name].state().get('selection').each(function(file) {
            json = file.toJSON();
            if ($.inArray(json.mime, mtypes) === -1) {
              alert(options.fileNotAllowedError);
              return false;
            }

            if (typeof json.sizes !== 'undefined') {
              imgsrc = typeof json.sizes.thumbnail !== 'undefined' ? json.sizes.thumbnail.url : json.sizes.full.url;
            } else {
              imgsrc = json.icon;
            }
            row = '<tr class="drts-wp-file-row">' + '<td><img src="' + imgsrc + '" alt="' + json.title + '" /></td>' + '<td>' + json.title + '</td>' + '<td>' + json.filesizeHumanReadable + '</td>' + '<td><input name="' + name + '[current][' + json.id + '][check][]" type="hidden" value="' + json.id + '" />' + '<a href="#" class="drts-wp-file-row-delete ' + DRTS.bsPrefix + 'btn ' + DRTS.bsPrefix + 'btn-sm ' + DRTS.bsPrefix + 'btn-danger"><i class="fas fa-times"></i></a>' + '</tr></tr>';
            if (!table.has('.drts-wp-file-row').length) {
              table.find('tbody').empty();
            }
            $(row).appendTo(table.find('tbody'));
          });
        });
      }
      frames[name].open();
    });
    table.on('click', '.drts-wp-file-row-delete', function() {
      if (!confirm(options.deleteConfirm)) return false;

      var row = $(this).closest('.drts-wp-file-row');
      row.fadeTo('fast', 0, function() {
        row.slideUp('fast', function() {
          row.remove();
        });
      });
      return false;
    });
    $button.closest('form').submit(function() {
      if (options.maxNumFiles && table.find('input[type="checkbox"]:checked').length > options.maxNumFiles) {
        if (options.maxNumFileExceededError) alert(options.maxNumFileExceededError);
        $(this).find('button[type=submit]').prop('disabled', false);
        return false;
      }
    });
    if (options.sortable) {
      table.find('tbody').sortable({
        containment: 'parent',
        axis: 'y',
        handle: options.sortableHandle,
        cursor: 'move'
      });
    }
  };
})(jQuery);