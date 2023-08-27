'use strict';

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function(obj) {
  return typeof obj;
} : function(obj) {
  return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
};

(function($) {
  DRTS.Location.googlemaps = DRTS.Location.googlemaps || {};
  DRTS.Location.googlemaps.parsePlace = function(place) {
    var ret = {},
      i = void 0,
      j = void 0,
      type = void 0,
      val = void 0;
    ret.address = place.formatted_address;
    ret.latlng = [place.geometry.location.lat(), place.geometry.location.lng()];
    var sw = place.geometry.viewport.getSouthWest(),
      ne = place.geometry.viewport.getNorthEast();
    ret.viewport = [sw.lat(), sw.lng(), ne.lat(), ne.lng()];
    for (i in place.address_components) {
      if (_typeof(place.address_components[i]) !== 'object' || !place.address_components[i].types || !place.address_components[i].long_name) continue;

      val = place.address_components[i].long_name;
      for (j in place.address_components[i].types) {
        type = place.address_components[i].types[j];
        switch (type) {
          case 'street_address':
            ret.street = val;
            break;
          case 'sublocality':
          case 'locality':
            ret.city = ret[type] = val;
            break;
          case 'administrative_area_level_1':
            ret.province = val;
            break;
          case 'postal_code':
            ret.zip = val;
            break;
          case 'country':
            ret.country = place.address_components[i].short_name.toUpperCase();
            break;
          case 'political':
            break;
          default:
            ret[type] = val;
        }
      }
    }

    if ('street' in ret === false) {
      if ('route' in ret === true) {
        if ('street_number' in ret === true) {
          if (DRTS_Location_googlemapsGeocoding.house_number_pos && DRTS_Location_googlemapsGeocoding.house_number_pos === 'after') {
            ret.street = ret.route + ' ' + ret.street_number;
          } else {
            ret.street = ret.street_number + ' ' + ret.route;
          }
        } else {
          ret.street = ret.route;
        }
      }
    }

    if ('city' in ret === false) {
      if ('administrative_area_level_3' in ret === true) {
        ret.city = ret.administrative_area_level_3;
      } else if ('administrative_area_level_2' in ret === true) {
        ret.city = ret.administrative_area_level_2;
      }
    }

    if (DRTS_Location_googlemapsGeocoding.cityComponent && DRTS_Location_googlemapsGeocoding.cityComponent in ret === true) {
      ret.city = ret[DRTS_Location_googlemapsGeocoding.cityComponent];
    }

    return ret;
  };
})(jQuery);