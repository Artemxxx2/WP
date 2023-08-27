'use strict';

(function($) {
  DRTS.Map.googlemaps.styles['Mixed'] = [{
    'featureType': 'landscape',
    'stylers': [{
      'hue': '#00dd00'
    }]
  }, {
    'featureType': 'road',
    'stylers': [{
      'hue': '#dd0000'
    }]
  }, {
    'featureType': 'water',
    'stylers': [{
      'hue': '#000040'
    }]
  }, {
    'featureType': 'road.arterial',
    'stylers': [{
      'hue': '#ffff00'
    }]
  }, {
    'featureType': 'road.local',
    'stylers': [{
      'visibility': 'off'
    }]
  }, {
    'featureType': 'poi',
    'stylers': [{
      'visibility': 'off'
    }]
  }];
})(jQuery);