'use strict';

(function($) {
  DRTS.Location.api.getTimezone = function(latlng, callback, errorHandler) {
    var data = {
      lat: latlng[0],
      lng: latlng[1],
      username: DRTS_Location_geonamesUsername
    };
    console.log('GeoNames timezone API request:', data);
    var host = document.location.protocol === 'https:' ? 'https://secure.geonames.org' : 'http://api.geonames.org';
    $.getJSON(host + '/timezoneJSON', data).done(function(json) {
      console.log('GeoNames timezone API results:', json);
      if (!json || !json.timezoneId) {
        var err = new Error('GeoNames timezone API returned no results');
        if (errorHandler) {
          errorHandler(err);
        } else {
          throw err;
        }
      }
      callback(json.timezoneId);
    }).fail(function(xhr, status, error) {
      var err = new Error('GeoNames timezone API failed due to: ' + error + '(' + status + ')');
      if (errorHandler) {
        errorHandler(err);
      } else {
        throw err;
      }
    });
  };
})(jQuery);