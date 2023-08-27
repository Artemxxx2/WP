'use strict';

(function($) {
  DRTS.FrontendSubmit = DRTS.Voting || {};
  DRTS.FrontendSubmit.verifyAccountWP = function(options) {
    var defaults = {
      verifyAccountUrl: null,
      verifyAccountText: null,
      unverifyAccountUrl: null,
      unverifyAccountText: null,
      resendVerifyAccountKeyUrl: null,
      resendVerifyAccountKeyText: null
    };
    options = $.extend({}, defaults, options);

    var list = $('#the-list');

    var hasTouch = 'ontouchstart' in document.documentElement;
    if (!hasTouch) {
      var container = $('<div class="drts"></div>').appendTo($('body'));
      $('[rel*="sabaitooltip"]', list).each(function() {
        $(this).sabaiTooltip({
          container: container
        });
      });
    }

    if (options.verifyAccountUrl) {
      list.on('click', '.drts_frontendsubmit_verify_account a', function() {
        var $this = $(this);
        DRTS.ajax({
          type: 'post',
          url: options.verifyAccountUrl,
          data: {
            'id': $this.data('user-id')
          },
          trigger: $this,
          onSuccess: function onSuccess(result, target, trigger) {
            if (result.messages) DRTS.flash(result.messages, 'success');

            // Update icon
            trigger.closest('tr').find('td.drts_frontendsubmit_verify i').fadeOut('fast', function() {
              var $i = $(this);
              $i.removeClass('fa-times-circle').addClass('fa-check-circle').parent().removeClass(DRTS.bsPrefix + 'text-danger').addClass(DRTS.bsPrefix + 'text-success').attr('data-original-title', '');
              $i.fadeIn('slow');
            });
            // Update row actions
            trigger.closest('.row-actions').append('<span class="drts_frontendsubmit_unverify_account"><a href="#" data-user-id="' + $this.data('user-id') + '"></a></span>').find('.drts_frontendsubmit_unverify_account a').text(options.unverifyAccountText).end().find('.drts_frontendsubmit_verify_account, .drts_frontendsubmit_resend_verify_account_key').remove();
          },
          onError: function onError(result) {
            if (result.messages) DRTS.flash(result.messages, 'danger');
          }
        });
        return false;
      });
    }
    if (options.resendVerifyAccountKeyUrl) {
      list.on('click', '.drts_frontendsubmit_resend_verify_account_key a', function() {
        var $this = $(this);
        DRTS.ajax({
          type: 'post',
          url: options.resendVerifyAccountKeyUrl,
          data: {
            'id': $this.data('user-id')
          },
          trigger: $this,
          onSuccess: function onSuccess(result, target, trigger) {
            if (result.messages) DRTS.flash(result.messages, 'success');

            // Update icon
            trigger.closest('tr').find('td.drts_frontendsubmit_verify span span').attr('data-original-title', result.title);
          },
          onError: function onError(result) {
            if (result.messages) DRTS.flash(result.messages, 'danger');
          }
        });
        return false;
      });
    }
    if (options.unverifyAccountUrl) {
      list.on('click', '.drts_frontendsubmit_unverify_account a', function() {
        var $this = $(this);
        DRTS.ajax({
          type: 'get',
          container: '#drts-modal',
          modalSize: 'lg',
          url: options.unverifyAccountUrl,
          data: {
            'id': $this.data('user-id')
          },
          trigger: $this
        });
        return false;
      });
    }
    $(DRTS).on('drts_frontendsubmit_unverify_account', function(e, data) {
      var trigger = $('tr#user-' + data.id).find('.drts_frontendsubmit_unverify_account');
      // Update icon
      trigger.closest('tr').find('td.drts_frontendsubmit_verify i').fadeOut('fast', function() {
        var $i = $(this);
        $i.removeClass('fa-check-circle').addClass('fa-times-circle').parent().removeClass(DRTS.bsPrefix + 'text-success').addClass(DRTS.bsPrefix + 'text-danger').attr('data-original-title', data.title);
        $i.fadeIn('slow');
      });
      // Update row actions
      trigger.closest('.row-actions').append('<span class="drts_frontendsubmit_verify_account"><a href="#" data-user-id="' + data.id + '"></a> | </span>' + '<span class="drts_frontendsubmit_resend_verify_account_key"><a href="#" data-user-id="' + data.id + '"></a></span>').find('.drts_frontendsubmit_verify_account a').text(options.verifyAccountText).end().find('.drts_frontendsubmit_resend_verify_account_key a').text(options.resendVerifyAccountKeyText).end().find('.drts_frontendsubmit_unverify_account').remove();
    });
  };
})(jQuery);