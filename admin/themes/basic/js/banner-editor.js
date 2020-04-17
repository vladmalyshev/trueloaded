var bannerEditor =
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
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
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
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./backend/banner-editor/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "../modules/draggablePopup.js":
/*!************************************!*\
  !*** ../modules/draggablePopup.js ***!
  \************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = (function (content, op) {
  var options = $.extend({
    heading: '',
    buttons: [],
    beforeRemove: function beforeRemove() {
      return true;
    }
  }, op);
  var popupDraggable = $('<div class="popup-draggable"></div>');
  $('body').append(popupDraggable);
  var close = $('<div class="pop-up-close"></div>');
  popupDraggable.append(close);

  if (options.heading) {
    var headingWrap = $('<div class="popup-heading"></div>');
    headingWrap.append(options.heading);
    popupDraggable.append(headingWrap);
  }

  var contentWrap = $('<div class="popup-content pop-mess-cont"></div>');
  contentWrap.append(content);
  popupDraggable.append(contentWrap);

  if (options.buttons && options.buttons.length > 0) {
    var buttonsWrap = $('<div class="popup-buttons"></div>');
    options.buttons.forEach(function (item) {
      buttonsWrap.append(item);
    });
    popupDraggable.append(buttonsWrap);
  }

  popupDraggable.css({
    left: ($(window).width() - popupDraggable.width()) / 2,
    top: $(window).scrollTop() + 200
  });
  close.on('click', function () {
    options.beforeRemove();
    popupDraggable.remove();
  });
  var handle = {};

  if ($('.popup-heading', popupDraggable).length) {
    handle = {
      handle: '.popup-heading'
    };
  }

  popupDraggable.draggable(handle);
  return popupDraggable;
});

/***/ }),

/***/ "./backend/banner-editor/index.js":
/*!****************************************!*\
  !*** ./backend/banner-editor/index.js ***!
  \****************************************/
/*! exports provided: init, bannerEdit */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "init", function() { return init; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "bannerEdit", function() { return bannerEdit; });
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./style.scss */ "./backend/banner-editor/style.scss");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_style_scss__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var src_draggablePopup__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! src/draggablePopup */ "../modules/draggablePopup.js");
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */


function init(incomingData) {
  if (!window.bannerEditor) return false;
  var data = window.bannerEditor.data = incomingData;
  data.jObjects = {
    svgEditorIframe: $('.svg-editor-iframe'),
    saveButton: $('.btn-save-boxes'),
    backButton: $('.btm-back')
  };
  iframeHeight();
  $(window).on('resize', iframeHeight);
  data.editorPromise = new Promise(function (resolve, reject) {
    document.getElementById('svg_editor_frame').onload = function () {
      var frame;

      for (var i = 0; i < 10; i++) {
        if (window.frames[i].frameElement.id === 'svg_editor_frame') {
          frame = window.frames[i];
          break;
        }
      }

      if (!frame) reject("SVG Editor hasn't loaded");
      data.frame = frame;
      data.svgEditor = frame.svgEditor.svgEditor;
      resolve(frame.svgEditor.svgEditor);
    };
  });
  uploadBanner();
  backButton();
  saveButton();
}
function bannerEdit(incomingData) {
  if (!window.bannerEditor) return false;
  var data = window.bannerEditor.data = incomingData;
  var tr = data.tr;
  var formChanged = false;
  var mainForm = $('#save_banner_form');
  mainForm.on('change', function () {
    formChanged = true;
  });
  $('.btn-edit-svg').on('click', function () {
    var editorUrl = $(this).data('href');

    if (!formChanged) {
      window.location = editorUrl;
      return;
    }

    var btnSave = $("<span class=\"btn btn-save\">".concat(tr.IMAGE_SAVE, "</span>"));
    var btnNotSave = $("<span class=\"btn btn-save\">".concat(tr.NOT_SAVE, "</span>"));
    var btnCancel = $("<span class=\"btn btn-cancel\">".concat(tr.IMAGE_CANCEL, "</span>"));
    var html = $("<div>".concat(tr.CHANGED_DATA_ON_PAGE, "</div>"));
    var popup = Object(src_draggablePopup__WEBPACK_IMPORTED_MODULE_1__["default"])(html, {
      heading: tr.GO_TO_BANNER_EDITOR,
      buttons: [btnSave, btnCancel, btnNotSave]
    });
    btnSave.on('click', function () {
      $.post(mainForm.attr('action'), mainForm.serialize(), function (data, status) {
        if (status === "success") {
          window.location = editorUrl;
        } else {
          alert("Request error.");
        }
      }, "html");
    });
    btnNotSave.on('click', function () {
      window.location = editorUrl;
    });
    btnCancel.on('click', function () {
      popup.remove();
    });
  });

  if (data.setLanguage) {
    $('.nav a[href="#tab_2"]').trigger('click');
    $(".nav a[data-id=\"".concat(data.setLanguage, "\"]")).trigger('click');
  }
}

