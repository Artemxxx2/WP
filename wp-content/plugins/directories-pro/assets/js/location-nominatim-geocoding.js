'use strict';

(function($) {
  DRTS.Location.api.geocode = function(address, callback, errorHandler) {
    var data = {
      format: 'json',
      limit: 1,
      q: address,
      'accept-language': DRTS_Location_nominatimGeocoding.language
    };
    console.log('Nominatim geocoding request:', data);
    $.getJSON('https://nominatim.openstreetmap.org/search', data).done(function(json) {
      console.log('Nominatim geocoding results:', json);
      if (!json.length) {
        var err = new Error(DRTS_Location_apiErrors['Geocoder returned no address components.']);
        if (errorHandler) {
          errorHandler(err);
          return;
        } else {
          throw err;
        }
      }
      callback([json[0].lat, json[0].lon]);
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
    var data = {
      format: 'json',
      addressdetails: 1,
      zoom: 18,
      lat: latlng[0],
      lon: latlng[1],
      'accept-language': DRTS_Location_nominatimGeocoding.language
    };
    console.log('Nominatim reverse geocoding request:', data);
    $.getJSON('https://nominatim.openstreetmap.org/reverse', data).done(function(json) {
      console.log('Nominatim reverse geocoding results:', json);

      if (json.error) {
        var err = new Error(json.error);
        if (errorHandler) {
          errorHandler(err);
          return;
        } else {
          throw err;
        }
      }

      if (!'display_name' in json || !'address' in json) {
        var _err = new Error(DRTS_Location_apiErrors['Geocoder returned no address components.']);
        if (errorHandler) {
          errorHandler(_err);
          return;
        } else {
          throw _err;
        }
      }

      var ret = {
        address: json.display_name,
        viewport: [json.boundingbox[0], json.boundingbox[2], json.boundingbox[1], json.boundingbox[3]]
      };

      for (var type in json.address) {
        switch (type) {
          case 'road':
            ret.street = json.address[type];
            break;
          case 'village':
            ret.city = json.address[type];
            break;
          case 'state':
            ret.province = json.address[type];
            break;
          case 'postcode':
            ret.zip = json.address[type];
            break;
          case 'country_code':
            ret.country = json.address[type].toUpperCase();
            break;
          default:
            ret[type] = json.address[type];
        }
      }

      if ('house_number' in ret && 'street' in ret) {
        if (DRTS_Location_nominatimGeocoding.house_number_pos && DRTS_Location_nominatimGeocoding.house_number_pos === 'after') {
          ret.street = ret.street + ' ' + ret.house_number;
        } else {
          ret.street = ret.house_number + ' ' + ret.street;
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