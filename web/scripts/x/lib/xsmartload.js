// xSmartLoad r1, Copyright 2007 Chris Nelson, based on xSmartLoadScript by Brendan Richards
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xSmartLoad(what, url)
{
  var loadedBefore = false;
  var s;

  for (var i=0; i<xSmartLoad.list.length; i++) {
    if (xSmartLoad.list[i].url == url) {
      loadedBefore = true;
      s = xSmartLoad.list[i].node;
      break;
    }
  }
  if (document.createElement && document.getElementsByTagName && !loadedBefore) {
    s = document.createElement(what);
    var h = document.getElementsByTagName('head');
    if (s && h.length) {
      switch (what.toUpperCase()) {
      case 'SCRIPT':
        s.src = url;
        break;
      case 'LINK':
        s.rel = 'stylesheet';
        s.type = 'text/css';
        s.href = url;
        break;
      default:
        s = null;
        break;
      }
      h[0].appendChild(s);
      xSmartLoad.list[xSmartLoad.list.length] = {url:url, node:s};
    }
  }
  return s;
}

xSmartLoad.list = []; // static property of xSmartLoad
