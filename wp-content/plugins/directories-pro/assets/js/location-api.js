'use strict';

(function($) {
  DRTS.Location = DRTS.Location || {};
  DRTS.Location.api = {
    settings: {},
    geocode: function geocode(address, callback, errorHandler) {
      console.log('DRTS.Location.api.geocode() is not implemented.');
    },
    _reverseGeocode: function _reverseGeocode(latlng, callback, errorHandler) {
      console.log('DRTS.Location.api.reverseGeocode() is not implemented.');
    },
    reverseGeocode: function reverseGeocode(latlng, callback, errorHandler) {
      var func = DRTS.Location.api.customReverseGeocode || DRTS.Location.api._reverseGeocode;
      func(latlng, callback, errorHandler);
    },
    getTimezone: function getTimezone(latlng, callback, errorHandler) {
      console.log('DRTS.Location.api.getTimezone() is not implemented.');
    },
    autocomplete: function autocomplete(selector, callback) {
      console.log('DRTS.Location.api.autocomplete() is not implemented.');
    },
    getSuggestions: function getSuggestions(query, callback) {
      console.log('DRTS.Location.api.getSuggestions() is not implemented.');
    },
    getPlaceRating: function getPlaceRating(placeId, callback, errorHandler) {
      console.log('DRTS.Location.api.getPlaceRating() is not implemented.');
    }
  };
  var DRTS_Location_apiErrors = {
    'Geocoder failed due to: %s': 'Geocoder failed due to: %s',
    'Geocoder returned no address components.': 'Geocoder returned no address components.'
  };
})(jQuery);