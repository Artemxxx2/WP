"use strict";

(function($) {
  DRTS.Map.googlemaps.styles['Paper'] = [{
    "featureType": "administrative",
    "stylers": [{
      "visibility": "off"
    }]
  }, {
    "featureType": "road",
    "stylers": [{
      "visibility": "simplified"
    }]
  }, {
    "featureType": "water",
    "stylers": [{
      "visibility": "simplified"
    }]
  }, {
    "featureType": "transit",
    "stylers": [{
      "visibility": "simplified"
    }]
  }, {
    "featureType": "landscape",
    "stylers": [{
      "visibility": "simplified"
    }]
  }, {
    "featureType": "road.highway",
    "stylers": [{
      "visibility": "off"
    }]
  }, {
    "featureType": "road.local",
    "stylers": [{
      "visibility": "on"
    }]
  }, {
    "featureType": "road.highway",
    "elementType": "geometry",
    "stylers": [{
      "visibility": "on"
    }]
  }, {
    "featureType": "road.arterial",
    "stylers": [{
      "visibility": "off"
    }]
  }, {
    "featureType": "water",
    "stylers": [{
      "color": "#5f94ff"
    }, {
      "lightness": 26
    }, {
      "gamma": 5.86
    }]
  }, {
    "featureType": "road.highway",
    "stylers": [{
      "weight": 0.6
    }, {
      "saturation": -85
    }, {
      "lightness": 61
    }]
  }, {
    "featureType": "landscape",
    "stylers": [{
      "hue": "#0066ff"
    }, {
      "saturation": 74
    }, {
      "lightness": 100
    }]
  }, {
    'featureType': 'poi',
    'stylers': [{
      'visibility': 'off'
    }]
  }];
})(jQuery);