/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__icons_icons_js__ = __webpack_require__(1);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__components_select_component_js__ = __webpack_require__(2);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_2__css_aweber_gutenberg_webform_block_css__ = __webpack_require__(3);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_2__css_aweber_gutenberg_webform_block_css___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_2__css_aweber_gutenberg_webform_block_css__);
function _extends() { _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

/*!     aweber_gutenberg_webform_block.js
 *
 *     Create custom AWeber Signup form block in the Gutenberg Editor in wordpress.
 */
var __ = wp.i18n.__; // Import registerBlockType() from wp.blocks

var registerBlockType = wp.blocks.registerBlockType; // wp.element is Gutenberg's thin abstraction layer atop React

var _wp$element = wp.element,
    Component = _wp$element.Component,
    Fragment = _wp$element.Fragment; // Import the AWeber ICONS

 // Import the Select Component to create the Select Dropdown with the Signup forms and Save Button.

 // Import the Stylesheet to style the AWeber Block

 // Register the AWeber Sign up form Block with Gutenberg.

registerBlockType('aweber-signupform-block/aweber-shortcode', {
  title: __('Add an AWeber Sign Up Form'),
  icon: __WEBPACK_IMPORTED_MODULE_0__icons_icons_js__["a" /* default */].aweber,
  category: 'common',
  attributes: {
    selectedShortCode: {
      type: 'string',
      default: ''
    }
  },
  edit: function edit(props) {
    // Create a HTML Image tag.
    function ImageTag(props) {
      return wp.element.createElement("img", {
        src: props.srcattr
      });
    } // Removes the selected shortcode from attributes.


    function removeSelectedShortCode(event) {
      props.setAttributes({
        selectedShortCode: ''
      });
    } // If the user has not selected the signup, then it will show the Dropdown with the list of Signup Forms.


    if (props.attributes.selectedShortCode == '') {
      return wp.element.createElement("div", {
        className: "aweber-placeholder"
      }, wp.element.createElement("div", {
        className: "aweber-placeholer-label"
      }, wp.element.createElement("span", {
        className: "aweber-placeholder-image"
      }, wp.element.createElement(ImageTag, {
        srcattr: gutenberg_php_vars.aweber_logo
      })), wp.element.createElement("span", {
        className: "aweber-placeholder-text"
      }, "Add an AWeber Sign Up Form")), wp.element.createElement("div", {
        className: "aweber-placeholder-content"
      }, wp.element.createElement(__WEBPACK_IMPORTED_MODULE_1__components_select_component_js__["a" /* default */], _extends({
        setAttributes: props.setAttributes
      }, props))));
    } // If user has selected the Signup Form, Show the Shortcode.


    var shortcodeSeperated = props.attributes.selectedShortCode.split('-');
    return wp.element.createElement("div", {
      className: "aweber-placeholder"
    }, wp.element.createElement("div", {
      className: "aweber-placeholer-label"
    }, wp.element.createElement("span", {
      className: "aweber-placeholder-image"
    }, wp.element.createElement(ImageTag, {
      srcattr: gutenberg_php_vars.aweber_logo
    })), wp.element.createElement("span", {
      className: "aweber-placeholder-text"
    }, "Add an AWeber Sign Up Form")), wp.element.createElement("div", {
      className: "aweber-placeholder-content"
    }, wp.element.createElement("label", {
      className: "aweber-shortcode-label"
    }, "Shortcode:"), wp.element.createElement("label", {
      className: "aweber-shortcode-label-content"
    }, "[aweber listid=", shortcodeSeperated[0], " formid=", shortcodeSeperated[1], " formtype=", shortcodeSeperated[2], "]"), wp.element.createElement("br", null), wp.element.createElement("button", {
      type: "button",
      onClick: removeSelectedShortCode,
      className: "aweber-button aweber-small-btn"
    }, "Edit")));
  },
  save: function save(props) {
    // This function is executed, Once the Wordpress homepage loads.
    var shortcodeSeperated = props.attributes.selectedShortCode; // If the Signup Form not selected. then just return empty Div.

    if (shortcodeSeperated === '') {
      return wp.element.createElement("div", null);
    } // Split the shortcode and return the HTML.


    shortcodeSeperated = shortcodeSeperated.split('-');
    return wp.element.createElement("div", null, "[aweber listid=", shortcodeSeperated[0], " formid=", shortcodeSeperated[1], " formtype=", shortcodeSeperated[2], "]");
  }
});

/***/ }),
/* 1 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/*!     Icons.js
 *
 *     Return the AWeber ICONS.
 */
