/**
 * Copyright (c) Tiny Technologies, Inc. All rights reserved.
 * Licensed under the LGPL or a commercial license.
 * For LGPL see License.txt in the project root for license information.
 * For commercial licenses see https://www.tiny.cloud/
 *
 * Version: 5.3.0 (2020-05-21)
 */
!function(){"use strict";var o=tinymce.util.Tools.resolve("tinymce.PluginManager"),r=function(o,t,e){var n,s;o.dom.toggleClass(o.getBody(),"mce-visualblocks"),e.set(!e.get()),n=o,s=e.get(),n.fire("VisualBlocks",{state:s})},m=function(e,n){return function(t){t.setActive(n.get());var o=function(o){return t.setActive(o.state)};return e.on("VisualBlocks",o),function(){return e.off("VisualBlocks",o)}}};!function t(){o.add("visualblocks",function(o,t){var e,n,s,i,c,u,l,a=(e=!1,{get:function(){return e},set:function(o){e=o}});s=a,(n=o).addCommand("mceVisualBlocks",function(){r(n,0,s)}),c=a,(i=o).ui.registry.addToggleButton("visualblocks",{icon:"visualblocks",tooltip:"Show blocks",onAction:function(){return i.execCommand("mceVisualBlocks")},onSetup:m(i,c)}),i.ui.registry.addToggleMenuItem("visualblocks",{text:"Show blocks",icon:"visualblocks",onAction:function(){return i.execCommand("mceVisualBlocks")},onSetup:m(i,c)}),l=a,(u=o).on("PreviewFormats AfterPreviewFormats",function(o){l.get()&&u.dom.toggleClass(u.getBody(),"mce-visualblocks","afterpreviewformats"===o.type)}),u.on("init",function(){u.getParam("visualblocks_default_state",!1,"boolean")&&r(u,0,l)}),u.on("remove",function(){u.dom.removeClass(u.getBody(),"mce-visualblocks")})})}()}();