'use strict';

(function($) {
  DRTS.Form.field.datepicker = function(selector) {
    var $date, $date_date, $date_val, $date_clear, current_val, current_val_format_options, current_val_formatted, current_val_date, params;

    $date = $(selector);
    if (!$date.length) return;

    $date_date = $date.find('.drts-form-datepicker-date');
    if (!$date_date.length) return;

    $date_val = $date.find('.drts-form-datepicker-date-val');
    if (!$date_val.length) return;

    $date_clear = $date.find('.drts-clear');

    params = $date_date.data('calendar-options');
    params.actions = {
      clickDay: function clickDay(event, dates) {
        if (!dates.length) {
          $date_date.val('');
          $date_val.val('');
          return;
        }
        switch (calendar.settings.selection.day) {
          case 'single':
            cal_container.hide();
            break;
          case 'multiple-ranged':
            if (dates.length <= 1) return;

            cal_container.hide();
            break;
          default:
        }

        if (dates.length > 1) {
          dates.sort(function(a, b) {
            return +new Date(a) - +new Date(b);
          });
          dates = [dates[0], dates[dates.length - 1]];
          var formatted = dates.map(function(date) {
            return new Date(date).toLocaleString([], {
              year: 'numeric',
              month: 'long',
              day: 'numeric',
              timeZone: DRTS.timeZone
            });
          });
          if (calendar.settings.selection.day === 'multiple') {
            $date_date.val(formatted.join(', '));
          } else if (calendar.settings.selection.day === 'multiple-ranged') {
            $date_date.val(formatted.join(' to '));
          }
          $date_val.val(dates.join(','));
        } else {
          updateSingleDateValues(calendar, dates[0]);
        }
        updateFormAfterChange();
      },
      clickMonth: function clickMonth(event, month) {
        if (calendar.type !== 'month') return;

        cal_container.hide();
        $date_val.val(calendar.selectedYear + '-' + (month + 1) + '-1');
        $date_date.val(new Date(calendar.selectedYear + '-' + (month + 1) + '-1').toLocaleString([], {
          year: 'numeric',
          month: 'long',
          timeZone: DRTS.timeZone
        }));

        updateFormAfterChange();
      },
      changeTime: function changeTime(event, time, hours, minutes, keeping) {
        if (calendar.selectedDates.length) {
          updateSingleDateValues(calendar, calendar.selectedDates[0]);
          updateFormAfterChange();
        }
      }
    };
    var updateSingleDateValues = function updateSingleDateValues(calendar, currentDate) {
      var format_options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        timeZone: DRTS.timeZone
      };
      if (calendar.settings.selection.time) {
        format_options.hour12 = calendar.settings.selection.time !== 24;
        format_options.hour = 'numeric';
        format_options.minute = 'numeric';
        if (calendar.selectedTime) currentDate += ' ' + calendar.selectedTime;
      }
      var formatted = new Date(currentDate).toLocaleString([], format_options);
      $date_date.val(formatted);
      $date_val.val(currentDate);
    };
    var updateFormAfterChange = function updateFormAfterChange() {
      $date_clear.length && $date_clear.css('visibility', $date_val.val().length > 0 ? 'visible' : 'hidden');
      if (cal_container.is(':hidden')) cal_container.closest('form').trigger('change.sabai');
    };
    if (params.settings.selected && params.settings.selected.dates) {
      switch (params.type) {
        case 'year':
        case 'month':
          // Make sure value is valid month/year
          current_val = params.settings.selected.dates[0];
          current_val_date = new Date(current_val);
          params.settings.selected.year = Number(current_val_date.getFullYear());
          params.settings.selected.dates = null;
          if (params.type === 'month') {
            params.settings.selected.month = current_val_date.getMonth();
            current_val_formatted = current_val_date.toLocaleString([], {
              year: 'numeric',
              month: 'long',
              timeZone: DRTS.timeZone
            });
          } else {
            current_val_formatted = current_val_date.toLocaleString([], {
              year: 'numeric',
              timeZone: DRTS.timeZone
            });
          }
          $date_date.val(current_val_formatted);
          $date_val.val(current_val);
          break;
        default:
          if (params.settings.selection.day === 'multiple-ranged') {
            current_val = params.settings.selected.dates[0];
            current_val_formatted = current_val.split(',').map(function(date) {
              return new Date(date).toLocaleString([], {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                timeZone: DRTS.timeZone
              });
            });
            $date_date.val(current_val_formatted.join(' to '));
            $date_val.val(current_val);
          } else if (params.settings.selection.day === 'multiple') {
            current_val = params.settings.selected.dates;
            current_val_formatted = current_val.map(function(date) {
              return new Date(date).toLocaleString([], {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                timeZone: DRTS.timeZone
              });
            });
            $date_date.val(current_val_formatted.join(', '));
            $date_val.val(current_val.join(','));
          } else {
            current_val = params.settings.selected.dates[0];
            current_val_format_options = {
              year: 'numeric',
              month: 'long',
              day: 'numeric',
              timeZone: DRTS.timeZone
            };
            if (params.settings.selection.time) {
              if (params.settings.selected.time) {
                current_val += ' ' + params.settings.selected.time;
              }
              current_val_format_options.hour12 = params.settings.selection.time !== 24;
              current_val_format_options.hour = 'numeric';
              current_val_format_options.minute = 'numeric';
            }
            current_val_formatted = new Date(current_val).toLocaleString([], current_val_format_options);
            $date_date.val(current_val_formatted);
            $date_val.val(current_val);
          }
      }
    }

    var cal_container = $date.find('.drts-form-datepicker-calendar');
    var calendar = new VanillaCalendar(cal_container.get(0), params);
    calendar.init();

    $date_date.off('focus.sabai').on('focus.sabai', function(e) {
      if (cal_container.is(':visible')) return;

      cal_container.show();
    });

    document.addEventListener("click", function(e) {
      if (!cal_container.is(':visible')) return;

      if (!e.target.closest('#' + $date.attr('id')) && !$(e.target).is('[class^=vanilla-calendar]') && !$(e.target).hasClass('drtsform-datepicker-date')) {
        // Clicked outside form field and calendar
        cal_container.hide();
      } else {
        $date_date.trigger('focus.sabai');
      }
    });

    $date.off('click.sabai').on('click.sabai', '.drts-clear', function() {
      calendar.reset();
      cal_container.hide();
      $(this).css('visibility', 'hidden').parent().find('.drts-form-datepicker-date').val('').end().find('.drts-form-datepicker-date-val').val('').end().closest('form').trigger('change.sabai'); // for some reason triggering change event on input doesn't work, so trigger form directly
    });

    // For resetting field
    $date.closest('.drts-form-field').off('entity_reset_form_field.sabai').on('entity_reset_form_field.sabai', function() {
      $(this).find('.drts-form-datepicker-date').val('').end().find('.drts-form-datepicker-date-val').val('');
    });
  };

  $(DRTS).on('clonefield.sabai', function(e, data) {
    if (data.clone.hasClass('drts-form-type-datepicker') || data.clone.hasClass('drts-form-type-daterangepicker') || data.clone.hasClass('drts-form-type-monthpicker')) {
      data.clone.find(".drts-form-datepicker-inputs").each(function() {
        DRTS.Form.field.datepicker($(this));
      });
    }
  });
})(jQuery);