var icons = {};
icons.aweber = wp.element.createElement("svg", {
  version: "1.2",
  xmlns: "https://www.w3.org/2000/svg",
  width: "475pt",
  height: "475pt",
  viewBox: "0 0 475 475",
  overflow: "visible"
}, wp.element.createElement("g", {
  transform: "translate(0,-20)"
}, wp.element.createElement("path", {
  fill: "#000000",
  d: "M282.7,0c-11.7,0-22.8,5.2-29.9,14.3c-50.7,64.8-81.3,141.4-95.2,217.5 c-12.1,66.6-6.4,151.3,16.2,229.3c4.7,16.2,12.8,27.5,76.8,27.5l0,0l0,0c172.6,0,249.3-67.3,249.3-244.5l0,0 C500,72.4,426.8,0,282.7,0 M278.1,466.6c-10.5,5.3-23.3,1.2-28.7-9c-0.1-0.2-0.1-0.3-0.2-0.5l0,0c-34.7-66-46.3-140.9-32.8-215.3 c13.5-74.5,50.9-141,106.9-191.4l0,0c0.1-0.1,0.3-0.3,0.4-0.4c8.8-7.7,22.3-7,30.1,1.6c7.9,8.6,7.1,21.8-1.7,29.4 c-0.1,0.1-0.2,0.2-0.4,0.3l0,0c-47.4,42.9-81.3,100.7-93.5,167.7c-12.1,66.8-0.6,132.3,28.6,188.5l-0.1,0.1c0.2,0.3,0.4,0.6,0.5,0.9 C292.7,448.8,288.5,461.4,278.1,466.6 M407.9,154c-0.2,0.2-0.5,0.3-0.8,0.5c-34.7,27.4-59.8,65-68.5,108.8 c-8.8,44,0.3,87.1,22.5,123.6l-0.2,0.1c0.2,0.3,0.4,0.5,0.6,0.8c3.6,6.3,0.9,14-6.2,17.2c-7.1,3.2-15.7,0.8-19.4-5.5 c-0.1-0.2-0.2-0.4-0.3-0.6l0,0c-25.2-41.4-35.4-90.2-25.5-140c9.9-49.7,38.4-92.4,78-123.5l0,0c0.3-0.2,0.5-0.5,0.8-0.7 c6-4.6,15.2-4.1,20.4,1.3C414.6,141.2,413.9,149.3,407.9,154 M76.8,430.4c1.7,8.6-8.9,14.5-15.5,8.5C23.6,404.8,0,361.5,0,244.2l0,0 C0,93.4,59.2,29,139.8,7.1c8-2.2,14.6,6.4,10.4,13.4c-36.6,60.3-62.1,127.3-74.8,197.1C62.6,288.3,63.1,360.7,76.8,430.4z"
})));
/* harmony default export */ __webpack_exports__["a"] = (icons);

/***/ }),
/* 2 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return AWeberSelectClass; });
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/*!     select_component.js
 *
 *     Return the Select Dropdown with the List of Signup forms and Save button.
 */
// wp.element is Gutenberg's thin abstraction layer atop React
var _wp$element = wp.element,
    Component = _wp$element.Component,
    Fragment = _wp$element.Fragment;

