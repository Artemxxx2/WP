"use strict";

(function($) {
  DRTS.Map.googlemaps.styles['Hot Pink'] = [{
    "stylers": [{
      "hue": "#ff61a6"
    }, {
      "visibility": "on"
    }, {
      "invert_lightness": true
    }, {
      "saturation": 40
    }, {
      "lightness": 10
    }]
  }, {
    'featureType': 'poi',
    'stylers': [{
      'visibility': 'off'
    }]
  }];
})(jQuery);