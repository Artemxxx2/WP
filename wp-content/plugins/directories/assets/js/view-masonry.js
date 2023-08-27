'use strict';

(function($) {
  DRTS.View = DRTS.View || {};
  DRTS.View.masonry = DRTS.View.masonry || function(selector, columns) {
    var $container = $(selector),
      _masonry = function _masonry() {
        var container_parent = $container.closest(DRTS.bsPrefix + 'row'),
          parent_margin = 0,
          border = 0,
          containerWidth,
          columnWidth;
        if (container_parent.length) {
          parent_margin = (parseInt(container_parent.css('margin-left'), 10) || 0) + (parseInt(container_parent.css('margin-right'), 10) || 0), border = (parseInt($container.css('border-left'), 10) || 0) + (parseInt($container.css('border-right'), 10) || 0);
        }
        containerWidth = $container.outerWidth() + parent_margin - border - 1;
        if (containerWidth > 768) {
          columnWidth = Math.floor((containerWidth - (columns - 1) * 20) / columns);
        } else if (containerWidth > 480) {
          columnWidth = Math.floor((containerWidth - 20) / 2);
        } else {
          columnWidth = containerWidth;
        }
        $container.find('> div').width(columnWidth).end().masonry({
          columnWidth: columnWidth,
          itemSelector: '.drts-view-entity-container',
          gutter: 20,
          isRTL: DRTS.isRTL
        }).css('display', 'block');
      },
      masonry = function masonry() {
        _masonry();
      };
    $container.imagesLoaded(function() {
      masonry();
    });
    $(DRTS).on('location_fullscreen.sabai location_exit_fullscreen.sabai', function(e, data) {
      if (data.container.find('.drts-view-entities-masonry-container').length) {
        masonry();
      }
    });
  };
})(jQuery);