var AWeberSelectClass = /*#__PURE__*/function (_Component) {
  _inherits(AWeberSelectClass, _Component);

  var _super = _createSuper(AWeberSelectClass);

  function AWeberSelectClass(props) {
    var _this;

    _classCallCheck(this, AWeberSelectClass);

    _this = _super.call(this, props); // Set the state of the class.

    _this.state = {
      isLoaded: false,
      error: false,
      selectedShortCode: '',
      errorMessage: '',
      errorClassName: '',
      message: 'Please wait loading the shortcodes.'
    }; // Bind the ON change event.

    _this.shortcodeSelected = _this.shortcodeSelected.bind(_assertThisInitialized(_this));
    _this.saveClicked = _this.saveClicked.bind(_assertThisInitialized(_this));
    _this.createMarkup = _this.createMarkup.bind(_assertThisInitialized(_this));
    return _this;
  }

  _createClass(AWeberSelectClass, [{
    key: "componentDidMount",
    value: function componentDidMount() {
      var _this2 = this;

      fetch(ajaxurl + '?action=get_aweber_webform_shortcodes&nonce=' + gutenberg_php_vars.aweber_nonce.get_aweber_webform_shortcodes).then(function (response) {
        return response.json();
      }).then(function (result) {
        status = result.status;

        if (status === 'error') {
          _this2.setState({
            isLoaded: true,
            error: true,
            errorClassName: 'aweber-block-error',
            message: result.message
          });
        } else {
          var shortcodes = [];
          var options = [];
          var currentListId = result.short_codes[0].list_id;
          var currentListName = result.short_codes[0].list_name;

          for (var i = 0; i < result.short_codes.length; i++) {
            if (currentListId != result.short_codes[i].list_id) {
              shortcodes.push({
                list_id: currentListId,
                list_name: currentListName,
                options: options
              });
              options = [];
              currentListId = result.short_codes[i].list_id;
              currentListName = result.short_codes[i].list_name;
            }

            options.push({
              'name': result.short_codes[i].text,
              'value': result.short_codes[i].value
            });
          }

          if (options.length > 0) {
            shortcodes.push({
              'list_id': currentListId,
              'list_name': currentListName,
              'options': options
            });
          }

          _this2.setState({
            isLoaded: true,
            error: false,
            errorClassName: '',
            shortcodes: shortcodes
          });
        }
      }, function (error) {
        _this2.setState({
          isLoaded: true,
          error: true,
          errorClassName: '',
          message: 'Something went wrong. Please try again.'
        });
      });
    }
  }, {
    key: "shortcodeSelected",
    value: function shortcodeSelected(event) {
      this.setState({
        errorClassName: '',
        selectedShortCode: event.target.value
      });
    }
  }, {
    key: "saveClicked",
    value: function saveClicked(event) {
      if (this.state.selectedShortCode === '') {
        this.setState({
          errorClassName: '',
          errorMessage: 'Please select the Sign Up Form'
        });
        return;
      }

      var setAttributes = this.props.setAttributes;
      setAttributes({
        selectedShortCode: this.state.selectedShortCode
      });
    }
  }, {
    key: "createMarkup",
    value: function createMarkup() {
      // If the error occurred in the backend. the response comes in the HTML format. To render the HTML it, used dangerouslySetInnerHTML
      return {
        __html: this.state.message
      };
    }
  }, {
    key: "render",
    value: function render() {
      if (this.state.isLoaded && !this.state.error) {
        var shortcodes = this.state.shortcodes;
        return wp.element.createElement(React.Fragment, null, wp.element.createElement("select", {
          className: "aweber-dropdown",
          value: this.state.selectedShortCode,
          onChange: this.shortcodeSelected
        }, wp.element.createElement("option", {
          value: ""
        }, "Please select a Sign Up Form"), shortcodes.map(function (shortcode, listkey) {
          return wp.element.createElement("optgroup", {
            key: listkey,
            label: shortcode.list_name
          }, shortcode.options.map(function (option, formkey) {
            return wp.element.createElement("option", {
              key: formkey,
              value: option.value
            }, option.name);
          }));
        })), wp.element.createElement("button", {
          type: "button",
          className: "aweber-button",
          onClick: this.saveClicked
        }, "Save"), wp.element.createElement("span", {
          className: "aweber-error-label"
        }, this.state.errorMessage));
      }

      return wp.element.createElement("div", {
        className: this.state.errorClassName,
        dangerouslySetInnerHTML: this.createMarkup()
      });
    }
  }]);

  return AWeberSelectClass;
}(Component);


;

