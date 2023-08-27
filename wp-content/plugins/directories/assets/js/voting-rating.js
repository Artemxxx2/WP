'use strict';

(function($) {
  DRTS.Voting = DRTS.Voting || {};
  DRTS.Voting.rating = DRTS.Voting.rating || function(selector, options) {
    var $ele = $(selector);
    if (!$ele.length) return;

    if ($ele.data('vote-rating-initialized')) return;

    $ele.data('vote-rating-initialized', true);

    var $select = $ele.find('select');
    if (!$select.length) return;

    var val = $select.val();
    options = options || {};
    if ($ele.data('vote-url')) {
      options.url = $ele.data('vote-url');
    }
    if ($ele.data('vote-rating')) {
      options.initialRating = $ele.data('vote-rating');
    }
    var o = $.extend({}, DRTS.Voting.rating.defaults, options);
    if (o.url) {
      o.onSelect = function(value, text, event) {
        if (!event) return; // set by the 'set' method, abort to prevent loop

        $select.barrating('readonly', true);
        $ele.fadeTo(600, 0.4, function() {
          $.ajax({
            url: o.url,
            type: 'post',
            data: 'value=' + (value ? value : val),
            complete: function complete(xhr) {
              try {
                var result = JSON.parse(xhr.responseText.replace(/<!--[\s\S]*?-->/g, ''));
              } catch (e) {
                console.log(e.toString());
                return;
              }
              if (xhr.status == 278 || xhr.status == 200) {
                //success
                $ele.fadeTo(600, 0.1, function() {
                  var rating = typeof result.average !== 'undefined' && result.average ? parseFloat(result.average) : 0;
                  var count = typeof result.count !== 'undefined' ? result.count ? parseInt(result.count) : 0 : 1;
                  $ele.parent().find('.drts-voting-rating-average').text(rating.toFixed(2)).end().find('.drts-voting-rating-count').text(count);
                  $select.val(result.level).barrating('set', result.level);
                  $ele.fadeTo(600, 1);
                });
              } else {
                //failure
                $ele.fadeTo('fast', 1);
                if (result.messages) {
                  DRTS.flash(result.messages, 'danger');
                } else {
                  console.log(result);
                }
              }
              // Re-enable rating after some itme
              //setTimeout(function(){$select.barrating('readonly', false);}, 2000);
            }
          });
        });
      };
    }
    $select.barrating(o);
  };

  DRTS.Voting.rating.defaults = {
    theme: 'fontawesome-stars-o',
    url: location.href,
    showSelectedRating: false,
    showValues: false,
    allowEmpty: true
  };

  $('.drts-voting-rating-select').each(function() {
    DRTS.Voting.rating($(this));
  });
  $(DRTS).on('drts_init.sabai', function(e, data) {
    $('.drts-voting-rating-select', data.context).each(function() {
      DRTS.Voting.rating($(this));
    });
  });
})(jQuery);