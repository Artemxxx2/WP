'use strict';

(function($) {
  DRTS.Form.field.timepicker = function(selector) {
    var $time, $all_day_select, maybeHideStartEnd, $day_select, maybeDisableAlldayStartEnd;

    $time = $(selector);
    if (!$time.length) return;

    $all_day_select = $time.find('.drts-form-timepicker-all_day select');
    if (!$all_day_select.length) return;

    $day_select = $time.find('.drts-form-timepicker-day select');
    if ($day_select.length) {
      if (!$day_select.data('allow-empty')) {
        maybeDisableAlldayStartEnd = function maybeDisableAlldayStartEnd() {
          var alldayStartEnd = '.drts-form-timepicker-all_day select, .drts-form-timepicker-start select, .drts-form-timepicker-end select';
          if ($day_select.val()) {
            $time.find(alldayStartEnd).prop('disabled', false);
          } else {
            $time.find(alldayStartEnd).prop('disabled', true).end().find('.drts-form-timepicker-all_day select').val('').trigger('change');
          }
        };
        maybeDisableAlldayStartEnd();
        $day_select.on('change', function() {
          maybeDisableAlldayStartEnd();
        });
      }
    }

    maybeHideStartEnd = function maybeHideStartEnd() {
      $time.find('.drts-form-timepicker-start, .drts-form-timepicker-end')[$all_day_select.val() !== '' ? 'hide' : 'show']();
    };
    maybeHideStartEnd();
    $all_day_select.on('change', function() {
      maybeHideStartEnd();
    });
  };

  $(DRTS).on('clonefield.sabai', function(e, data) {
    if (data.clone.hasClass('drts-form-type-timepicker')) {
      DRTS.Form.field.timepicker(data.clone);
    }
  });
})(jQuery);