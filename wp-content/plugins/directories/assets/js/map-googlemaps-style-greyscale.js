'use strict';

(function($) {
  DRTS.Map.googlemaps.styles['Greyscale'] = [{
    'featureType': 'all',
    'stylers': [{
      'gamma': 0.50
    }, {
      'saturation': -100
    }]
  }, {
    'featureType': 'poi',
    'stylers': [{
      'visibility': 'off'
    }]
  }];
})(jQuery);