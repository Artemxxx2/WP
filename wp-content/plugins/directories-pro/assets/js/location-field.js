'use strict';

var _createClass = function() {
  function defineProperties(target, props) {
    for (var i = 0; i < props.length; i++) {
      var descriptor = props[i];
      descriptor.enumerable = descriptor.enumerable || false;
      descriptor.configurable = true;
      if ("value" in descriptor) descriptor.writable = true;
      Object.defineProperty(target, descriptor.key, descriptor);
    }
  }
  return function(Constructor, protoProps, staticProps) {
    if (protoProps) defineProperties(Constructor.prototype, protoProps);
    if (staticProps) defineProperties(Constructor, staticProps);
    return Constructor;
  };
}();

var _get = function get(object, property, receiver) {
  if (object === null) object = Function.prototype;
  var desc = Object.getOwnPropertyDescriptor(object, property);
  if (desc === undefined) {
    var parent = Object.getPrototypeOf(object);
    if (parent === null) {
      return undefined;
    } else {
      return get(parent, property, receiver);
    }
  } else if ("value" in desc) {
    return desc.value;
  } else {
    var getter = desc.get;
    if (getter === undefined) {
      return undefined;
    }
    return getter.call(receiver);
  }
};

function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

function _possibleConstructorReturn(self, call) {
  if (!self) {
    throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  }
  return call && (typeof call === "object" || typeof call === "function") ? call : self;
}

function _inherits(subClass, superClass) {
  if (typeof superClass !== "function" && superClass !== null) {
    throw new TypeError("Super expression must either be null or a function, not " + typeof superClass);
  }
  subClass.prototype = Object.create(superClass && superClass.prototype, {
    constructor: {
      value: subClass,
      enumerable: false,
      writable: true,
      configurable: true
    }
  });
  if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass;
}