/***/ }),
/* 3 */
/***/ (function(module, exports, __webpack_require__) {

var api = __webpack_require__(4);
            var content = __webpack_require__(5);

            content = content.__esModule ? content.default : content;

            if (typeof content === 'string') {
              content = [[module.i, content, '']];
            }

var options = {};

options.insert = "head";
options.singleton = false;

var update = api(content, options);



module.exports = content.locals || {};

/***/ }),
/* 4 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var isOldIE = function isOldIE() {
  var memo;
  return function memorize() {
    if (typeof memo === 'undefined') {
      // Test for IE <= 9 as proposed by Browserhacks
      // @see http://browserhacks.com/#hack-e71d8692f65334173fee715c222cb805
      // Tests for existence of standard globals is to allow style-loader
      // to operate correctly into non-standard environments
      // @see https://github.com/webpack-contrib/style-loader/issues/177
      memo = Boolean(window && document && document.all && !window.atob);
    }

    return memo;
  };
}();

var getTarget = function getTarget() {
  var memo = {};
  return function memorize(target) {
    if (typeof memo[target] === 'undefined') {
      var styleTarget = document.querySelector(target); // Special case to return head of iframe instead of iframe itself

      if (window.HTMLIFrameElement && styleTarget instanceof window.HTMLIFrameElement) {
        try {
          // This will throw an exception if access to iframe is blocked
          // due to cross-origin restrictions
          styleTarget = styleTarget.contentDocument.head;
        } catch (e) {
          // istanbul ignore next
          styleTarget = null;
        }
      }

      memo[target] = styleTarget;
    }

    return memo[target];
  };
}();

var stylesInDom = [];

function getIndexByIdentifier(identifier) {
  var result = -1;

  for (var i = 0; i < stylesInDom.length; i++) {
    if (stylesInDom[i].identifier === identifier) {
      result = i;
      break;
    }
  }

  return result;
}

function modulesToDom(list, options) {
  var idCountMap = {};
  var identifiers = [];

  for (var i = 0; i < list.length; i++) {
    var item = list[i];
    var id = options.base ? item[0] + options.base : item[0];
    var count = idCountMap[id] || 0;
    var identifier = "".concat(id, " ").concat(count);
    idCountMap[id] = count + 1;
    var index = getIndexByIdentifier(identifier);
    var obj = {
      css: item[1],
      media: item[2],
      sourceMap: item[3]
    };

    if (index !== -1) {
      stylesInDom[index].references++;
      stylesInDom[index].updater(obj);
    } else {
      stylesInDom.push({
        identifier: identifier,
        updater: addStyle(obj, options),
        references: 1
      });
    }

    identifiers.push(identifier);
  }

  return identifiers;
}

function insertStyleElement(options) {
  var style = document.createElement('style');
  var attributes = options.attributes || {};

  if (typeof attributes.nonce === 'undefined') {
    var nonce =  true ? __webpack_require__.nc : null;

    if (nonce) {
      attributes.nonce = nonce;
    }
  }

  Object.keys(attributes).forEach(function (key) {
    style.setAttribute(key, attributes[key]);
  });

  if (typeof options.insert === 'function') {
    options.insert(style);
  } else {
    var target = getTarget(options.insert || 'head');

    if (!target) {
      throw new Error("Couldn't find a style target. This probably means that the value for the 'insert' parameter is invalid.");
    }

    target.appendChild(style);
  }

  return style;
}

function removeStyleElement(style) {
  // istanbul ignore if
  if (style.parentNode === null) {
    return false;
  }

  style.parentNode.removeChild(style);
}
/* istanbul ignore next  */


var replaceText = function replaceText() {
  var textStore = [];
  return function replace(index, replacement) {
    textStore[index] = replacement;
    return textStore.filter(Boolean).join('\n');
  };
}();

function applyToSingletonTag(style, index, remove, obj) {
  var css = remove ? '' : obj.media ? "@media ".concat(obj.media, " {").concat(obj.css, "}") : obj.css; // For old IE

  /* istanbul ignore if  */

  if (style.styleSheet) {
    style.styleSheet.cssText = replaceText(index, css);
  } else {
    var cssNode = document.createTextNode(css);
    var childNodes = style.childNodes;

    if (childNodes[index]) {
      style.removeChild(childNodes[index]);
    }

    if (childNodes.length) {
      style.insertBefore(cssNode, childNodes[index]);
    } else {
      style.appendChild(cssNode);
    }
  }
}

