'use strict';

(function($) {
  DRTS.Location = DRTS.Location || {};

  DRTS.Location.map = DRTS.Location.map || function(map) {
    var options = map.getOptions();
    if (!$(DRTS).data('initialized-' + map.getContainerSelector())) {
      var entitiesContainer = map.getContainer().find('.drts-view-entities-container');
      if (entitiesContainer.length > 0) {
        $(DRTS).on('loaded.sabai', function(e, data) {
          if (data.target.hasClass('drts-view-entities-container') && data.target.find('.drts-location-entities-map-container').length) {
            if (!data.append && data.target.hasClass('drts-location-map-full')) {
              $('.drts-location-entities-container').animate({
                scrollTop: 0
              }, 500, 'swing');
            }
            if (data.response.markers && Array.isArray(data.response.markers)) {
              if (!data.append) {
                map.clearMarkers();
              }
              map.setMarkers(data.response.markers).draw(data.response.draw_options);
            }

            map.getContainer().find('.drts-location-map-control > i').removeClass('fa-spin');

            if (data.append && options.sticky) {
              DRTS.Location.stickyScroll(map, false, false, true);
            }
          }
        });

        var triggerCallback = function triggerCallback(e) {
          e.preventDefault();

          // Do nothing if map not shown
          var mapContainerContainer = map.getContainer().find('.drts-location-map-container-container');
          if (!mapContainerContainer.length || mapContainerContainer.is(':hidden')) return;

          var trigger = $(e.currentTarget),
            entity = void 0,
            key = void 0;
          if (trigger.hasClass('drts-entity')) {
            entity = trigger;
            key = 0;
          } else {
            entity = trigger.closest('.drts-entity');
            if (!entity.length) return;

            key = trigger.data('key');
          }
          var marker = map.getMarker(entity.data('entity-id') + '-' + key);
          if (marker) {
            map.clickMarker(marker, true);
          }
        };
        entitiesContainer.hoverIntent(triggerCallback, function() {}, '.drts-map-marker-trigger, .drts-entity');
      }
      map.getContainer().on('marker_click.sabai', function(e, data) {
        map.getContainer().find('.drts-highlighted').removeClass('drts-highlighted');

        if (!options.scroll_to_item || !data.marker) return;

        var entity_id = void 0;
        if (data.marker.get) {
          entity_id = data.marker.get('entity_id');
        } else {
          entity_id = data.marker._entity_id;
        }
        if (!entity_id) return;

        var $item = map.getContainer().find('.drts-entity[data-entity-id="' + entity_id + '"]');
        if (!$item.length || !$item.is(':visible')) return;

        DRTS.Location.scrollToItem($item, function() {
          $item.addClass('drts-highlighted');
        }, options.sticky);
      });

      $(DRTS).data('initialized-' + map.getContainerSelector(), true);
    }
    DRTS.Location.enableMapControls(map, options);
    if (options.sticky) {
      DRTS.Location.stickyScroll(map);
    }
  };

  // Decorate map with extra features when drawn
  $(DRTS).on('map_drawn.sabai', function(e, data) {
    if (!data.map || !data.map.getContainer().find('.drts-map-map').closest('.drts-location-entities-map-container').length) return;

    DRTS.Location.map(data.map);
    $(data.map.getContainerSelector() + '-view-filter-form').on('shown.bs.collapse hidden.bs.collapse', function() {
      DRTS.Location.stickyScroll(data.map, true);
      DRTS.Location.stickyScroll(data.map);
    });
  });

  DRTS.Location.stickyScroll = function(map, unstick, manual, useDefaultOffset) {
    if (!$.fn.stickyScroll) return;

    var mapContainer = map.getContainer().find('.drts-map-container');
    if (!mapContainer.data('sticky-default-offset')) {
      setTimeout(function() {
        // wait for all HTML to load
        mapContainer.data('sticky-default-offset', mapContainer.offset().top);
      }, 200);
    }
    var namespace = map.getContainer().attr('id') || 'stickyScroll';
    if (unstick) {
      mapContainer.stickyScroll({
        namespace: namespace,
        remove: true
      });
    } else {
      var topSpacing = void 0,
        mapContainerContainer = map.getContainer().find('.drts-location-map-container-container');
      if (mapContainerContainer.data('sticky-scroll-top')) {
        topSpacing = mapContainerContainer.data('sticky-scroll-top');
      } else {
        topSpacing = $('#wpadminbar').length > 0 && $('#wpadminbar').css('position') === 'fixed' ? $('#wpadminbar').outerHeight() : 0;
      }
      if (mapContainerContainer.length > 0) {}
      var options = {
        topSpacing: topSpacing,
        stopper: map.getContainer().find('.drts-location-sticky-scroll-stopper'),
        namespace: namespace,
        useDefaultOffset: useDefaultOffset,
        parent: '.drts-location-map-container-container',
        child: '.drts-map-map'
      };
      if ((typeof manual === 'undefined' || !manual) && typeof imagesLoaded !== 'undefined') {
        $('body').imagesLoaded(function() {
          mapContainer.stickyScroll(options);
        });
      } else {
        mapContainer.stickyScroll(options);
      }
    }
  };

  DRTS.Location.enableMapControls = function(map, options) {
    if (map.getContainer().find('.drts-location-map-controls').length) return;

    var controls = $('<div class="drts-location-map-controls"></div>');
    if (options.fullscreen) {
      var fullscreen_btn = $('<button type="button" class="' + DRTS.bsPrefix + 'd-none ' + DRTS.bsPrefix + 'd-sm-block ' + DRTS.bsPrefix + 'btn ' + DRTS.bsPrefix + 'btn-sm ' + DRTS.bsPrefix + 'btn-light drts-location-map-control" data-action="fullscreen" rel="sabaitooltip" data-placement="right"><i class="fas fa-expand"></i></button>');
      var exit_fullscreen_btn = $('<button type="button" class="' + DRTS.bsPrefix + 'd-none ' + DRTS.bsPrefix + 'd-sm-block ' + DRTS.bsPrefix + 'btn ' + DRTS.bsPrefix + 'btn-sm ' + DRTS.bsPrefix + 'btn-light drts-location-map-control" data-action="exit_fullscreen" rel="sabaitooltip" data-placement="right"><i class="fas fa-compress"></i></button>');
      controls.append(fullscreen_btn.prop('title', options.text_control_fullscreen || 'Full screen'));
      controls.append(exit_fullscreen_btn.prop('title', options.text_control_exit_fullscreen || 'Exit full screen'));
    }
    var search_this_ares_btn = $('<button type="button" class="' + DRTS.bsPrefix + 'btn ' + DRTS.bsPrefix + 'btn-sm ' + DRTS.bsPrefix + 'btn-light drts-location-map-control" style="display:none;" data-action="update" rel="sabaitooltip" data-placement="right"><i class="fas fa-sync"></i></button>');
    var search_my_location_btn = $('<button type="button" class="' + DRTS.bsPrefix + 'btn ' + DRTS.bsPrefix + 'btn-sm ' + DRTS.bsPrefix + 'btn-light drts-location-map-control" style="display:none;" data-action="geolocate" rel="sabaitooltip" data-placement="right"><i class="fas fa-location-arrow"></i></button>');
    controls.append(search_this_ares_btn.prop('title', options.text_control_search_this_area || 'Search this area'));
    controls.append(search_my_location_btn.prop('title', options.text_control_search_my_location || 'Search my location'));
    map.getContainer().find('.drts-map-container').prepend(controls);

    var filter_form = $('.drts-view-filter-form[data-entities-container="' + map.getContainerSelector() + '"]');
    var has_location_filter = false;
    if (filter_form.length > 0) {
      var location_filter = filter_form.find('.drts-view-filter-form-field-type-location-address');
      if (location_filter.length > 0) {
        has_location_filter = true;
        // Show custom control buttons
        if (location_filter.data('search-my-loc')) {
          if (navigator.geolocation && (document.location.protocol === 'https:' || document.location.hostname === 'localhost')) {
            controls.find('.drts-location-map-control[data-action="geolocate"]').show();
          }
        }
        if (location_filter.data('search-this-area')) {
          if (location_filter.data('search-this-area-drag')) {
            var updaterTimeout = void 0;
            map.getContainer().on('map_dragend.sabai', function(e, data) {
              updaterTimeout = setTimeout(function() {
                $(".drts-location-map-control[data-action=\'update\']", map.getContainerSelector()).click();
              }, 1000);
            });
            map.getContainer().on('map_mousedown.sabai', function(e, data) {
              if (updaterTimeout) clearTimeout(updaterTimeout);
            });
          } else {
            controls.find('.drts-location-map-control[data-action="update"]').show();
          }
        }
      }
    }
    var map_map = map.getContainer().find('.drts-map-map');
    var height = map_map.outerHeight(),
      width = map_map.outerWidth();
    // Handle click on custom control buttons
    controls.on('click', '.drts-location-map-control', function(e) {
      e.preventDefault();
      var $this = $(this);

      var resize_fullscreen_map = function resize_fullscreen_map(e) {
        var height = $(window).outerHeight() - e.data.offset;
        e.data.container.find('.drts-location-entities-container').outerHeight(height).end().find('.drts-map-map').outerHeight(height);
      };
      switch ($this.data('action')) {
        case 'update':
        case 'geolocate':
          if (!has_location_filter) break;

          // Need to re-fetch filter form since it gets disconnected with the current page after first submit 
          var _filter_form = $('.drts-view-filter-form[data-entities-container="' + map.getContainerSelector() + '"]');
          if (!_filter_form.length) return this;

          _filter_form.find('.drts-view-filter-form-field-type-location-address').each(function() {
            DRTS.View.removeFilter(_filter_form, $(this).data('view-filter-name'));
          });

          $this.find('> i').addClass('fa-spin');

          switch ($this.data('action')) {
            case 'update':
              var sw = map.getSouthWest(),
                ne = map.getNorthEast();
              _filter_form.find('.drts-location-text-viewport').val([sw[0], sw[1], ne[0], ne[1]].join(',')).end().find('.drts-location-text-center').val('').end().find('.drts-location-text-radius').val('').end().find('.drts-location-text-zoom').val(map.getZoom()).end().find('.drts-location-text-input').val(location_filter.data('search-this-area-label'));
              _filter_form.find('input[name^="search_location_location"]').val('').end().submit();
              break;

            case 'geolocate':
              navigator.geolocation.getCurrentPosition(function(pos) {
                var $radius = _filter_form.find('.drts-location-text-radius');
                _filter_form.find('.drts-location-text-viewport').val('').end().find('.drts-location-text-center').val(pos.coords.latitude + ',' + pos.coords.longitude).end().find('.drts-location-text-zoom').val(7).end().find('.drts-location-text-input').val(location_filter.data('search-my-loc-label'));
                if ($radius.length && !$radius.val()) {
                  $radius.val($radius.data('default-radius') > 0 ? $radius.data('default-radius') : 5);
                }
                _filter_form.find('input[name^="search_location_location"]').val('').end().submit();
              }, function(error) {
                $this.find('> i').removeClass('fa-spin');
                if (error.code !== 1) {
                  // 1 = user denied access
                  DRTS.flash(error.message + ' (' + error.code + ')', 'danger');
                }
              }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: Infinity
              });
              break;
          }
          break;
        case 'fullscreen':
        case 'exit_fullscreen':
          var _container = map.getContainer().find('.drts-view-entities-container'),
            is_map_view = _container.hasClass('drts-view-entities-container-map'),
            map_container = is_map_view ? null : _container.find('.drts-location-map-container-container'),
            entities_map_container = _container.find('.drts-location-entities-map-container');
          switch ($this.data('action')) {
            case 'fullscreen':
              height = map_map.outerHeight();
              width = map_map.outerWidth();
              var offset = $('#wpadminbar').length ? $('#wpadminbar').outerHeight(true) : 0;
              if (entities_map_container.length > 0 && entities_map_container.data('fullscreen-offset')) {
                offset = entities_map_container.data('fullscreen-offset');
              }
              var _height = $(window).outerHeight() - offset;
              _container.addClass('drts-location-map-full').css('top', offset).toggleClass('drts-location-map-full-no-filter', $(map.getContainerSelector() + '-view-filter-form').length === 0);
              if (!is_map_view) {
                _container.find('.drts-location-entities-container').outerHeight(_height).find('.drts-location-entities').before(_container.find('.drts-view-entities-header')).before(_container.find('.drts-view-entities-filter-form')).after(_container.find('.drts-view-entities-footer')).end().end();
                if (map_container.length) {
                  _container.find('.drts-location-entities-with-map').removeClass('drts-location-entities-with-map-top drts-location-entities-with-map-bottom').end().find('.drts-location-entities-container').removeClass(DRTS.bsPrefix + 'col-sm-' + (12 - map_container.data('span'))).addClass(DRTS.bsPrefix + 'col-sm-' + (12 - map_container.data('fullscreen-span')));
                  map_container.removeClass(DRTS.bsPrefix + 'col-sm-' + map_container.data('span')).addClass(DRTS.bsPrefix + 'col-sm-' + map_container.data('fullscreen-span'));
                  if (options.sticky) DRTS.Location.stickyScroll(map, true);
                }
              } else {
                //_height -= _container.find('.drts-view-entities-header').outerHeight(true);
                //_height -= _container.find('.drts-view-entities-footer').outerHeight(true);
              }
              _container.find('.drts-map-map').outerWidth('100%').outerHeight(_height);
              $(window).on('resize.sabai', {
                container: _container,
                offset: offset
              }, resize_fullscreen_map);
              $('body').css('overflow', 'hidden').addClass('_drts-location-map-full');
              map.onResized();
              $(DRTS).trigger("location_fullscreen.sabai", {
                container: map.getContainer()
              });
              break;
            case 'exit_fullscreen':
              _container.removeClass('drts-location-map-full drts-location-map-full-no-filter').find('.drts-map-map').outerWidth(width).outerHeight(height); // revert to original width/height
              if (!is_map_view) {
                _container.find('> .drts-view-entities').before(_container.find('.drts-view-entities-header')).before(_container.find('.drts-view-entities-filter-form')).after(_container.find('.drts-view-entities-footer')).end().find('.drts-location-entities-container').css('height', '100%');
                if (map_container.length) {
                  _container.find('.drts-location-entities-with-map').toggleClass('drts-location-entities-with-map-top', map_container.data('position') === 'top').toggleClass('drts-location-entities-with-map-bottom', map_container.data('position') === 'bottom').end().find('.drts-location-entities-container').removeClass(DRTS.bsPrefix + 'col-sm-' + (12 - map_container.data('fullscreen-span'))).addClass(DRTS.bsPrefix + 'col-sm-' + (12 - map_container.data('span')));
                  map_container.removeClass(DRTS.bsPrefix + 'col-sm-' + map_container.data('fullscreen-span')).addClass(DRTS.bsPrefix + 'col-sm-' + map_container.data('span'));
                  if (options.sticky) {
                    DRTS.scrollTo(_container, null, null, function() {
                      DRTS.Location.stickyScroll(map, false, true);
                    });
                  }
                }
              }
              $(window).off('resize.sabai');
              $('body').css('overflow', '').removeClass('_drts-location-map-full');
              map.onResized();
              $(DRTS).trigger("location_exit_fullscreen.sabai", {
                container: map.getContainer()
              });
              break;
          }
          map.draw();
          break;
      }
    });
    // Clear field values when filter removed
    $(window).on('entity_reset_form_field.sabai', function(e, filterName) {
      if (filterName !== 'filter_location_address') return;

      var _filter_form = $('.drts-view-filter-form[data-entities-container="' + map.getContainerSelector() + '"]');
      if (!_filter_form.length) return this;
      _filter_form.find('.drts-location-text-viewport').val('').end().find('.drts-location-text-center').val('').end().find('.drts-location-text-radius').val('').end().find('.drts-location-text-zoom').val('').end().find('.drts-location-text-input').val('');
    });

    return controls;
  };

  DRTS.Location.scrollToItem = function(item, callback, isStickyMap) {
    var container = item.closest('.drts-view-entities-container');
    if (!container.length) return;

    var is_map_view = container.hasClass('drts-view-entities-container-map');
    if (is_map_view) return; // this should not happen, but just in case

    var is_full_map = container.hasClass('drts-location-map-full');
    if (is_full_map) {
      var _container = container.find('.drts-location-entities-container');
      if (_container.length) {
        _container.animate({
          scrollTop: _container.scrollTop() - _container.offset().top + item.offset().top
        }, 500, 'swing', callback);
      }
    } else {
      var offset,
        map_container = container.find('.drts-location-map-container-container');
      if (map_container.data('scroll-offset')) {
        offset = map_container.data('scroll-offset');
      } else {
        offset = $('#wpadminbar').length > 0 && $('#wpadminbar').css('position') === 'fixed' ? $('#wpadminbar').outerHeight() : 0;
        offset += 10;
      }
      if (isStickyMap && DRTS.getScreenSize() !== 'xs') {
        if (map_container.data('position') === 'top') {
          offset += map_container.find('.drts-map-map').outerHeight();
        }
      }
      $('html,body').animate({
        scrollTop: item.offset().top - offset
      }, 500, 'swing', callback);
    }
  };
})(jQuery);