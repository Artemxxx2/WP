'use strict';

(function($) {
  DRTS.Map.googlemaps.styles['Red'] = [{
    'featureType': 'all',
    'stylers': [{
      'hue': '#ff0000'
    }]
  }, {
    'featureType': 'poi',
    'stylers': [{
      'visibility': 'off'
    }]
  }];
})(jQuery);