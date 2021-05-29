/**
 * Copyright (c) Tiny Technologies, Inc. All rights reserved.
 * Licensed under the LGPL or a commercial license.
 * For LGPL see License.txt in the project root for license information.
 * For commercial licenses see https://www.tiny.cloud/
 *
 * Version: 5.3.0 (2020-05-21)
 */
(function () {
    'use strict';

    var global = tinymce.util.Tools.resolve('tinymce.PluginManager');

    var isNamedAnchor = function (editor, node) {
      return node.tagName === 'A' && editor.dom.getAttrib(node, 'href') === '';
    };
    var isValidId = function (id) {
      return /^[A-Za-z][A-Za-z0-9\-:._]*$/.test(id);
    };
    var getId = function (editor) {
      var selectedNode = editor.selection.getNode();
      return isNamedAnchor(editor, selectedNode) ? selectedNode.getAttribute('id') || selectedNode.getAttribute('name') : '';
    };
    var insert = function (editor, id) {
      var selectedNode = editor.selection.getNode();
      if (isNamedAnchor(editor, selectedNode)) {
        selectedNode.removeAttribute('name');
        selectedNode.id = id;
        editor.undoManager.add();
      } else {
        editor.focus();
        editor.selection.collapse(true);
        editor.insertContent(editor.dom.createHTML('a', { id: id }));
      }
    };

    var insertAnchor = function (editor, newId) {
      if (!isValidId(newId)) {
        editor.windowManager.alert('Id should start with a letter, followed only by letters, numbers, dashes, dots, colons or underscores.');
        return false;
      } else {
        insert(editor, newId);
        return true;
      }
    };
    var open = function (editor) {
      var currentId = getId(editor);
      editor.windowManager.open({
        title: 'Anchor',
        size: 'normal',
        body: {
          type: 'panel',
          items: [{
              name: 'id',
              type: 'input',
              label: 'ID',
              placeholder: 'example'
            }]
        },
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
        initialData: { id: currentId },
        onSubmit: function (api) {
          if (insertAnchor(editor, api.getData().id)) {
            api.close();
          }
        }
      });
    };

    var register = function (editor) {
      editor.addCommand('mceAnchor', function () {
        open(editor);
      });
    };

    var isNamedAnchorNode = function (node) {
      return !node.attr('href') && (node.attr('id') || node.attr('name')) && !node.firstChild;
    };
    var setContentEditable = function (state) {
      return function (nodes) {
        for (var i = 0; i < nodes.length; i++) {
          if (isNamedAnchorNode(nodes[i])) {
            nodes[i].attr('contenteditable', state);
          }
        }
      };
    };
    var setup = function (editor) {
      editor.on('PreInit', function () {
        editor.parser.addNodeFilter('a', setContentEditable('false'));
        editor.serializer.addNodeFilter('a', setContentEditable(null));
      });
    };

    var register$1 = function (editor) {
      editor.ui.registry.addToggleButton('anchor', {
        icon: 'bookmark',
        tooltip: 'Anchor',
        onAction: function () {
          return editor.execCommand('mceAnchor');
        },
        onSetup: function (buttonApi) {
          return editor.selection.selectorChangedWithUnbind('a:not([href])', buttonApi.setActive).unbind;
        }
      });
      editor.ui.registry.addMenuItem('anchor', {
        icon: 'bookmark',
        text: 'Anchor...',
        onAction: function () {
          return editor.execCommand('mceAnchor');
        }
      });
    };

    function Plugin () {
      global.add('anchor', function (editor) {
        setup(editor);
        register(editor);
        register$1(editor);
      });
    }

    Plugin();

}());
