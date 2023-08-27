"use strict";

(function($) {
  DRTS.Map.googlemaps.styles['Flat Map'] = [{
    "stylers": [{
      "visibility": "off"
    }]
  }, {
    "featureType": "road",
    "stylers": [{
      "visibility": "on"
    }, {
      "color": "#ffffff"
    }]
  }, {
    "featureType": "road.arterial",
    "stylers": [{
      "visibility": "on"
    }, {
      "color": "#fee379"
    }]
  }, {
    "featureType": "road.highway",
    "stylers": [{
      "visibility": "on"
    }, {
      "color": "#fee379"
    }]
  }, {
    "featureType": "landscape",
    "stylers": [{
      "visibility": "on"
    }, {
      "color": "#f3f4f4"
    }]
  }, {
    "featureType": "water",
    "stylers": [{
      "visibility": "on"
    }, {
      "color": "#7fc8ed"
    }]
  }, {
    "featureType": "road",
    "elementType": "labels",
    "stylers": [{
      "visibility": "off"
    }]
  }, {
    "elementType": "labels",
    "stylers": [{
      "visibility": "off"
    }]
  }, {
    "featureType": "landscape.man_made",
    "elementType": "geometry",
    "stylers": [{
      "weight": 0.9
    }, {
      "visibility": "off"
    }]
  }, {
    'featureType': 'poi',
    'stylers': [{
      'visibility': 'off'
    }]
  }];
})(jQuery);