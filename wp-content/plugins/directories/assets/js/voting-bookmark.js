'use strict';

(function($) {
  $(DRTS).on('voting_vote_entity_send_data.sabai', function(e, data) {
    console.log(data.type);
    if (data.type !== 'bookmark' || !data.trigger.data('view-container')) return;

    var $view = $('#' + data.trigger.data('view-container') + ' .drts-view-entities-container-list');
    if (!$view.length) return;

    data.data.view_name = $view.data('view-name');
    data.data.view_container = data.trigger.data('view-container');
  });
  $(DRTS).on('voting_vote_entity_success.sabai', function(e, data) {
    console.log(data.type);
    if (data.type !== 'bookmark' || !data.trigger.data('view-container') || !data.result.view_html) return;

    var $view = $('#' + data.trigger.data('view-container') + ' .drts-view-entities-container-list');
    if (!$view.length) return;

    $view.html(data.result.view_html);
  });
  $(DRTS).on('voting_vote_entity_button_clicked.sabai', function(e, data) {
    if (data.type !== 'bookmark' || !data.button.data('view-container')) return;

    var $view = $('#' + data.button.data('view-container') + ' .drts-view-entities-container-list');
    if (!$view.length) return;

    console.log($view.data('view-url'));
    DRTS.ajax({
      type: 'get',
      container: '#' + data.button.data('view-container'),
      target: '.drts-view-entities-container-list',
      url: $view.data('view-url'),
      pushState: false,
      scroll: false,
      loadingImage: false
    });
  });
})(jQuery);