'use strict';

(function($) {
  DRTS.Form.field.addmore = function(context) {
    $('.drts-form-field-add', context).each(function() {
      var $this = $(this),
        $container = $this.closest($this.data('container') || '.drts-form-type-fieldset'),
        maxNum = parseInt($this.data('field-max-num')),
        fieldsSelector;
      if (!$container.length) {
        $container = $this.closest('.drts-display-element'); // field display element without label
      }
      if ($container.children('[class*="' + DRTS.bsPrefix + 'col-"]').length) {
        // horizontal fields
        fieldsSelector = '> div > .drts-form-field:not(.drts-form-type-addmore)';
      } else if ($container.children('.drts-form-row').length) {
        // fields shown in a grid row
        fieldsSelector = '> .drts-form-row';
      } else if ($container.children('.drts-form-wrap').length) {
        // fields shown conditionally
        fieldsSelector = '> .drts-form-wrap > .drts-form-field:not(.drts-form-type-addmore)';
      } else {
        fieldsSelector = '> .drts-form-field:not(.drts-form-type-addmore)';
      }
      $this.click(function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var nextIndex = $this.data('field-next-index');
        DRTS.cloneField($container, fieldsSelector, maxNum, nextIndex);
        if (nextIndex) $this.data('field-next-index', ++nextIndex);
        if (maxNum && $container.find(fieldsSelector).length >= maxNum) {
          $this.closest('.drts-form-field').hide();
        }
      });
      $container.find(fieldsSelector).each(function(i) {
        if (i === 0) return;
        $(this).addClass('drts-form-field-removable').css('position', 'relative').append('<button type="button" class="' + DRTS.bsPrefix + 'btn ' + DRTS.bsPrefix + 'btn-danger ' + DRTS.bsPrefix + 'btn-sm drts-form-field-remove"><i class="fas fa-times" title="Remove this field"></i></button>');
      });
      $container.on('click', '.drts-form-field-remove', function(e) {
        e.preventDefault();
        $(this).closest('.drts-form-field-removable').fadeTo(100, 0, function() {
          var $_this = $(this);
          $_this.slideUp(100, function() {
            $_this.remove();
            var bros = $container.find(fieldsSelector);
            if (bros.length) {
              bros.find(':input').trigger('cloneremoved.sabai');
            }
            if (maxNum && bros.length < maxNum && $this.is(':hidden')) {
              $this.closest('.drts-form-field').show();
            }
          });
        });
      });
    });
  };
})(jQuery);