function iframeHeight() {
  var data = window.bannerEditor.data;
  var obj = data.jObjects;
  var height = $(window).height() - 200;
  obj.svgEditorIframe.css({
    height: height
  });
}

function uploadBanner() {
  var data = window.bannerEditor.data;
  data.editorPromise.then(function (svgEditor) {
    svgEditor.bannerUploaded = new Promise(function (resolve) {
      $.get('banner_manager/get-svg', {
        banners_id: data.banners_id,
        language_id: data.language_id
      }, function (d) {
        svgEditor.loadFromString(d);
        resolve();
      });
    });
  }).catch(function (error) {
    console.error(new Error(error));
  });
}

function backButton() {
  var data = window.bannerEditor.data;
  var tr = data.tr;
  data.editorPromise.then(function (svgEditor) {
    data.jObjects.backButton.on('click', function () {
      var bannerEditUrl = "banner_manager/banneredit?banners_id=".concat(data.banners_id, "&language_id=").concat(data.language_id);

      if (!data.undoStackSize) {
        data.undoStackSize = 1;
      }

      if (svgEditor.canvas.undoMgr.getUndoStackSize() === data.undoStackSize) {
        window.location = bannerEditUrl;
        return;
      }

      var btnSave = $("<span class=\"btn btn-save\">".concat(tr.IMAGE_SAVE, "</span>"));
      var btnNotSave = $("<span class=\"btn btn-save\">".concat(tr.NOT_SAVE, "</span>"));
      var btnCancel = $("<span class=\"btn btn-cancel\">".concat(tr.IMAGE_CANCEL, "</span>"));
      var html = $("<div>".concat(tr.YOU_CHANGED_BANNER, "</div>"));
      var popup = Object(src_draggablePopup__WEBPACK_IMPORTED_MODULE_1__["default"])(html, {
        heading: tr.GO_TO_BANNER_PAGE,
        buttons: [btnSave, btnCancel, btnNotSave]
      });
      btnSave.on('click', function () {
        saveSvg();
        window.location = bannerEditUrl;
      });
      btnNotSave.on('click', function () {
        window.location = bannerEditUrl;
      });
      btnCancel.on('click', function () {
        popup.remove();
      });
    });
  }).catch(function (error) {
    console.error(new Error(error));
  });
}

function saveButton() {
  var data = window.bannerEditor.data;
  data.editorPromise.then(function () {
    data.jObjects.saveButton.on('click', saveSvg);
  }).catch(function (error) {
    console.error(new Error(error));
  });
}

function saveSvg() {
  var data = window.bannerEditor.data;
  if (!data.svgEditor) return false;
  var svgString = data.svgEditor.canvas.getSvgString();
  svgString = svgString.replace(/width="([.0-9]+)" height="([.0-9]+)"/, 'viewBox="0 0 $1 $2"');
  data.undoStackSize = data.svgEditor.canvas.undoMgr.getUndoStackSize();
  $.post('banner_manager/save-svg', {
    banners_id: data.banners_id,
    language_id: data.language_id,
    svg: svgString
  }, function (response) {
    var popup = Object(src_draggablePopup__WEBPACK_IMPORTED_MODULE_1__["default"])("<div class=\"alert-message\">".concat(response, "</div>"));
    setTimeout(function () {
      popup.remove();
    }, 1000);
  });
}

/***/ }),

/***/ "./backend/banner-editor/style.scss":
/*!******************************************!*\
  !*** ./backend/banner-editor/style.scss ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ })

/******/ });
//# sourceMappingURL=banner-editor.js.map