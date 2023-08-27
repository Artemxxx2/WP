'use strict';

(function($) {
  var $sections = $('.drts-wordpress-homepage-section-full-width');

  if (!$sections.length || !$('.site-main').length) return;

  var isRtl = $sections.first().find('.drts').hasClass('drts-rtl');

  /**
   * Make sections full width and centrally aligned.
   */
  function setFullWidth() {
    var offset = $('.site-main').offset();

    $sections.css('width', $(window).width()).css(isRtl ? 'margin-right' : 'margin-left', -offset.left);
  }

  /**
   * On document ready
   * Set photo slider dimensions / layout
   */
  $(function() {
    setFullWidth();
  });

  /**
   * On window resize
   * Set photoslider dimensions / layout
   */
  $(window).resize(function() {
    setFullWidth();
  });
})(jQuery);