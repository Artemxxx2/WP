'use strict';

(function($) {
  DRTS.Slider = DRTS.Slider || {};
  DRTS.Slider.carousel = function(selector) {
    var $container = $(selector);
    if (!$container.length) return;
    var $slider = $container.find('.drts-slider-carousel-slider');
    if (!$slider.length) return;

    var options = $container.data('slick-options') || {};
    var $arrows = $container.find('.drts-slider-carousel-arrows');
    if ($arrows.length) {
      options.appendArrows = $arrows;
    }

    $slider.on('setPosition', function(event, slick) {
      setTimeout(function() {
        $slider.find('.drts-slider-carousel-item').css('height', slick.$slideTrack.height() + 'px').end().parent('.drts-slider-carousel').css('opacity', 1);
      }, 100);
    }).slick_(options);

    $(window).resize(function() {
      $slider.slick_('resize');
    });
  };
})(jQuery);