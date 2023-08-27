'use strict';

var DRTS_WordPress_bnfwAdmin = function DRTS_WordPress_bnfwAdmin(childBundles) {
  var notification = jQuery('#notification'),
    parentAuthor = jQuery('#parent-post-author');

  if (!childBundles.length || !notification.length || !parentAuthor.length) return;

  var showOrHideParentAuthor = function showOrHideParentAuthor() {
    var val = notification.val();
    var showParentAuthor = false;

    var _iteratorNormalCompletion = true;
    var _didIteratorError = false;
    var _iteratorError = undefined;

    try {
      for (var _iterator = childBundles[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
        var bundle = _step.value;

        if (val === 'new-' + bundle || val === 'comment-' + bundle || val === 'commentreply-' + bundle) {
          showParentAuthor = true;
          break;
        }
      }
    } catch (err) {
      _didIteratorError = true;
      _iteratorError = err;
    } finally {
      try {
        if (!_iteratorNormalCompletion && _iterator.return) {
          _iterator.return();
        }
      } finally {
        if (_didIteratorError) {
          throw _iteratorError;
        }
      }
    }

    if (showParentAuthor) {
      parentAuthor.show();
    } else {
      parentAuthor.hide();
    }
  };

  showOrHideParentAuthor();
  notification.on('change', function() {
    showOrHideParentAuthor();
  });

  jQuery('#publish').click(function() {
    if (jQuery('#users').is(':visible') && null === $(BNFW.validation_element).val() && jQuery('#only-post-author:checked').length <= 0 && jQuery('#only-parent-post-author:checked').length) {
      jQuery('#users').hide(); // required to pass BNFW validation
    }
  });
};