'use strict';

(function($) {
  $(DRTS).on('loaded.sabai', function(e, data) {
    if ('function' === typeof WPPlaylistView) {
      $('.wp-playlist:not(:has(.mejs-container))', data.container).each(function() {
        new WPPlaylistView({
          el: this
        });
      });
    }
  });
})(jQuery);