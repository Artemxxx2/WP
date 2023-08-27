'use strict';

(function($) {
  DRTS.Form.field.upload = function(options) {
    options = $.extend({
      selector: '',
      maxNumFiles: 0,
      maxNumFileExceededError: '',
      inputName: 'files',
      sortable: true,
      formData: {},
      deleteConfirm: 'Are you sure?',
      sortableHandle: false,
      upload: true,
      flashPosition: 'center'
    }, options);

    var $uploader = $(options.selector);
    if (!$uploader.length) return;

    var $container = $uploader.closest('.drts-form-upload-container'),
      $progress = $container.find('.' + DRTS.bsPrefix + 'progress'),
      $progressBar = $progress.find('.' + DRTS.bsPrefix + 'progress-bar'),
      numFilesUploaded = 0,
      progress = 0;

    if (options.upload) {
      $uploader.fileupload({
        url: options.url,
        dataType: 'json',
        paramName: 'drts_form_upload',
        formData: options.formData,
        singleFileUploads: true,
        sequentialUploads: true,
        //forceIframeTransport: true,
        send: function send(e, data) {
          document.dispatchEvent(new CustomEvent('file-upload-start', {
            'detail': $container.data('form-field-name')
          }));
          if (options.maxNumFiles && numFilesUploaded + data.files.length > options.maxNumFiles) {
            if (options.maxNumFileExceededError) {
              DRTS.flash(options.maxNumFileExceededError, 'danger', null, options.flashPosition);
            }
            return false;
          }
          if ($progress.is(':hidden')) {
            $progressBar.attr('aria-valuenow', 0).css('width', '0%').text('0%').addClass(DRTS.bsPrefix + 'progress-bar-striped ' + DRTS.bsPrefix + 'progress-bar-animated');
            $progress.show();
          }
        },
        fail: function fail(e, data) {
          $progress.removeClass(DRTS.bsPrefix + 'progress-bar-striped ' + DRTS.bsPrefix + 'progress-bar-animated').hide();
        },
        done: function done(e, data) {
          if (data.result.error) {
            $progress.removeClass(DRTS.bsPrefix + 'progress-bar-striped ' + DRTS.bsPrefix + 'progress-bar-animated').hide();
            DRTS.flash(data.result.error, 'danger', null, options.flashPosition);
            return;
          }
          numFilesUploaded += data.files.length;
          var table = $container.find('.drts-form-upload-current').find('table');
          $.each(data.result.files, function(index, file) {
            var new_row = $('<tr class="drts-form-upload-row"/>'),
              check = $('<input type="hidden"/>').attr('name', options.inputName + '[current][' + file.id + '][check][]').val(file.id),
              name = $('<input class="' + DRTS.bsPrefix + 'form-control" type="text">').attr('name', options.inputName + '[current][' + file.id + '][name]').val(file.title),
              link = $('<a target="_blank"/>').attr('href', file.url),
              deleteBtn = $('<a href="#" class="drts-form-upload-row-delete ' + DRTS.bsPrefix + 'btn ' + DRTS.bsPrefix + 'btn-sm ' + DRTS.bsPrefix + 'btn-danger"/>').html('<i class="fas fa-times"></i>');
            if (file.thumbnail) {
              $('<td style="text-align:center;"/>').html(link.html($('<img/>').attr('src', file.thumbnail))).appendTo(new_row);
            } else {
              $('<td style="text-align:center;"/>').html(link.html($('<i/>').attr('class', file.icon))).appendTo(new_row);
            }
            $('<td/>').html(name).appendTo(new_row);
            $('<td/>').text(file.size_hr).appendTo(new_row);
            $('<td style="text-align:center;"/>').html(check).prepend(deleteBtn).appendTo(new_row);

            if (!table.has('.drts-form-upload-row').length) {
              table.find('tbody').empty();
            }
            new_row.appendTo(table.find('tbody'));
          });
          if (progress === 100) {
            if (options.sortable) {
              DRTS.init(table.find('tbody').sortable('destroy').sortable({
                containment: 'parent',
                axis: 'y'
              }).parent()); // reset table
            }
            $progress.removeClass(DRTS.bsPrefix + 'progress-bar-striped ' + DRTS.bsPrefix + 'progress-bar-animated').hide();
          }
        },
        progressall: function progressall(e, data) {
          progress = parseInt(data.loaded / data.total * 100, 10);
          $progressBar.attr('aria-valuenow', progress).css('width', progress + '%').text(progress + '%');
        },
        always: function always(e, data) {
          document.dispatchEvent(new CustomEvent('file-upload-end', {
            'detail': $container.data('form-field-name')
          }));
        }
      });
    }
    $container.on('click', '.drts-form-upload-row-delete', function() {
      if (!confirm(options.deleteConfirm)) return false;

      var row = $(this).closest('.drts-form-upload-row');
      row.fadeTo('fast', 0, function() {
        row.slideUp('fast', function() {
          --numFilesUploaded;
          row.remove();
        });
      });
      return false;
    });
    $uploader.closest('form').submit(function() {
      if (options.maxNumFiles && $container.find('.drts-form-upload-current tbody input[type="checkbox"]:checked').length > options.maxNumFiles) {
        if (options.maxNumFileExceededError) alert(options.maxNumFileExceededError);
        return false;
      }
    });
    if (options.sortable) {
      $container.find('.drts-form-upload-current tbody').sortable({
        containment: 'parent',
        axis: 'y',
        handle: options.sortableHandle,
        cursor: 'move'
      });
    }
  };
})(jQuery);