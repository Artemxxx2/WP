'use strict';

/* jSticky Plugin
 * =============
 * Author: Andrew Henderson (@AndrewHenderson)
 * Date: 9/7/2012
 * Update: 02/14/2013
 * Website: http://github.com/andrewhenderson/jsticky/
 * Description: A jQuery plugin that keeps select DOM
 * element(s) in view while scrolling the page.
 */

;
(function($) {

  $.fn.stickyScroll = function(options) {

    var defaults = {
        topSpacing: 0, // No spacing by default
        stopper: '.sticky-stopper', // Default stopper class, also accepts number value
        namespace: 'stickyScroll',
        useDefaultOffset: false,
        remove: false,
        parent: null,
        child: null
      },
      settings = $.extend({}, defaults, options),
      // Accepts custom stopper id or class
      scrollEvent = 'scroll.' + settings.namespace,
      resizeEvent = 'resize.' + settings.namespace,
      exitFullScreenEvent = 'location_exit_fullscreen.sabai.' + settings.namespace,
      resetCss = {
        position: 'static',
        top: null,
        left: null
      };

    if (settings.remove) {
      $(window).off(resizeEvent + ' ' + scrollEvent);
      $(this).css(resetCss);
      return this;
    }

    var hasStopper = $(settings.stopper).length > 0; // True or false

    return this.each(function() {

      var $this = $(this),
        thisHeight = $this.outerHeight(),
        thisWidth = $this.width('auto').outerWidth(),
        topSpacing = settings.topSpacing,
        topOffset = settings.useDefaultOffset && $this.data('sticky-default-offset') ? $this.data('sticky-default-offset') : $this.offset().top,
        pushPoint = topOffset - topSpacing,
        // Point at which the sticky element starts pushing
        placeholder = $('<div></div>').width(thisWidth).addClass('sticky-placeholder'),
        // Cache a clone sticky element
        stopper = settings.stopper,
        $window = $(window),
        hasParent = settings.parent && $(settings.parent).length > 0,
        hasChild = settings.child && $(settings.child).length > 0;

      function stickyScroll() {

        var windowTop = $window.scrollTop(); // Check window's scroll position

        if (hasStopper) {
          var $stopper = $(stopper),
            stopperTop = $stopper.offset().top,
            stopperMarginTop = parseInt($stopper.css('margin-top'), 10),
            stopPoint = stopperTop - thisHeight - topSpacing - stopperMarginTop;
        }

        if (pushPoint < windowTop) {
          // Create a placeholder for sticky element to occupy vertical real estate
          $this.width(thisWidth).find('.sticky-placeholder').remove().end().append(placeholder.width(thisWidth)).css({
            position: 'fixed',
            top: topSpacing
          });

          if (hasStopper) {
            if (stopPoint < windowTop) {
              var diff = stopPoint - windowTop + topSpacing;
              $this.css({
                top: diff
              });
            }
          }
        } else {
          $this.css(resetCss).find('.sticky-placeholder').remove();
        }
      };

      function onResize() {
        if (hasParent) thisWidth = $this.closest(settings.parent).outerWidth();
        $this.width(thisWidth);
        if (hasChild) $this.find(settings.child).width(thisWidth);

        $window.off(scrollEvent);
        if (DRTS.getScreenSize() !== 'xs') {
          $window.on(scrollEvent, stickyScroll);
        } else {
          $this.css(resetCss);
        }
      }

      $window.off(scrollEvent);
      if (DRTS.getScreenSize() !== 'xs') $window.on(scrollEvent, stickyScroll);
      $window.off(resizeEvent).on(resizeEvent, onResize);
      $(DRTS).off(exitFullScreenEvent).on(exitFullScreenEvent, onResize);
    });
  };
})(jQuery);