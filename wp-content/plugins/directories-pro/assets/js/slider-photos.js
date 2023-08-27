'use strict';

(function($) {
  DRTS.Slider = DRTS.Slider || {};
  DRTS.Slider.photos = function(selector) {
    var $slider = $(selector);
    if (!$slider.length) return;

    var options = $slider.data('slick-options'),
      $thumbs,
      resizeIframes = function resizeIframes(slider) {
        var $slider = $(slider),
          sliderWidth = $slider.width(),
          videoHeight = sliderWidth * 9 / 16;
        $slider.find('iframe').each(function() {
          var $this = $(this),
            wrapper = $this.width(sliderWidth).height(videoHeight).parent();
          // Remove div.fluid-width-video-wrapper added by jquery.fitvids.js
          if (wrapper.hasClass('fluid-width-video-wrapper')) {
            $this.unwrap();
            wrapper = $this.parent();
          }
          wrapper.height(videoHeight);
        });
      };
    if (options.asNavFor) {
      $thumbs = $(options.asNavFor);
    }
    $slider.on('init', function() {
      var thumbsOptions;
      if ($thumbs && $thumbs.length) {
        thumbsOptions = $thumbs.data('slick-options');
        if (thumbsOptions) {
          $thumbs.slick_(thumbsOptions);
        }
        // Fix for some reason main slider not sliding to the selected image
        // when coming back to the first thumbnail slide 
        $thumbs.on('click', '.slick-slide', function(event) {
          var i = $(this).data('slick-index');
          if (!$slider.find('[data-slide-index="' + i + '"]').length) {
            $slider.slick_('slickGoTo', 0);
          }
        });
      }
      // Adjust iframe dimensions to fit the slider
      resizeIframes($slider);

      $slider.parent('.drts-slider-photos').css('opacity', 1);
    }).on('beforeChange', function(event, slick) {
      // Pause current video
      var currentSlide = $slider.find('.slick-current'),
        type = currentSlide.data('type'),
        msg;
      switch (type) {
        case 'youtube':
        case 'vimeo':
          msg = type === 'youtube' ? {
            event: 'command',
            func: 'pauseVideo'
          } : {
            method: 'pause',
            value: 1
          };
          currentSlide.find('iframe').get(0).contentWindow.postMessage(JSON.stringify(msg), '*');
          break;
        default:
      }
    }).slick_(options);

    var events = ['resize'];
    if ($slider.closest('.drts-display-element[data-name="tabs"]').length) {
      events.push('shown.bs.tab', 'shown.bs.collapse');
    }
    $(window).on(events.join(' '), function() {
      $slider.slick_('resize');
      if ($thumbs && $thumbs.length) {
        $thumbs.slick_('resize');
      }
      resizeIframes($slider);
    });
  };
})(jQuery);