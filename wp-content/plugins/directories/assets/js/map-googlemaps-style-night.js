'use strict';

(function($) {
  DRTS.Map.googlemaps.styles['Night'] = [{
    'featureType': 'all',
    'stylers': [{
      'invert_lightness': 'true'
    }]
  }, {
    'featureType': 'poi',
    'stylers': [{
      'visibility': 'off'
    }]
  }];
})(jQuery);