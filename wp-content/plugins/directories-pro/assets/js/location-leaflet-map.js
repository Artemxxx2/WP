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
  DRTS.Location = DRTS.Location || {};
  DRTS.Location.leaflet = DRTS.Location.leaflet || {};
  DRTS.Location.leaflet.map = function(_DRTS$Map$map) {
    _inherits(_class, _DRTS$Map$map);

    function _class(container, options) {
      _classCallCheck(this, _class);

      var _this = _possibleConstructorReturn(this, (_class.__proto__ || Object.getPrototypeOf(_class)).call(this, container, options));

      var settings = {
        zoomControl: false,
        zoom: _this.options.default_zoom,
        scrollWheelZoom: _this.options.scrollwheel,
        center: L.latLng(_this.options.default_location.lat, _this.options.default_location.lng),
        layers: [L.tileLayer(_this.options.use_custom_tile_url && _this.options.tile_url ? _this.options.tile_url : '//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          id: 'osm',
          attribution: _this.options.add_attribution || typeof _this.options.add_attribution === 'undefined' ? _this.options.attribution || '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors' : false
        })],
        gestureHandling: true
      };
      _this.map = L.map(_this.$map.get(0), settings);
      L.control.zoom({
        position: 'bottomright'
      }).addTo(_this.map);
      _this.currentCircle = null;

      // Add marker clusterer?
      _this.markerCluster = null;
      if (_this.options.marker_clusters) {
        _this.markerCluster = L.markerClusterGroup({
          spiderfyOnMaxZoom: true,
          showCoverageOnHover: false,
          zoomToBoundsOnClick: true
        });
      }

      // Enable popover?
      if (_this.options.infobox) {
        _this.map.on('resize movestart zoomstart', function(event) {
          _this.getPopover().sabaiPopover('hide');
          _this.currentMarker = null;
        });
      }

      // Fire events
      _this.map.on('click', function(e) {
        _this.container.trigger('map_clicked.sabai', {
          map: _this.map,
          latlng: [e.latlng.lat, e.latlng.wrap().lng]
        });
      });
      _this.map.on('zoomend', function(e) {
        _this.container.trigger('map_zoom_changed.sabai', {
          map: _this.map,
          zoom: _this.map.getZoom()
        });
      });
      _this.map.on('dragend', function(e) {
        _this.container.trigger('map_dragend.sabai', {
          map: _this.map
        });
      });
      _this.map.on('mousedown', function(e) {
        _this.container.trigger('map_mousedown.sabai', {
          map: _this.map
        });
      });
      return _this;
    }

    _createClass(_class, [{
      key: 'clearMarkers',
      value: function clearMarkers() {
        for (var i in this.markers) {
          if (!this.markers.hasOwnProperty(i)) continue;

          this.map.removeLayer(this.markers[i]);
        }
        if (this.markerCluster) {
          this.markerCluster.clearLayers();
        }
        return _get(_class.prototype.__proto__ || Object.getPrototypeOf(_class.prototype), 'clearMarkers', this).call(this);
      }
    }, {
      key: 'addMarker',
      value: function addMarker(marker) {
        if (!marker.lat || !marker.lng) return this;

        var _marker = void 0,
          icon = void 0,
          _markerOptions = {
            riseOnHover: true
            //bounceOnAdd: this.markerCluster ? false : true // does not work well with field
          };
        if (this.options.marker_custom) {
          if (marker.icon) {
            icon = DRTS.Location.leaflet.map.divIcon({
              html: marker.icon.url ? $('<img/>').attr('src', marker.icon.url)[0].outerHTML : null,
              icon: marker.icon.icon || this.options.marker_icon,
              icon_color: marker.icon.icon_color || this.options.marker_icon_color,
              size: marker.icon.size || this.options.marker_size,
              color: marker.icon.color || this.options.marker_color || '#fff',
              full: marker.icon.is_full ? true : false
            });
          } else {
            icon = DRTS.Location.leaflet.map.divIcon({
              icon: this.options.marker_icon || '',
              icon_color: this.options.marker_icon_color,
              size: this.options.marker_size,
              color: this.options.marker_color || '#fff'
            });
          }
          _markerOptions.icon = icon;
        }
        _marker = L.marker([marker.lat, marker.lng], _markerOptions);
        _marker._id = marker.entity_id + '-' + marker.index;
        _marker._content = marker.content;
        _marker._entity_id = marker.entity_id;
        _marker._key = marker.index;

        this.markers[_marker._id] = _marker;
        return this;
      }
    }, {
      key: 'getMarkerLatlng',
      value: function getMarkerLatlng(marker) {
        if (typeof marker === 'undefined' || marker === null) {
          var index = Object.keys(this.markers)[0];
          marker = this.markers[index];
        }

        var latlng = marker.getLatLng();
        return [latlng.lat, latlng.lng];
      }
    }, {
      key: 'getMarkerContent',
      value: function getMarkerContent(marker) {
        return marker._content;
      }
    }, {
      key: 'getMarkerPosition',
      value: function getMarkerPosition(marker) {
        return this.map.latLngToContainerPoint(marker.getLatLng());
      }
    }, {
      key: 'getMarkerHeight',
      value: function getMarkerHeight(marker) {
        return marker._marker_height;
      }
    }, {
      key: 'getMarkerEntityId',
      value: function getMarkerEntityId(marker) {
        return marker._entity_id;
      }
    }, {
      key: 'getMarkerKey',
      value: function getMarkerKey(marker) {
        return marker._key;
      }
    }, {
      key: 'draw',
      value: function draw(options) {
        var _this2 = this;

        options = options || {};
        this.currentMarker = null;
        if (this.currentCircle) {
          this.currentCircle.remove();
        }

        if (Object.keys(this.markers).length > 0) {
          var fit_bounds = void 0,
            bounds = [];
          fit_bounds = typeof options.fit_bounds === 'undefined' ? this.options.fit_bounds : options.fit_bounds;
          if (Object.keys(this.markers).length <= 1) {
            fit_bounds = false;
          }

          for (var i in this.markers) {
            if (!this.markers.hasOwnProperty(i)) continue;

            if (!this.markerCluster) {
              // will add markers in bulk later if marker cluster exists
              this.markers[i].addTo(this.map);
            }
            if (fit_bounds) {
              var pos = this.markers[i].getLatLng();
              bounds.push(pos);
              if (options.center) {
                // Extend bound to include the point opposite the marker so the center stays the same
                bounds.push([options.center[0] * 2 - pos.lat, options.center[1] * 2 - pos.lng]);
              }
            }
            this.markers[i].on(this.options.infobox_event, function(map, marker) {
              return function(e) {
                map.clickMarker(marker);
              };
            }(this, this.markers[i]));
          }

          if (this.markerCluster) {
            this.markerCluster.addLayers(Object.values(this.markers));
            this.map.addLayer(this.markerCluster);
          }

          if (fit_bounds) {
            var padding = typeof options.fit_bounds_padding === 'undefined' ? this.options.fit_bounds_padding : options.fit_bounds_padding;
            this.map.fitBounds(bounds, {
              padding: [padding, padding]
            });
          } else {
            // Center position required if no automatic bounding
            if (!options.center) {
              if (this.options.center_default) {
                options.center = [this.options.default_location.lat, this.options.default_location.lng];
              } else {
                options.center = this.markers[Object.keys(this.markers)[0]].getLatLng();
              }
            }
            // To fix map being centered incorrectly in flexbox
            setTimeout(function() {
              _this2.map.invalidateSize();
            }, 500);
          }
        }

        if (options.center) {
          this.map.setView(options.center, options.zoom || this.options.default_zoom || 10);
          if (options.circle) {
            this.currentCircle = L.circle(options.center, options.circle.radius, {
              color: options.circle.stroke_color || '#99f',
              opacity: 0.8,
              weight: 1,
              fill: true,
              fillColor: options.circle.fill_color || '#99f',
              fillOpacity: 0.3
            }).addTo(this.map);
          }
        }

        $(DRTS).trigger('map_drawn.sabai', {
          map: this
        });

        return this;
      }
    }, {
      key: 'clickMarker',
      value: function clickMarker(marker, triggered) {
        var _this3 = this;

        if (this.currentMarker) {
          if (this.currentMarker === marker._id) {
            this.showMarkerContent(marker, triggered);
            this.currentMarker = marker._id;
            if (!triggered) {
              // make sure manually clicked
              this.container.trigger('marker_click.sabai', {
                map: this,
                marker: marker
              });
            }
            return;
          }

          this.markers[this.currentMarker].setZIndexOffset(0);
        }

        marker.setZIndexOffset(2000);

        if (triggered && this.markerCluster) {
          // Add back previously removed marker
          if (this.currentMarker) {
            this.map.removeLayer(this.markers[this.currentMarker]);
            this.markerCluster.addLayer(this.markers[this.currentMarker]);
          }
          // Remove marker from cluster for better view of the marker
          this.markerCluster.removeLayer(marker);
          marker.addTo(this.map);
        }

        if (this.map.getBounds() && !this.map.getBounds().contains(marker.getLatLng())) {
          this.map.panTo(marker.getLatLng(), {
            duration: .25
          });
          // Wait till pan finishes
          setTimeout(function() {
            _this3.showMarkerContent(marker);
          }, 300);
        } else {
          this.showMarkerContent(marker, triggered);
        }

        this.currentMarker = marker._id;

        if (!triggered) {
          // make sure manually clicked
          this.container.trigger('marker_click.sabai', {
            map: this,
            marker: marker
          });
        }

        return this;
      }
    }, {
      key: 'animateMarker',
      value: function animateMarker(marker) {
        marker.bounce({
          duration: 800,
          height: 50
        });
      }
    }, {
      key: 'onResized',
      value: function onResized() {
        this.map.invalidateSize();
        return this;
      }
    }, {
      key: 'getZoom',
      value: function getZoom() {
        return this.map.getZoom();
      }
    }, {
      key: 'getSouthWest',
      value: function getSouthWest() {
        var bounds = this.map.getBounds();
        return [bounds.getSouthWest().lat, bounds.getSouthWest().lng];
      }
    }, {
      key: 'getNorthEast',
      value: function getNorthEast() {
        var bounds = this.map.getBounds();
        return [bounds.getNorthEast().lat, bounds.getNorthEast().lng];
      }
    }]);

    return _class;
  }(DRTS.Map.map);

  DRTS.Location.leaflet.map.divIcon = function(options) {
    var div = document.createElement('div');
    var cls = 'drts-map-marker';
    if (options.full) {
      cls += ' drts-map-marker-full';
      div.innerHTML = options.html;
    } else {
      var size = options.size || 39;
      var inner = document.createElement('div');
      if (options.color) {
        div.style.backgroundColor = div.style.color = inner.style.borderColor = options.color;
      }
      div.style.width = size + 'px';
      div.style.height = size + 'px';
      div.style.marginTop = '-' + (size * Math.sqrt(2) - DRTS.Map.markerHeight(size)) + 'px';
      if (options.html) {
        inner.innerHTML = options.html;
      } else if (options.icon) {
        inner.innerHTML = '<i class="' + options.icon + '"></i>';
        if (options.icon_color) {
          inner.style.backgroundColor = options.icon_color;
        }
      } else {
        inner.style.boxShadow = 'none';
        if (options.icon_color) {
          inner.style.backgroundColor = options.icon_color;
        }
      }
      div.appendChild(inner);
    }
    div.className = cls;
    if (options.data) {
      div.dataset = options.data;
    }

    return L.divIcon({
      html: div.outerHTML,
      iconSize: [0, 0]
    });
  };

  DRTS.Map.api.getMap = function(container, options) {
    return new DRTS.Location.leaflet.map(container, options);
  };
})(jQuery);