function applyToTag(style, options, obj) {
  var css = obj.css;
  var media = obj.media;
  var sourceMap = obj.sourceMap;

  if (media) {
    style.setAttribute('media', media);
  } else {
    style.removeAttribute('media');
  }

  if (sourceMap && typeof btoa !== 'undefined') {
    css += "\n/*# sourceMappingURL=data:application/json;base64,".concat(btoa(unescape(encodeURIComponent(JSON.stringify(sourceMap)))), " */");
  } // For old IE

  /* istanbul ignore if  */


  if (style.styleSheet) {
    style.styleSheet.cssText = css;
  } else {
    while (style.firstChild) {
      style.removeChild(style.firstChild);
    }

    style.appendChild(document.createTextNode(css));
  }
}

var singleton = null;
var singletonCounter = 0;

function addStyle(obj, options) {
  var style;
  var update;
  var remove;

  if (options.singleton) {
    var styleIndex = singletonCounter++;
    style = singleton || (singleton = insertStyleElement(options));
    update = applyToSingletonTag.bind(null, style, styleIndex, false);
    remove = applyToSingletonTag.bind(null, style, styleIndex, true);
  } else {
    style = insertStyleElement(options);
    update = applyToTag.bind(null, style, options);

    remove = function remove() {
      removeStyleElement(style);
    };
  }

  update(obj);
  return function updateStyle(newObj) {
    if (newObj) {
      if (newObj.css === obj.css && newObj.media === obj.media && newObj.sourceMap === obj.sourceMap) {
        return;
      }

      update(obj = newObj);
    } else {
      remove();
    }
  };
}

module.exports = function (list, options) {
  options = options || {}; // Force single-tag solution on IE6-9, which has a hard limit on the # of <style>
  // tags it will allow on a page

  if (!options.singleton && typeof options.singleton !== 'boolean') {
    options.singleton = isOldIE();
  }

  list = list || [];
  var lastIdentifiers = modulesToDom(list, options);
  return function update(newList) {
    newList = newList || [];

    if (Object.prototype.toString.call(newList) !== '[object Array]') {
      return;
    }

    for (var i = 0; i < lastIdentifiers.length; i++) {
      var identifier = lastIdentifiers[i];
      var index = getIndexByIdentifier(identifier);
      stylesInDom[index].references--;
    }

    var newLastIdentifiers = modulesToDom(newList, options);

    for (var _i = 0; _i < lastIdentifiers.length; _i++) {
      var _identifier = lastIdentifiers[_i];

      var _index = getIndexByIdentifier(_identifier);

      if (stylesInDom[_index].references === 0) {
        stylesInDom[_index].updater();

        stylesInDom.splice(_index, 1);
      }
    }

    lastIdentifiers = newLastIdentifiers;
  };
};

