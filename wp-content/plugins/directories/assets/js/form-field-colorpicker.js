'use strict';

(function($) {
  DRTS.Form.field.colorpicker = function(selector, options) {
    var $colorpicker = $(selector);
    if (!$colorpicker.length) return;

    var defaults = {
      'inputSelector': 'input',
      'clearSelector': '.drts-clear'
    };
    options = $.extend({}, defaults, options);

    var input = $colorpicker.find(options.inputSelector),
      clear = $colorpicker.find(options.clearSelector);

    var hueb = new Huebee(input.get(0), {
      saturations: input.data('saturations') || 1,
      notation: 'hex',
      hues: input.data('hues') || 12,
      customColors: input.data('custom-colors') ? input.data('custom-colors').split(',') : ['#CC2255', '#EE6622', '#EEAA00', '#1199FF', '#333333'],
      staticOpen: input.data('static-open') ? true : false
    });

    if (clear.length) {
      var showClear = function showClear() {
        var pos, prev;
        if (input.outerWidth() === 0) {
          pos = 181;
        } else {
          pos = input.outerWidth() - clear.outerWidth() - 5;
          prev = input.prev('.' + DRTS.bsPrefix + 'input-group-prepend');
          if (prev.length) {
            pos += prev.outerWidth();
          }
        }
        clear.css({
          'visibility': 'visible',
          right: DRTS.isRTL ? pos + 'px' : 'auto',
          left: DRTS.isRTL ? 'auto' : pos + 'px'
        });
      };
      clear.on('click', function() {
        input.val('').css('background-color', '');
        clear.css('visibility', 'hidden');
      });

      // Show clear icon if input has value, hide if not
      if (input.val().length > 0) {
        showClear();
      }
      input.on('keyup', function(e) {
        if (e.keyCode !== 13 && e.keyCode !== 27 && e.keyCode !== 32) {
          if (input.val().length > 0) {
            showClear();
          } else {
            clear.css('visibility', 'hidden');
          }
        }
      });
      hueb.on('change', function(color, hue, sat, lum) {
        showClear();
      });
    } else {
      input.on('keyup', function(e) {
        if (e.keyCode !== 13 && e.keyCode !== 27 && e.keyCode !== 32) {
          if (input.val().length < 4) {
            input.css({
              'background-color': '',
              color: ''
            });
          }
        }
      });
    }
  };
  $(DRTS).on('clonefield.sabai', function(e, data) {
    if (data.clone.hasClass('drts-form-type-colorpicker')) {
      data.clone.find('input').css('background-color', '');
      DRTS.Form.field.colorpicker(data.clone);
    }
  });
})(jQuery);