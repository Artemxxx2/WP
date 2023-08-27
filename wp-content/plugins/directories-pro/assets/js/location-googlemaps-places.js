'use strict';

(function($) {
  DRTS.Location.api.getPlaceRating = function(placeId, callback, errorHandler) {
    $.get(DRTS_Location_googlemapsApiEndpoint, {
      timestamp: Math.round(new Date().getTime() / 1000).toString(),
      action: 'placeRating',
      placeId: placeId
    }, function(results) {
      console.log('GoogleMaps place details results:', results);
      callback(results.rating, results.count);
    }).fail(function(xhr, status, error) {
      var err = new Error('Failed fetching place details for ' + placeId);
      if (errorHandler) {
        errorHandler(err);
      } else {
        throw err;
      }
    });
  };
})(jQuery);