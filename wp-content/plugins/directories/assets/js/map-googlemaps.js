'use strict';

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function(obj) {
  return typeof obj;
} : function(obj) {
  return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
};

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
  DRTS.Map.googlemaps = {
    styles: {}
  };

  DRTS.Map.googlemaps.map = function(_DRTS$Map$map) {
    _inherits(_class, _DRTS$Map$map);

    function _class(container, options) {
      _classCallCheck(this, _class);

      var _this = _possibleConstructorReturn(this, (_class.__proto__ || Object.getPrototypeOf(_class)).call(this, container, options));

      _this.markerClusterer = null;
      //this.spiderfier = null;
      _this.overlay = null;
      _this.currentCircle = null;
      var mapTypeIds = [];
      for (var mapType in google.maps.MapTypeId) {
        mapTypeIds.push(google.maps.MapTypeId[mapType]);
      }
      var settings = {
        mapTypeId: $.inArray(_this.options.type, mapTypeIds) !== -1 ? _this.options.type : google.maps.MapTypeId.ROADMAP,
        mapTypeControl: typeof _this.options.map_type_control === 'undefined' || _this.options.map_type_control ? true : false,
        zoomControl: true,
        streetViewControl: false,
        scaleControl: false,
        rotateControl: false,
        fullscreenControl: _this.options.fullscreen_control || false,
        center: new google.maps.LatLng(_this.options.default_location.lat, _this.options.default_location.lng),
        scrollwheel: _this.options.scrollwheel,
        styles: _this.options.style && DRTS.Map.googlemaps.styles[_this.options.style] ? DRTS.Map.googlemaps.styles[_this.options.style] : [{
          'featureType': 'poi',
          'stylers': [{
            'visibility': 'off'
          }]
        }],
        zoom: _this.options.default_zoom
      };
      if (settings.mapTypeControl) {
        settings.mapTypeControlOptions = {
          style: google.maps.MapTypeControlStyle.DROPDOWN_MENU,
          mapTypeIds: mapTypeIds,
          position: google.maps.ControlPosition.TOP_RIGHT
        };
      }
      _this.map = new google.maps.Map(_this.$map.get(0), settings);

      // Add marker clusterer?
      if (_this.options.marker_clusters) {
        var marker_cluster_color = _this.options.marker_cluster_color || null;
        var renderer = {
          render: function render(_ref, stats) {
            var count = _ref.count,
              position = _ref.position;

            var color = marker_cluster_color || (count > Math.max(10, stats.clusters.markers.mean) ? '#ff0000' : '#0000ff');
            var svg = window.btoa('\n  <svg fill="' + color + '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240">\n    <circle cx="120" cy="120" opacity=".6" r="70" />\n    <circle cx="120" cy="120" opacity=".3" r="90" />\n    <circle cx="120" cy="120" opacity=".2" r="110" />\n  </svg>');
            return new google.maps.Marker({
              position: position,
              icon: {
                url: 'data:image/svg+xml;base64,' + svg,
                scaledSize: new google.maps.Size(45, 45)
              },
              label: {
                text: String(count),
                color: 'rgba(255,255,255,0.9)',
                fontSize: '12px'
              },
              title: 'Cluster of ' + count + ' markers',
              zIndex: Number(google.maps.Marker.MAX_ZINDEX) + count
            });
          }
        };
        _this.markerClusterer = new markerClusterer.MarkerClusterer({
          map: _this.map,
          renderer: renderer
        });
      }

      // Add marker spiderifier?
      //if (this.options.spiderify) {
      //    this.spiderfier = new OverlappingMarkerSpiderfier(this.map, {
      //        markersWontMove: true,
      //        markersWontHide: true,
      //        basicFormatEvents: true
      //    });
      //    this.options.infobox_event = 'spider_click';
      //}

      // Enable popover
      if (_this.options.infobox) {
        _this.getPopover();
        _this.getOverlay();
        var hidePopover = function hidePopover() {
          _this.getPopover().sabaiPopover('hide');
          _this.currentMarker = null;
        };
        google.maps.event.addListener(_this.map, 'dragstart', hidePopover);
        google.maps.event.addListener(_this.map, 'zoom_changed', hidePopover);
        $(window).on('resize', hidePopover);
      }

      // Init street view panorama
      _this.map.getStreetView().setOptions({
        disableDefaultUI: true,
        enableCloseButton: false,
        zoomControl: true,
        visible: false
      });

      // Fire events
      google.maps.event.addListener(_this.map, 'click', function(e) {
        _this.container.trigger('map_clicked.sabai', {
          map: _this.map,
          latlng: [e.latLng.lat(), e.latLng.lng()]
        });
      });
      google.maps.event.addListener(_this.map, 'zoom_changed', function(e) {
        _this.container.trigger('map_zoom_changed.sabai', {
          map: _this.map,
          zoom: _this.map.getZoom()
        });
      });
      google.maps.event.addListener(_this.map, 'dragend', function(e) {
        _this.container.trigger('map_dragend.sabai', {
          map: _this.map
        });
      });
      google.maps.event.addListener(_this.map, 'mousedown', function(e) {
        _this.container.trigger('map_mousedown.sabai', {
          map: _this.map,
          latlng: [e.latLng.lat(), e.latLng.lng()]
        });
      });
      return _this;
    }

    _createClass(_class, [{
      key: 'clearMarkers',
      value: function clearMarkers() {
        for (var i in this.markers) {
          if (!this.markers.hasOwnProperty(i)) continue;

          this.markers[i].setMap(null);
        }
        if (this.markerClusterer) {
          this.markerClusterer.clearMarkers();
        }
        return _get(_class.prototype.__proto__ || Object.getPrototypeOf(_class.prototype), 'clearMarkers', this).call(this);
      }
    }, {
      key: 'addMarker',
      value: function addMarker(marker) {
        if (!marker.lat || !marker.lng) return this;

        var _marker = void 0;
        var defaultMarkerIconOptions = void 0;
        if (this.options.marker_custom) {
          if (marker.icon) {
            var markerIconOptions = {
              html: marker.icon.url ? $('<img/>').attr('src', marker.icon.url)[0].outerHTML : null,
              icon: marker.icon.icon || this.options.marker_icon,
              icon_color: marker.icon.icon_color || this.options.marker_icon_color,
              full: marker.icon.is_full ? true : false,
              size: marker.icon.size || this.options.marker_size,
              color: marker.icon.color || this.options.marker_color || '#fff',
              event: this.options.infobox_event
            };
            _marker = new DRTS.Map.googlemaps.map.marker(markerIconOptions);
          } else {
            if (typeof defaultMarkerIconOptions === 'undefined') {
              defaultMarkerIconOptions = {
                icon: this.options.marker_icon || '',
                icon_color: this.options.marker_icon_color,
                size: this.options.marker_size,
                color: this.options.marker_color || '#fff',
                event: this.options.infobox_event
              };
            }
            _marker = new DRTS.Map.googlemaps.map.marker(defaultMarkerIconOptions);
          }
        } else {
          _marker = new google.maps.Marker();
        }
        _marker.setPosition(new google.maps.LatLng(marker.lat, marker.lng));
        _marker.set('id', marker.entity_id + '-' + marker.index);
        _marker.set('content', marker.content);
        _marker.set('entity_id', marker.entity_id);
        _marker.set('key', marker.index);

        this.markers[_marker.get('id')] = _marker;
        return this;
      }
    }, {
      key: 'getMarkerLatlng',
      value: function getMarkerLatlng(marker) {
        if (typeof marker === 'undefined' || marker === null) {
          var index = Object.keys(this.markers)[0];
          marker = this.markers[index];
        }

        var pos = marker.getPosition();
        return [pos.lat(), pos.lng()];
      }
    }, {
      key: 'getMarkerContent',
      value: function getMarkerContent(marker) {
        return marker.get('content');
      }
    }, {
      key: 'getMarkerPosition',
      value: function getMarkerPosition(marker) {
        if (!this.getOverlay() || !this.getOverlay().getProjection()) {
          return;
        }

        return this.getOverlay().getProjection().fromLatLngToContainerPixel(marker.getPosition());
      }
    }, {
      key: 'getMarkerHeight',
      value: function getMarkerHeight(marker) {
        return marker.get('marker_height');
      }
    }, {
      key: 'getMarkerEntityId',
      value: function getMarkerEntityId(marker) {
        return marker.get('entity_id');
      }
    }, {
      key: 'getMarkerKey',
      value: function getMarkerKey(marker) {
        return marker.get('key');
      }
    }, {
      key: 'draw',
      value: function draw(options) {
        var _this2 = this;

        options = options || {};
        this.currentMarker = null;
        if (this.currentCircle) {
          this.currentCircle.setMap(null);
        }

        if (Object.keys(this.markers).length > 0) {
          var fit_bounds = void 0,
            fit_bounds_padding = void 0,
            bounds = void 0;
          fit_bounds = typeof options.fit_bounds === 'undefined' ? this.options.fit_bounds : options.fit_bounds;

          if (fit_bounds && Object.keys(this.markers).length > 1) {
            bounds = new google.maps.LatLngBounds();
          }

          for (var i in this.markers) {
            if (!this.markers.hasOwnProperty(i)) continue;

            //if (this.spiderfier) {
            //    this.spiderfier.addMarker(this.markers[i]);
            //} else {
            if (!this.markerClusterer) {
              // will add markers in bulk later if marker cluster exists
              this.markers[i].setMap(this.map);
            }
            //}

            if (bounds) {
              var pos = this.markers[i].getPosition();
              bounds.extend(pos);
              if (options.center) {
                // Extend bound to include the point opposite the marker so the center stays the same
                bounds.extend(new google.maps.LatLng(options.center[0] * 2 - pos.lat(), options.center[1] * 2 - pos.lng()));
              }
            }
            google.maps.event.addListener(this.markers[i], this.options.infobox_event, function(marker) {
              return function(e) {
                _this2.clickMarker(marker);
              };
            }(this.markers[i]));

            if (Object.keys(this.markers).length <= 100) {
              // Bounce on display
              this.markers[i].setAnimation(google.maps.Animation.BOUNCE);
              setTimeout(function(marker) {
                return function() {
                  marker.setAnimation(null);
                };
              }(this.markers[i]), 500);
            }
          }

          if (this.markerClusterer) {
            this.markerClusterer.addMarkers(Object.values(this.markers));
          }

          if (bounds) {
            this.map.fitBounds(bounds, typeof options.fit_bounds_padding === 'undefined' ? this.options.fit_bounds_padding : options.fit_bounds_padding);
          } else {
            // Center position required if no automatic bounding
            if (!options.center) {
              if (this.options.center_default) {
                options.center = [this.options.default_location.lat, this.options.default_location.lng];
              } else {
                var _pos = this.markers[Object.keys(this.markers)[0]].getPosition();
                options.center = [_pos.lat(), _pos.lng()];
              }
            }
          }

          if (options.street_view) {
            this.drawStreetView(_typeof(options.street_view) === 'object' ? options.street_view : this.markers[Object.keys(this.markers)[0]]);
          }
        }

        if (options.center) {
          var center = new google.maps.LatLng(options.center[0], options.center[1]);
          this.map.setZoom(options.zoom || this.options.default_zoom || 10);
          this.map.panTo(center);
          if (options.circle) {
            this.currentCircle = new google.maps.Circle({
              strokeColor: options.circle.stroke_color || '#99f',
              strokeOpacity: 0.8,
              strokeWeight: 1,
              fillColor: options.circle.fill_color || '#99f',
              fillOpacity: 0.3,
              map: this.map,
              center: center,
              radius: options.circle.radius
            });
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
        if (this.currentMarker) {
          if (this.currentMarker.get('id') === marker.get('id')) {
            this.showMarkerContent(marker, triggered);
            this.currentMarker = marker;
            if (!triggered) {
              // make sure manually clicked
              this.container.trigger('marker_click.sabai', {
                map: this,
                marker: marker
              });
            }
            return;
          }

          this.currentMarker.setZIndex(0);
        }

        marker.setZIndex(1);

        if (this.markerClusterer) {
          // Add back previously removed marker
          if (this.currentMarker) {
            this.markerClusterer.addMarker(this.currentMarker);
          }
          // Remove marker from cluster for better view of the marker
          this.markerClusterer.removeMarker(marker);
          marker.setMap(this.map);
        }

        if (this.map.getBounds() && !this.map.getBounds().contains(marker.getPosition())) {
          this.map.panTo(marker.getPosition());
        }

        if (this.markerClusterer) {
          // For some reason delay is required before showing content in popover when marker cluster enabled
          setTimeout(function() {
            this.showMarkerContent(marker, triggered);
          }.bind(this), 100);
        } else {
          this.showMarkerContent(marker, triggered);
        }

        this.currentMarker = marker;

        if (!triggered) {
          // make sure manually clicked
          this.container.trigger('marker_click.sabai', {
            map: this,
            marker: marker
          });
        }
      }
    }, {
      key: 'animateMarker',
      value: function animateMarker(marker) {
        marker.setAnimation(google.maps.Animation.BOUNCE);
        setTimeout(function() {
          marker.setAnimation(null);
        }, 1000);
      }
    }, {
      key: 'onResized',
      value: function onResized() {
        this.getOverlay(true);
        google.maps.event.trigger(this.map, 'resize');
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
        return [bounds.getSouthWest().lat(), bounds.getSouthWest().lng()];
      }
    }, {
      key: 'getNorthEast',
      value: function getNorthEast() {
        var bounds = this.map.getBounds();
        return [bounds.getNorthEast().lat(), bounds.getNorthEast().lng()];
      }
    }, {
      key: 'getOverlay',
      value: function getOverlay(create) {
        if (!this.overlay || create) {
          this.overlay = new google.maps.OverlayView();
          this.overlay.draw = function() {};
          this.overlay.setMap(this.map);
        }
        return this.overlay;
      }
    }, {
      key: 'drawStreetView',
      value: function drawStreetView(position, radius, notify) {
        var sv = new google.maps.StreetViewService(),
          map = this.map,
          marker = void 0;
        if (position.setMap) {
          marker = position;
          position = position.getPosition();
        }
        sv.getPanorama({
          location: position,
          radius: radius || 50
        }, function(data, status) {
          if (status === google.maps.StreetViewStatus.OK) {
            var pano = map.getStreetView();
            pano.setPosition(data.location.latLng);
            if (marker) {
              var heading = google.maps.geometry.spherical.computeHeading(data.location.latLng, position);
              pano.setPov({
                heading: heading,
                pitch: 0,
                zoom: 1
              });
              marker.setMap(pano);
            }
            pano.setVisible(true);
          } else {
            if (notify) {
              alert('No street map view is available for this location.');
            }
            console.log(status);
          }
        });
        return this;
      }
    }]);

    return _class;
  }(DRTS.Map.map);

  DRTS.Map.api.getMap = function(container, options) {
    return new DRTS.Map.googlemaps.map(container, options);
  };

  DRTS.Map.googlemaps.map.marker = function(options) {
    this.options = options || {};
    this.visible = true;
    this.classes = this.options.full ? ['drts-map-marker drts-map-marker-full'] : ['drts-map-marker'];
    this.div = null;
  };
  DRTS.Map.googlemaps.map.marker.prototype = new google.maps.OverlayView();
  DRTS.Map.googlemaps.map.marker.prototype.onAdd = function() {
    var _this3 = this;
    this.div = document.createElement('div');
    this.div.className = this.classes.join(' ');
    if (this.options.full) {
      this.div.innerHTML = this.options.html;
    } else {
      var size = this.options.size || 38;
      var marker = document.createElement('div');
      this.div.style.width = size + 'px';
      this.div.style.height = size + 'px';
      this.div.style.marginTop = '-' + (size * Math.sqrt(2) - DRTS.Map.markerHeight(size)) + 'px';
      if (this.options.color) {
        this.div.style.backgroundColor = this.div.style.color = marker.style.borderColor = this.options.color;
      }
      if (this.options.html) {
        marker.innerHTML = this.options.html;
      } else if (this.options.icon) {
        marker.innerHTML = '<i class="' + this.options.icon + '"></i>';
        if (this.options.icon_color) {
          marker.style.backgroundColor = this.options.icon_color;
        }
      } else {
        marker.style.boxShadow = 'none';
        if (this.options.icon_color) {
          marker.style.backgroundColor = this.options.icon_color;
        }
      }
      this.div.appendChild(marker);
      this.set('marker_height', DRTS.Map.markerHeight(size));
    }
    if (this.options.data) {
      this.div.dataset = this.options.data;
    }
    this.getPanes().overlayImage.appendChild(this.div);
    var ev = this.options.event;
    this.div.addEventListener(ev, function(event) {
      google.maps.event.trigger(_this3, ev);
    });
    this.setPosition(this.position);
  };
  DRTS.Map.googlemaps.map.marker.prototype.draw = function() {
    this.setPosition(this.position);
  };
  DRTS.Map.googlemaps.map.marker.prototype.setPosition = function(position) {
    this.position = position;
    if (this.div) {
      var point = this.getProjection().fromLatLngToDivPixel(this.position);
      if (point) {
        this.div.style.left = point.x + 'px';
        this.div.style.top = point.y + 'px';
      }
    }
  };
  DRTS.Map.googlemaps.map.marker.prototype.onRemove = function() {
    if (this.div) {
      this.div.parentNode.removeChild(this.div);
    }
    this.div = null;
  };
  DRTS.Map.googlemaps.map.marker.prototype.getPosition = function() {
    return this.position;
  };
  DRTS.Map.googlemaps.map.marker.prototype.setDraggable = function(draggable) {
    this.draggable = draggable;
  };
  DRTS.Map.googlemaps.map.marker.prototype.getDraggable = function() {
    this.draggable;
  };
  DRTS.Map.googlemaps.map.marker.prototype.getVisible = function() {
    return this.visible;
  };
  DRTS.Map.googlemaps.map.marker.prototype.setVisible = function(visible) {
    if (this.div) {
      this.div.style.display = visible ? 'inline-block' : 'none';
    }
    this.visible = visible;
  };
  DRTS.Map.googlemaps.map.marker.prototype.getDraggable = function() {
    return this.draggable;
  };
  DRTS.Map.googlemaps.map.marker.prototype.setDraggable = function(draggable) {
    this.draggable = draggable;
  };
  DRTS.Map.googlemaps.map.marker.prototype.setZIndex = function(zIndex) {
    this.zIndex = zIndex;
    if (this.div) {
      this.div.style.zIndex = this.zIndex;
    }
  };
  DRTS.Map.googlemaps.map.marker.prototype.setAnimation = function(animation) {
    var class_name = 'drts-map-marker-bounce';
    if (animation) {
      if (this.classes.indexOf(class_name) === -1) {
        this.classes.push(class_name);
      }
    } else {
      var index = this.classes.indexOf(class_name);
      if (index > -1) {
        this.classes.splice(index, 1);
      }
    }
    if (this.div) {
      this.div.className = this.classes.join(' ');
    }
  };
})(jQuery);