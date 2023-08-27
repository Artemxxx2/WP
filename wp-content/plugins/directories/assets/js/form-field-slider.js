'use strict';

(function($) {
  DRTS.Form.field.slider = function(selector) {
    var $slider = $(selector);
    if (!$slider.length) return;

    var $slider_slider = $slider.find('.irs').remove().end().find('.drts-form-slider');
    if (!$slider_slider.length) return;

    $slider_slider.removeClass('irs-hidden-input').show().on('change', function(e, triggered) {
      if (!triggered) return false;
    }).ionRangeSlider({
      onFinish: function onFinish(data) {
        data.input.trigger('change', [true]);
      },
      prettify: function prettify(num) {
        if (num === $slider_slider.data('min')) {
          if ($slider_slider.data('min-text').length) {
            return $slider_slider.data('min-text');
          }
        } else if (num === $slider_slider.data('max')) {
          if ($slider_slider.data('max-text').length) {
            return $slider_slider.data('max-text');
          }
        }
        return num;
      }
    });
    // Reset val and prop inintialized by ionRangeSlider with empty value if attr is empty
    if ($slider_slider.attr('value') === '') {
      $slider_slider.val('').prop('value', '');
    }
  };

  $(DRTS).on('clonefield.sabai', function(e, data) {
    if (data.clone.hasClass('drts-form-type-slider') || data.clone.hasClass('drts-form-type-range')) {
      DRTS.Form.field.slider(data.clone);
    }
  });
})(jQuery);