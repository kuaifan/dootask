/**
 * Copyright (c) Tiny Technologies, Inc. All rights reserved.
 * Licensed under the LGPL or a commercial license.
 * For LGPL see License.txt in the project root for license information.
 * For commercial licenses see https://www.tiny.cloud/
 *
 * Version: 5.3.0 (2020-05-21)
 */
(function (domGlobals) {
    'use strict';

    var global = tinymce.util.Tools.resolve('tinymce.PluginManager');

    var global$1 = tinymce.util.Tools.resolve('tinymce.util.VK');

    var typeOf = function (x) {
      var t = typeof x;
      if (x === null) {
        return 'null';
      } else if (t === 'object' && (Array.prototype.isPrototypeOf(x) || x.constructor && x.constructor.name === 'Array')) {
        return 'array';
      } else if (t === 'object' && (String.prototype.isPrototypeOf(x) || x.constructor && x.constructor.name === 'String')) {
        return 'string';
      } else {
        return t;
      }
    };
    var isType = function (type) {
      return function (value) {
        return typeOf(value) === type;
      };
    };
    var isSimpleType = function (type) {
      return function (value) {
        return typeof value === type;
      };
    };
    var eq = function (t) {
      return function (a) {
        return t === a;
      };
    };
    var isString = isType('string');
    var isArray = isType('array');
    var isNull = eq(null);
    var isBoolean = isSimpleType('boolean');
    var isFunction = isSimpleType('function');

    var assumeExternalTargets = function (editor) {
      var externalTargets = editor.getParam('link_assume_external_targets', false);
      if (isBoolean(externalTargets) && externalTargets) {
        return 1;
      } else if (isString(externalTargets) && (externalTargets === 'http' || externalTargets === 'https')) {
        return externalTargets;
      }
      return 0;
    };
    var hasContextToolbar = function (editor) {
      return editor.getParam('link_context_toolbar', false, 'boolean');
    };
    var getLinkList = function (editor) {
      return editor.getParam('link_list');
    };
    var getDefaultLinkTarget = function (editor) {
      return editor.getParam('default_link_target');
    };
    var getTargetList = function (editor) {
      return editor.getParam('target_list', true);
    };
    var getRelList = function (editor) {
      return editor.getParam('rel_list', [], 'array');
    };
    var getLinkClassList = function (editor) {
      return editor.getParam('link_class_list', [], 'array');
    };
    var shouldShowLinkTitle = function (editor) {
      return editor.getParam('link_title', true, 'boolean');
    };
    var allowUnsafeLinkTarget = function (editor) {
      return editor.getParam('allow_unsafe_link_target', false, 'boolean');
    };
    var useQuickLink = function (editor) {
      return editor.getParam('link_quicklink', false, 'boolean');
    };
    var getDefaultLinkProtocol = function (editor) {
      return editor.getParam('link_default_protocol', 'http', 'string');
    };

    var appendClickRemove = function (link, evt) {
      domGlobals.document.body.appendChild(link);
      link.dispatchEvent(evt);
      domGlobals.document.body.removeChild(link);
    };
    var open = function (url) {
      var link = domGlobals.document.createElement('a');
      link.target = '_blank';
      link.href = url;
      link.rel = 'noreferrer noopener';
      var evt = domGlobals.document.createEvent('MouseEvents');
      evt.initMouseEvent('click', true, true, domGlobals.window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
      appendClickRemove(link, evt);
    };

    var __assign = function () {
      __assign = Object.assign || function __assign(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
          s = arguments[i];
          for (var p in s)
            if (Object.prototype.hasOwnProperty.call(s, p))
              t[p] = s[p];
        }
        return t;
      };
      return __assign.apply(this, arguments);
    };

    var noop = function () {
    };
    var constant = function (value) {
      return function () {
        return value;
      };
    };
    var never = constant(false);
    var always = constant(true);

    var none = function () {
      return NONE;
    };
    var NONE = function () {
      var eq = function (o) {
        return o.isNone();
      };
      var call = function (thunk) {
        return thunk();
      };
      var id = function (n) {
        return n;
      };
      var me = {
        fold: function (n, _s) {
          return n();
        },
        is: never,
        isSome: never,
        isNone: always,
        getOr: id,
        getOrThunk: call,
        getOrDie: function (msg) {
          throw new Error(msg || 'error: getOrDie called on none.');
        },
        getOrNull: constant(null),
        getOrUndefined: constant(undefined),
        or: id,
        orThunk: call,
        map: none,
        each: noop,
        bind: none,
        exists: never,
        forall: always,
        filter: none,
        equals: eq,
        equals_: eq,
        toArray: function () {
          return [];
        },
        toString: constant('none()')
      };
      return me;
    }();
    var some = function (a) {
      var constant_a = constant(a);
      var self = function () {
        return me;
      };
      var bind = function (f) {
        return f(a);
      };
      var me = {
        fold: function (n, s) {
          return s(a);
        },
        is: function (v) {
          return a === v;
        },
        isSome: always,
        isNone: never,
        getOr: constant_a,
        getOrThunk: constant_a,
        getOrDie: constant_a,
        getOrNull: constant_a,
        getOrUndefined: constant_a,
        or: self,
        orThunk: self,
        map: function (f) {
          return some(f(a));
        },
        each: function (f) {
          f(a);
        },
        bind: bind,
        exists: bind,
        forall: bind,
        filter: function (f) {
          return f(a) ? me : NONE;
        },
        toArray: function () {
          return [a];
        },
        toString: function () {
          return 'some(' + a + ')';
        },
        equals: function (o) {
          return o.is(a);
        },
        equals_: function (o, elementEq) {
          return o.fold(never, function (b) {
            return elementEq(a, b);
          });
        }
      };
      return me;
    };
    var from = function (value) {
      return value === null || value === undefined ? NONE : some(value);
    };
    var Option = {
      some: some,
      none: none,
      from: from
    };

    var nativeIndexOf = Array.prototype.indexOf;
    var nativePush = Array.prototype.push;
    var rawIndexOf = function (ts, t) {
      return nativeIndexOf.call(ts, t);
    };
    var contains = function (xs, x) {
      return rawIndexOf(xs, x) > -1;
    };
    var map = function (xs, f) {
      var len = xs.length;
      var r = new Array(len);
      for (var i = 0; i < len; i++) {
        var x = xs[i];
        r[i] = f(x, i);
      }
      return r;
    };
    var each = function (xs, f) {
      for (var i = 0, len = xs.length; i < len; i++) {
        var x = xs[i];
        f(x, i);
      }
    };
    var foldl = function (xs, f, acc) {
      each(xs, function (x) {
        acc = f(acc, x);
      });
      return acc;
    };
    var flatten = function (xs) {
      var r = [];
      for (var i = 0, len = xs.length; i < len; ++i) {
        if (!isArray(xs[i])) {
          throw new Error('Arr.flatten item ' + i + ' was not an array, input: ' + xs);
        }
        nativePush.apply(r, xs[i]);
      }
      return r;
    };
    var bind = function (xs, f) {
      return flatten(map(xs, f));
    };
    var findMap = function (arr, f) {
      for (var i = 0; i < arr.length; i++) {
        var r = f(arr[i], i);
        if (r.isSome()) {
          return r;
        }
      }
      return Option.none();
    };

    var keys = Object.keys;
    var each$1 = function (obj, f) {
      var props = keys(obj);
      for (var k = 0, len = props.length; k < len; k++) {
        var i = props[k];
        var x = obj[i];
        f(x, i);
      }
    };
    var objAcc = function (r) {
      return function (x, i) {
        r[i] = x;
      };
    };
    var internalFilter = function (obj, pred, onTrue, onFalse) {
      var r = {};
      each$1(obj, function (x, i) {
        (pred(x, i) ? onTrue : onFalse)(x, i);
      });
      return r;
    };
    var filter = function (obj, pred) {
      var t = {};
      internalFilter(obj, pred, objAcc(t), noop);
      return t;
    };

    var global$2 = tinymce.util.Tools.resolve('tinymce.util.Tools');

    var hasRtcPlugin = function (editor) {
      if (/(^|[ ,])rtc([, ]|$)/.test(editor.settings.plugins) && global.get('rtc')) {
        return true;
      } else {
        return false;
      }
    };

    var hasProtocol = function (url) {
      return /^\w+:/i.test(url);
    };
    var getHref = function (elm) {
      var href = elm.getAttribute('data-mce-href');
      return href ? href : elm.getAttribute('href');
    };
    var applyRelTargetRules = function (rel, isUnsafe) {
      var rules = ['noopener'];
      var rels = rel ? rel.split(/\s+/) : [];
      var toString = function (rels) {
        return global$2.trim(rels.sort().join(' '));
      };
      var addTargetRules = function (rels) {
        rels = removeTargetRules(rels);
        return rels.length > 0 ? rels.concat(rules) : rules;
      };
      var removeTargetRules = function (rels) {
        return rels.filter(function (val) {
          return global$2.inArray(rules, val) === -1;
        });
      };
      var newRels = isUnsafe ? addTargetRules(rels) : removeTargetRules(rels);
      return newRels.length > 0 ? toString(newRels) : '';
    };
    var trimCaretContainers = function (text) {
      return text.replace(/\uFEFF/g, '');
    };
    var getAnchorElement = function (editor, selectedElm) {
      selectedElm = selectedElm || editor.selection.getNode();
      if (isImageFigure(selectedElm)) {
        return editor.dom.select('a[href]', selectedElm)[0];
      } else {
        return editor.dom.getParent(selectedElm, 'a[href]');
      }
    };
    var getAnchorText = function (selection, anchorElm) {
      var text = anchorElm ? anchorElm.innerText || anchorElm.textContent : selection.getContent({ format: 'text' });
      return trimCaretContainers(text);
    };
    var isLink = function (elm) {
      return elm && elm.nodeName === 'A' && !!getHref(elm);
    };
    var hasLinks = function (elements) {
      return global$2.grep(elements, isLink).length > 0;
    };
    var isOnlyTextSelected = function (html) {
      if (/</.test(html) && (!/^<a [^>]+>[^<]+<\/a>$/.test(html) || html.indexOf('href=') === -1)) {
        return false;
      }
      return true;
    };
    var isImageFigure = function (elm) {
      return elm && elm.nodeName === 'FIGURE' && /\bimage\b/i.test(elm.className);
    };
    var getLinkAttrs = function (data) {
      return foldl([
        'title',
        'rel',
        'class',
        'target'
      ], function (acc, key) {
        data[key].each(function (value) {
          acc[key] = value.length > 0 ? value : null;
        });
        return acc;
      }, { href: data.href });
    };
    var handleExternalTargets = function (href, assumeExternalTargets) {
      if ((assumeExternalTargets === 'http' || assumeExternalTargets === 'https') && !hasProtocol(href)) {
        return assumeExternalTargets + '://' + href;
      }
      return href;
    };
    var applyLinkOverrides = function (editor, linkAttrs) {
      var newLinkAttrs = __assign({}, linkAttrs);
      if (!(getRelList(editor).length > 0) && allowUnsafeLinkTarget(editor) === false) {
        var newRel = applyRelTargetRules(newLinkAttrs.rel, newLinkAttrs.target === '_blank');
        newLinkAttrs.rel = newRel ? newRel : null;
      }
      if (Option.from(newLinkAttrs.target).isNone() && getTargetList(editor) === false) {
        newLinkAttrs.target = getDefaultLinkTarget(editor);
      }
      newLinkAttrs.href = handleExternalTargets(newLinkAttrs.href, assumeExternalTargets(editor));
      return newLinkAttrs;
    };
    var updateLink = function (editor, anchorElm, text, linkAttrs) {
      text.each(function (text) {
        if (anchorElm.hasOwnProperty('innerText')) {
          anchorElm.innerText = text;
        } else {
          anchorElm.textContent = text;
        }
      });
      editor.dom.setAttribs(anchorElm, linkAttrs);
      editor.selection.select(anchorElm);
    };
    var createLink = function (editor, selectedElm, text, linkAttrs) {
      if (isImageFigure(selectedElm)) {
        linkImageFigure(editor, selectedElm, linkAttrs);
      } else {
        text.fold(function () {
          editor.execCommand('mceInsertLink', false, linkAttrs);
        }, function (text) {
          editor.insertContent(editor.dom.createHTML('a', linkAttrs, editor.dom.encode(text)));
        });
      }
    };
    var linkDomMutation = function (editor, attachState, data) {
      var selectedElm = editor.selection.getNode();
      var anchorElm = getAnchorElement(editor, selectedElm);
      var linkAttrs = applyLinkOverrides(editor, getLinkAttrs(data));
      editor.undoManager.transact(function () {
        if (data.href === attachState.href) {
          attachState.attach();
        }
        if (anchorElm) {
          editor.focus();
          updateLink(editor, anchorElm, data.text, linkAttrs);
        } else {
          createLink(editor, selectedElm, data.text, linkAttrs);
        }
      });
    };
    var unlinkDomMutation = function (editor) {
      editor.undoManager.transact(function () {
        var node = editor.selection.getNode();
        if (isImageFigure(node)) {
          unlinkImageFigure(editor, node);
        } else {
          var anchorElm = editor.dom.getParent(node, 'a[href]', editor.getBody());
          if (anchorElm) {
            editor.dom.remove(anchorElm, true);
          }
        }
        editor.focus();
      });
    };
    var unwrapOptions = function (data) {
      var cls = data.class, href = data.href, rel = data.rel, target = data.target, text = data.text, title = data.title;
      return filter({
        class: cls.getOrNull(),
        href: href,
        rel: rel.getOrNull(),
        target: target.getOrNull(),
        text: text.getOrNull(),
        title: title.getOrNull()
      }, function (v, _k) {
        return isNull(v) === false;
      });
    };
    var link = function (editor, attachState, data) {
      hasRtcPlugin(editor) ? editor.execCommand('createlink', false, unwrapOptions(data)) : linkDomMutation(editor, attachState, data);
    };
    var unlink = function (editor) {
      hasRtcPlugin(editor) ? editor.execCommand('unlink') : unlinkDomMutation(editor);
    };
    var unlinkImageFigure = function (editor, fig) {
      var img = editor.dom.select('img', fig)[0];
      if (img) {
        var a = editor.dom.getParents(img, 'a[href]', fig)[0];
        if (a) {
          a.parentNode.insertBefore(img, a);
          editor.dom.remove(a);
        }
      }
    };
    var linkImageFigure = function (editor, fig, attrs) {
      var img = editor.dom.select('img', fig)[0];
      if (img) {
        var a = editor.dom.create('a', attrs);
        img.parentNode.insertBefore(a, img);
        a.appendChild(img);
      }
    };

    var cat = function (arr) {
      var r = [];
      var push = function (x) {
        r.push(x);
      };
      for (var i = 0; i < arr.length; i++) {
        arr[i].each(push);
      }
      return r;
    };

    var getValue = function (item) {
      return isString(item.value) ? item.value : '';
    };
    var sanitizeList = function (list, extractValue) {
      var out = [];
      global$2.each(list, function (item) {
        var text = isString(item.text) ? item.text : isString(item.title) ? item.title : '';
        if (item.menu !== undefined) ; else {
          var value = extractValue(item);
          out.push({
            text: text,
            value: value
          });
        }
      });
      return out;
    };
    var sanitizeWith = function (extracter) {
      if (extracter === void 0) {
        extracter = getValue;
      }
      return function (list) {
        return Option.from(list).map(function (list) {
          return sanitizeList(list, extracter);
        });
      };
    };
    var sanitize = function (list) {
      return sanitizeWith(getValue)(list);
    };
    var createUi = function (name, label) {
      return function (items) {
        return {
          name: name,
          type: 'selectbox',
          label: label,
          items: items
        };
      };
    };
    var ListOptions = {
      sanitize: sanitize,
      sanitizeWith: sanitizeWith,
      createUi: createUi,
      getValue: getValue
    };

    var Cell = function (initial) {
      var value = initial;
      var get = function () {
        return value;
      };
      var set = function (v) {
        value = v;
      };
      return {
        get: get,
        set: set
      };
    };

    var findTextByValue = function (value, catalog) {
      return findMap(catalog, function (item) {
        return Option.some(item).filter(function (i) {
          return i.value === value;
        });
      });
    };
    var getDelta = function (persistentText, fieldName, catalog, data) {
      var value = data[fieldName];
      var hasPersistentText = persistentText.length > 0;
      return value !== undefined ? findTextByValue(value, catalog).map(function (i) {
        return {
          url: {
            value: i.value,
            meta: {
              text: hasPersistentText ? persistentText : i.text,
              attach: noop
            }
          },
          text: hasPersistentText ? persistentText : i.text
        };
      }) : Option.none();
    };
    var findCatalog = function (settings, fieldName) {
      if (fieldName === 'link') {
        return settings.catalogs.link;
      } else if (fieldName === 'anchor') {
        return settings.catalogs.anchor;
      } else {
        return Option.none();
      }
    };
    var init = function (initialData, linkSettings) {
      var persistentText = Cell(initialData.text);
      var onUrlChange = function (data) {
        if (persistentText.get().length <= 0) {
          var urlText = data.url.meta.text !== undefined ? data.url.meta.text : data.url.value;
          var urlTitle = data.url.meta.title !== undefined ? data.url.meta.title : '';
          return Option.some({
            text: urlText,
            title: urlTitle
          });
        } else {
          return Option.none();
        }
      };
      var onCatalogChange = function (data, change) {
        var catalog = findCatalog(linkSettings, change.name).getOr([]);
        return getDelta(persistentText.get(), change.name, catalog, data);
      };
      var onChange = function (getData, change) {
        if (change.name === 'url') {
          return onUrlChange(getData());
        } else if (contains([
            'anchor',
            'link'
          ], change.name)) {
          return onCatalogChange(getData(), change);
        } else if (change.name === 'text') {
          persistentText.set(getData().text);
          return Option.none();
        } else {
          return Option.none();
        }
      };
      return { onChange: onChange };
    };
    var DialogChanges = {
      init: init,
      getDelta: getDelta
    };

    var global$3 = tinymce.util.Tools.resolve('tinymce.util.Delay');

    var global$4 = tinymce.util.Tools.resolve('tinymce.util.Promise');

    var delayedConfirm = function (editor, message, callback) {
      var rng = editor.selection.getRng();
      global$3.setEditorTimeout(editor, function () {
        editor.windowManager.confirm(message, function (state) {
          editor.selection.setRng(rng);
          callback(state);
        });
      });
    };
    var tryEmailTransform = function (data) {
      var url = data.href;
      var suggestMailTo = url.indexOf('@') > 0 && url.indexOf('//') === -1 && url.indexOf('mailto:') === -1;
      return suggestMailTo ? Option.some({
        message: 'The URL you entered seems to be an email address. Do you want to add the required mailto: prefix?',
        preprocess: function (oldData) {
          return __assign(__assign({}, oldData), { href: 'mailto:' + url });
        }
      }) : Option.none();
    };
    var tryProtocolTransform = function (assumeExternalTargets, defaultLinkProtocol) {
      return function (data) {
        var url = data.href;
        var suggestProtocol = assumeExternalTargets === 1 && !hasProtocol(url) || assumeExternalTargets === 0 && /^\s*www[\.|\d\.]/i.test(url);
        return suggestProtocol ? Option.some({
          message: 'The URL you entered seems to be an external link. Do you want to add the required ' + defaultLinkProtocol + ':// prefix?',
          preprocess: function (oldData) {
            return __assign(__assign({}, oldData), { href: defaultLinkProtocol + '://' + url });
          }
        }) : Option.none();
      };
    };
    var preprocess = function (editor, data) {
      return findMap([
        tryEmailTransform,
        tryProtocolTransform(assumeExternalTargets(editor), getDefaultLinkProtocol(editor))
      ], function (f) {
        return f(data);
      }).fold(function () {
        return global$4.resolve(data);
      }, function (transform) {
        return new global$4(function (callback) {
          delayedConfirm(editor, transform.message, function (state) {
            callback(state ? transform.preprocess(data) : data);
          });
        });
      });
    };
    var DialogConfirms = { preprocess: preprocess };

    var getAnchors = function (editor) {
      var anchorNodes = editor.dom.select('a:not([href])');
      var anchors = bind(anchorNodes, function (anchor) {
        var id = anchor.name || anchor.id;
        return id ? [{
            text: id,
            value: '#' + id
          }] : [];
      });
      return anchors.length > 0 ? Option.some([{
          text: 'None',
          value: ''
        }].concat(anchors)) : Option.none();
    };
    var AnchorListOptions = { getAnchors: getAnchors };

    var getClasses = function (editor) {
      var list = getLinkClassList(editor);
      if (list.length > 0) {
        return ListOptions.sanitize(list);
      }
      return Option.none();
    };
    var ClassListOptions = { getClasses: getClasses };

    var global$5 = tinymce.util.Tools.resolve('tinymce.util.XHR');

    var parseJson = function (text) {
      try {
        return Option.some(JSON.parse(text));
      } catch (err) {
        return Option.none();
      }
    };
    var getLinks = function (editor) {
      var extractor = function (item) {
        return editor.convertURL(item.value || item.url, 'href');
      };
      var linkList = getLinkList(editor);
      return new global$4(function (callback) {
        if (isString(linkList)) {
          global$5.send({
            url: linkList,
            success: function (text) {
              return callback(parseJson(text));
            },
            error: function (_) {
              return callback(Option.none());
            }
          });
        } else if (isFunction(linkList)) {
          linkList(function (output) {
            return callback(Option.some(output));
          });
        } else {
          callback(Option.from(linkList));
        }
      }).then(function (optItems) {
        return optItems.bind(ListOptions.sanitizeWith(extractor)).map(function (items) {
          if (items.length > 0) {
            return [{
                text: 'None',
                value: ''
              }].concat(items);
          } else {
            return items;
          }
        });
      });
    };
    var LinkListOptions = { getLinks: getLinks };

    var getRels = function (editor, initialTarget) {
      var list = getRelList(editor);
      if (list.length > 0) {
        var isTargetBlank_1 = initialTarget.is('_blank');
        var enforceSafe = allowUnsafeLinkTarget(editor) === false;
        var safeRelExtractor = function (item) {
          return applyRelTargetRules(ListOptions.getValue(item), isTargetBlank_1);
        };
        var sanitizer = enforceSafe ? ListOptions.sanitizeWith(safeRelExtractor) : ListOptions.sanitize;
        return sanitizer(list);
      }
      return Option.none();
    };
    var RelOptions = { getRels: getRels };

    var fallbacks = [
      {
        text: 'Current window',
        value: ''
      },
      {
        text: 'New window',
        value: '_blank'
      }
    ];
    var getTargets = function (editor) {
      var list = getTargetList(editor);
      if (isArray(list)) {
        return ListOptions.sanitize(list).orThunk(function () {
          return Option.some(fallbacks);
        });
      } else if (list === false) {
        return Option.none();
      }
      return Option.some(fallbacks);
    };
    var TargetOptions = { getTargets: getTargets };

    var nonEmptyAttr = function (dom, elem, name) {
      var val = dom.getAttrib(elem, name);
      return val !== null && val.length > 0 ? Option.some(val) : Option.none();
    };
    var extractFromAnchor = function (editor, anchor) {
      var dom = editor.dom;
      var onlyText = isOnlyTextSelected(editor.selection.getContent());
      var text = onlyText ? Option.some(getAnchorText(editor.selection, anchor)) : Option.none();
      var url = anchor ? Option.some(dom.getAttrib(anchor, 'href')) : Option.none();
      var target = anchor ? Option.from(dom.getAttrib(anchor, 'target')) : Option.none();
      var rel = nonEmptyAttr(dom, anchor, 'rel');
      var linkClass = nonEmptyAttr(dom, anchor, 'class');
      var title = nonEmptyAttr(dom, anchor, 'title');
      return {
        url: url,
        text: text,
        title: title,
        target: target,
        rel: rel,
        linkClass: linkClass
      };
    };
    var collect = function (editor, linkNode) {
      return LinkListOptions.getLinks(editor).then(function (links) {
        var anchor = extractFromAnchor(editor, linkNode);
        return {
          anchor: anchor,
          catalogs: {
            targets: TargetOptions.getTargets(editor),
            rels: RelOptions.getRels(editor, anchor.target),
            classes: ClassListOptions.getClasses(editor),
            anchor: AnchorListOptions.getAnchors(editor),
            link: links
          },
          optNode: Option.from(linkNode),
          flags: { titleEnabled: shouldShowLinkTitle(editor) }
        };
      });
    };
    var DialogInfo = { collect: collect };

    var handleSubmit = function (editor, info) {
      return function (api) {
        var data = api.getData();
        if (!data.url.value) {
          unlink(editor);
          api.close();
          return;
        }
        var getChangedValue = function (key) {
          return Option.from(data[key]).filter(function (value) {
            return !info.anchor[key].is(value);
          });
        };
        var changedData = {
          href: data.url.value,
          text: getChangedValue('text'),
          target: getChangedValue('target'),
          rel: getChangedValue('rel'),
          class: getChangedValue('linkClass'),
          title: getChangedValue('title')
        };
        var attachState = {
          href: data.url.value,
          attach: data.url.meta !== undefined && data.url.meta.attach ? data.url.meta.attach : function () {
          }
        };
        DialogConfirms.preprocess(editor, changedData).then(function (pData) {
          link(editor, attachState, pData);
        });
        api.close();
      };
    };
    var collectData = function (editor) {
      var anchorNode = getAnchorElement(editor);
      return DialogInfo.collect(editor, anchorNode);
    };
    var getInitialData = function (info, defaultTarget) {
      return {
        url: {
          value: info.anchor.url.getOr(''),
          meta: {
            attach: function () {
            },
            text: info.anchor.url.fold(function () {
              return '';
            }, function () {
              return info.anchor.text.getOr('');
            }),
            original: { value: info.anchor.url.getOr('') }
          }
        },
        text: info.anchor.text.getOr(''),
        title: info.anchor.title.getOr(''),
        anchor: info.anchor.url.getOr(''),
        link: info.anchor.url.getOr(''),
        rel: info.anchor.rel.getOr(''),
        target: info.anchor.target.or(defaultTarget).getOr(''),
        linkClass: info.anchor.linkClass.getOr('')
      };
    };
    var makeDialog = function (settings, onSubmit, editor) {
      var urlInput = [{
          name: 'url',
          type: 'urlinput',
          filetype: 'file',
          label: 'URL'
        }];
      var displayText = settings.anchor.text.map(function () {
        return {
          name: 'text',
          type: 'input',
          label: 'Text to display'
        };
      }).toArray();
      var titleText = settings.flags.titleEnabled ? [{
          name: 'title',
          type: 'input',
          label: 'Title'
        }] : [];
      var defaultTarget = Option.from(getDefaultLinkTarget(editor));
      var initialData = getInitialData(settings, defaultTarget);
      var dialogDelta = DialogChanges.init(initialData, settings);
      var catalogs = settings.catalogs;
      var body = {
        type: 'panel',
        items: flatten([
          urlInput,
          displayText,
          titleText,
          cat([
            catalogs.anchor.map(ListOptions.createUi('anchor', 'Anchors')),
            catalogs.rels.map(ListOptions.createUi('rel', 'Rel')),
            catalogs.targets.map(ListOptions.createUi('target', 'Open link in...')),
            catalogs.link.map(ListOptions.createUi('link', 'Link list')),
            catalogs.classes.map(ListOptions.createUi('linkClass', 'Class'))
          ])
        ])
      };
      return {
        title: 'Insert/Edit Link',
        size: 'normal',
        body: body,
        buttons: [
          {
            type: 'cancel',
            name: 'cancel',
            text: 'Cancel'
          },
          {
            type: 'submit',
            name: 'save',
            text: 'Save',
            primary: true
          }
        ],
        initialData: initialData,
        onChange: function (api, _a) {
          var name = _a.name;
          dialogDelta.onChange(api.getData, { name: name }).each(function (newData) {
            api.setData(newData);
          });
        },
        onSubmit: onSubmit
      };
    };
    var open$1 = function (editor) {
      var data = collectData(editor);
      data.then(function (info) {
        var onSubmit = handleSubmit(editor, info);
        return makeDialog(info, onSubmit, editor);
      }).then(function (spec) {
        editor.windowManager.open(spec);
      });
    };

    var getLink = function (editor, elm) {
      return editor.dom.getParent(elm, 'a[href]');
    };
    var getSelectedLink = function (editor) {
      return getLink(editor, editor.selection.getStart());
    };
    var hasOnlyAltModifier = function (e) {
      return e.altKey === true && e.shiftKey === false && e.ctrlKey === false && e.metaKey === false;
    };
    var gotoLink = function (editor, a) {
      if (a) {
        var href = getHref(a);
        if (/^#/.test(href)) {
          var targetEl = editor.$(href);
          if (targetEl.length) {
            editor.selection.scrollIntoView(targetEl[0], true);
          }
        } else {
          open(a.href);
        }
      }
    };
    var openDialog = function (editor) {
      return function () {
        open$1(editor);
      };
    };
    var gotoSelectedLink = function (editor) {
      return function () {
        gotoLink(editor, getSelectedLink(editor));
      };
    };
    var setupGotoLinks = function (editor) {
      editor.on('click', function (e) {
        var link = getLink(editor, e.target);
        if (link && global$1.metaKeyPressed(e)) {
          e.preventDefault();
          gotoLink(editor, link);
        }
      });
      editor.on('keydown', function (e) {
        var link = getSelectedLink(editor);
        if (link && e.keyCode === 13 && hasOnlyAltModifier(e)) {
          e.preventDefault();
          gotoLink(editor, link);
        }
      });
    };
    var toggleActiveState = function (editor) {
      return function (api) {
        var nodeChangeHandler = function (e) {
          return api.setActive(!editor.mode.isReadOnly() && !!getAnchorElement(editor, e.element));
        };
        editor.on('NodeChange', nodeChangeHandler);
        return function () {
          return editor.off('NodeChange', nodeChangeHandler);
        };
      };
    };
    var toggleEnabledState = function (editor) {
      return function (api) {
        var parents = editor.dom.getParents(editor.selection.getStart());
        api.setDisabled(!hasLinks(parents));
        var nodeChangeHandler = function (e) {
          return api.setDisabled(!hasLinks(e.parents));
        };
        editor.on('NodeChange', nodeChangeHandler);
        return function () {
          return editor.off('NodeChange', nodeChangeHandler);
        };
      };
    };

    var register = function (editor) {
      editor.addCommand('mceLink', function () {
        if (useQuickLink(editor)) {
          editor.fire('contexttoolbar-show', { toolbarKey: 'quicklink' });
        } else {
          openDialog(editor)();
        }
      });
    };

    var setup = function (editor) {
      editor.addShortcut('Meta+K', '', function () {
        editor.execCommand('mceLink');
      });
    };

    var setupButtons = function (editor) {
      editor.ui.registry.addToggleButton('link', {
        icon: 'link',
        tooltip: 'Insert/edit link',
        onAction: openDialog(editor),
        onSetup: toggleActiveState(editor)
      });
      editor.ui.registry.addButton('openlink', {
        icon: 'new-tab',
        tooltip: 'Open link',
        onAction: gotoSelectedLink(editor),
        onSetup: toggleEnabledState(editor)
      });
      editor.ui.registry.addButton('unlink', {
        icon: 'unlink',
        tooltip: 'Remove link',
        onAction: function () {
          return unlink(editor);
        },
        onSetup: toggleEnabledState(editor)
      });
    };
    var setupMenuItems = function (editor) {
      editor.ui.registry.addMenuItem('openlink', {
        text: 'Open link',
        icon: 'new-tab',
        onAction: gotoSelectedLink(editor),
        onSetup: toggleEnabledState(editor)
      });
      editor.ui.registry.addMenuItem('link', {
        icon: 'link',
        text: 'Link...',
        shortcut: 'Meta+K',
        onAction: openDialog(editor)
      });
      editor.ui.registry.addMenuItem('unlink', {
        icon: 'unlink',
        text: 'Remove link',
        onAction: function () {
          return unlink(editor);
        },
        onSetup: toggleEnabledState(editor)
      });
    };
    var setupContextMenu = function (editor) {
      var inLink = 'link unlink openlink';
      var noLink = 'link';
      editor.ui.registry.addContextMenu('link', {
        update: function (element) {
          return hasLinks(editor.dom.getParents(element, 'a')) ? inLink : noLink;
        }
      });
    };
    var setupContextToolbars = function (editor) {
      var collapseSelectionToEnd = function (editor) {
        editor.selection.collapse(false);
      };
      var onSetupLink = function (buttonApi) {
        var node = editor.selection.getNode();
        buttonApi.setDisabled(!getAnchorElement(editor, node));
        return function () {
        };
      };
      editor.ui.registry.addContextForm('quicklink', {
        launch: {
          type: 'contextformtogglebutton',
          icon: 'link',
          tooltip: 'Link',
          onSetup: toggleActiveState(editor)
        },
        label: 'Link',
        predicate: function (node) {
          return !!getAnchorElement(editor, node) && hasContextToolbar(editor);
        },
        initValue: function () {
          var elm = getAnchorElement(editor);
          return !!elm ? getHref(elm) : '';
        },
        commands: [
          {
            type: 'contextformtogglebutton',
            icon: 'link',
            tooltip: 'Link',
            primary: true,
            onSetup: function (buttonApi) {
              var node = editor.selection.getNode();
              buttonApi.setActive(!!getAnchorElement(editor, node));
              return toggleActiveState(editor)(buttonApi);
            },
            onAction: function (formApi) {
              var anchor = getAnchorElement(editor);
              var value = formApi.getValue();
              if (!anchor) {
                var attachState = {
                  href: value,
                  attach: function () {
                  }
                };
                var onlyText = isOnlyTextSelected(editor.selection.getContent());
                var text = onlyText ? Option.some(getAnchorText(editor.selection, anchor)).filter(function (t) {
                  return t.length > 0;
                }).or(Option.from(value)) : Option.none();
                link(editor, attachState, {
                  href: value,
                  text: text,
                  title: Option.none(),
                  rel: Option.none(),
                  target: Option.none(),
                  class: Option.none()
                });
                formApi.hide();
              } else {
                editor.dom.setAttrib(anchor, 'href', value);
                collapseSelectionToEnd(editor);
                formApi.hide();
              }
            }
          },
          {
            type: 'contextformbutton',
            icon: 'unlink',
            tooltip: 'Remove link',
            onSetup: onSetupLink,
            onAction: function (formApi) {
              unlink(editor);
              formApi.hide();
            }
          },
          {
            type: 'contextformbutton',
            icon: 'new-tab',
            tooltip: 'Open link',
            onSetup: onSetupLink,
            onAction: function (formApi) {
              gotoSelectedLink(editor)();
              formApi.hide();
            }
          }
        ]
      });
    };

    function Plugin () {
      global.add('link', function (editor) {
        setupButtons(editor);
        setupMenuItems(editor);
        setupContextMenu(editor);
        setupContextToolbars(editor);
        setupGotoLinks(editor);
        register(editor);
        setup(editor);
      });
    }

    Plugin();

}(window));
