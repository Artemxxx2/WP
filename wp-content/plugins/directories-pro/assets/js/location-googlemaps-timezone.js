'use strict';

(function($) {
  DRTS.Location.api.getTimezone = function(latlng, callback, errorHandler) {
    $.get(DRTS_Location_googlemapsApiEndpoint, {
      timestamp: Math.round(new Date().getTime() / 1000).toString(),
      action: 'timezone',
      latlng: latlng[0] + ',' + latlng[1]
    }, function(results) {
      console.log('GoogleMaps time zone results:', results);
      callback(results);
    }).fail(function(xhr, status, error) {
      var err = new Error('Failed fetching timezone for ' + latlng[0] + ',' + latlng[1]);
      if (errorHandler) {
        errorHandler(err);
      } else {
        throw err;
      }
    });
  };
})(jQuery);