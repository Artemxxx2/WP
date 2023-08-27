'use strict';

(function($) {
  DRTS.Form.field.rangelist = function(selector) {
    var $field = $(selector);
    if (!$field.length) return;

    $field.find('.drts-form-field-rangelist').sortable({
      handle: '.drts-form-field-rangelist-option-sort',
      containment: 'parent',
      axis: 'y',
      cursor: 'move',
      cancel: '.' + DRTS.bsPrefix + 'disabled'
    });
  };
  DRTS.Form.field.rangelist.add = function(fieldName, trigger, callback) {
    var $original = $(trigger).closest('.drts-form-field-rangelist-option'),
      option = $original.clone().toggleClass('drts-form-field-rangelist-option-new', true).clearInput().hide().insertAfter($original);
    if (callback) {
      callback.call(null, option);
    }
    option.slideDown(100);
    return false;
  };

  DRTS.Form.field.rangelist.remove = function(trigger, confirmMsg) {
    if (!confirm(confirmMsg)) return false;
    $(trigger).closest('.drts-form-field-rangelist-option').slideUp('fast', function() {
      $(this).remove();
    });
  };
})(jQuery);