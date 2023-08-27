'use strict';

function _toConsumableArray(arr) {
  if (Array.isArray(arr)) {
    for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) {
      arr2[i] = arr[i];
    }
    return arr2;
  } else {
    return Array.from(arr);
  }
}

(function($) {
  DRTS.Voting = DRTS.Voting || {};
  DRTS.Voting.onSendData = function(type, trigger, data) {
    // Temporarily store original label
    if (trigger.data('success-label')) {
      var label = trigger.find('.drts-voting-vote-label');
      if (label.length) {
        // Button
        trigger.data('original-label', label.text());
        label.text(trigger.data('success-label'));
      } else {
        // Tooltip
        trigger.data('original-label', trigger.attr('data-original-title'));
        trigger.attr('data-original-title', trigger.data('success-label'));
      }
    }
    // Toggle icon
    trigger.find('i').attr('class', trigger.data(trigger.hasClass(DRTS.bsPrefix + 'active') ? 'voting-icon' : 'voting-icon-active'));
    // Notify
    $(DRTS).trigger('voting_vote_entity_send_data.sabai', {
      type: type,
      trigger: trigger,
      data: data
    });
  };
  DRTS.Voting.onSuccess = function(type, trigger, result) {
    if (trigger.data('original-label')) {
      // Update success label
      trigger.data('success-label', trigger.data('original-label')).removeData('original-label');
    }
    // Toggle active status
    trigger.closest('.drts-display-element[data-name="button"], .drts-display-element-buttons').find('button[data-voting-type="' + type + '"]').each(function() {
      var $this = $(this),
        active = result.value == $this.data('active-value');
      $this.toggleClass(DRTS.bsPrefix + 'active', active).find('i').attr('class', $this.data(active ? 'voting-icon-active' : 'voting-icon'));
      if ($this.find('.drts-voting-vote-num').length) {
        $this.find('.drts-voting-vote-num').text($this.data('active-value') < 0 ? result.num_down : result.num);
      }
    });
    // Notify
    $(DRTS).trigger('voting_vote_entity_success.sabai', {
      type: type,
      trigger: trigger,
      result: result
    });
  };
  DRTS.Voting.onError = function(type, trigger, error) {
    // Restore original label
    if (trigger.data('original-label')) {
      var label = trigger.find('.drts-voting-vote-label');
      if (label.length) {
        // Button
        label.text(trigger.data('original-label'));
      } else {
        // Tooltip
        trigger.attr('data-original-title', trigger.data('original-label'));
      }
      trigger.removeData('original-label');
    }
    // Set icon
    trigger.find('i').attr('class', trigger.data(trigger.hasClass(DRTS.bsPrefix + 'active') ? 'voting-icon-active' : 'voting-icon'));
    // Notify
    $(DRTS).trigger('voting_vote_entity_error.sabai', {
      type: type,
      trigger: trigger,
      error: error
    });
  };
  DRTS.Voting.button = function(selector) {
    var btn = $(selector);
    if (!btn.length || !btn.data('voting-type') || !btn.data('entity-type') || !btn.data('entity-id')) return;

    var name = 'drts-voting-' + btn.data('voting-type') + '-' + btn.data('entity-type') + '-' + DRTS.cookieHash,
      id = String(btn.data('entity-id')),
      expires = btn.data('voting-expires-days') ? btn.data('voting-expires-days') : 2000,
      isVoted = function isVoted(id, toggle) {
        var ids = $.cookie(name),
          idx,
          exists,
          result;
        ids = ids ? ids.split('/') : [];
        idx = ids.indexOf(id);
        exists = idx !== -1;
        if (toggle) {
          if (exists === false) {
            ids.push(id);
            if (ids.length >= 100) {
              ids = ids.slice(ids.length - 100, ids.length);
            }
          } else {
            ids.splice(idx, 1);
          }
          DRTS.setCookie(name, [].concat(_toConsumableArray(new Set(ids))).join('/'), expires);
          exists = !exists;
        }
        return exists;
      },
      updateBtn = function updateBtn(btn, active) {
        var label, icon;
        if (active) {
          btn.addClass(DRTS.bsPrefix + 'active');
          label = btn.data('label-active');
          icon = btn.data('voting-icon-active');
        } else {
          btn.removeClass(DRTS.bsPrefix + 'active');
          label = btn.data('label');
          icon = btn.data('voting-icon');
        }
        btn.find('.drts-voting-vote-label').text(label).end().find('i').attr('class', icon);
      };
    updateBtn(btn, isVoted(id));
    btn.on('click', function() {
      var voted = isVoted(id, true);
      updateBtn(btn, voted);
      // Notify
      $(DRTS).trigger('voting_vote_entity_button_clicked.sabai', {
        type: btn.data('voting-type'),
        button: btn,
        voted: voted
      });
    });
  };

  $(DRTS).on('drts_init.sabai', function(e, data) {
    $('.drts-voting-button[data-voting-guest="1"]', data.context).each(function() {
      DRTS.Voting.button($(this));
    });
  });
})(jQuery);