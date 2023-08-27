'use strict';

(function($) {
  DRTS.Location.api.autocomplete = function(selector, callback) {
    var $field = $(selector);
    if (!$field.length) return;

    var options = DRTS.Location.api._getAutocompleteOptions();
    $field.each(function(index, field) {
      field.addEventListener('focus', function(e) {
        var autocomplete = new google.maps.places.Autocomplete(field, options);
        autocomplete.addListener('place_changed', function() {
          var place = autocomplete.getPlace();
          console.log('GoogleMaps autocomplete results:', place);
          callback(DRTS.Location.googlemaps.parsePlace(place));
        });
      });
      field.addEventListener('keydown', function(e) {
        if (e.keyCode === 13) {
          e.preventDefault();
        }
      });
    });
  };

  DRTS.Location.api.getSuggestions = function(query, callback) {
    var autocomplete = new google.maps.places.AutocompleteService();
    autocomplete.getPlacePredictions(DRTS.Location.api._getAutocompleteOptions(query), function(predictions, status) {
      if (status === google.maps.places.PlacesServiceStatus.OK) {
        console.log('GoogleMaps place predictions:', predictions);
        var results = [];
        var _iteratorNormalCompletion = true;
        var _didIteratorError = false;
        var _iteratorError = undefined;

        try {
          for (var _iterator = predictions[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
            var item = _step.value;

            results.push({
              text: item.description,
              placeId: item.place_id
            });
          }
        } catch (err) {
          _didIteratorError = true;
          _iteratorError = err;
        } finally {
          try {
            if (!_iteratorNormalCompletion && _iterator.return) {
              _iterator.return();
            }
          } finally {
            if (_didIteratorError) {
              throw _iteratorError;
            }
          }
        }

        callback(results);
      }
    });
  };

  DRTS.Location.api.geocodeSuggestion = function(item, callback, errorHandler) {
    var places = new google.maps.places.PlacesService(document.createElement('div'));
    places.getDetails({
      placeId: item.placeId,
      sessionToken: DRTS.Location.api._autocompleteSessionToken
    }, function(place, status) {
      if (status === google.maps.places.PlacesServiceStatus.OK) {
        console.log('GoogleMaps place details:', place, place.geometry.viewport.toString());
        var latlng = place.geometry.location,
          viewport = place.geometry.viewport;
        callback([latlng.lat(), latlng.lng()], [viewport.getSouthWest().lat(), viewport.getSouthWest().lng(), viewport.getNorthEast().lat(), viewport.getNorthEast().lng()]);
        DRTS.Location.api._autocompleteSessionToken = null;
      } else {
        var err = new Error('Geocoding place (ID: ' + item.placeId + ') failed due to: ' + status);
        if (errorHandler) {
          errorHandler(err);
        } else {
          throw err;
        }
      }
    });
  };

  DRTS.Location.api._getAutocompleteOptions = function(query) {
    if (!DRTS.Location.api._autocompleteSessionToken) {
      DRTS.Location.api._autocompleteSessionToken = new google.maps.places.AutocompleteSessionToken();
    }
    var options = {
      sessionToken: DRTS.Location.api._autocompleteSessionToken,
      componentRestrictions: {
        country: []
      }
    };
    if (DRTS_Location_googlemapsAutocomplete.type) {
      options.types = [DRTS_Location_googlemapsAutocomplete.type];
    }
    if (DRTS_Location_googlemapsAutocomplete.country && DRTS_Location_googlemapsAutocomplete.country instanceof Array) {
      options.componentRestrictions.country = DRTS_Location_googlemapsAutocomplete.country;
    }
    if (typeof query !== 'undefined') {
      options.input = query;
    }
    return options;
  };
})(jQuery);