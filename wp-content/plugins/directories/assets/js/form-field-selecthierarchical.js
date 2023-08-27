'use strict';

(function($) {
  $(DRTS).on('clonefield.sabai', function(e, data) {
    if (!data.clone.hasClass('drts-form-type-selecthierarchical')) return;

    var states = {},
      conditions,
      selects = data.clone.find('.drts-form-type-select'),
      selector = '.drts-form-field-selecthierarchical-';
    for (var i = 0; i < selects.length; ++i) {
      conditions = [];
      conditions.push({
        type: 'selected',
        value: true,
        target: selector + i + ' select',
        container: '#' + data.clone.attr('id')
      });
      states['#' + data.clone.attr('id') + ' ' + selector + (i + 1)] = {
        'load_options': {
          'conditions': conditions
        }
      };
    }
    DRTS.states(states);
  });
})(jQuery);