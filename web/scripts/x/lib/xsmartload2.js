// xSmartLoad2 r1, Copyright 2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xSmartLoad2(what, url1, url2, url3, etc)
{
  var u, i, j, s, h, loaded, c = 0, e = what.toLowerCase();
  if (document.createElement && document.getElementsByTagName) {
    h = document.getElementsByTagName('head');
    if (h.length && h[0].appendChild) {
      for (i = 1; i < arguments.length; ++i) {
        loaded = false;
        u = arguments[i];
        for (j = 0; j < xSmartLoad2.list.length; j++) {
          if (xSmartLoad2.list[j] == u) {
            loaded = true;
            break; // for (j
          }
        }
        if (!loaded) {
          s = document.createElement(e);
          if (s) {
            switch (e) {
            case 'script':
              s.type = 'text/javascript';
              s.src = u;
              break;
            case 'link':
              s.rel = 'stylesheet';
              s.type = 'text/css';
              s.href = u;
              break;
            default:
              continue; // for (i
            }
            h[0].appendChild(s);
            xSmartLoad2.list[xSmartLoad2.list.length] = u;
            ++c;
          }
        }
      }
    }
  }
  return c;
}

xSmartLoad2.list = []; // static property of xSmartLoad2