/***/ }),
/* 5 */
/***/ (function(module, exports, __webpack_require__) {

// Imports
var ___CSS_LOADER_API_IMPORT___ = __webpack_require__(6);
exports = ___CSS_LOADER_API_IMPORT___(false);
// Module
exports.push([module.i, ".aweber-placeholder {\n\tmargin: 0;\n\tpadding: 1em;\n\tmin-height: 150px;\n\ttext-align: center;\n\tfont-size: 13px;\n\ttop: 0;\n\theight: 100%;\n\toverflow: auto;\n\tbackground-color: #8b8b941a;\n\tfont-family: -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Oxygen-Sans,Ubuntu,Cantarell,Helvetica Neue,sans-serif;\n}\n.aweber-placeholer-label {\n\tfont-weight: 600;\n\tmargin-bottom: 5px;\n\tfont-size: 15px;\n\tfont-family: sans-serif;\n}\n.aweber-dropdown {\n\twidth: 70% !important;\n\tfont-size: 14px !important;\n    line-height: 2 !important;\n    color: #2c3338 !important;\n    border-color: #8c8f94 !important;\n    box-shadow: none !important;\n    border-radius: 3px !important;\n    padding: 0 24px 0 8px !important;\n    min-height: 30px !important;\n    background-size: 16px 16px !important;\n    cursor: pointer !important;\n    vertical-align: middle !important;\n}\n.aweber-button {\n\tfont-size: 15px;\n\tcolor: #555;\n\tbackground-color: #f7f7f7;\n\tborder: 1px solid #cccccc;\n\tborder-radius: 5px;\n\tpadding: 5px 15px;\n\t-moz-transition: all 0.8s;\n\t-webkit-transition: all 0.8s;\n\ttransition: all 0.8s;\n\tbox-shadow: inset 0 -1px 0 #ccc;\n\tmargin-left: 10px;\n\tcursor: pointer;\n\tvertical-align: middle;\n}\n.aweber-placeholder-image {\n\twidth: 25px;\n    display: inline-block;\n    margin-top: 10px;\n    vertical-align: middle;\n}\n.aweber-placeholder-image img {\n\twidth: 25px;\n}\n.aweber-placeholder-text {\n\tdisplay: inline-block;\n    vertical-align: middle;\n    margin-left: 10px;\n}\n.aweber-shortcode-label {\n\tfont-size: 15px;\n    margin-right: 10px;\n    font-weight: bold;\n}\n.aweber-shortcode-label-content {\n\tfont-size: 14px;\n}\n.aweber-small-btn {\n\tpadding: .25rem .5rem;\n    line-height: 1.5;\n    border-radius: .2rem;\n}\n.aweber-error-label {\n\tfont-size: 10px;\n    display: block;\n    text-align: center;\n    color: #ec1111;\n}\n.aweber-block-error {\n\tdisplay: inline;\n    padding: .2em .6em .3em;\n    font-weight: 700;\n    line-height: 1;\n    color: #fff;\n    vertical-align: baseline;\n    border-radius: .25em;\n    background-color: #d9534f;\n    vertical-align: middle;\n}\n.aweber-block-error a, .aweber-block-error a:hover {\n\tcolor: #fff !important;\n}\n", ""]);
// Exports
module.exports = exports;


/***/ }),
/* 6 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/*
  MIT License http://www.opensource.org/licenses/mit-license.php
  Author Tobias Koppers @sokra
*/
// css base code, injected by the css-loader
// eslint-disable-next-line func-names
module.exports = function (useSourceMap) {
  var list = []; // return the list of modules as css string

  list.toString = function toString() {
    return this.map(function (item) {
      var content = cssWithMappingToString(item, useSourceMap);

      if (item[2]) {
        return "@media ".concat(item[2], " {").concat(content, "}");
      }

      return content;
    }).join('');
  }; // import a list of modules into the list
  // eslint-disable-next-line func-names


  list.i = function (modules, mediaQuery, dedupe) {
    if (typeof modules === 'string') {
      // eslint-disable-next-line no-param-reassign
      modules = [[null, modules, '']];
    }

    var alreadyImportedModules = {};

    if (dedupe) {
      for (var i = 0; i < this.length; i++) {
        // eslint-disable-next-line prefer-destructuring
        var id = this[i][0];

        if (id != null) {
          alreadyImportedModules[id] = true;
        }
      }
    }

    for (var _i = 0; _i < modules.length; _i++) {
      var item = [].concat(modules[_i]);

      if (dedupe && alreadyImportedModules[item[0]]) {
        // eslint-disable-next-line no-continue
        continue;
      }

      if (mediaQuery) {
        if (!item[2]) {
          item[2] = mediaQuery;
        } else {
          item[2] = "".concat(mediaQuery, " and ").concat(item[2]);
        }
      }

      list.push(item);
    }
  };

  return list;
};

function cssWithMappingToString(item, useSourceMap) {
  var content = item[1] || ''; // eslint-disable-next-line prefer-destructuring

  var cssMapping = item[3];

  if (!cssMapping) {
    return content;
  }

  if (useSourceMap && typeof btoa === 'function') {
    var sourceMapping = toComment(cssMapping);
    var sourceURLs = cssMapping.sources.map(function (source) {
      return "/*# sourceURL=".concat(cssMapping.sourceRoot || '').concat(source, " */");
    });
    return [content].concat(sourceURLs).concat([sourceMapping]).join('\n');
  }

  return [content].join('\n');
} // Adapted from convert-source-map (MIT)


function toComment(sourceMap) {
  // eslint-disable-next-line no-undef
  var base64 = btoa(unescape(encodeURIComponent(JSON.stringify(sourceMap))));
  var data = "sourceMappingURL=data:application/json;charset=utf-8;base64,".concat(base64);
  return "/*# ".concat(data, " */");
}

/***/ })
/******/ ]);