'use strict';

(function($) {
  DRTS.Form.field.file = function(selector, name, options) {
    var numFilesUploaded = 0,
      $uploader,
      field,
      progressBarContainer,
      progressBar;

    options = $.extend({
      maxNumFiles: 0,
      maxNumFileExceededError: '',
      paramName: 'drts_form_upload',
      formData: {},
      onError: function onError(result) {
        alert(result.messages[0].msg);
      },
      onMaxNumFileExceededError: null,
      onSuccess: null,
      uploadUrl: null
    }, options);

    if (!selector || !name || !options.uploadUrl) {
      return;
    }

    $uploader = $(selector);
    if (!$uploader.length) return;

    field = $uploader.closest('.drts-form-type-file');
    if (!field.length) return;

    if (!options.onSuccess) {
      options.onSuccess = function(result) {
        var files = [],
          disable = options.maxNumFiles && numFilesUploaded >= options.maxNumFiles;
        $.each(result, function(i, data) {
          files.push('<span class="' + DRTS.bsPrefix + 'badge ' + DRTS.bsPrefix + 'badge-secondary ' + DRTS.bsPrefix + (DRTS.isRTL ? 'mr-2' : 'ml-2') + ' drts-form-file-uploaded">' + data.name + ' <i class="fas fa-times"></i><input name="' + name + '[selected][]" type="hidden" value="' + data.saved_file_name + '" checked="checked" /></span>\n');
        });
        field.find('input[type=file]').prop('disabled', disable).parent('.' + DRTS.bsPrefix + 'btn').after(files).toggleClass(DRTS.bsPrefix + 'disabled', disable);
      };
      field.on('click', '.drts-form-file-uploaded > i', function() {
        $(this).closest('.drts-form-file-uploaded').remove();
        --numFilesUploaded;
        var disable = options.maxNumFiles && numFilesUploaded >= options.maxNumFiles;
        field.find('input[type=file]').prop('disabled', disable).parent('.' + DRTS.bsPrefix + 'btn').toggleClass(DRTS.bsPrefix + 'disabled', disable);
      });
      field.on('hover', '.drts-form-file-uploaded > i', function() {
        $(this).css('cursor', 'pointer');
      });
    }

    progressBarContainer = field.find('.' + DRTS.bsPrefix + 'progress');
    if (progressBarContainer.length) {
      progressBar = progressBarContainer.find('.' + DRTS.bsPrefix + 'progress-bar');
      if (!progressBar.length) {
        progressBar = null;
      }
    }

    $uploader.fileupload({
      url: options.uploadUrl,
      dataType: 'json',
      paramName: options.paramName,
      formData: options.formData,
      singleFileUploads: true,
      sequentialUploads: true,
      //forceIframeTransport: true,
      send: function send(e, data) {
        if (options.maxNumFiles && numFilesUploaded + data.files.length > options.maxNumFiles) {
          if (options.onMaxNumFileExceededError) {
            options.onMaxNumFileExceededError(numFilesUploaded);
          }
          return false;
        }
        if (progressBar) {
          progressBar.attr('aria-valuenow', 0).css('width', '0%').text('0%');
          progressBarContainer.show();
        }
      },
      fail: function fail(e, data) {
        if (progressBar) {
          progressBarContainer.hide();
        }
        if (options.onError) {
          try {
            var error = JSON.parse(data.jqXHR.responseText.replace(/<!--[\s\S]*?-->/g, ''));
            options.onError(error);
          } catch (e) {
            console.log(e.toString());
          }
        }
      },
      done: function done(e, data) {
        if (progressBar) {
          progressBarContainer.hide();
        }
        numFilesUploaded += data.result.length;
        if (options.onSuccess) {
          options.onSuccess(data.result);
        }
      },
      progressall: function progressall(e, data) {
        if (progressBar) {
          var progress = parseInt(data.loaded / data.total * 100, 10);
          progressBar.attr('aria-valuenow', progress).css('width', progress + '%').text(progress + '%');
        }
      }
    });
  };
})(jQuery);