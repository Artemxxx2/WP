'use strict';

(function($) {
  DRTS.View = DRTS.View || {};
  DRTS.View.filterForm = DRTS.View.filterForm || function(selector) {
    var $form = $(selector),
      $entities_container,
      entities;

    // Remove display:none for collapsible to work properly
    if ($form.parent('.drts-view-entities-filter-form').length) {
      $form.parent('.drts-view-entities-filter-form').css('display', '');
    }

    $entities_container = $($form.data('entities-container'));
    if ($entities_container.length) {
      entities = $entities_container.find($form.data('entities-target'));
      if (entities.length) {
        entities.off('click').on('click', '.drts-view-entities-filters-applied .drts-view-remove-filter', function(e) {
          var $this = $(this);
          e.preventDefault();
          $this.remove();
          if (!$this.data('filter-name')) {
            entities.find('.drts-view-entities-filters-applied .drts-view-remove-filter').each(function() {
              var $_this = $(this);
              $_this.remove();
              DRTS.View.removeFilter($form, $_this.data('filter-name'), $_this.data('filter-value'));
            });
          } else {
            DRTS.View.removeFilter($form, $this.data('filter-name'), $this.data('filter-value'));
          }
          // Filter(s) removed, re-submit form
          $form.submit();
        });
      }
    }

    $form.on('submit', function(e, event_data) {
      // Clear placeholder texts
      $form.find('[placeholder]').each(function() {
        var input = $(this);
        if (input.val() == input.attr('placeholder')) {
          input.val('');
        }
      });

      if (!entities || !entities.length) return;

      e.preventDefault();
      var data = $form.serialize(),
        sort = entities.find('.drts-view-entities-sort-selected').data('value'),
        num = entities.find('.drts-view-entities-perpage-selected').data('value'),
        action = $form.attr('action'),
        form_container = $($form.data('entities-container') + '-view-filter-form'),
        ajax_options,
        is_from_modal = $form.closest('#drts-modal').length && $form.is(':visible') ? true : false;
      if (sort) data += '&sort=' + sort;
      if (num) data += '&num=' + num;
      ajax_options = {
        type: 'post',
        container: $form.data('entities-container'),
        target: $form.data('entities-target'),
        url: action,
        data: data,
        pushState: $form.data('push-state') ? true : false,
        pushStateUrl: action + (action.indexOf('?') > -1 ? '&' : '?') + data,
        scroll: $form.data('scroll-offset') === 0 ? true : $form.data('scroll-offset') < 0 ? false : $form.data('scroll-offset'),
        loadingImage: true,
        trigger: event_data && event_data.trigger ? event_data.trigger : null // triggered by .drts-view-filter-trigger?
      };
      if (is_from_modal) {
        $('#drts-modal').sabaiModal('hide');
      }
      if ($form.data('external')) {
        ajax_options.url += (ajax_options.url.indexOf('?') > -1 ? '&' : '?') + DRTS.params.contentType + '=json';
        ajax_options.onContent = function(response, target, trigger, isCache) {
          // Remove select2 results container if any
          $('body > .select2-container').remove();

          $form.remove();

          form_container.html(response['html']['.drts-view-entities-filter-form']);
          DRTS.init(form_container);
        };
        ajax_options.onError = function(error, target, trigger, status) {
          //    if (status === 422) {
          //        DRTS.Form.handle422Error($form, error);
          //        error.messages = [];
          //    }
        };
      }

      if (form_container.data('collapsible')) {
        form_container.sabaiCollapse('hide');
      }

      DRTS.ajax(ajax_options);
    }).on('change.sabai', function(e) {
      if ($form.hasClass('drts-view-filter-form-manual') || $form.closest('#drts-modal').length) return; // do not auto-submit

      var target = $(e.target),
        ignore = target.closest('.drts-view-filter-ignore');
      if (ignore.length) {
        if (!ignore.data('ignore-element-name') || typeof ignore.data('ignore-element-value') === 'undefined') return;

        var ignore_ele = ignore.find('[name="' + ignore.data('ignore-element-name') + '"]');

        if (ignore_ele.length && ignore_ele.attr('name') !== target.attr('name') && ignore_ele.val() === ignore.data('ignore-element-value')) return;
      }

      $form.submit();
    }).find('.drts-view-filter-trigger').on('click', function(e) {
      e.preventDefault();
      $form.trigger('submit', {
        trigger: $(this)
      });
    });

    // If the form is in modal window, attach event to the submit button in modal footer
    if ($form.closest('#drts-modal').length) {
      var modal = $('#drts-modal');
      modal.find('.drts-form-buttons').find('button').on('click', function(e) {
        e.preventDefault();
        $(this).addClass(DRTS.bsPrefix + 'disabled');
        $form.submit();
      });
      $form.find('.' + DRTS.bsPrefix + 'card-group-none').removeClass(DRTS.bsPrefix + 'card-group-none').addClass(DRTS.bsPrefix + 'card-group');
    }

    $('#drts-content').on('entity_entities_filter_removed.sabai', function(e, filterName, filterValue) {
      DRTS.View.removeFilter($(selector), filterName, filterValue);
    });

    $(DRTS).on('location_exit_fullscreen.sabai', function(e, data) {
      // Make sure form is not left out in the modal
      $('#drts-modal').find('.' + DRTS.bsPrefix + 'modal-title,' + '.' + DRTS.bsPrefix + 'modal-body,' + '.' + DRTS.bsPrefix + 'modal-footer').empty();
    });

    $(DRTS).on('drts_hidden.sabai', function(e, data) {
      if (data.element.parent().hasClass(DRTS.bsPrefix + 'card-body')) {
        data.element.closest('.' + DRTS.bsPrefix + 'card').hide();
      }
    });
    $(DRTS).on('drts_shown.sabai', function(e, data) {
      if (data.element.parent().hasClass(DRTS.bsPrefix + 'card-body')) {
        data.element.closest('.' + DRTS.bsPrefix + 'card').show();
      }
    });
  };
  DRTS.View.removeFilter = DRTS.View.removeFilter || function(form, filterName, filterValue) {
    if (filterName) {
      var filter = form.find('[data-view-filter-name="' + filterName + '"]');
      if (!filter.length) return;

      switch (filter.data('view-filter-form-type')) {
        case 'textfield':
        case 'hidden':
          if (filter.find('input[name="' + filterName + '"]').length) {
            filter.find('input[name="' + filterName + '"]').val('');
          } else {
            filter.find('input[name^="' + filterName + '["]').val('');
          }
          break;
        case 'checkboxes':
          if (filterValue) {
            filter.find('input:checkbox[value="' + filterValue + '"]').prop('checked', false);
          } else {
            filter.find('input:checkbox[name="' + filterName + '[]"]').prop('checked', false);
          }
          break;
        case 'radios':
          if (filterValue) {
            filter.find('input:radio[value="' + filterValue + '"]').prop('checked', false);
          } else {
            filter.find('input:radio[name="' + filterName + '"]').prop('checked', false);
          }
          filter.find('input:radio[value=""]').prop('checked', true); // check default option
          break;
        case 'select':
          var selects = filter.find('select[name^="' + filterName + '["]');
          if (selects.length) {
            // Clearing hierarchical select
            selects.each(function(index) {
              var $this = $(this);
              if (index > 0) {
                $this.val('').closest('.drts-form-type-select').hide();
              } else {
                $this.val('');
              }
              if ($this.hasClass('select2-hidden-accessible')) {
                $this.select2('close');
              }
            });
          } else {
            filter.find('select[name="' + filterName + '"]').val('');
          }
          break;
        case 'slider':
          var slider = filter.find('.drts-form-slider');
          if (slider.length) {
            slider.data('ionRangeSlider').update({
              from: slider.data('min'),
              to: slider.data('max')
            });
          }
          break;
      }

      // Let filter perform specific tasks
      var reset_target = filter.find('.drts-entity-reset-form-field-target');
      (reset_target.length ? reset_target : filter).trigger('entity_reset_form_field.sabai', [filterName]);
    } else {
      // Clear all filters
      form.find('.drts-view-filter-form-field').each(function() {
        var $this = $(this);
        if ($this.data('view-filter-name')) {
          $('#drts-content').trigger('entity_entities_filter_removed.sabai', [$this.data('view-filter-name'), null, true]);
        }
      });
    }
  };
})(jQuery);