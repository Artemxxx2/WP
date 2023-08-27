'use strict';

(function($) {
  var DRTS_WordPress_RankMath = function DRTS_WordPress_RankMath() {
    this.fields = this.getFields();
    this.getContent = this.getContent.bind(this);
    wp.hooks.addFilter('rank_math_content', 'rank-math', this.getContent, 11);
    $(this.fields).each(function(key, selector) {
      $(selector).on('change', function() {
        rankMathEditor.refresh('content');
      });
    });
  };

  DRTS_WordPress_RankMath.prototype.getContent = function(content) {
    $(this.fields).each(function(key, selector) {
      content += $(selector).val();
    });
    return content;
  };

  DRTS_WordPress_RankMath.prototype.getFields = function() {
    var fields = [];
    fields.push('[data-form-field-name="drts[post_content][0]"] textarea');
    return fields;
  };

  setTimeout(function() {
    new DRTS_WordPress_RankMath();
  }, 500);
})(jQuery);