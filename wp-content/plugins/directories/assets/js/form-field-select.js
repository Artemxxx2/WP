'use strict';

(function($) {
  DRTS.Form.field.select = function(selector, clear) {
    var $field = $(selector);
    if (!$field.length) return;
    var $select = $field.find('.select2').remove().end().find('select');
    if (!$select.length) return;

    if (clear) $select.val(null);
    var options = {
      escapeMarkup: function escapeMarkup(markup) {
        return markup;
      }, // let our custom formatter work
      itemClass: $field.data('select2-item-class') || 'drts-form-select-item',
      itemIdKey: $field.data('select2-item-id-key') || 'id',
      itemTextKey: $field.data('select2-item-text-key') || 'title',
      itemImageKey: $field.data('select2-item-image-key') || 'image',
      minimumResultsForSearch: $field.data('select2-search-min-results') || Infinity
    };
    if ($field.data('select2-lang')) {
      options.language = $field.data('select2-lang');
    }

    if ($field.data('select2-ajax')) {
      options.ajax = {
        url: $field.data('select2-ajax-url'),
        dataType: 'json',
        delay: $field.data('select2-ajax-delay') || 250,
        data: function data(params) {
          return {
            query: params.term, //search term
            page: params.page // page number tracked by Select2
          };
        },
        processResults: function processResults(data, params) {
          // parse the results into the format expected by Select2
          // since we are using custom formatting functions we do not need to
          // alter the remote JSON data, except to indicate that infinite
          // scrolling can be used
          params.page = params.page || 1;

          var results = [];
          $.each(data, function(i, item) {
            item.id = item[options.itemIdKey];
            item.text = item[options.itemTextKey];
            results.push(item);
          });

          return {
            results: results,
            pagination: {
              more: params.page * 20 < data.total
            }
          };
        }
      };
    }

    var _renderItem = function _renderItem(text, image) {
      var html = '<span class="' + options.itemClass + '">';
      if (image) {
        html += '<img src="' + image + '" alt="' + text + '" /> ';
      }
      html += text + '</span>';
      return html;
    };

    options.templateResult = function(item) {
      return options.itemTextKey in item && item[options.itemTextKey] ? _renderItem(item[options.itemTextKey], item[options.itemImageKey]) : item.text;
    };

    options.templateSelection = function(item) {
      return item.id && item.element ? _renderItem(item.text, item.element.getAttribute('data-image')) : item.text;
    };

    options.dropdownParent = $select.closest('.drts-form-field');
    $select.select2(options);

    // For some reason a change event is triggered from <input class="select2-search__field"> when using dropdownParent option.
    // Therefore we will intercept the event and stop it from going up to the form element.
    $field.on('change.select2', function(e) {
      if (!$(e.target).attr('name')) {
        e.stopPropagation();
      }
    });

    // Fix for weird positioning issue of suggestions
    $select.data('select2').on('results:message', function() {
      this.dropdown._resizeDropdown();
      this.dropdown._positionDropdown();
    });

    // For resetting filter
    $field.on('entity_reset_form_field.sabai', function() {
      $(this).find('select').val(null);
    });
  };

  $(DRTS).on('clonefield.sabai', function(e, data) {
    if (data.clone.hasClass('drts-form-select2')) {
      DRTS.Form.field.select(data.clone, true);
    }
  });
})(jQuery);