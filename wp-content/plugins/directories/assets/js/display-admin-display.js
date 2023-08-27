"use strict";

var _createClass = function() {
  function defineProperties(target, props) {
    for (var i = 0; i < props.length; i++) {
      var descriptor = props[i];
      descriptor.enumerable = descriptor.enumerable || false;
      descriptor.configurable = true;
      if ("value" in descriptor) descriptor.writable = true;
      Object.defineProperty(target, descriptor.key, descriptor);
    }
  }
  return function(Constructor, protoProps, staticProps) {
    if (protoProps) defineProperties(Constructor.prototype, protoProps);
    if (staticProps) defineProperties(Constructor, staticProps);
    return Constructor;
  };
}();

function _defineProperty(obj, key, value) {
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }
  return obj;
}

function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

(function($) {
  DRTS.Display = DRTS.Display || {};

  DRTS.Display.adminDisplays = function() {
    function _class(form, options) {
      var _this = this;

      _classCallCheck(this, _class);

      this.form = $(form);
      this.options = $.extend({
        addDisplayUrl: null,
        elementTypes: {},
        addElementTitle: null,
        editElementTitle: null,
        deleteConfirm: null,
        saveChangesAlert: null
      }, options);
      this.submitRequired = [];

      // Add display link
      $(".drts-display-add-display").on('click', function(e) {
        if (_this.isSubmitRequired()) {
          alert(_this.options.saveChangesAlert);
          return;
        }

        var $this = $(e.currentTarget);
        DRTS.ajax({
          type: "get",
          container: "#drts-modal",
          modalSize: 'xl',
          url: _this.options.addDisplayUrl,
          data: {
            display_type: $this.data("display-type"),
            display_name: $this.data("display-name")
          },
          trigger: $this,
          cache: true,
          cacheId: _this.options.addDisplayUrl + $this.data("display-type") + "-" + $this.data("display-name")
        });
      });

      // Delete display link
      $(".drts-display-delete-display").on('click', function(e) {
        var $this = $(e.currentTarget);
        var tab = $this.parent('.drts-display-tab2-link');
        if (!tab.length) return;

        if (!confirm(_this.options.deleteConfirm)) return false;

        var data = {
          display_type: tab.data("display-type"),
          display_name: tab.data("display-name")
        };
        data[DRTS.params.token] = _this.form.find('input[name="' + DRTS.params.token + '"]').val();
        DRTS.ajax({
          type: "post",
          url: _this.options.deleteDisplayUrl,
          data: data,
          trigger: tab,
          onSuccess: function onSuccess(result, target, trigger) {
            trigger.fadeTo("fast", 0, function() {
              trigger.slideUp("fast", function() {
                trigger.remove();
              });
              if (trigger.hasClass(DRTS.bsPrefix + 'active')) {
                trigger.parent().find('.drts-display-tab2-link-default').click();
              }
            });
            // Clear add display form cache
            var addDisplayLink = trigger.closest('.' + DRTS.bsPrefix + 'tab-pane').find('.drts-display-add-display');
            if (addDisplayLink.length) {
              console.log(_this.options.addDisplayUrl + addDisplayLink.data('display-type') + '-' + addDisplayLink.data('display-name'));
              DRTS.cache(_this.options.addDisplayUrl + addDisplayLink.data('display-type') + '-' + addDisplayLink.data('display-name'), false);
            }
          }
        });
        return false;
      });

      this.form.on('submit', function(e) {
        _this.submitRequired = [];
      });

      // Submit form immediately if submit timeout is active when leaving the page
      $(window).on('beforeunload', function(e) {
        if (_this.isSubmitRequired()) {
          return '';
        }
      });
    }

    _createClass(_class, [{
      key: "addDisplay",
      value: function addDisplay(selector, options) {
        DRTS.Display.adminDisplay(this, selector, $.extend({}, this.options, options));
      }
    }, {
      key: "isSubmitRequired",
      value: function isSubmitRequired(selector, value) {
        if (!selector) return this.submitRequired.length > 0;

        var index = this.submitRequired.indexOf(selector);
        if (index !== -1) {
          // found in array
          if (value === false) {
            this.submitRequired.splice(index, 1);
          } else if (value !== true) {
            return true;
          }
        } else {
          // not found in array
          if (value === true) {
            this.submitRequired.push(selector);
          } else if (value !== false) {
            return false;
          }
        }
      }
    }]);

    return _class;
  }();

  DRTS.Display.adminDisplay = function(admin, selector, options) {
    options = $.extend({
      selector: selector,
      name: 'elements',
      listElementsUrl: null,
      addElementUrl: null,
      editElementUrl: null,
      deleteElementTitle: null,
      configureInline: false
    }, options);
    var dragging,
      addElement = function addElement() {
        var $this = $(this),
          container;
        hidePopover();
        $this.sabaiTooltip('hide');
        if (options.configureInline) {
          var parentElement = $this.closest('.drts-display-element');
          if (parentElement.length) {
            container = '#drts-display-element-settings-inline-' + parentElement.data('element-id');
          } else {
            container = '#drts-display-element-settings-inline-0';
          }
        } else {
          container = '#drts-modal';
          $this.attr('data-modal-title', $this.data('element-type') && $this.data('element-type').length === 1 ? options.addElementTitle + ' - ' + options.elementTypes[$this.data('element-type')[0]] : options.addElementTitle);
        }
        DRTS.ajax({
          type: 'get',
          container: container,
          modalSize: 'xl',
          url: options.listElementsUrl,
          data: $this.data('element-type') ? {
            type: $this.data('element-type')
          } : null,
          onErrorFlash: true,
          onContent: function onContent(response, target, trigger) {
            target.find('.drts-display-elements').off('click', '.drts-display-element', addSingleElement).on('click', '.drts-display-element', {
              parentId: trigger.closest('.drts-display-element').data('element-id') || 0
            }, addSingleElement);
          },
          trigger: $this,
          cache: true,
          cacheId: $this.data('element-type') ? options.listElementsUrl + '-' + JSON.stringify($this.data('element-type')) : options.listElementsUrl,
          slideDown: true,
          scroll: options.configureInline
        });
        return false;
      },
      addSingleElement = function addSingleElement(e) {
        e.data = e.data || {};
        var $this = $(this),
          element = $this.closest('.drts-display-element'),
          parentId = e.data.parentId || element.data('element-id') || 0,
          container;
        hidePopover();
        $this.sabaiTooltip('hide');
        if (options.configureInline) {
          $this.addClass('drts-bs-active');
          container = '#drts-display-element-settings-inline-' + parentId;
        } else {
          container = '#drts-modal';
          $this.attr('data-modal-title', (element.data('element-type') && element.data('element-type').length === 1 ? options.addElementTitle + ' - ' + options.elementTypes[element.data('element-type')[0]] : options.addElementTitle) + ' - ' + element.data('element-label'));
        }
        DRTS.ajax({
          type: 'get',
          container: container,
          modalSize: 'xl',
          url: options.addElementUrl,
          data: {
            element: $this.data('element-name')
          },
          onSendData: function onSendData(data, trigger) {},
          onContent: function onContent(response, target, trigger) {
            target.find('#drts-display-add-element-parent').val(parentId);
          },
          onErrorFlash: true,
          trigger: $this,
          cache: true,
          cacheId: 'drts-display-element-name-' + $this.data('element-name'),
          slideDown: true
        });
        return false;
      },
      editElement = function editElement() {
        var $this = $(this),
          element = $this.closest('.drts-display-element'),
          container;
        element.addClass('drts-display-element-editing');

        hidePopover();
        if (options.configureInline) {
          container = '#drts-display-element-settings-inline-' + element.data('element-id');
        } else {
          container = '#drts-modal';
          if (!$this.attr('data-modal-title')) {
            $this.attr('data-modal-title', (element.data('element-type') && element.data('element-type').length === 1 ? options.editElementTitle + ' - ' + options.elementTypes[element.data('element-type')[0]] : options.editElementTitle) + ' - ' + element.data('element-label') + ' (ID: ' + element.data('element-id') + ')');
          }
        }
        DRTS.ajax({
          type: 'get',
          container: container,
          modalSize: 'xl',
          url: options.editElementUrl,
          data: {
            'element_id': element.data('element-id')
          },
          onContent: function onContent(response, target, trigger) {
            element.removeClass('drts-display-element-editing');
            if (options.configureInline) {
              element.addClass('drts-display-element-editing-inline');
            }
          },
          onErrorFlash: true,
          trigger: $this,
          cache: true,
          cacheId: 'drts-display-element-id-' + element.data('element-id'),
          slideDown: true
        });
        return false;
      },
      deleteElement = function deleteElement() {
        if (!confirm(options.deleteConfirm)) return false;

        hidePopover();
        var element = $(this).closest('.drts-display-element');
        element.fadeTo('fast', 0, function() {
          element.slideUp('medium', function() {
            element.remove();
          });
        });

        submitForm();

        return false;
      },
      createElement = function createElement(data, container) {
        var html = '<div class="drts-display-element" data-element-id="" data-element-type="">' + '<div class="drts-display-element-title">' + '<span class="drts-display-element-handle"><i class="fas fa-arrows-alt fa-fw fa-lg"></i> <span class="drts-display-element-label"></span></span>' + '</div>' + '<div class="drts-display-element-control">' + '<div class="drts-bs-btn-group">' + '<button type="button" class="drts-display-element-info drts-bs-btn drts-bs-btn-info drts-bs-btn-sm"><i class="fas fa-info fa-fw"></i></button>' + '<button type="button" class="drts-display-element-edit drts-bs-btn drts-bs-btn-primary drts-bs-btn-sm"><i class="fas fa-cog fa-fw"></i></button>' + '<button type="button" class="drts-display-element-delete drts-bs-btn drts-bs-btn-danger drts-bs-btn-sm"><i class="fas fa-trash-alt fa-fw"></i></button>' + '<button type="button" class="drts-display-add-element drts-bs-btn drts-bs-btn-success drts-bs-btn-sm" rel="sabaitooltip" title="" data-original-title="' + options.addElementTitle + '" data-placement="right"><i class="fas fa-plus fa-fw"></i></button>' + '</div>' + '</div>' + '<div class="drts-display-element-settings-inline"></div>' + '<input type="hidden" name="' + options.name + '[]" value="" />' + '</div>',
          element = $(html).attr('data-element-id', data.id).attr('data-element-type', data.type).attr('data-element-name', data.name).attr('data-element-label', data.label).data('element-data', data.data).addClass('drts-display-element-name-' + data.name).toggleClass('drts-display-element-dimmed', data.dimmed).find('input[name="' + options.name + '[]"]').val(data.id).end().appendTo(container).find('[rel*="sabaitooltip"]').sabaiTooltip({
            container: container
          }).end().show();
        if (data.attr) {
          $.each(data.attr, function(key, value) {
            element.attr(key, value);
          });
        }
        if (options.configureInline) {
          element.find('.drts-display-element-settings-inline').attr('id', 'drts-display-element-settings-inline-' + data.id);
        } else {
          element.find('.drts-display-element-settings-inline').remove();
        }
        setElementLabel(element, data, true);
        if (data.containable) {
          var html = '<input type="hidden" name="' + options.name + '[]" value="__CHILDREN_START__" />' + '<div class="drts-display-element-wrapper"></div>' + '<input type="hidden" name="' + options.name + '[]" value="__CHILDREN_END__" />',
            child_container = element.addClass('drts-display-element-containable').append(html).find('.drts-display-add-element').attr('data-original-title', data.add_child_label).end().find(' > .drts-display-element-wrapper').sortable(elementSortableConf);
          if (data.child_element_name) {
            element.find('.drts-display-add-element').attr('data-element-name', data.child_element_name);
            child_container.attr('child-element-name', data.child_element_name);
            if (!DRTS.cache(options.addElementsUrl + '-' + data.child_element_name)) {
              var _$$get;

              $.get(options.addElementUrl, (_$$get = {}, _defineProperty(_$$get, DRTS.params.ajax, '#drts-modal'), _defineProperty(_$$get, "element", data.child_element_name), _$$get), function(_data) {
                DRTS.cache(options.addElementsUrl + '-' + data.child_element_name, _data);
              });
            }
          } else if (data.child_element_type) {
            var child_element_type_str = typeof data.child_element_type !== 'string' ? JSON.stringify(data.child_element_type) : data.child_element_type;
            element.find('.drts-display-add-element').attr('data-element-type', child_element_type_str);
            child_container.attr('child-element-type', child_element_type_str);
            if (!DRTS.cache(options.listElementsUrl + '-' + child_element_type_str)) {
              var _$$get2;

              $.get(options.listElementsUrl, (_$$get2 = {}, _defineProperty(_$$get2, DRTS.params.ajax, '#drts-modal'), _defineProperty(_$$get2, "type", data.child_element_type), _$$get2), function(_data) {
                DRTS.cache(options.listElementsUrl + '-' + child_element_type_str, _data);
              });
            }
            child_container.sortable('option', 'connectWith', '.drts-display-element-wrapper');
          } else {
            child_container.sortable('option', 'connectWith', '.drts-display-element-wrapper');
          }
          if (data.children) {
            $.each(data.children, function(i, val) {
              createElement(val, child_container);
            });
          }
        } else {
          element.find('.drts-display-add-element').remove();
        }

        return element;
      },
      setElementLabel = function setElementLabel(element, data, addIcon) {
        var title = element.find('> .drts-display-element-title');
        if (data.title && data.title.length) {
          title.find('.drts-display-element-label').html(data.title).toggleClass('drts-display-element-no-label', data.no_title);
        } else {
          title.find('.drts-display-element-label').text(data.label).addClass('drts-display-element-no-label');
        }
        if (addIcon && data.icon) {
          title.prepend('<span class="drts-display-element-icon"><i class="fa-fw ' + data.icon + '"></i></span>');
        }
      },
      submitForm = function submitForm() {
        if (!options.configureInline) {
          admin.isSubmitRequired(options.selector, true);
        }
      },
      highlightElement = function highlightElement(element) {
        if (element.hasClass('drts-display-element-containable')) {
          element.find('> .drts-display-element-title').effect('highlight', {}, 1000);
        } else {
          element.effect('highlight', {}, 1000);
        }
      },
      hidePopover = function hidePopover() {
        $(options.selector).find('.drts-popover-processed').sabaiPopover('hide');
      };

    // Make sortable

    var elementSortableConf = {
      items: '> .drts-display-element',
      placeholder: 'drts-display-element-placeholder',
      opacity: 0.8,
      handle: '.drts-display-element-handle',
      tolerance: "intersect",
      zIndex: 99999,
      start: function start(e, ui) {
        var height = ui.helper.find('.drts-display-element-title').outerHeight() - 2;
        dragging = true;
        hidePopover();
        ui.helper.height(height).css({
          overflow: 'hidden',
          width: ui.helper.find('.drts-display-element-handle').outerWidth() + 32
        });
        ui.placeholder.height(height);
        if (ui.item.data('element-width')) {
          ui.placeholder.css('width', ui.item.data('element-width'));
        }
      },
      update: function update(e, ui) {
        submitForm(true);
      },
      over: function over(event, ui) {
        var container = $(this);
        // Make sure the container accepts the element being hovered over
        if (container.data('child-element-type') && $.inArray(ui.item.data('element-type'), container.data('child-element-type')) === -1 || container.data('child-element-name') && ui.item.data('element-name') !== container.data('child-element-name') || container.data('child-element-attr') && !ui.item.data(container.data('child-element-attr')) || ui.item.data('parent-element-name') && container.closest('.drts-display-element').data('element-name') !== ui.item.data('parent-element-name')) {
          ui.item.find('.drts-display-element-handle').css('cursor', 'no-drop');
          ui.placeholder.addClass(DRTS.bsPrefix + 'bg-danger');
        } else {
          ui.item.find('.drts-display-element-handle').css('cursor', 'move');
          ui.placeholder.removeClass(DRTS.bsPrefix + 'bg-danger');
        }
      },
      out: function out(event, ui) {
        ui.item.css('cursor', 'move');
      },
      stop: function stop(event, ui) {
        dragging = false;
        ui.item.css({
          overflow: 'visible',
          cursor: 'auto'
        });
      },
      receive: function receive(event, ui) {
        var container = $(this);
        // Make sure the container accepts the element being hovered over
        if (container.data('child-element-type') && $.inArray(ui.item.data('element-type'), container.data('child-element-type')) === -1 || container.data('child-element-name') && ui.item.data('element-name') !== container.data('child-element-name') || container.data('child-element-attr') && !ui.item.data(container.data('child-element-attr')) || ui.item.data('parent-element-name') && container.closest('.drts-display-element').data('element-name') !== ui.item.data('parent-element-name')) {
          ui.sender.sortable('cancel');
        }
      }
    };
    $('.drts-display-element-wrapper', options.selector).each(function() {
      var $this = $(this).sortable(elementSortableConf);
      if (!$this.data('child-element-name')) {
        $this.sortable('option', 'connectWith', options.selector + ' .drts-display-element-wrapper:not([data-child-element-name])');
      }
    });

    // Bind events

    $('.drts-display-display', options.selector).on('click', '.drts-display-element-delete', deleteElement);

    $(DRTS).on('display_element_created.sabai', function(e, data) {
      if (data.target.hasClass('drts-display-element-settings-inline')) {
        data.target.empty().hide();
      }

      var selector = '.drts-display-display[data-display-id="' + data.result.display_id + '"]';
      if (data.result.parent_id) {
        selector += ' .drts-display-element[data-element-id="' + data.result.parent_id + '"]';
      }

      selector += ' > .drts-display-element-wrapper';
      var container = $(options.selector).find(selector);
      if (!container.length) {
        console.log('Container ' + container + ' not found.');
        return;
      }

      var element = createElement(data.result, container);
      highlightElement(element);
      // Clear cached form
      DRTS.cache('drts-display-element-name-' + element.data('element-name'), false);

      submitForm();
    });

    $(DRTS).on('display_element_updated.sabai', function(e, data) {
      var element = $(options.selector).find('.drts-display-element[data-element-id="' + data.result.id + '"]');
      if (!element.length) return;

      if (options.configureInline) {
        var settings = element.find('> .drts-display-element-settings-inline');
        if (!settings.is(':empty')) {
          settings.slideUp('fast', function() {
            $(this).empty();
          });
          element.removeClass('drts-display-element-editing-inline');
        }
      }

      if (data.result.attr) {
        $.each(data.result.attr, function(key, value) {
          element.attr(key, value);
        });
      }
      element.toggleClass('drts-display-element-dimmed', data.result.dimmed);
      if (data.result.data) {
        element.data('element-data', data.result.data);
        var infoBtn = element.find('.drts-display-element-info');
        if (infoBtn.hasClass('drts-popover-processed')) {
          infoBtn.removeClass('drts-popover-processed').sabaiPopover('dispose');
        }
      }
      setElementLabel(element, data.result);
      highlightElement(element);
      // Clear cached form
      DRTS.cache('drts-display-element-id-' + element.data('element-id'), false);
    });

    // Load and cache pages
    $(function() {
      $.get(options.listElementsUrl, _defineProperty({}, DRTS.params.ajax, '#drts-modal'), function(data) {
        DRTS.cache(options.listElementsUrl, data);
        $('.drts-display-display', options.selector).on('click', '.drts-display-add-element:not([data-element-name])', addElement);
        $('.drts-display-add-element-main', options.selector).prop('disabled', false);
      });
      //$('.drts-display-add-element[data-element-name]', options.selector).each(function(){
      //    var $this = $(this).prop('disabled', true), element_name = $this.data('element-name');
      //    if (!DRTS.cache('drts-display-element-name-' + element_name)) {
      //        $.get(options.addElementUrl, {[DRTS.params.ajax]: '#drts-modal', element: element_name}, function(_data) {
      //            DRTS.cache(options.addElementUrl + '-' + element_name, _data);
      //            $this.prop('disabled', false);
      //        });
      //    }
      //});
      //$('.drts-display-add-element[data-element-type]', options.selector).each(function(){
      //    var $this = $(this).prop('disabled', true), element_type = $this.data('element-type');
      //    if (!DRTS.cache(options.listElementsUrl + '-' + element_type)) {
      //        $.get(options.listElementsUrl, {[DRTS.params.ajax]: '#drts-modal', type: element_type}, function(_data) {
      //            DRTS.cache(options.listElementsUrl + '-' + element_type, _data);
      //            $this.prop('disabled', false);
      //        });
      //    }
      //});
      // Bind events
      $('.drts-display-display', options.selector).on('click', '.drts-display-element-edit', editElement).on('click', '.drts-display-add-element[data-element-name]', addSingleElement).on('click', '.drts-display-element-info', function(e) {
        var $this = $(this);
        e.preventDefault();
        $this.toggleClass('drts-display-element-info-clicked').closest('.drts-display-element-control').toggleClass('drts-display-element-info-clicked');
        $this.off('hidden.bs.popover').on('hidden.bs.popover', function() {
          $this.removeClass('drts-display-element-info-clicked').closest('.drts-display-element-control').removeClass('drts-display-element-info-clicked');
        });
      }).on('click', '.drts-display-elements-buttons', function(e) {
        var $this = $(this);
        $this.closest('.drts-display-element-settings-inline').slideUp('fast').empty();
      });
      // Bind events
      $('.drts-display-display', options.selector).hoverIntent({
        selector: '.drts-display-element-info',
        over: function over(e) {
          var $this = $(this);
          if (dragging) return;
          $(options.selector).find('.drts-popover-processed').not(this).sabaiPopover('hide');
          if ($this.hasClass('drts-popover-processed')) {
            $this.sabaiPopover('show');
          } else {
            var ele = $this.closest('.drts-display-element'),
              table = $('<table></table>'),
              data = ele.data('element-data');
            table.attr('class', 'drts-display-element-data ' + DRTS.bsPrefix + 'table ' + DRTS.bsPrefix + 'table-bordered ' + DRTS.bsPrefix + 'table-sm ' + DRTS.bsPrefix + 'm-0');
            Object.keys(data).forEach(function(k) {
              var isFirstRow = true,
                keys = Object.keys(data[k].value),
                keyLen = keys.length;
              keys.forEach(function(j) {
                var label = $('<td></td>').text(data[k].value[j].label + ':'),
                  value = $('<td></td>'),
                  tr = $('<tr></tr>').append(label).append(value).appendTo(table);
                if (data[k].value[j].is_html) {
                  value.html(data[k].value[j].value);
                } else if (data[k].value[j].is_bool) {
                  value.html('<i class="fas ' + (data[k].value[j].value ? 'fa-check' : 'fa-times') + '"></i>');
                } else {
                  value.text(data[k].value[j].value);
                }
                if (isFirstRow) {
                  tr.prepend($('<th class="' + DRTS.bsPrefix + 'table-secondary" rowspan="' + keyLen + '"></th>').text(data[k].label));
                  isFirstRow = false;
                }
              });
            });
            DRTS.popover($this, {
              html: true,
              trigger: 'manual',
              placement: 'top',
              container: options.selector,
              content: table,
              template: '<div class="' + DRTS.bsPrefix + 'popover"><div class="' + DRTS.bsPrefix + 'arrow"></div>' + '<div class="' + DRTS.bsPrefix + 'popover-body ' + DRTS.bsPrefix + 'p-1"></div></div>'
            });
          }
        },
        out: function out(e) {
          if (!$(this).hasClass('drts-display-element-info-clicked')) {
            hidePopover();
          }
        }
      });
      $('.drts-display-display', options.selector).on('click', '.drts-display-element-title', function() {
        var $this = $(this);
        if ($this.hasClass('drts-popover-processed')) {
          $this.sabaiPopover('toggle');
        }
      });
    });
  };
})(jQuery);