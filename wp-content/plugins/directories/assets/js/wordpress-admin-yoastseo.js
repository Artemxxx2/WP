'use strict';

(function($) {
  var DRTS_WordPress_YoastSEO = function DRTS_WordPress_YoastSEO() {
    this.fields = this.getFields();
    this.getContent = this.getContent.bind(this);
    YoastSEO.app.registerPlugin('directories', {
      status: 'ready'
    });
    YoastSEO.app.registerModification('content', this.getContent, 'directories', 5);
  };

  DRTS_WordPress_YoastSEO.prototype.getContent = function() {
    var content = '';
    $(this.fields).each(function(key, selector) {
      content += $(selector).val();
    });
    return content;
  };

  DRTS_WordPress_YoastSEO.prototype.getFields = function() {
    var fields = [];
    fields.push('[data-form-field-name="drts[post_content][0]"] textarea');
    return fields;
  };

  $(window).on('YoastSEO:ready', function() {
    new DRTS_WordPress_YoastSEO();
  });
})(jQuery);