'use strict';

(function($) {
  DRTS.Map.googlemaps.styles['Blue'] = [{
    'featureType': 'all',
    'stylers': [{
      'invert_lightness': 'true'
    }, {
      'hue': '#0000b0'
    }, {
      'saturation': -30
    }]
  }, {
    'featureType': 'poi',
    'stylers': [{
      'visibility': 'off'
    }]
  }];
})(jQuery);