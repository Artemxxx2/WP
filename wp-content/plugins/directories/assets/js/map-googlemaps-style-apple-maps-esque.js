'use strict';

(function($) {
  DRTS.Map.googlemaps.styles['Apple Maps-esque'] = [{
    'featureType': 'water',
    'stylers': [{
      'visibility': 'on',
      'color': '#acbcc9'
    }]
  }, {
    'featureType': 'landscape',
    'stylers': [{
      'color': '#f2e5d4'
    }]
  }, {
    'featureType': 'road.highway',
    'elementType': 'geometry',
    'stylers': [{
      'color': '#c5c6c6'
    }]
  }, {
    'featureType': 'road.arterial',
    'elementType': 'geometry',
    'stylers': [{
      'color': '#e4d7c6'
    }]
  }, {
    'featureType': 'road.local',
    'elementType': 'geometry',
    'stylers': [{
      'color': '#fbfaf7'
    }]
  }, {
    'featureType': 'administrative',
    'stylers': [{
      'visibility': 'on',
      'lightness': 33
    }]
  }, {
    'featureType': 'road',
    'stylers': [{
      'lightness': 20
    }]
  }, {
    "featureType": "poi",
    "stylers": [{
      "visibility": "off"
    }]
  }];
})(jQuery);