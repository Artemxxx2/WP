'use strict';

(function($) {
  DRTS.Location = DRTS.Location || {};
  DRTS.Location.textfield = DRTS.Location.textfield || function(selector) {
    var $field = $(selector);
    if (!$field.length) return;

    var $input = $field.find('.drts-location-text-input'),
      $center = $field.find('.drts-location-text-center'),
      $viewport = $field.find('.drts-location-text-viewport'),
      $zoom = $field.find('.drts-location-text-zoom'),
      $radius = $field.find('.drts-location-text-radius'),
      radius_is_slider = $radius.hasClass('drts-location-text-radius-slider'),
      resetRadius = function resetRadius() {
        if (radius_is_slider) {
          $radius.data('ionRangeSlider').reset();
        } else {
          $radius.val('');
        }
      },
      is_geolocated;

    if ($input.length) {
      var options = {
          suggest_place: typeof $field.data('suggest-place') === 'undefined' ? true : $field.data('suggest-place') || false,
          suggest_place_header: $field.data('suggest-place-header') || '',
          suggest_place_footer: $field.data('suggest-place-footer') || '',
          suggest_place_icon: $field.data('suggest-place-icon') || 'fas fa-map-pin',
          suggest_place_country: $field.data('suggest-place-country') || null,
          suggest_place_minlength: $field.data('suggest-place-minlength') || 1,
          suggest_location: $field.data('suggest-location'),
          suggest_location_header: $field.data('suggest-location-header') || '',
          suggest_location_icon: $field.data('suggest-location-icon') || '',
          suggest_location_url: $field.data('suggest-location-url') || null,
          suggest_location_count: $field.data('suggest-location-count') || null,
          suggest_location_parents: $field.data('suggest-location-parents') || false,
          geolocation: typeof $field.data('geolocation') === 'undefined' ? true : $field.data('geolocation') || false
        },
        $term_id = $field.find('.drts-location-text-term-id'),
        $taxonomy = $field.find('.drts-location-text-taxonomy'),
        $clear = $field.find('.drts-clear').css('visibility', $input.val().length > 0 ? 'visible' : 'hidden'),
        datasets = [];
      //if ($slider.length) {
      //    var radius_timeout = null;
      //    $input.focus(function(){
      //        if (!$term_id.val()) {                
      //            $slider.slideDown('fast');
      //        } else {
      //            $slider.slideUp('fast');
      //        }
      //        if (radius_timeout !== null) {
      //            clearTimeout(radius_timeout);
      //        }
      //        radius_timeout = setTimeout(function(){
      //            if ($input.is(':focus')) {
      //                $input.trigger('focus');
      //            } else {
      //                $slider.slideUp('fast');
      //            }
      //        }, 3000);
      //    });
      //    $slider.on('slidestart slidechange', function(e) {
      //        if (radius_timeout !== null) {
      //            clearTimeout(radius_timeout);
      //            radius_timeout = setTimeout(function(){$slider.slideUp('fast');}, e.type === 'slidestart' ? 10000 : 5000);
      //        }
      //    });
      //}

      if (options.suggest_location && options.suggest_location_url) {
        var taxonomy_templates = {},
          taxonomy_terms = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.obj.whitespace('title', 'pt'),
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            prefetch: {
              url: options.suggest_location_url
            }
          });
        if (options.suggest_location_header) {
          taxonomy_templates.header = '<h4>' + options.suggest_location_header + '</h4>';
        }
        taxonomy_templates.suggestion = function(item) {
          var title = item.title;
          if (options.suggest_location_count) {
            title += ' (' + (item.count || '0') + ')';
          }
          if (options.suggest_location_parents && item.pt) {
            title = item.pt.join(' -> ') + ' -> ' + title;
          }
          var div = $('<div/>').html($('<span />').text(title)),
            i;
          if (item.icon_src) {
            i = $('<img/>').attr('class', 'drts-icon drts-icon-sm').attr('src', item.icon_src);
          } else if (options.suggest_location_icon) {
            i = $('<i/>').attr('class', 'drts-icon drts-icon-sm ' + options.suggest_location_icon);
          }
          if (i) div.prepend(i);

          return div[0].outerHTML;
        };
        datasets.push({
          name: options.suggest_location,
          source: function source(q, sync, async) {
            if (q.length > 0) {
              taxonomy_terms.search(q, sync, async);
            }
          },
          display: 'title',
          templates: taxonomy_templates,
          limit: $field.data('suggest-location-num') || 100
        });
      }

      if (options.suggest_place) {
        var location_templates = {};
        if (options.suggest_place_header) {
          location_templates.header = $('<h4/>').text(options.suggest_place_header)[0].outerHTML;
        }
        if (options.suggest_place_icon) {
          location_templates.suggestion = function(item) {
            var div = $('<div/>');
            div.append($('<i/>').attr('class', 'drts-icon drts-icon-sm ' + options.suggest_place_icon)).append($('<span/>').text(item.text));
            return div[0].outerHTML;
          };
        }
        if (options.suggest_place_footer) {
          location_templates.footer = $('<div/>').attr('class', 'tt-footer').css({
            'text-align': 'right',
            margin: '0 5px 3px'
          }).html(options.suggest_place_footer)[0].outerHTML;
        }

        datasets.push({
          name: 'place',
          display: 'text',
          templates: location_templates,
          source: function source(q, sync, async) {
            if (q.length < options.suggest_place_minlength) return;

            DRTS.Location.api.getSuggestions(q, async);
          }
        });
      }

      if (options.geolocation && navigator.geolocation && (document.location.protocol === 'https:' || document.location.hostname === 'localhost')) {
        var geolocate = function geolocate() {
          var success = false;
          $input.addClass('drts-ajax-loading');
          navigator.geolocation.getCurrentPosition(function(pos) {
            $input.removeClass('drts-ajax-loading');
            success = is_geolocated = true;
            DRTS.Location.api.reverseGeocode([pos.coords.latitude, pos.coords.longitude], function(results) {
              $input.val(results.address).typeahead('val', results.address);
              $taxonomy.val('');
              $term_id.val('');
              $viewport.val('');
              $zoom.val('');
              if (!radius_is_slider && (!$radius.val() || $radius.val() < 5)) {
                $radius.val($radius.data('default-radius') > 5 ? $radius.data('default-radius') : 5);
              }
              $clear.css('visibility', $input.val().length > 0 ? 'visible' : 'hidden');
              var center = pos.coords.latitude + ',' + pos.coords.longitude;
              if (center !== $center.val()) {
                $center.val(center);
                $input.trigger('change');
              }
            }, function(error) {
              DRTS.flash(error.message, 'danger');
            });
          }, function(error) {
            $input.removeClass('drts-ajax-loading');
            if (!success) {
              if (error.code !== 1) {
                // 1 = user denied access
                DRTS.flash(error.message, 'danger');
              }
            }
            console.log(error.message + ' (' + error.code + ')');
          }, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: Infinity
          });
        };
        datasets.push({
          name: 'geolocate',
          source: function source(q, sync) {
            if (is_geolocated) return;
            sync([{
              content: ''
            }]);
          },
          display: 'content',
          templates: {
            suggestion: function suggestion() {
              var i = $('<i/>').attr('class', 'drts-icon drts-icon-sm fas fa-location-arrow');
              return $('<div/>').html($('<span/>').text($field.data('geolocation-text') || 'Current location')).prepend(i)[0].outerHTML;
            }
          }
        });
      } else {
        $field.addClass('drts-location-no-geolocation');
      }

      if (datasets.length) {
        $input.typeahead({
          highlight: true,
          minLength: 0 // needs to be 0 to show taxonomy options by default
        }, datasets);
        $input.on('typeahead:select', function(e, item, name) {
          $center.val('');
          $viewport.val('');
          $zoom.val('');
          is_geolocated = false;
          if (!radius_is_slider) resetRadius();
          if (name === options.suggest_location) {
            $taxonomy.val(options.suggest_location);
            $term_id.val(item.id);
          } else if (name === 'place') {
            $taxonomy.val('');
            $term_id.val('');
            if (!item.latlng) {
              var callback = function callback(latlng, viewport) {
                  $center.val(latlng.join(','));
                  if (viewport) {
                    $viewport.val(viewport.join(','));
                  }
                },
                errorCallback = function errorCallback(err) {
                  console.log(err);
                };
              if (DRTS.Location.api.geocodeSuggestion && $field.data('suggest-place-geocode')) {
                DRTS.Location.api.geocodeSuggestion(item, callback, errorCallback);
              } else {
                DRTS.Location.api.geocode(item.text, callback, errorCallback);
              }
            } else {
              $center.val(item.latlng[0] + ',' + item.latlng[1]);
            }
          } else if (name === 'geolocate') {
            geolocate();
          }
          //$input.blur();
          $clear.css('visibility', $input.val().length > 0 ? 'visible' : 'hidden');
        }).on('keyup', function(e) {
          if (e.keyCode !== 13 && e.keyCode !== 27 && e.keyCode !== 32) {
            $taxonomy.val('');
            $term_id.val('');
            $center.val('');
            $viewport.val('');
            $zoom.val('');
            if (!radius_is_slider) resetRadius();
            $clear.css('visibility', $input.val().length > 0 ? 'visible' : 'hidden');
          }
          is_geolocated = false;
        }).on('typeahead:open', function() {
          $input.closest('.twitter-typeahead').addClass('twitter-typeahead-open');
        }).on('typeahead:close', function() {
          $input.closest('.twitter-typeahead').removeClass('twitter-typeahead-open');
        });

        $clear.click(function() {
          $taxonomy.val('');
          $term_id.val('');
          $center.val('');
          $viewport.val('');
          $zoom.val('');
          if (!radius_is_slider) resetRadius();
          $clear.css('visibility', 'hidden');
          $input.typeahead('val', '').focus().trigger('change');
          is_geolocated = false;
          $input.typeahead('open');
        });
      } else {
        $input.bind('keyup', function(e) {
          if (e.keyCode !== 13 && e.keyCode !== 27 && e.keyCode !== 32) {
            $clear.css('visibility', $input.val().length > 0 ? 'visible' : 'hidden');
          }
        });
        $clear.click(function() {
          $clear.css('visibility', 'hidden');
          $input.val('').focus().trigger('change');
        });
      }

      if (radius_is_slider) {
        $radius.on('change', function() {
          if ($zoom.val()) return; // current map view is being requested, so keep viewport and ignore radius

          $viewport.val('');
        });
      }
    }

    $field.on('entity_reset_form_field.sabai', function() {
      if ($input.length) {
        if (datasets.length) {
          $input.typeahead('val', '');
        } else {
          $input.val('');
        }
        $taxonomy.val('');
        $term_id.val('');
      }
      $center.val('');
      $viewport.val('');
      $zoom.val('');
      resetRadius();
    });
  };
})(jQuery);