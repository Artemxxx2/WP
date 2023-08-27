'use strict';

var _extends = Object.assign || function(target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = arguments[i];
    for (var key in source) {
      if (Object.prototype.hasOwnProperty.call(source, key)) {
        target[key] = source[key];
      }
    }
  }
  return target;
};

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

function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

(function($) {
  DRTS.Form.field.picker = function() {
    function _class(selector, options) {
      var _this = this;

      _classCallCheck(this, _class);

      this.field = $(selector);
      this.items = [];
      this._allItems = null;
      this.page = 1;
      var defaults = DRTS.Form.field.picker.DEFAULTS;
      this.options = _extends({}, defaults, options, this.field.data());
      var btn_class = DRTS.bsPrefix + 'btn ' + DRTS.bsPrefix + 'btn-sm ' + DRTS.bsPrefix + 'btn-primary ' + DRTS.bsPrefix + 'disabled';
      var input_class = DRTS.bsPrefix + 'form-control ' + DRTS.bsPrefix + 'form-control-sm';
      var input_placeholder = this.options.searchText || 'Search...';
      var prev_icon = DRTS.isRTL ? 'fa-angle-right' : 'fa-angle-left';
      var next_icon = DRTS.isRTL ? 'fa-angle-left' : 'fa-angle-right';
      this.div = $('<div class="drts-form-picker">' + '<div class="drts-form-picker-pager" style="display:none;"><button type="button" class="drts-form-picker-prev ' + btn_class + '" value="-1"><i class="fas ' + prev_icon + '"></i></button><span></span><button type="button" class="drts-form-picker-next ' + btn_class + '" value="1"><i class="fas ' + next_icon + '"></i></buttondrts-form-picker-pager></div>' + '<div class="drts-form-picker-search" style="display:none;"><input type="text" class="' + input_class + '" placeholder="' + input_placeholder + '"></input></div>' + '<div class="drts-form-picker-items"></div>' + '</div>');

      // Init field
      var input = $('<input type="hidden"/>').attr('name', this.field.attr('name') || '').val(this.options.current).prop('disabled', this.field.prop('disabled') ? true : false);
      this.field.addClass('drts-form-picker-trigger').empty().append(input);
      if (this.field.prop('tagName') === 'BUTTON') {
        this.inline = false;
        this.field.append('<div></div>').append('<span class="fas fa-caret-down"></span>').on('click', function(e) {
          e.preventDefault();
          _this.field.sabaiPopover({
            animation: false,
            trigger: 'manual',
            html: true,
            content: _this.div,
            container: _this.field.closest('.drts'),
            placement: _this.options.placement
          }).on('shown.bs.popover', function() {
            _this.showItem(input.val());
            _this._bindEvents();
          });
          $(_this.field.data('bs.popover').getTipElement()).addClass('drts-form-picker-popover');
          _this.field.sabaiPopover('show');
        });
      } else {
        this.inline = true;
        this.field.append(this.div).addClass(DRTS.bsPrefix + this.options.align);
        this.showItem(input.val());
        this._bindEvents();
      }
    }

    _createClass(_class, [{
      key: 'setItems',
      value: function setItems(items) {
        var _this2 = this;

        this.items = items;
        if (this.inline === false) {
          var current = this.field.find('input').val();
          if (current) {
            var item = this.items.find(function(_item) {
              return _this2._getItemValue(_item).toString() === current;
            });
            if (item) {
              this.field.find('div').html(this._getItemLabel(item));
            }
          }
        }
      }
    }, {
      key: 'showItem',
      value: function showItem(item) {
        var _this3 = this;

        var i = this.items.findIndex(function(_item) {
          return _this3._getItemValue(_item).toString() === item;
        });
        var page = i > 1 ? Math.ceil((i + 1) / this.getItemCountPerPage()) : 1;
        this.showPage(page);

        this.div.find('.drts-form-picker-item[value="' + item + '"]').removeClass(this.options.unselectedClass).addClass(this.options.selectedClass);
      }
    }, {
      key: 'showPage',
      value: function showPage(page) {
        this.page = page;
        var table = this.div.find('.drts-form-picker-items').empty();
        var offset = (page - 1) * this.getItemCountPerPage();
        var pos = offset;
        var rows = this.options.rows;
        var cols = $.inArray(this.options.cols, [1, 2, 3, 4, 6, 12]) ? this.options.cols : 4;
        if (this.options.rows === 0) {
          rows = this.items.length;
        }
        var btn_class = DRTS.bsPrefix + 'btn ' + DRTS.bsPrefix + 'btn-sm';
        for (var i = 0; i < rows; i++) {
          var tr = $('<div class="drts-row drts-gutter-none"></div>');
          for (var j = 0; j < cols; j++) {
            pos = offset + i * cols + j;
            var btn = $('<button></button>').addClass('drts-form-picker-item' + ' ' + this.options.unselectedClass + ' ' + btn_class).css('visibility', 'hidden');
            if (pos < this.items.length) {
              var v = this._getItemValue(this.items[pos]);
              btn.val(v).data('value', v).append(this._renderItem(this.items[pos])).css('visibility', 'visible').attr('title', this._getItemName(this.items[pos]));
            }
            tr.append($('<div class="drts-col-' + 12 / cols + '"></div>').append(btn));
          }
          table.append(tr);
          if (pos >= this.items.length) break;
        }

        // Pager
        if (this.options.pager) {
          var pager = this.div.find('.drts-form-picker-pager');
          var pages = this._getTotalPages();
          pager.css('display', 'flex').find('.drts-form-picker-prev').toggleClass(DRTS.bsPrefix + 'disabled', page <= 1).end().find('.drts-form-picker-next').toggleClass(DRTS.bsPrefix + 'disabled', page === pages).end().find('> span').text(pages > 0 ? this.page + ' / ' + pages : '');
        }

        // Search
        if (this.options.search) {
          this.div.find('.drts-form-picker-search').css('display', 'block');
        }

        this._bindEvents();
      }
    }, {
      key: 'selectItem',
      value: function selectItem(item) {
        var _this4 = this;

        console.log(item);
        var i = this.items.findIndex(function(_item) {
          return _this4._getItemValue(_item).toString() === item;
        });

        if (typeof i !== 'undefined') {
          this.field.find('input').val(item);
          if (this.inline === false) {
            console.log(i, this.items[i]);
            this.field.find('div').html(this._getItemLabel(this.items[i]));
          }
          this.field.trigger('change', {
            item: this.items[i]
          });
        }
      }
    }, {
      key: 'getItemCountPerPage',
      value: function getItemCountPerPage() {
        return this.options.rows === 0 ? this.items.length : this.options.cols * this.options.rows;
      }
    }, {
      key: '_getTotalPages',
      value: function _getTotalPages() {
        return Math.ceil(this.items.length / this.getItemCountPerPage());
      }
    }, {
      key: '_getItemName',
      value: function _getItemName(item) {
        return item.value;
      }
    }, {
      key: '_getItemValue',
      value: function _getItemValue(item) {
        return item.value;
      }
    }, {
      key: '_getItemLabel',
      value: function _getItemLabel(item) {
        return item.label;
      }
    }, {
      key: '_renderItem',
      value: function _renderItem(item) {
        return item.label;
      }
    }, {
      key: '_bindEvents',
      value: function _bindEvents() {
        var _this5 = this;

        this.div.find('.drts-form-picker-item').off('click').on('click', function(e) {
          e.preventDefault();
          var val = $(e.currentTarget).val();
          _this5.selectItem(val);
          if (_this5.inline === false) {
            _this5.field.sabaiPopover('dispose');
          } else {
            _this5.div.find('.drts-form-picker-item[value="' + val + '"]').removeClass(_this5.options.unselectedClass).addClass(_this5.options.selectedClass);
          }
        });
        // Pager
        if (this.options.pager) {
          this.div.find('.drts-form-picker-prev, .drts-form-picker-next').off('click').on('click', function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            if (!$btn.hasClass(DRTS.bsPrefix + 'disabled')) {
              _this5.showPage(_this5.page + parseInt($btn.val(), 10));
            }
          });
        }
        // Search
        if (this.options.search) {
          this.div.find('.drts-form-picker-search input').off('keyup').on('keyup', function(e) {
            _this5.filterItems($(e.currentTarget).val());
            _this5.showPage(1);
          });
        }
      }
    }, {
      key: 'filterItems',
      value: function filterItems(text) {
        var _this6 = this;

        if (this._allItems === null) {
          this._allItems = this.items;
        }
        if (text !== '') {
          var result = [];
          $.each(this._allItems, function(i, item) {
            if (_this6._itemMatches(item, text)) {
              result.push(item);
            }
          });
          this.items = result;
        } else {
          this.items = this._allItems;
        }
      }
    }, {
      key: '_itemMatches',
      value: function _itemMatches(item, text) {
        return item.toLowerCase().indexOf(text) > -1;
      }
    }]);

    return _class;
  }();

  DRTS.Form.field.picker.DEFAULTS = {
    deselectable: false,
    placement: 'bottom',
    align: 'center',
    cols: 4,
    rows: 4,
    current: '',
    pager: true,
    search: true,
    selectedClass: DRTS.bsPrefix + 'btn-info',
    unselectedClass: DRTS.bsPrefix + 'btn-outline-secondary'
  };

  $(document).on('click', 'body', function(e) {
    $('.drts-form-picker-trigger').each(function() {
      var $this = $(this);
      //the 'is' for buttons that trigger popups
      //the 'has' for icons within a button that triggers a popup
      if (!$this.is(e.target) && $this.has(e.target).length === 0 && $('.' + DRTS.bsPrefix + 'popover').has(e.target).length === 0) {
        $this.sabaiPopover('dispose');
      }
    });
  });
})(jQuery);