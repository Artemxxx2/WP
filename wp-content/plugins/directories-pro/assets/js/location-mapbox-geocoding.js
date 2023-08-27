'use strict';

(function($) {
  DRTS.Location.api.geocode = function(address, callback, errorHandler) {
    var url = 'https://api.mapbox.com/geocoding/v5/mapbox.places/' + encodeURIComponent(address) + '.json';
    var data = {
      access_token: DRTS_Location_mapboxGeocoding.accessToken,
      language: DRTS_Location_mapboxGeocoding.language,
      limit: 1
    };
    if (DRTS_Location_mapboxGeocoding.country) {
      data.country = DRTS_Location_mapboxGeocoding.country;
    }
    console.log('Mapbox geocoding url:', url);
    console.log('Mapbox geocoding request:', data);
    $.getJSON(url, data).done(function(json) {
      console.log('Mapbox geocoding results:', json);
      if (!'features' in json || !json.features.length) {
        var err = new Error(DRTS_Location_apiErrors['Geocoder returned no address components.']);
        if (errorHandler) {
          errorHandler(err);
          return;
        } else {
          throw err;
        }
      }
      callback([json.features[0].center[1], json.features[0].center[0]]);
    }).fail(function(xhr, status, error) {
      var err = new Error(DRTS.sprintf(DRTS_Location_apiErrors['Geocoder failed due to: %s'], error + '(' + status + ')'));
      if (errorHandler) {
        errorHandler(err);
      } else {
        throw err;
      }
    });
  };

  DRTS.Location.api._reverseGeocode = function(latlng, callback, errorHandler) {
    var url = 'https://api.mapbox.com/geocoding/v5/mapbox.places/' + encodeURIComponent(latlng[1] + ',' + latlng[0]) + '.json';
    var data = {
      access_token: DRTS_Location_mapboxGeocoding.accessToken,
      language: DRTS_Location_mapboxGeocoding.language,
      limit: 1
    };
    if (DRTS_Location_mapboxGeocoding.country) {
      data.country = DRTS_Location_mapboxGeocoding.country;
    }
    console.log('Mapbox reverse geocoding url:', url);
    console.log('Mapbox reverse geocoding request:', data);
    $.getJSON(url, data).done(function(json) {
      console.log('Mapbox reverse geocoding results:', json);

      if (!'features' in json || !json.features.length) {
        var err = new Error(DRTS_Location_apiErrors['Geocoder returned no address components.']);
        if (errorHandler) {
          errorHandler(err);
          return;
        } else {
          throw err;
        }
      }

      var ret = {
        address: json.features[0].place_name,
        street: [json.features[0].address || '', json.features[0].text || ''].join(' ')
      };
      if (json.features[0].bbox) {
        ret.viewport = [json.features[0].bbox[1], json.features[0].bbox[0], json.features[0].bbox[3], json.features[0].bbox[2]];
      }

      var context_name = void 0;
      var _iteratorNormalCompletion = true;
      var _didIteratorError = false;
      var _iteratorError = undefined;

      try {
        for (var _iterator = json.features[0].context[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
          var context = _step.value;

          context_name = context.id.substr(0, context.id.indexOf('.'));
          switch (context_name) {
            case 'place':
              ret.city = context.text;
              break;
            case 'region':
              ret.province = context.text;
              break;
            case 'postcode':
              ret.zip = context.text;
              break;
            case 'country':
              ret.country = context.short_code.toUpperCase();
              break;
            default:
              ret[context_name] = context.text;
          }
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

      callback(ret);
    }).fail(function(xhr, status, error) {
      var err = new Error(DRTS.sprintf(DRTS_Location_apiErrors['Geocoder failed due to: %s'], error + '(' + status + ')'));
      if (errorHandler) {
        errorHandler(err);
      } else {
        throw err;
      }
    });
  };
})(jQuery);