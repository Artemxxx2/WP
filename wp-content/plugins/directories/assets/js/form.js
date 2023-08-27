'use strict';

(function($) {
  DRTS.Form = {
    field: {}
  };
  DRTS.Form.form = function(selector) {
    var $form = $(selector),
      buttonContainer = void 0;
    if (!$form.length) return;

    buttonContainer = $form.closest('.' + DRTS.bsPrefix + 'modal').length ? $('#drts-modal') : $form;
    buttonContainer.find('button[type=submit]:not(:disabled)').click(function(e) {
      var $this = $(this);
      // Clear placeholder values
      $form.find('[data-clear-placeholder]').each(function() {
        var input = $(this);
        if (input.data('clear-placeholder') && input.val() == input.attr('placeholder')) {
          input.val('');
        }
      });

      DRTS.Form.appendInvisibleFieldNames($form);

      // Form.serialize() will not include the value of submit button so append the value as a hidden element.
      $form.append($('<input>', {
        type: 'hidden',
        name: $this.attr('name'),
        value: $this.val()
      }));

      if ($form.data('force-submit') || $this.closest('.' + DRTS.bsPrefix + 'modal-footer').length // button was moved outside the form in modal window
      ) {
        $form.data('force-submit', false);
        $form.submit();
      }
    }).end().submit(function(e) {
      if (!e.isDefaultPrevented()) {
        DRTS.Form._maybeDisableSubmitBtn(buttonContainer);
      }
    });

    if (!$form.data('allow-enter-submit')) {
      DRTS.Form.preventEnterKeySubmit($form);
    }

    if (typeof acf !== 'undefined') {
      acf.validation.active = false;
    }

    return $form;
  };
  DRTS.Form.ajaxForm = function(selector, container, url, options) {
    var $form = $(selector),
      buttonContainer = void 0;
    if (!$form.length) return;

    buttonContainer = $form.closest('.' + DRTS.bsPrefix + 'modal').length ? $('#drts-modal') : $form;
    buttonContainer.find('button[type=submit]:not(:disabled)').click(function(e) {
      var $this = $(this),
        file_found = false;

      // Uploading file via ajax is not supported.
      $form.find('input[type^=file]').each(function() {
        if ($(this).attr('value')) {
          file_found = true;
          return false;
        }
      });
      if (file_found) {
        return true;
      }

      // Clear placeholder values
      $form.find('[data-clear-placeholder]').each(function() {
        var input = $(this);
        if (input.data('clear-placeholder') && input.val() == input.attr('placeholder')) {
          input.val('');
        }
      });

      DRTS.Form.appendInvisibleFieldNames($form);

      // Form.serialize() will not include the value of submit button so append the value as a hidden element.
      $form.append($('<input>', {
        type: 'hidden',
        name: $this.attr('name'),
        value: $this.val()
      }));

      if ($this.hasClass('drts-form-back-btn-no-ajax')) {
        return true;
      }

      e.preventDefault();

      $form.trigger('form_ajax_submit.sabai');

      var ajaxOptions = {
        trigger: $this,
        type: $form.attr('method'),
        container: container,
        target: options.target || '',
        url: url,
        data: $form.serialize(),
        scroll: false,
        onSuccess: options.onSuccess || null,
        onError: function onError(error, target, trigger, status) {
          DRTS.Form.handleError($form, error, target, trigger, status, options);
        },
        onContent: options.onContent || null,
        onReadyState: options.onReadyState || null,
        onSuccessFlash: options.onSuccessFlash || false,
        onSuccessRedirect: options.onSuccessRedirect || false,
        onErrorFlash: false,
        onErrorRedirect: options.onErrorRedirect || false,
        loadingImage: options.loadingImage || false,
        modalHideOnSend: options.modalHideOnSend || false,
        modalHideOnSuccess: options.modalHideOnSuccess || false
      };

      if (options.onSubmit) {
        options.onSubmit($form, $this, ajaxOptions);
      }

      DRTS.ajax(ajaxOptions);
    }).end().submit(function(e) {
      if (!e.isDefaultPrevented()) {
        DRTS.Form._maybeDisableSubmitBtn($form);
      }
    });

    if (!$form.data('allow-enter-submit')) {
      DRTS.Form.preventEnterKeySubmit($form);
    }

    return $form;
  };

  DRTS.Form.preventEnterKeySubmit = function(form) {
    $(form).on('keypress', 'input:not(textarea),select', function(e) {
      var key = e.keyCode || e.charCode || 0;
      if (key === 13) {
        e.preventDefault();
      }
    });
  };

  DRTS.Form._maybeDisableSubmitBtn = function(container) {
    var submitBtn = container.find('button[type=submit]');
    if (!submitBtn.length) return;

    if (!submitBtn.hasClass('drts-form-field-submit-safari-no-disable') || !/^((?!chrome|android).)*safari/i.test(navigator.userAgent)) {
      submitBtn.prop('disabled', true);
    }
  };

  // Fix issue with Safari showing disabled submit button when using browser Back button
  window.onpageshow = function(event) {
    if (event.persisted) {
      $('.drts-form button[type=submit]').prop('disabled', false);
    }
  };

  DRTS.Form.handleError = function(form, error, target, trigger, status, options) {
    options = $.extend({}, options);

    if (status === 422) {
      DRTS.Form.handle422Error(form, error);
      if (options.scroll) {
        DRTS.scrollTo(form, 1000);
      }
    } else {
      if (options.onError) {
        options.onError(error, target, trigger, status);
      }
      if (error.messages.length) {
        DRTS.Form.addFormErrorHeaders(form, error.messages);
      }
    }
  };
  DRTS.Form.addFormErrorHeaders = function(form, messages) {
    var headers = form.prev('.drts-form-headers');
    if (headers.length) {
      headers.text('');
      for (var i = 0; i < messages.length; i++) {
        headers.append($('<div class="' + DRTS.bsPrefix + 'alert ' + DRTS.bsPrefix + 'alert-danger"></div>').text(messages[i]));
      }
    }
  };
  DRTS.Form.handle422Error = function(form, error) {
    var messages = error.messages || [];

    $.each(error.errors, function(name, _error) {
      if (name === "") {
        // form level error
        messages.push(_error);
        return;
      }

      var ele = form.find('[name="' + name + '"]');
      if (!ele.length) {
        ele = form.find('[data-form-field-name="' + name + '"]');
      }
      if (ele.length) {
        var field = ele.closest('.drts-form-field');
        if (field.length) {
          field.addClass('drts-form-has-error').find('.drts-form-error').text(_error);
          if (field.hasClass('drts-form-states-invisible')) {
            field.removeClass('drts-form-states-invisible').show();
          } else if (field.closest('.drts-form-states-invisible').length) {
            field.closest('.drts-form-states-invisible').removeClass('drts-form-states-invisible').show();
          }
        }
      }
    });
    if (messages.length) {
      DRTS.Form.addFormErrorHeaders(form, messages);
    }
  };
  DRTS.Form.appendInvisibleFieldNames = function(form, wrap) {
    var invisibleFields = form.find('.drts-form-states-invisible');
    // Workaround for select hierarchy dropdowns that are hidden
    var hiddenSelectFields = $('.drts-form-type-select').filter(function() {
      var $this = $(this);
      return $this.css('display') === 'none' && !$this.hasClass('drts-form-states-invisible');
    });
    if (hiddenSelectFields.length) {
      invisibleFields = invisibleFields.add(hiddenSelectFields);
    }
    if (invisibleFields.length) {
      var invisibleFieldNames = [];
      invisibleFields.each(function(i, ele) {
        var $ele = $(ele);
        if ($ele.hasClass('drts-display-element') && $ele.find('> .drts-entity-form-field').length) {
          $ele = $ele.find('> .drts-entity-form-field').first();
        }
        if ($ele.data('form-field-name')) {
          invisibleFieldNames.push($ele.data('form-field-name'));
        }
        $ele.find('[data-form-field-name]').each(function(i2, ele2) {
          invisibleFieldNames.push($(ele2).data('form-field-name'));
        });
      });
      form.append($('<input>', {
        type: 'hidden',
        name: wrap ? wrap + '[_drts_form_invisible_fields]' : '_drts_form_invisible_fields',
        value: invisibleFieldNames.join(',')
      }));
    }
  };
})(jQuery);