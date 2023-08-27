'use strict';

(function($) {
  DRTS.DirectoryPro = DRTS.DirectoryPro || {};
  DRTS.DirectoryPro.adminCustomTaxonomies = function(selector, options) {
    var $container = $(selector);
    if (!$container.length) return;

    options = $.extend({
      deleteConfirm: 'Are you sure?'
    }, options);

    $container.on('click', '.drts-directorypro-delete-custom-taxonomy', function(e) {
      e.preventDefault();

      if (!confirm(options.deleteConfirm)) return false;

      $(this).closest('tr').fadeTo(400, 0, function() {
        var $_this = $(this);
        $_this.slideUp(100, function() {
          $_this.remove();
        });
      });
    });
  };
  DRTS.DirectoryPro.onAddCustomTaxonomy = function(selector, result) {
    var $container = $(selector);
    if (!$container.length) return;

    var $tbody = $container.find("tbody"),
      $row = $tbody.children("tr:first").clone(),
      classes = [DRTS.bsPrefix + 'btn', DRTS.bsPrefix + 'btn-sm', DRTS.bsPrefix + 'btn-link', DRTS.bsPrefix + 'text-danger', DRTS.bsPrefix + 'text-danger', 'drts-directorypro-delete-custom-taxonomy'];
    $row.find("td:nth-child(1) i").removeClass("fa-tag fa-folder").addClass(result.hierarchical ? "fa-folder" : "fa-tag").end().find("td:nth-child(2) span").text(result.name).end().find("td:nth-child(3) input").attr("name", "DirectoryPro[custom_taxonomies][0][" + result.name + "][label]").val(result.label).prop('disabled', false).end().find("td:nth-child(4) input").attr("name", "DirectoryPro[custom_taxonomies][0][" + result.name + "][label_singular]").val(result.label_singular).prop('disabled', false).end().find("td:nth-child(5) button").data("current", result.icon).attr("name", "DirectoryPro[custom_taxonomies][0][" + result.name + "][icon]").prop('disabled', false).end().find("td:nth-child(6)").html('<span class="' + classes.join(' ') + '"><i class="fas fa-trash fa-fw"></i></span>');
    $row.appendTo($tbody).slideDown();
    DRTS.Form.field.iconpicker.factory($row.find("td:nth-child(5) .drts-form-type-iconpicker button"));
  };
})(jQuery);