// xLoadLink r1, Copyright 2007 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
function xLoadLink(url, rel, typ, med)
{
  var l, h, d = document, r = null;
  if (d.createElement && d.getElementsByTagName) {
    l = d.createElement('link');
    h = d.getElementsByTagName('head');
    if (l && h && h.length && h[0].appendChild) {
      l.rel = rel || 'stylesheet';
      l.type = typ || 'text/css';
      l.media = med || 'all';
      l.href = url;
      h[0].appendChild(l);
      r = l;
    }
  }
  return r;
}
