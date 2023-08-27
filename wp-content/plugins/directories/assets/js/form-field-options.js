'use strict';

(function($) {
  DRTS.Form.field.options = function(selector) {
    var $field = $(selector);
    if (!$field.length) return;

    $field.find('.drts-form-field-options').sortable({
      handle: '.drts-form-field-option-sort',
      containment: 'parent',
      axis: 'y',
      cursor: 'move',
      cancel: '.' + DRTS.bsPrefix + 'disabled'
    }).end().on('keyup', '.drts-form-field-option-new .drts-form-field-option-label', function() {
      var $val = $(this).closest('.drts-form-field-option').find('.drts-form-field-option-value:not(.drts-form-field-value-filled)');
      if ($val.length) {
        $val.val($val.data('slugify') ? this.value.latinise().replace(/\W+/g, '_').toLowerCase() : this.value);
      }
    }).on('keyup', '.drts-form-field-option-new .drts-form-field-option-value', function() {
      $(this).toggleClass('drts-form-field-value-filled', $this.val().length !== 0);
    }).find('.drts-form-field-option-csv').click(function() {
      var $this = $(this),
        tarea = $this.next();
      if (tarea.is(':visible')) {
        tarea.slideUp('fast');
        $this.removeClass(DRTS.bsPrefix + 'active').blur();
      } else {
        autosize(tarea.slideDown('fast').focus());
        $this.addClass(DRTS.bsPrefix + 'active');
      }
      return false;
    }).end().find('.drts-form-field-option-icon').each(function() {
      DRTS.Form.field.iconpicker.factory(this);
    }).end().find('.drts-form-field-option-color').each(function() {
      DRTS.Form.field.colorpicker($(this).closest('.drts-form-field-option'), {
        inputSelector: '.drts-form-field-option-color'
      });
    });
  };
  DRTS.Form.field.options.add = function(container, fieldName, trigger, isCheckbox, checked, callback) {
    var $container = $(container),
      $original = $(trigger).closest('.drts-form-field-option'),
      optionsSelector = $container.children('[class*="' + DRTS.bsPrefix + 'col-"]').length ? '> div > div > .drts-form-field-option' // horizontal fields
      :
      '> div > .drts-form-field-option',
      options = $container.find(optionsSelector),
      choiceName = isCheckbox ? fieldName + "[default][]" : fieldName + "[default]",
      i = $original.find("input[name='" + choiceName + "']").val(),
      option = $original.clone().toggleClass('drts-form-field-option-new', true).find(':text,:hidden').each(function() {
        var $this = $(this);
        if (!$this.attr('name')) return;
        $this.attr('name', $this.attr('name').replace(fieldName + '[options][' + i + ']', fieldName + '[options][' + options.length + ']'));
      }).end().clearInput().find("input[name='" + choiceName + "']").val(options.length).end(),
      icon = option.find('.drts-form-field-option-icon'),
      color = option.find('.drts-form-field-option-color');
    if (icon.length) {
      icon.data('current', '');
      DRTS.Form.field.iconpicker.factory(icon);
    }
    if (color.length) {
      option.find('.drts-form-field-option-color').css('background-color', '');
      DRTS.Form.field.colorpicker(option, {
        inputSelector: '.drts-form-field-option-color'
      });
    }
    if (checked) option.find("input[name='" + choiceName + "']").prop('checked', true);
    option.hide().insertAfter($original);
    if (callback) {
      callback.call(null, option);
    }
    option.slideDown(100);
    return false;
  };

  DRTS.Form.field.options.remove = function(container, trigger, confirmMsg) {
    var $container = $(container),
      options_non_disabled = $container.find('.drts-form-field-option:not(.drts-form-field-option-disabled)');
    if (options_non_disabled.length === 1) {
      // There must be at least one non-disabled optoin, so just clear it instead of removing
      options_non_disabled.clearInput();
      return;
    }
    // Confirm deletion
    if (!confirm(confirmMsg)) return false;
    $(trigger).closest('.drts-form-field-option').slideUp('fast', function() {
      $(this).remove();
    });
  };
})(jQuery);