(function($) {
  DRTS.Location.field = function(_DRTS$Map$field) {
    _inherits(_class, _DRTS$Map$field);

    function _class(field) {
      _classCallCheck(this, _class);

      var _this = _possibleConstructorReturn(this, (_class.__proto__ || Object.getPrototypeOf(_class)).call(this, field));

      _this.field.find('.drts-location-find-address').click(function(e) {
        var address = [],
          container = _this.field.closest('.drts-location-address-container');
        if (!container.length) {
          container = _this.field;
        }
        container.find('.drts-location-find-address-component').each(function(i, component) {
          var $component = $(component),
            text = void 0;
          if (component.tagName.toLowerCase() === 'select') {
            if (!$component.val()) return true;

            text = $.trim($component.find('option:selected').text());
          } else {
            text = $.trim($component.val());
          }
          if (text.length) {
            address.push(text);
          }
        });
        if (address.length) {
          DRTS.ajaxLoader(e.currentTarget);
          DRTS.Location.api.geocode($.trim(address.join(' ')), function(latlng, viewport) {
            DRTS.ajaxLoader(e.currentTarget, true);
            console.log('Geocoding results:', latlng);
            if (viewport) console.log('Geocoding results (viewport):', viewport);
            _this.setMarker(latlng).updateFields(latlng).updateAddressFields(latlng, e.currentTarget.getAttribute('data-overwrite-fields') ? true : false).updateTimezoneField(latlng);
            _this.map.draw({
              zoom: _this.map.getZoom()
            });
          }, function(err) {
            DRTS.ajaxLoader(e.currentTarget, true);
            _this._showApiError(err);
          });
        }

        return false;
      });
      _this.field.find('.drts-location-get-address').click(function() {
        var latlng = _this.map.getMarkerLatlng();
        if (latlng) {
          _this.updateFields(latlng).updateAddressFields(latlng, true).updateTimezoneField(latlng);
        }

        return false;
      });
      _this.field.find('.drts-location-clear-address').click(function() {
        _this.updateFields(['', ''], '').updateAddressFields('', true).updateTimezoneField();
        _this.map.clearMarkers();
        return false;
      });
      if (navigator.geolocation && (document.location.protocol === 'https:' || document.location.hostname === 'localhost')) {
        _this.field.find('.drts-location-geolocate').show().click(function(e) {
          DRTS.ajaxLoader(e.currentTarget);
          navigator.geolocation.getCurrentPosition(function(pos) {
            DRTS.ajaxLoader(e.currentTarget, true);
            var latlng = [pos.coords.latitude, pos.coords.longitude];
            console.log('Geolocation results:', latlng);
            _this.setMarker(latlng).updateFields(latlng).updateAddressFields(latlng, e.currentTarget.getAttribute('data-overwrite-fields') ? true : false).updateTimezoneField(latlng);
            _this.map.draw({
              zoom: _this.map.getZoom()
            });
          }, function(error) {
            DRTS.ajaxLoader(e.currentTarget, true);
            if (error.code !== 1) {
              // 1 = user denied access
              DRTS.flash(error.message, 'danger');
            }
            console.log(error.message + ' (' + error.code + ')');
          }, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: Infinity
          });

          return false;
        });
      }
      // Apply autocomplete to full address input field
      var input = _this.field.find('.drts-location-text-input');
      if (input.length) {
        DRTS.Location.api.autocomplete(input, function(results) {
          console.log('Autocomplete results:', results);
          _this.setMarker(results.latlng).updateFields(results.latlng).updateTimezoneField(results.latlng)._setAddressComponentValues(results, true);
          _this.map.draw({
            zoom: _this.map.getZoom()
          });
        });
      }
      return _this;
    }

    _createClass(_class, [{
      key: '_onMapClicked',
      value: function _onMapClicked(latlng) {
        return _get(_class.prototype.__proto__ || Object.getPrototypeOf(_class.prototype), '_onMapClicked', this).call(this, latlng).updateAddressFields(latlng, false).updateTimezoneField(latlng);
      }
    }, {
      key: '_onLatLngFieldModified',
      value: function _onLatLngFieldModified(latlng) {
        return _get(_class.prototype.__proto__ || Object.getPrototypeOf(_class.prototype), '_onLatLngFieldModified', this).call(this, latlng).updateAddressFields(latlng, false).updateTimezoneField(latlng);
      }
    }, {
      key: 'updateTimezoneField',
      value: function updateTimezoneField(latlng) {
        var _this2 = this;

        var timezoneField = this.field.find('.drts-location-address-timezone select');
        if (timezoneField.length) {
          if (latlng) {
            DRTS.Location.api.getTimezone(latlng, function(timezone) {
              console.log('Time zone: ' + timezone);
              timezoneField.val(timezone);
            }, function(err) {
              _this2._showApiError(err);
              timezoneField.val('');
            });
          } else {
            timezoneField.val('');
          }
        }

        return this;
      }
    }, {
      key: 'updateAddressFields',
      value: function updateAddressFields(latlng, overwrite) {
        var _this3 = this;

        if (latlng) {
          DRTS.Location.api.reverseGeocode(latlng, function(results) {
            console.log('Reverse geocoding results:', results);
            _this3._setAddressComponentValues(results, overwrite);
          }, function(err) {
            _this3._showApiError(err);
          });
        } else {
          this._setAddressComponentValues([], overwrite);
        }

        return this;
      }
    }, {
      key: '_showApiError',
      value: function _showApiError(err) {
        console.log(err);
        DRTS.flash(err.message, 'danger', 10000);

        return this;
      }
    }, {
      key: '_setAddressComponentValues',
      value: function _setAddressComponentValues(values, overwrite) {
        var components = ['address', 'street', 'city', 'province', 'zip', 'country'];
        var _iteratorNormalCompletion = true;
        var _didIteratorError = false;
        var _iteratorError = undefined;

        try {
          for (var _iterator = components[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
            var component = _step.value;

            this._setAddressComponentValue(component, values[component] || '', overwrite, values);
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

        return this;
      }
    }, {
      key: '_setAddressComponentValue',
      value: function _setAddressComponentValue(component, value, overwrite, values) {
        var found = [];
        this.field.find('.drts-location-address-' + component).each(function(index, field) {
          var $field = $(field);
          if (!overwrite && $field.val() !== '' && $field.attr('type') !== 'hidden' && !$field.closest('.drts-map-location').length) return;

          // Check if custom format requested
          if ($field.data('format')) {
            value = $field.data('format').replace(/{(.*?)}/g, function(all, key) {
              return key in values ? values[key] : '';
            });
            value = value.replace(/\s+/g, ' ').trim().replace(/(^,)|(,$)/g, '') // remove starting/trailing commas
              .trim();
          }

          if (field.tagName.toLowerCase() === 'select') {
            $field.find('option').each(function(i, option) {
              if (option.value === value || option.innerHTML === value) {
                $field.val(option.value);
                found.push(component);
                return false;
              }
            });
          } else {
            $field.val(value);
            found.push(component);
          }
        });

        return found;
      }
    }]);

    return _class;
  }(DRTS.Map.field);
})(jQuery);