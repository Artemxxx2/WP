'use strict';

(function($) {
  DRTS.Map.googlemaps.styles['Chilled'] = [{
    'featureType': 'road',
    'elementType': 'geometry',
    'stylers': [{
      'visibility': 'simplified'
    }]
  }, {
    'featureType': 'road.arterial',
    'stylers': [{
      'lightness': 0
    }, {
      'hue': 149
    }, {
      'saturation': -78
    }]
  }, {
    'featureType': 'road.highway',
    'stylers': [{
      'lightness': 2.8
    }, {
      'hue': -31
    }, {
      'saturation': -40
    }]
  }, {
    'featureType': 'landscape',
    'stylers': [{
      'lightness': -1.1
    }, {
      'hue': 163
    }, {
      'saturation': -26
    }]
  }, {
    'featureType': 'transit',
    'stylers': [{
      'visibility': 'off'
    }]
  }, {
    'featureType': 'water',
    'stylers': [{
      'lightness': -38.57
    }, {
      'hue': 3
    }, {
      'saturation': -24.24
    }]
  }, {
    'featureType': 'poi',
    'stylers': [{
      'visibility': 'off'
    }]
  }];
})(jQuery);