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

function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

(function($) {
  DRTS.Map.field = function() {
    function _class(field) {
      var _this = this;

      _classCallCheck(this, _class);

      this.field = $(field);
      this.map = DRTS.Map.api.getMap(field, {
        infobox: false,
        default_zoom: this.field.data('zoom'),
        default_location: {
          lat: this.field.data('center-lat'),
          lng: this.field.data('center-lng')
        },
        scrollwheel: parseInt(this.field.data('scrollwheel')) === 1
      });

      // Add marker
      var lat = this.field.data('lat'),
        lng = this.field.data('lng');
      if (lat && lng) {
        this.setMarker([lat, lng]);
      }

      // Register events
      this.map.getContainer().on('map_clicked.sabai', function(e, data) {
        _this._onMapClicked(data.latlng);
      }).on('map_zoom_changed.sabai', function(e, data) {
        _this._onMapZoomChanged(data.zoom);
      });
      this.field.on('change', '.drts-map-field-lat, .drts-map-field-lng', function(e) {
        var lat = _this.field.find('.drts-map-field-lat').val(),
          lng = _this.field.find('.drts-map-field-lng').val();
        if (lat && lng) {
          if (!lat.match('^[-+]?([1-8]?\\d(\\.\\d+)?|90(\\.0+)?)$')) {
            console.log('Invalid latitude.');
            return;
          }
          if (!lng.match('^[-+]?(180(\\.0+)?|((1[0-7]\\d)|([1-9]?\\d))(\\.\\d+)?)$')) {
            console.log('Invalid longitude.');
            return;
          }
          _this._onLatLngFieldModified([lat, lng]);
        }
      });

      this.map.draw();
    }

    _createClass(_class, [{
      key: '_onMapClicked',
      value: function _onMapClicked(latlng) {
        this.setMarker(latlng).updateFields(latlng);
        this.map.draw({
          zoom: this.map.getZoom()
        });

        return this;
      }
    }, {
      key: '_onMapZoomChanged',
      value: function _onMapZoomChanged(zoom) {
        this.field.find('.drts-map-field-zoom').val(zoom);

        return this;
      }
    }, {
      key: '_onLatLngFieldModified',
      value: function _onLatLngFieldModified(latlng) {
        this.setMarker(latlng).updateFields(latlng);
        this.map.draw({
          zoom: this.map.getZoom()
        });

        return this;
      }
    }, {
      key: 'updateFields',
      value: function updateFields(latlng, zoom) {
        if (typeof zoom === 'undefined') {
          zoom = this.map.getZoom();
        }
        this.field.find('.drts-map-field-zoom').val(zoom).end().find('.drts-map-field-lat').val(latlng[0]).end().find('.drts-map-field-lng').val(latlng[1]);

        return this;
      }
    }, {
      key: 'setMarker',
      value: function setMarker(latlng) {
        this.map.clearMarkers().addMarker({
          lat: latlng[0],
          lng: latlng[1],
          index: 0
        }).getContainer().find('.drts-map-map').fitMaps();

        return this;
      }
    }]);

    return _class;
  }();
})(jQuery);