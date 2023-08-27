'use strict';

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

var _get = function get(object, property, receiver) {
  if (object === null) object = Function.prototype;
  var desc = Object.getOwnPropertyDescriptor(object, property);
  if (desc === undefined) {
    var parent = Object.getPrototypeOf(object);
    if (parent === null) {
      return undefined;
    } else {
      return get(parent, property, receiver);
    }
  } else if ("value" in desc) {
    return desc.value;
  } else {
    var getter = desc.get;
    if (getter === undefined) {
      return undefined;
    }
    return getter.call(receiver);
  }
};

function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

function _possibleConstructorReturn(self, call) {
  if (!self) {
    throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  }
  return call && (typeof call === "object" || typeof call === "function") ? call : self;
}

function _inherits(subClass, superClass) {
  if (typeof superClass !== "function" && superClass !== null) {
    throw new TypeError("Super expression must either be null or a function, not " + typeof superClass);
  }
  subClass.prototype = Object.create(superClass && superClass.prototype, {
    constructor: {
      value: subClass,
      enumerable: false,
      writable: true,
      configurable: true
    }
  });
  if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass;
}

(function($) {
  DRTS.Form.field.iconpicker = function(_DRTS$Form$field$pick) {
    _inherits(_class, _DRTS$Form$field$pick);

    function _class(selector, items) {
      _classCallCheck(this, _class);

      var _this = _possibleConstructorReturn(this, (_class.__proto__ || Object.getPrototypeOf(_class)).call(this, selector, DRTS.Form.field.iconpicker.DEFAULTS));

      console.log(items);
      _this.setItems(items);
      return _this;
    }

    _createClass(_class, [{
      key: '_renderItem',
      value: function _renderItem(item) {
        return $('<i></i>').addClass(item)[0].outerHTML;
      }
    }, {
      key: '_getItemLabel',
      value: function _getItemLabel(item) {
        return this._renderItem(item);
      }
    }, {
      key: '_getItemValue',
      value: function _getItemValue(item) {
        return item;
      }
    }, {
      key: '_itemMatches',
      value: function _itemMatches(item, text) {
        return _get(_class.prototype.__proto__ || Object.getPrototypeOf(_class.prototype), '_itemMatches', this).call(this, this._getItemName(item), text);
      }
    }]);

    return _class;
  }(DRTS.Form.field.picker);

  DRTS.Form.field.iconpicker.factory = function(selector, iconset) {
    if (!iconset) {
      iconset = $(selector).data('iconset');
    } else {
      $(selector).data('iconset', iconset);
    }
    switch (iconset) {
      case 'dashicons':
        return new DRTS.Form.field.dashicons(selector);
      case 'fontawesome':
      default:
        return new DRTS.Form.field.fontawesome(selector);
    }
  };

  DRTS.Form.field.iconpicker.DEFAULTS = {
    rows: 6,
    cols: 12
  };
})(jQuery);